<?php
/**
 * controllers/IntegrasiController.php
 * SATUSEHAT FHIR R4 integration — OAuth2 token fetch + Encounter push.
 */
require_once APP_ROOT . '/config/satusehat_api.php';

class IntegrasiController {
    private AuditModel     $audit;
    private KunjunganModel $kunjungan;

    public function __construct() {
        $this->audit     = new AuditModel();
        $this->kunjungan = new KunjunganModel();
    }

    /** Dashboard integrasi */
    public function index(): void {
        require_role(['Admin', 'Dokter']);
        $token    = null;
        $tokenErr = null;
        $visits   = $this->kunjungan->all();

        // Try to fetch token for display
        $result = $this->fetchAccessToken();
        if ($result['success']) {
            $token = $result['token'];
        } else {
            $tokenErr = $result['error'];
        }

        $flashOk  = flash('ok');
        $flashErr = flash('error');
        view('integrasi/index', compact('token', 'tokenErr', 'visits', 'flashOk', 'flashErr'));
    }

    /**
     * Fetch OAuth2 access token from SATUSEHAT API using client_credentials.
     * Uses cURL with CLIENT_ID and CLIENT_SECRET.
     * @return array ['success' => bool, 'token' => string|null, 'expires_in' => int|null, 'error' => string|null]
     */
    public function fetchAccessToken(): array {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => SATUSEHAT_AUTH_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
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
            $this->audit->log(current_user()['id'], 'satusehat_token_ok', 'integrasi');
            return [
                'success'    => true,
                'token'      => $data['access_token'],
                'expires_in' => (int)($data['expires_in'] ?? 3600),
                'error'      => null,
            ];
        }

        $errMsg = $data['error_description'] ?? $data['error'] ?? "HTTP $httpCode";
        $this->audit->log(current_user()['id'] ?? 0, 'satusehat_token_failed', 'integrasi');
        return ['success' => false, 'token' => null, 'expires_in' => null, 'error' => $errMsg];
    }

    /**
     * Push Encounter resource to SATUSEHAT FHIR R4 endpoint.
     */
    public function pushEncounter(array $fhirPayload): array {
        $tokenResult = $this->fetchAccessToken();
        if (!$tokenResult['success']) {
            return ['success' => false, 'error' => 'Cannot obtain access token: ' . $tokenResult['error']];
        }

        $url = rtrim(SATUSEHAT_BASE_URL, '/') . '/Encounter';
        $jsonPayload = json_encode($fhirPayload);

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
            return ['success' => false, 'http_code' => 0, 'encounter_id' => null, 'error' => 'cURL error: ' . $curlError];
        }

        $success = ($httpCode >= 200 && $httpCode < 300);
        $this->audit->log(
            current_user()['id'],
            $success ? 'satusehat_encounter_sent' : 'satusehat_encounter_failed',
            'kunjungan'
        );

        $body = json_decode($response, true);
        return [
            'success'      => $success,
            'http_code'    => $httpCode,
            'encounter_id' => $body['id'] ?? null,
            'error'        => $success ? null : ($body['issue'][0]['details']['text'] ?? "HTTP $httpCode"),
            'response'     => $body,
        ];
    }
}
