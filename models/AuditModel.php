<?php
/**
 * models/AuditModel.php
 * Insert/read audit logs with SHA-256 hash signature (Estonian-inspired chain).
 */
class AuditModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    /**
     * Record an audit entry. Hash = SHA256(user_id|aksi|target_tabel|waktu|previous_hash).
     */
    public function log(int $userId, string $action, string $targetTable): bool {
        $timestamp = date('Y-m-d H:i:s');
        $prevHash  = $this->lastHash();
        $data      = $userId . '|' . $action . '|' . $targetTable . '|' . $timestamp . '|' . $prevHash;
        $hash      = hash('sha256', $data);

        $stmt = $this->db->prepare(
            'INSERT INTO audit_log (user_id, aksi, target_tabel, waktu, hash_signature)
             VALUES (:uid, :aksi, :tabel, :waktu, :hash)'
        );
        return $stmt->execute([
            ':uid'   => $userId,
            ':aksi'  => $action,
            ':tabel' => $targetTable,
            ':waktu' => $timestamp,
            ':hash'  => $hash,
        ]);
    }

    public function all(int $limit = 100): array {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.nama_lengkap, u.role
             FROM audit_log a
             JOIN users u ON a.user_id = u.id
             ORDER BY a.waktu DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function lastHash(): string {
        $row = $this->db->query('SELECT hash_signature FROM audit_log ORDER BY id DESC LIMIT 1')->fetch();
        return $row ? $row['hash_signature'] : str_repeat('0', 64);
    }

    public function count(): int {
        return (int) $this->db->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();
    }
}
