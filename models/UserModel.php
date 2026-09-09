<?php
/**
 * models/UserModel.php
 * User CRUD, password verification, role checks.
 */
class UserModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        return $stmt->fetch() ?: null;
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT id, username, nama_lengkap, role FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }

    public function create(string $username, string $password, string $namaLengkap, string $role): int {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, password_hash, nama_lengkap, role) VALUES (:u, :p, :n, :r)'
        );
        $stmt->execute([':u' => $username, ':p' => $hash, ':n' => $namaLengkap, ':r' => $role]);
        return (int) $this->db->lastInsertId();
    }

    public function all(): array {
        return $this->db->query('SELECT id, username, nama_lengkap, role FROM users ORDER BY id')->fetchAll();
    }
}
