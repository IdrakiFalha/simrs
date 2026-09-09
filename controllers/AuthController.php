<?php
/**
 * controllers/AuthController.php
 * Login / logout / session management.
 */
class AuthController {
    private UserModel  $users;
    private AuditModel $audit;

    public function __construct() {
        $this->users = new UserModel();
        $this->audit = new AuditModel();
    }

    /** Show login form */
    public function login(): void {
        if (current_user()) {
            redirect('dashboard');
        }
        $error = null;
        view('auth/login', compact('error'));
    }

    /** Process login POST */
    public function doLogin(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('auth/login');
        }
        if (!csrf_verify()) {
            flash('error', 'Sesi tidak valid, silakan coba lagi.');
            redirect('auth/login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = $this->users->findByUsername($username);
        if ($user && $this->users->verifyPassword($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'id'           => $user['id'],
                'username'     => $user['username'],
                'nama_lengkap' => $user['nama_lengkap'],
                'role'         => $user['role'],
            ];
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            $this->audit->log($user['id'], 'login', 'users');
            redirect('dashboard');
        }

        flash('error', 'Username atau password salah.');
        redirect('auth/login');
    }

    /** Logout and destroy session */
    public function logout(): void {
        $userId = current_user()['id'] ?? 0;
        if ($userId) {
            $this->audit->log($userId, 'logout', 'users');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        redirect('auth/login');
    }

    /** Show registration form for Nakes */
    public function register(): void {
        $flashErr = flash('error');
        $flashOk  = flash('ok');
        view('auth/register', compact('flashErr', 'flashOk'));
    }

    /** Process registration POST */
    public function doRegister(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('auth/register');
        }
        if (!csrf_verify()) {
            flash('error', 'Sesi tidak valid, silakan coba lagi.');
            redirect('auth/register');
        }

        $nama     = trim($_POST['nama_lengkap'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'Dokter';

        $allowedRoles = ['Dokter', 'Perawat', 'Farmasi', 'Admin'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'Dokter';
        }

        if (empty($nama) || empty($username) || empty($password)) {
            flash('error', 'Semua bidang wajib diisi.');
            redirect('auth/register');
        }

        if (strlen($password) < 6) {
            flash('error', 'Password minimal 6 karakter.');
            redirect('auth/register');
        }

        // Check duplicate username
        $existing = $this->users->findByUsername($username);
        if ($existing) {
            flash('error', 'Username "' . e($username) . '" sudah digunakan. Silakan gunakan username lain.');
            redirect('auth/register');
        }

        $newUserId = $this->users->create($username, $password, $nama, $role);

        $this->audit->log($newUserId, 'register_user', 'users');

        flash('ok', 'Akun Tenaga Kesehatan berhasil terdaftar! Silakan masuk dengan akun baru Anda.');
        redirect('auth/login');
    }
}
