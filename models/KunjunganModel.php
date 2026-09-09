<?php
/**
 * models/KunjunganModel.php
 * Visit CRUD + NEWS2 scoring (NHS standard).
 */
class KunjunganModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function all(): array {
        return $this->db->query(
            'SELECT k.*, p.nama AS nama_pasien, u.nama_lengkap AS nama_dokter
             FROM kunjungan k
             JOIN pasien p ON k.pasien_id = p.id
             JOIN users  u ON k.dokter_id = u.id
             ORDER BY k.waktu DESC'
        )->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM kunjungan WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByPasien(int $pasienId): array {
        $stmt = $this->db->prepare(
            'SELECT * FROM kunjungan WHERE pasien_id = :pid ORDER BY waktu DESC'
        );
        $stmt->execute([':pid' => $pasienId]);
        return $stmt->fetchAll();
    }

    public function create(array $d): int {
        $stmt = $this->db->prepare(
            'INSERT INTO kunjungan
             (pasien_id, dokter_id, waktu, sistole, diastole, nadi, suhu, laju_napas,
              keluhan, diagnosa_icd10, satusehat_encounter_id, status_kirim_satusehat)
             VALUES (:pasien, :dokter, NOW(), :sistole, :diastole, :nadi, :suhu, :napas,
                     :keluhan, :diagnosa, :encounter, :status)'
        );
        $stmt->execute([
            ':pasien'    => $d['pasien_id'],
            ':dokter'    => $d['dokter_id'],
            ':sistole'   => $d['sistole'] ?? null,
            ':diastole'  => $d['diastole'] ?? null,
            ':nadi'      => $d['nadi'] ?? null,
            ':suhu'      => $d['suhu'] ?? null,
            ':napas'     => $d['laju_napas'] ?? null,
            ':keluhan'   => $d['keluhan'] ?? null,
            ':diagnosa'  => $d['diagnosa_icd10'] ?? null,
            ':encounter' => $d['satusehat_encounter_id'] ?? null,
            ':status'    => $d['status_kirim_satusehat'] ?? 'pending',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateEncounterStatus(int $id, string $encounterId, string $status): bool {
        $stmt = $this->db->prepare(
            'UPDATE kunjungan SET satusehat_encounter_id = :eid, status_kirim_satusehat = :s WHERE id = :id'
        );
        return $stmt->execute([':eid' => $encounterId, ':s' => $status, ':id' => $id]);
    }

    public function count(): int {
        return (int) $this->db->query('SELECT COUNT(*) FROM kunjungan')->fetchColumn();
    }

    public function countToday(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM kunjungan WHERE DATE(waktu) = CURDATE()")->fetchColumn();
    }


}
