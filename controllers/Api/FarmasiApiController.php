<?php
/**
 * controllers/Api/FarmasiApiController.php
 * REST API — Pharmacy (Farmasi).
 *
 * Endpoints:
 *  GET /api/farmasi/resep   → List prescriptions (filter by ?kunjungan_id=N)
 *  GET /api/farmasi/obat    → Full drug catalogue (no search filter)
 */
class FarmasiApiController
{
    private ObatModel   $obat;
    private AuditModel  $audit;

    public function __construct()
    {
        $this->obat  = new ObatModel();
        $this->audit = new AuditModel();
    }

    // ── GET /api/farmasi/resep ────────────────────────────────────────────────
    /**
     * Query params:
     *  ?kunjungan_id=N  → prescriptions for a specific visit
     *
     * Without kunjungan_id: returns all prescriptions (Admin/Farmasi view).
     */
    public function index(): void
    {
        api_role_guard(['Admin', 'Dokter', 'Farmasi']);

        $kunjunganId = (int)($_GET['kunjungan_id'] ?? 0);
        $db          = Database::connect();

        if ($kunjunganId > 0) {
            $stmt = $db->prepare(
                'SELECT r.*, o.nama_obat, o.kode_kfa, o.kandungan_aktif
                 FROM resep r
                 JOIN tabel_obat o ON r.obat_id = o.id
                 WHERE r.kunjungan_id = :kid
                 ORDER BY r.id ASC'
            );
            $stmt->execute([':kid' => $kunjunganId]);
        } else {
            $stmt = $db->prepare(
                'SELECT r.*, o.nama_obat, o.kode_kfa,
                        p.nama AS nama_pasien, k.waktu AS waktu_kunjungan
                 FROM resep r
                 JOIN tabel_obat o ON r.obat_id = o.id
                 JOIN kunjungan k  ON r.kunjungan_id = k.id
                 JOIN pasien p     ON k.pasien_id = p.id
                 ORDER BY r.id DESC
                 LIMIT 200'
            );
            $stmt->execute();
        }

        $data = $stmt->fetchAll();
        json_response(['ok' => true, 'data' => $data, 'total' => count($data)]);
    }

    // ── GET /api/farmasi/obat ─────────────────────────────────────────────────
    /**
     * Returns the full drug catalogue. Used to pre-populate the offline
     * IndexedDB drug store so autocomplete works without internet.
     */
    public function obatList(): void
    {
        api_role_guard(['Admin', 'Dokter', 'Farmasi', 'Perawat']);

        $obatList = $this->obat->all();
        json_response(['ok' => true, 'data' => $obatList, 'total' => count($obatList)]);
    }
}
