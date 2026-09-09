<?php
/**
 * controllers/FarmasiController.php
 * Pharmacy — drug list view.
 */
class FarmasiController {
    private ObatModel  $obat;

    public function __construct() {
        $this->obat = new ObatModel();
    }

    public function index(): void {
        require_role(['Admin', 'Dokter', 'Farmasi']);
        $obatList = $this->obat->all();
        view('farmasi/index', compact('obatList'));
    }

    public function store(): void {
        require_role(['Admin', 'Dokter', 'Farmasi']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $kode_kfa        = trim($_POST['kode_kfa'] ?? '');
            $nama_obat       = trim($_POST['nama_obat'] ?? '');
            $kandungan_aktif = trim($_POST['kandungan_aktif'] ?? '');
            
            if ($kode_kfa && $nama_obat) {
                $this->obat->create([
                    'kode_kfa'             => $kode_kfa,
                    'nama_obat'            => $nama_obat,
                    'kandungan_aktif'      => $kandungan_aktif,
                    'peringatan_interaksi' => null
                ]);
            }
            redirect('farmasi');
        }
    }
}
