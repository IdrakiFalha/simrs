<?php
/**
 * controllers/AdmisiController.php
 * Patient registration, listing, consent toggle.
 */
class AdmisiController {
    private PasienModel    $pasien;
    private KunjunganModel $kunjungan;
    private AuditModel     $audit;

    public function __construct() {
        $this->pasien    = new PasienModel();
        $this->kunjungan = new KunjunganModel();
        $this->audit     = new AuditModel();
    }

    /** List patients (with optional search) */
    public function index(): void {
        $q = trim($_GET['q'] ?? '');
        $pasien = $q !== '' ? $this->pasien->search($q) : $this->pasien->all();
        $flashOk  = flash('ok');
        $flashErr = flash('error');
        view('admisi/index', compact('pasien', 'q', 'flashOk', 'flashErr'));
    }

    /** Store new patient */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admisi');
        if (!csrf_verify()) { flash('error', 'Token CSRF tidak valid.'); redirect('admisi'); }

        $nik = preg_replace('/\D/', '', $_POST['nik'] ?? '');
        if (strlen($nik) !== 16) {
            flash('error', 'NIK harus 16 digit.');
            redirect('admisi');
        }
        if ($this->pasien->findByNik($nik)) {
            flash('error', 'NIK sudah terdaftar.');
            redirect('admisi');
        }

        $this->pasien->create([
            'nik'               => $nik,
            'nama'              => trim($_POST['nama'] ?? ''),
            'jenis_kelamin'     => $_POST['jenis_kelamin'] ?? 'L',
            'tanggal_lahir'     => $_POST['tanggal_lahir'] ?? date('Y-m-d'),
            'ihs_number'        => $_POST['ihs_number'] ?? null,
            'consent_satusehat' => isset($_POST['consent_satusehat']),
        ]);
        $this->audit->log(current_user()['id'], 'create_patient', 'pasien');
        flash('ok', 'Pasien berhasil didaftarkan.');
        redirect('admisi');
    }

    /** AJAX: toggle consent SATUSEHAT */
    public function toggleConsent(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false], 400);
        if (!csrf_verify()) json_response(['ok' => false, 'msg' => 'CSRF invalid'], 403);

        $id = (int)($_POST['pasien_id'] ?? 0);
        $consent = ($_POST['consent'] ?? '0') === '1';
        $this->pasien->updateConsent($id, $consent);
        $this->audit->log(current_user()['id'], $consent ? 'enable_consent' : 'disable_consent', 'pasien');
        json_response(['ok' => true, 'consent' => $consent]);
    }
}
