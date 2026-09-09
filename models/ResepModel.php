<?php
require_once APP_ROOT . '/config/database.php';

class ResepModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function insert(int $kunjunganId, int $obatId, string $aturan, int $durWarning): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO resep (kunjungan_id, obat_id, aturan_pakai, status_dur_warning)
             VALUES (:k, :o, :a, :d)'
        );
        $stmt->execute([
            'k' => $kunjunganId,
            'o' => $obatId,
            'a' => $aturan,
            'd' => $durWarning,
        ]);
    }

    public function byKunjungan(int $kunjunganId): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, o.nama_obat, o.kode_kfa, o.kandungan_aktif
             FROM resep r
             JOIN `tabel obat` o ON o.id = r.obat_id
             WHERE r.kunjungan_id = :id'
        );
        $stmt->execute(['id' => $kunjunganId]);
        return $stmt->fetchAll();
    }
}
