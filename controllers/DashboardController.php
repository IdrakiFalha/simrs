<?php
/**
 * controllers/DashboardController.php
 * Overview dashboard — counts, recent visits, quick stats.
 */
require_once APP_ROOT . '/config/satusehat_api.php';

class DashboardController {
    private PasienModel    $pasien;
    private KunjunganModel $kunjungan;
    private ObatModel      $obat;
    private AuditModel     $audit;

    public function __construct() {
        $this->pasien    = new PasienModel();
        $this->kunjungan = new KunjunganModel();
        $this->obat      = new ObatModel();
        $this->audit     = new AuditModel();
    }

    public function index(): void {
        $totalPasien    = $this->pasien->count();
        $totalKunjungan = $this->kunjungan->count();
        $kunjunganHariIni = $this->kunjungan->countToday();
        $totalObat      = $this->obat->count();
        $totalAudit     = $this->audit->count();
        $recentVisits   = array_slice($this->kunjungan->all(), 0, 5);

        // Fetch Recent CS Messages
        $db = Database::connect();
        $stmt = $db->query("SELECT * FROM `pesan_cs` ORDER BY `waktu` DESC LIMIT 5");
        $pesanCS = $stmt->fetchAll();

        view('dashboard/index', compact(
            'totalPasien', 'totalKunjungan', 'kunjunganHariIni',
            'totalObat', 'totalAudit', 'recentVisits', 'pesanCS'
        ));
    }
}
