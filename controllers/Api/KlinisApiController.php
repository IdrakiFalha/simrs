<?php
/**
 * controllers/Api/KlinisApiController.php
 * REST API — Clinical Examination (Klinis).
 *
 * Endpoints:
 *  GET  /api/kunjungan          → Visit history (filter by ?pasien_id=N)
 *  POST /api/kunjungan          → Store new clinical examination + prescription
 *  POST /api/kunjungan/dur      → Drug Utilization Review (DUR) check
 *  GET  /api/obat               → Search drugs (requires ?q=keyword)
 */
class KlinisApiController
{
    private PasienModel    $pasien;
    private KunjunganModel $kunjungan;
    private ObatModel      $obat;
    private AuditModel     $audit;

    public function __construct()
    {
        $this->pasien    = new PasienModel();
        $this->kunjungan = new KunjunganModel();
        $this->obat      = new ObatModel();
        $this->audit     = new AuditModel();
    }

    // ── GET /api/kunjungan ────────────────────────────────────────────────────
    /**
     * Query params:
     *  ?pasien_id=N  → filter by patient (required for klinis page context)
     *
     * Without pasien_id: returns all visits (for dashboard/integrasi views).
     */
    public function index(): void
    {
        api_auth_guard();

        $pasienId = (int)($_GET['pasien_id'] ?? 0);

        if ($pasienId > 0) {
            $visits = $this->kunjungan->findByPasien($pasienId);
        } else {
            $visits = $this->kunjungan->all();
        }

        json_response(['ok' => true, 'data' => $visits, 'total' => count($visits)]);
    }

    // ── POST /api/kunjungan ───────────────────────────────────────────────────
    /**
     * Body (JSON or form):
     * {
     *   "pasien_id":      N,
     *   "sistole":        120,
     *   "diastole":       80,
     *   "nadi":           72,
     *   "suhu":           36.5,
     *   "laju_napas":     18,
     *   "keluhan":        "...",
     *   "diagnosa_icd10": "J06.9",
     *   "resep": [
     *     { "obat_id": N, "aturan_pakai": "3x1 sesudah makan" },
     *     ...
     *   ]
     * }
     *
     * Offline-First Note:
     *  When syncing offline-queued visits, the JS sync layer sends records one
     *  by one. If the pasien_id doesn't exist (patient was also queued offline
     *  and not yet synced), this returns 404 — JS should sync admisi first.
     *
     * SATUSEHAT push:
     *  Occurs synchronously here if the patient has consent. The result is
     *  embedded in the response so the JS layer knows whether to retry later.
     */
    public function store(): void
    {
        api_auth_guard();
        if (!csrf_verify()) {
            json_response(['ok' => false, 'error' => 'CSRF', 'message' => 'Token CSRF tidak valid.'], 403);
        }

        $input    = api_input();
        $pasienId = (int)($input['pasien_id'] ?? 0);
        $pasien   = $this->pasien->find($pasienId);

        if (!$pasien) {
            json_response(['ok' => false, 'error' => 'Not Found', 'message' => 'Pasien tidak ditemukan.'], 404);
        }

        // ── Vitals & NEWS2 ────────────────────────────────────────────────────
        $vitals = [
            'sistole'    => (int)($input['sistole']    ?? 0),
            'diastole'   => (int)($input['diastole']   ?? 0),
            'nadi'       => (int)($input['nadi']       ?? 0),
            'suhu'       => (float)($input['suhu']     ?? 0),
            'laju_napas' => (int)($input['laju_napas'] ?? 0),
        ];
        $news2    = KunjunganModel::calculateNews2($vitals);
        $kategori = KunjunganModel::getNews2Category($news2);

        // ── SATUSEHAT Push (live, if consent is active) ───────────────────────
        $satusehatResult   = ['pushed' => false, 'encounter_id' => null, 'error' => null];
        $encounterStatus   = 'pending';
        $satusehatEncId    = null;

        if ($pasien['consent_satusehat']) {
            $fhirPayload       = $this->buildFhirEncounter($pasien, $vitals, $news2, $kategori, $input);
            $integrasiCtrl     = new IntegrasiApiController();
            $pushResult        = $integrasiCtrl->doPushEncounter($fhirPayload);

            if ($pushResult['success']) {
                $encounterStatus = 'sent';
                $satusehatEncId  = $pushResult['encounter_id'];
                $satusehatResult = ['pushed' => true, 'encounter_id' => $satusehatEncId, 'error' => null];
            } else {
                $encounterStatus = 'failed';
                $satusehatResult = ['pushed' => false, 'encounter_id' => null, 'error' => $pushResult['error']];
            }
        } else {
            $encounterStatus = 'skipped';
            $satusehatResult = ['pushed' => false, 'encounter_id' => null, 'error' => 'Consent nonaktif'];
        }

        // ── Simpan Kunjungan ──────────────────────────────────────────────────
        $kunjunganId = $this->kunjungan->create(array_merge($vitals, [
            'pasien_id'              => $pasienId,
            'dokter_id'              => current_user()['id'],
            'skor_news2'             => $news2,
            'kategori_news2'         => $kategori,
            'keluhan'                => $input['keluhan'] ?? '',
            'diagnosa_icd10'         => $input['diagnosa_icd10'] ?? '',
            'satusehat_encounter_id' => $satusehatEncId,
            'status_kirim_satusehat' => $encounterStatus,
        ]));

        // ── Simpan Resep ──────────────────────────────────────────────────────
        $resepRows = $input['resep'] ?? [];
        $db        = Database::connect();
        $durWarnings = [];

        if (!empty($resepRows)) {
            $obatIds     = array_column($resepRows, 'obat_id');
            $durWarnings = $this->obat->checkDur(array_map('intval', $obatIds));

            foreach ($resepRows as $row) {
                $obatId = (int)($row['obat_id'] ?? 0);
                if ($obatId <= 0) continue;

                // Determine DUR status for this specific drug
                $durStatus = 'none';
                $drug      = $this->obat->find($obatId);
                if ($drug) {
                    foreach ($durWarnings as $w) {
                        if (stripos($w['nama'], $drug['nama_obat']) !== false) {
                            $durStatus = ($w['level'] === 'major') ? 'critical' : 'warning';
                        }
                    }
                }

                $stmt = $db->prepare(
                    'INSERT INTO resep (kunjungan_id, obat_id, aturan_pakai, status_dur_warning)
                     VALUES (:kid, :oid, :aturan, :dur)'
                );
                $stmt->execute([
                    ':kid'    => $kunjunganId,
                    ':oid'    => $obatId,
                    ':aturan' => $row['aturan_pakai'] ?? '',
                    ':dur'    => $durStatus,
                ]);
            }
        }

        $this->audit->log(current_user()['id'], 'api_create_kunjungan', 'kunjungan');

        json_response([
            'ok'           => true,
            'kunjungan_id' => $kunjunganId,
            'satusehat'    => $satusehatResult,
            'dur_warnings' => $durWarnings,
            'message'      => "Pemeriksaan berhasil disimpan.",
        ], 201);
    }

    // ── POST /api/kunjungan/dur ───────────────────────────────────────────────
    /**
     * Body (JSON or form): { "obat_ids": [1, 2, 3] }
     * Returns DUR interaction warnings for the selected drug combination.
     */
    public function cekDur(): void
    {
        api_auth_guard();
        $input    = api_input();
        $ids      = array_map('intval', $input['obat_ids'] ?? []);
        $warnings = $this->obat->checkDur($ids);
        $hasMajor = false;
        foreach ($warnings as $w) {
            if ($w['level'] === 'major') {
                $hasMajor = true;
                break;
            }
        }
        json_response(['ok' => true, 'warnings' => $warnings, 'has_major' => $hasMajor]);
    }

    // ── GET /api/obat ─────────────────────────────────────────────────────────
    /**
     * Query params: ?q=keyword (min 1 char)
     * Returns up to 20 matching drugs. Used for autocomplete in the klinis form.
     */
    public function cariObat(): void
    {
        api_auth_guard();
        $q       = trim($_GET['q'] ?? '');
        $results = $q !== '' ? $this->obat->search($q) : [];
        json_response(['ok' => true, 'data' => $results]);
    }

    // ── Private: Build FHIR Encounter Payload ─────────────────────────────────
    /**
     * Mirrors the logic in the old KlinisController::buildFhirEncounter().
     * Kept private here; the IntegrasiApiController uses doPushEncounter() directly.
     */
    private function buildFhirEncounter(array $pasien, array $vitals, int $news2, string $kategori, array $input): array
    {
        $nowIso = date('Y-m-d\TH:i:sP');

        if (SATUSEHAT_ENV === 'staging') {
            $patientIhs = SANDBOX_PATIENT_IHS;
            $doctorIhs  = SANDBOX_PRACTITIONER_IHS;
        } else {
            $patientIhs = !empty($pasien['ihs_number'])           ? $pasien['ihs_number']           : '';
            $doctorIhs  = !empty(current_user()['ihs_number'])    ? current_user()['ihs_number']    : '';
        }

        $encounterIdLocal = 'ENC-' . time() . '-' . rand(100, 999);
        $icdCode          = !empty($input['diagnosa_icd10']) ? trim($input['diagnosa_icd10']) : 'K29.7';

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
                    'display'   => current_user()['nama_lengkap'] ?? 'Dokter SIMRS',
                ],
            ]],
            'period'        => ['start' => $nowIso],
            'statusHistory' => [['status' => 'arrived', 'period' => ['start' => $nowIso]]],
            'serviceProvider' => ['reference' => 'Organization/' . ORGANIZATION_ID],
            'location'      => [[
                'location' => ['reference' => 'Location/' . LOCATION_ID, 'display' => 'Poliklinik Rawat Jalan'],
                'status'   => 'active',
            ]],
        ];
    }
}
