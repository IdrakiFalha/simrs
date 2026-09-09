<?php
/**
 * controllers/Api/IntegrasiApiController.php
 * REST API — SATUSEHAT FHIR R4 Integration.
 *
 * Endpoints:
 *  GET  /api/integrasi/token      → Fetch fresh OAuth2 access token
 *  POST /api/integrasi/encounter  → Push single Encounter by kunjungan_id
 *  POST /api/integrasi/sync       → Batch sync pending encounters (offline-first)
 *
 * Internal method:
 *  doPushEncounter(array $fhirPayload): array  → Used by KlinisApiController
 */
require_once APP_ROOT . '/config/satusehat_api.php';

class IntegrasiApiController
{
    private AuditModel     $audit;
    private KunjunganModel $kunjungan;
    private PasienModel    $pasien;

    public function __construct()
    {
        $this->audit     = new AuditModel();
        $this->kunjungan = new KunjunganModel();
        $this->pasien    = new PasienModel();
    }

    // ── GET /api/integrasi/token ──────────────────────────────────────────────
    /**
     * Fetches a fresh OAuth2 token from SATUSEHAT and caches it in the session.
     * The JS Background Sync calls this first before batch-syncing encounters.
     *
     * Response: { ok, token, expires_in, cached }
     *  cached=true means the session token was still valid, no new HTTP call made.
     */
    public function getToken(): void
    {
        api_role_guard(['Admin', 'Dokter']);

        // Return cached session token if still valid (>60 sec buffer)
        if (
            !empty($_SESSION['satusehat_token']) &&
            isset($_SESSION['satusehat_token_exp']) &&
            $_SESSION['satusehat_token_exp'] > time() + 60
        ) {
            json_response([
                'ok'         => true,
                'token'      => $_SESSION['satusehat_token'],
                'expires_in' => $_SESSION['satusehat_token_exp'] - time(),
                'cached'     => true,
            ]);
        }

        $result = $this->fetchAccessToken();

        if ($result['success']) {
            json_response([
                'ok'         => true,
                'token'      => $result['token'],
                'expires_in' => $result['expires_in'],
                'cached'     => false,
            ]);
        }

        json_response([
            'ok'    => false,
            'error' => 'SATUSEHAT Auth Failed',
            'message' => $result['error'],
        ], 502);
    }

    // ── POST /api/integrasi/encounter ─────────────────────────────────────────
    /**
     * Push a single encounter by kunjungan_id.
     * Body: { "kunjungan_id": N }
     *
     * This endpoint fetches the visit from the DB, rebuilds the FHIR payload,
     * and pushes it to SATUSEHAT. It is idempotent: if the kunjungan already
     * has a satusehat_encounter_id, it skips the push and returns success.
     */
    public function pushEncounter(): void
    {
        api_role_guard(['Admin', 'Dokter']);
        if (!csrf_verify()) {
            json_response(['ok' => false, 'error' => 'CSRF'], 403);
        }

        $input       = api_input();
        $kunjunganId = (int)($input['kunjungan_id'] ?? 0);

        if ($kunjunganId <= 0) {
            json_response(['ok' => false, 'error' => 'Validation', 'message' => 'kunjungan_id wajib diisi.'], 422);
        }

        $kunjungan = $this->kunjungan->find($kunjunganId);
        if (!$kunjungan) {
            json_response(['ok' => false, 'error' => 'Not Found', 'message' => 'Kunjungan tidak ditemukan.'], 404);
        }

        // ── Idempotency Check ─────────────────────────────────────────────────
        if (!empty($kunjungan['satusehat_encounter_id'])) {
            json_response([
                'ok'          => true,
                'skipped'     => true,
                'encounter_id'=> $kunjungan['satusehat_encounter_id'],
                'message'     => 'Encounter sudah pernah dikirim sebelumnya.',
            ]);
        }

        $pasien = $this->pasien->find((int)$kunjungan['pasien_id']);
        if (!$pasien || !$pasien['consent_satusehat']) {
            json_response([
                'ok'      => false,
                'error'   => 'Consent',
                'message' => 'Pasien tidak memiliki consent SATUSEHAT aktif.',
            ], 403);
        }

        $fhirPayload = $this->buildFhirFromKunjungan($pasien, $kunjungan);
        $result      = $this->doPushEncounter($fhirPayload);

        if ($result['success']) {
            $this->kunjungan->updateEncounterStatus(
                $kunjunganId,
                $result['encounter_id'],
                'sent'
            );
            json_response([
                'ok'           => true,
                'kunjungan_id' => $kunjunganId,
                'encounter_id' => $result['encounter_id'],
                'http_code'    => $result['http_code'],
            ]);
        }

        // Update status to 'failed' (but do NOT wipe encounter_id — it's null)
        $this->kunjungan->updateEncounterStatus($kunjunganId, '', 'failed');
        json_response([
            'ok'           => false,
            'kunjungan_id' => $kunjunganId,
            'error'        => $result['error'],
            'http_code'    => $result['http_code'],
        ], 502);
    }

    // ── POST /api/integrasi/sync ──────────────────────────────────────────────
    /**
     * Batch sync endpoint — called by the JS Background Sync worker after
     * coming back online. Processes encounters one by one; stops on token error.
     *
     * Body:
     * {
     *   "encounters": [
     *     { "kunjungan_id": N },
     *     ...
     *   ]
     * }
     *
     * Response:
     * {
     *   "ok": true,
     *   "results": [
     *     { "kunjungan_id": N, "success": true,  "encounter_id": "...", "skipped": false },
     *     { "kunjungan_id": N, "success": false, "error": "...",        "skipped": false },
     *     ...
     *   ],
     *   "total": N, "succeeded": N, "failed": N
     * }
     *
     * Important: The JS layer must NOT remove a record from IndexedDB until
     * its entry in results has success=true (or skipped=true).
     */
    public function batchSync(): void
    {
        api_role_guard(['Admin', 'Dokter']);
        if (!csrf_verify()) {
            json_response(['ok' => false, 'error' => 'CSRF'], 403);
        }

        $input      = api_input();
        $encounters = $input['encounters'] ?? [];

        if (!is_array($encounters) || empty($encounters)) {
            json_response(['ok' => false, 'error' => 'Validation', 'message' => 'encounters array wajib diisi.'], 422);
        }

        // Fetch a fresh token once for the whole batch
        $tokenResult = $this->fetchAccessToken();
        if (!$tokenResult['success']) {
            json_response([
                'ok'    => false,
                'error' => 'SATUSEHAT Auth Failed',
                'message' => 'Gagal mendapatkan token SATUSEHAT: ' . $tokenResult['error'],
            ], 502);
        }

        $results   = [];
        $succeeded = 0;
        $failed    = 0;

        foreach ($encounters as $enc) {
            $kunjunganId = (int)($enc['kunjungan_id'] ?? 0);
            if ($kunjunganId <= 0) {
                $results[] = ['kunjungan_id' => $kunjunganId, 'success' => false, 'error' => 'ID tidak valid', 'skipped' => false];
                $failed++;
                continue;
            }

            $kunjungan = $this->kunjungan->find($kunjunganId);
            if (!$kunjungan) {
                $results[] = ['kunjungan_id' => $kunjunganId, 'success' => false, 'error' => 'Kunjungan tidak ditemukan', 'skipped' => false];
                $failed++;
                continue;
            }

            // ── Idempotency: already sent ─────────────────────────────────────
            if (!empty($kunjungan['satusehat_encounter_id'])) {
                $results[] = [
                    'kunjungan_id' => $kunjunganId,
                    'success'      => true,
                    'encounter_id' => $kunjungan['satusehat_encounter_id'],
                    'skipped'      => true,
                ];
                $succeeded++;
                continue;
            }

            $pasien = $this->pasien->find((int)$kunjungan['pasien_id']);
            if (!$pasien || !$pasien['consent_satusehat']) {
                $results[] = [
                    'kunjungan_id' => $kunjunganId,
                    'success'      => false,
                    'error'        => 'Consent tidak aktif atau pasien tidak ditemukan',
                    'skipped'      => false,
                ];
                $failed++;
                continue;
            }

            $fhirPayload = $this->buildFhirFromKunjungan($pasien, $kunjungan);
            $pushResult  = $this->doPushEncounter($fhirPayload);

            if ($pushResult['success']) {
                $this->kunjungan->updateEncounterStatus($kunjunganId, $pushResult['encounter_id'], 'sent');
                $results[] = [
                    'kunjungan_id' => $kunjunganId,
                    'success'      => true,
                    'encounter_id' => $pushResult['encounter_id'],
                    'skipped'      => false,
                ];
                $succeeded++;
            } else {
                $this->kunjungan->updateEncounterStatus($kunjunganId, '', 'failed');
                $results[] = [
                    'kunjungan_id' => $kunjunganId,
                    'success'      => false,
                    'error'        => $pushResult['error'],
                    'http_code'    => $pushResult['http_code'],
                    'skipped'      => false,
                ];
                $failed++;
            }
        }

        $this->audit->log(current_user()['id'], 'api_batch_sync', 'kunjungan');

        json_response([
            'ok'        => true,
            'results'   => $results,
            'total'     => count($encounters),
            'succeeded' => $succeeded,
            'failed'    => $failed,
        ]);
    }

    // ── Public Internal: doPushEncounter ──────────────────────────────────────
    /**
     * Called internally by KlinisApiController::store() to push a freshly-built
     * FHIR payload to SATUSEHAT. Fetches the token internally.
     *
     * @param  array $fhirPayload  FHIR R4 Encounter resource array
     * @return array { success, http_code, encounter_id, error, response }
     */
    public function doPushEncounter(array $fhirPayload): array
    {
        $tokenResult = $this->fetchAccessToken();
        if (!$tokenResult['success']) {
            return ['success' => false, 'http_code' => 0, 'encounter_id' => null, 'error' => 'Cannot obtain access token: ' . $tokenResult['error'], 'response' => null];
        }

        $url         = rtrim(SATUSEHAT_BASE_URL, '/') . '/Encounter';
        $jsonPayload = json_encode($fhirPayload, JSON_UNESCAPED_UNICODE);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $tokenResult['token'],
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            $this->audit->log(current_user()['id'] ?? 0, 'satusehat_encounter_curl_error', 'kunjungan');
            return ['success' => false, 'http_code' => 0, 'encounter_id' => null, 'error' => 'cURL error: ' . $curlError, 'response' => null];
        }

        $body    = json_decode($response, true);
        $success = ($httpCode >= 200 && $httpCode < 300);

        $this->audit->log(
            current_user()['id'] ?? 0,
            $success ? 'satusehat_encounter_sent' : 'satusehat_encounter_failed',
            'kunjungan'
        );

        return [
            'success'      => $success,
            'http_code'    => $httpCode,
            'encounter_id' => $body['id'] ?? null,
            'error'        => $success ? null : ($body['issue'][0]['details']['text'] ?? ($body['issue'][0]['diagnostics'] ?? "HTTP {$httpCode}")),
            'response'     => $body,
        ];
    }

    // ── Private: fetchAccessToken ──────────────────────────────────────────────
    /**
     * Fetches OAuth2 client_credentials token from SATUSEHAT API.
     * Mirrors IntegrasiController::fetchAccessToken() but is self-contained here.
     * @return array { success, token, expires_in, error }
     */
    private function fetchAccessToken(): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => SATUSEHAT_AUTH_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS     => http_build_query([
                'client_id'     => CLIENT_ID,
                'client_secret' => CLIENT_SECRET,
            ]),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $this->audit->log(current_user()['id'] ?? 0, 'satusehat_token_curl_error', 'integrasi');
            return ['success' => false, 'token' => null, 'expires_in' => null, 'error' => 'cURL error: ' . $curlErr];
        }

        $data = json_decode($response, true);
        if ($httpCode === 200 && isset($data['access_token'])) {
            $_SESSION['satusehat_token']     = $data['access_token'];
            $_SESSION['satusehat_token_exp'] = time() + (int)($data['expires_in'] ?? 3600);
            $this->audit->log(current_user()['id'] ?? 0, 'satusehat_token_ok', 'integrasi');
            return [
                'success'    => true,
                'token'      => $data['access_token'],
                'expires_in' => (int)($data['expires_in'] ?? 3600),
                'error'      => null,
            ];
        }

        $errMsg = $data['error_description'] ?? $data['error'] ?? "HTTP {$httpCode}";
        $this->audit->log(current_user()['id'] ?? 0, 'satusehat_token_failed', 'integrasi');
        return ['success' => false, 'token' => null, 'expires_in' => null, 'error' => $errMsg];
    }

    // ── Private: buildFhirFromKunjungan ───────────────────────────────────────
    /**
     * Rebuilds the FHIR Encounter payload from stored kunjungan data.
     * Used when re-pushing failed/pending encounters via pushEncounter() or batchSync().
     */
    private function buildFhirFromKunjungan(array $pasien, array $kunjungan): array
    {
        $nowIso = date('Y-m-d\TH:i:sP', strtotime($kunjungan['waktu'] ?? 'now'));

        if (SATUSEHAT_ENV === 'staging') {
            $patientIhs = SANDBOX_PATIENT_IHS;
            $doctorIhs  = SANDBOX_PRACTITIONER_IHS;
        } else {
            $patientIhs = $pasien['ihs_number'] ?? '';
            $doctorIhs  = ''; // Doctor IHS stored per-user — extend UserModel if needed
        }

        $encounterIdLocal = 'ENC-RESYNC-' . $kunjungan['id'];

        return [
            'resourceType' => 'Encounter',
            'identifier'   => [[
                'system' => 'http://sys-ids.kemkes.go.id/encounter/' . ORGANIZATION_ID,
                'value'  => $encounterIdLocal,
            ]],
            'status' => 'arrived',
            'class'  => [
                'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code'    => 'AMB',
                'display' => 'ambulatory',
            ],
            'subject'     => ['reference' => 'Patient/' . $patientIhs, 'display' => $pasien['nama'] ?? 'Pasien SIMRS'],
            'participant' => [[
                'type' => [[
                    'coding' => [[
                        'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                        'code'    => 'ATND',
                        'display' => 'attending',
                    ]]
                ]],
                'individual' => [
                    'reference' => 'Practitioner/' . $doctorIhs,
                    'display'   => 'Dokter SIMRS',
                ],
            ]],
            'period'          => ['start' => $nowIso],
            'statusHistory'   => [['status' => 'arrived', 'period' => ['start' => $nowIso]]],
            'serviceProvider' => ['reference' => 'Organization/' . ORGANIZATION_ID],
            'location'        => [[
                'location' => ['reference' => 'Location/' . LOCATION_ID, 'display' => 'Poliklinik Rawat Jalan'],
                'status'   => 'active',
            ]],
        ];
    }
}
