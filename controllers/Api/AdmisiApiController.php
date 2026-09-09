<?php
/**
 * controllers/Api/AdmisiApiController.php
 * REST API — Patient Registration (Admisi).
 *
 * Endpoints:
 *  GET   /api/pasien                     → List all patients (with optional ?q= search)
 *  POST  /api/pasien                     → Register a new patient
 *  PATCH /api/pasien/{id}/consent        → Toggle SATUSEHAT consent
 */
class AdmisiApiController
{
    private PasienModel    $pasien;
    private KunjunganModel $kunjungan;
    private AuditModel     $audit;

    public function __construct()
    {
        $this->pasien    = new PasienModel();
        $this->kunjungan = new KunjunganModel();
        $this->audit     = new AuditModel();
    }

    // ── GET /api/pasien ───────────────────────────────────────────────────────
    /**
     * Query params:
     *  ?q=keyword   → full-text search on NIK / nama
     *  ?pasien_id=N → return single patient + their visit history (for klinis page)
     */
    public function index(): void
    {
        api_auth_guard();

        $pasienId = (int)($_GET['pasien_id'] ?? 0);

        // Single patient detail mode (used by the klinis/periksa page)
        if ($pasienId > 0) {
            $pasien = $this->pasien->find($pasienId);
            if (!$pasien) {
                json_response(['ok' => false, 'error' => 'Not Found', 'message' => 'Pasien tidak ditemukan.'], 404);
            }
            $riwayat = $this->kunjungan->findByPasien($pasienId);
            $umur    = (int) date_diff(date_create($pasien['tanggal_lahir']), date_create('now'))->y;
            json_response([
                'ok'     => true,
                'pasien' => $pasien,
                'umur'   => $umur,
                'riwayat'=> $riwayat,
            ]);
        }

        // List mode
        $q      = trim($_GET['q'] ?? '');
        $pasien = $q !== '' ? $this->pasien->search($q) : $this->pasien->all();

        json_response(['ok' => true, 'data' => $pasien, 'total' => count($pasien)]);
    }

    // ── POST /api/pasien ──────────────────────────────────────────────────────
    /**
     * Body (JSON or form):
     * {
     *   "nik": "3201...",           (16 digits, required)
     *   "nama": "Budi Santoso",     (required)
     *   "jenis_kelamin": "L"|"P",   (required)
     *   "tanggal_lahir": "1990-01-15", (required, YYYY-MM-DD)
     *   "ihs_number": "P02...",     (optional)
     *   "consent_satusehat": true   (optional, default false)
     * }
     *
     * Offline-First Note:
     *  When the device comes back online, the JS sync layer sends queued admisi
     *  records here. The server returns 409 Conflict if NIK already exists
     *  (meaning a previous sync already saved it) — the JS layer should treat
     *  409 as success and remove the record from IndexedDB.
     */
    public function store(): void
    {
        api_auth_guard();
        if (!csrf_verify()) {
            json_response(['ok' => false, 'error' => 'CSRF', 'message' => 'Token CSRF tidak valid. Muat ulang halaman.'], 403);
        }

        $input = api_input();
        $nik   = preg_replace('/\D/', '', $input['nik'] ?? '');

        // ── Validasi ──────────────────────────────────────────────────────────
        $errors = [];
        if (strlen($nik) !== 16)             { $errors['nik']  = 'NIK harus 16 digit angka.'; }
        if (empty(trim($input['nama'] ?? ''))) { $errors['nama'] = 'Nama pasien wajib diisi.'; }
        if (!in_array($input['jenis_kelamin'] ?? '', ['L', 'P'], true)) {
            $errors['jenis_kelamin'] = 'Jenis kelamin tidak valid (L atau P).';
        }

        if (!empty($errors)) {
            json_response(['ok' => false, 'error' => 'Validation', 'messages' => $errors], 422);
        }

        // ── Duplikasi NIK ─────────────────────────────────────────────────────
        if ($this->pasien->findByNik($nik)) {
            json_response([
                'ok'      => false,
                'error'   => 'Conflict',
                'message' => 'NIK sudah terdaftar. Pasien tidak perlu didaftarkan ulang.',
            ], 409);
        }

        // ── Simpan ────────────────────────────────────────────────────────────
        $pasienId = $this->pasien->create([
            'nik'               => $nik,
            'nama'              => trim($input['nama']),
            'jenis_kelamin'     => $input['jenis_kelamin'],
            'tanggal_lahir'     => $input['tanggal_lahir'] ?? date('Y-m-d'),
            'ihs_number'        => $input['ihs_number'] ?? null,
            'consent_satusehat' => !empty($input['consent_satusehat']),
        ]);

        $this->audit->log(current_user()['id'], 'api_create_patient', 'pasien');

        json_response([
            'ok'        => true,
            'pasien_id' => $pasienId,
            'message'   => 'Pasien berhasil didaftarkan.',
        ], 201);
    }

    // ── PATCH /api/pasien/{id}/consent ────────────────────────────────────────
    /**
     * Body (JSON or form): { "consent": true|false }
     * The patient's `id` is extracted from the URI by the router and placed in $_GET['id'].
     */
    public function toggleConsent(): void
    {
        api_auth_guard();
        if (!csrf_verify()) {
            json_response(['ok' => false, 'error' => 'CSRF', 'message' => 'Token CSRF tidak valid.'], 403);
        }

        $id    = (int)($_GET['id'] ?? 0);
        $input = api_input();

        if ($id <= 0) {
            json_response(['ok' => false, 'error' => 'Validation', 'message' => 'ID pasien tidak valid.'], 422);
        }

        $pasien = $this->pasien->find($id);
        if (!$pasien) {
            json_response(['ok' => false, 'error' => 'Not Found', 'message' => 'Pasien tidak ditemukan.'], 404);
        }

        // Accept both boolean JSON and "1"/"0" form values
        $consent = filter_var($input['consent'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->pasien->updateConsent($id, $consent);
        $this->audit->log(
            current_user()['id'],
            $consent ? 'api_enable_consent' : 'api_disable_consent',
            'pasien'
        );

        json_response(['ok' => true, 'pasien_id' => $id, 'consent' => $consent]);
    }
}
