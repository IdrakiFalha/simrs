<?php
/**
 * models/PasienModel.php
 * Patient CRUD, search, consent handling.
 */
class PasienModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function all(): array {
        return $this->db->query('SELECT * FROM pasien ORDER BY id DESC')->fetchAll();
    }

    public function search(string $q): array {
        $like = '%' . $q . '%';
        $stmt = $this->db->prepare('SELECT * FROM pasien WHERE nik LIKE :q OR nama LIKE :q2 ORDER BY id DESC');
        $stmt->execute([':q' => $like, ':q2' => $like]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM pasien WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByNik(string $nik): ?array {
        $stmt = $this->db->prepare('SELECT * FROM pasien WHERE nik = :nik');
        $stmt->execute([':nik' => $nik]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $d): int {
        $stmt = $this->db->prepare(
            'INSERT INTO pasien (nik, nama, jenis_kelamin, tanggal_lahir, ihs_number, consent_satusehat)
             VALUES (:nik, :nama, :jk, :tgl, :ihs, :consent)'
        );
        $stmt->execute([
            ':nik'     => $d['nik'],
            ':nama'    => $d['nama'],
            ':jk'      => $d['jenis_kelamin'],
            ':tgl'     => $d['tanggal_lahir'],
            ':ihs'     => $d['ihs_number'] ?? null,
            ':consent' => !empty($d['consent_satusehat']) ? 1 : 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateConsent(int $id, bool $consent): bool {
        $stmt = $this->db->prepare('UPDATE pasien SET consent_satusehat = :c WHERE id = :id');
        return $stmt->execute([':c' => $consent ? 1 : 0, ':id' => $id]);
    }

    public function count(): int {
        return (int) $this->db->query('SELECT COUNT(*) FROM pasien')->fetchColumn();
    }
}
