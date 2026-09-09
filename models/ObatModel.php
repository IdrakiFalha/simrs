<?php
/**
 * models/ObatModel.php
 * Interacts with `tabel_obat` — Drug CRUD + DUR interaction check.
 */
class ObatModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function all(): array {
        return $this->db->query('SELECT * FROM tabel_obat ORDER BY nama_obat')->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM tabel_obat WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function search(string $q): array {
        $like = '%' . $q . '%';
        $stmt = $this->db->prepare(
            'SELECT * FROM tabel_obat WHERE nama_obat LIKE :q OR kode_kfa LIKE :q2 OR kandungan_aktif LIKE :q3 ORDER BY nama_obat LIMIT 20'
        );
        $stmt->execute([':q' => $like, ':q2' => $like, ':q3' => $like]);
        return $stmt->fetchAll();
    }

    public function create(array $d): int {
        $stmt = $this->db->prepare(
            'INSERT INTO tabel_obat (kode_kfa, nama_obat, kandungan_aktif)
             VALUES (:kode, :nama, :kand)'
        );
        $stmt->execute([
            ':kode'       => $d['kode_kfa'],
            ':nama'       => $d['nama_obat'],
            ':kand'       => $d['kandungan_aktif'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }



    public function count(): int {
        return (int) $this->db->query('SELECT COUNT(*) FROM tabel_obat')->fetchColumn();
    }
}
