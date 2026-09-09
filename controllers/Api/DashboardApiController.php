<?php
/**
 * controllers/Api/DashboardApiController.php
 * REST API — Dashboard Statistics.
 *
 * Endpoints:
 *  GET /api/dashboard → Aggregate statistics for the dashboard page
 */
class DashboardApiController
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

    // ── GET /api/dashboard ────────────────────────────────────────────────────
    /**
     * Returns aggregate counts and the 5 most recent visits.
     * The PWA frontend uses this to populate the dashboard cards.
     *
     * Response shape:
     * {
     *   "ok": true,
     *   "stats": {
     *     "total_pasien": N,
     *     "total_kunjungan": N,
     *     "kunjungan_hari_ini": N,
     *     "total_obat": N,
     *     "total_audit": N
     *   },
     *   "recent_visits": [...]
     * }
     */
    public function index(): void
    {
        api_auth_guard();

        json_response([
            'ok'    => true,
            'stats' => [
                'total_pasien'       => $this->pasien->count(),
                'total_kunjungan'    => $this->kunjungan->count(),
                'kunjungan_hari_ini' => $this->kunjungan->countToday(),
                'total_obat'         => $this->obat->count(),
                'total_audit'        => $this->audit->count(),
            ],
            'recent_visits' => array_slice($this->kunjungan->all(), 0, 5),
        ]);
    }
}
