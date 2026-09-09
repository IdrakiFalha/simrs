<?php
/**
 * controllers/Api/AuthApiController.php
 * REST API — Authentication & Session Management.
 *
 * Endpoints:
 *  GET  /api/csrf-token   → Issue a fresh CSRF token (no auth required)
 *  GET  /api/auth/me      → Return current session user
 *  POST /api/auth/login   → Authenticate and set session cookie
 *  POST /api/auth/logout  → Destroy session
 *  POST /api/auth/register → Register new Nakes account
 */
class AuthApiController
{
    private UserModel  $users;
    private AuditModel $audit;

    public function __construct()
    {
        $this->users = new UserModel();
        $this->audit = new AuditModel();
    }

    // ── GET /api/csrf-token ───────────────────────────────────────────────────
    /**
     * Returns a fresh CSRF token. The frontend JS should call this once on load,
     * then attach the token to every mutating request via the X-CSRF-Token header.
     */
    public function csrfToken(): void
    {
        $token = csrf_token();
        json_response(['ok' => true, 'token' => $token, 'csrf_token' => $token]);
    }

    // ── GET /api/auth/me ──────────────────────────────────────────────────────
    /**
     * Returns the currently authenticated user from the session.
     * Frontend uses this to decide whether to show the login page or the app.
     */
    public function me(): void
    {
        $user = current_user();
        if (!$user) {
            json_response(['ok' => false, 'error' => 'Unauthenticated'], 401);
        }
        json_response(['ok' => true, 'user' => $user]);
    }

    // ── POST /api/auth/login ──────────────────────────────────────────────────
    /**
     * Body (JSON or form): { username, password }
     * On success: sets session cookie and returns user info.
     * On failure: 401 with error message.
     */
    public function login(): void
    {
        $input    = api_input();
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';

        if ($username === '' || $password === '') {
            json_response(['ok' => false, 'error' => 'Validation', 'message' => 'Username dan password wajib diisi.'], 422);
        }

        $user = $this->users->findByUsername($username);
        if ($user && $this->users->verifyPassword($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'id'           => $user['id'],
                'username'     => $user['username'],
                'nama_lengkap' => $user['nama_lengkap'],
                'role'         => $user['role'],
            ];
            session_regenerate_id(true);
            // Issue a fresh CSRF token for this new session
            $csrfToken = csrf_token();
            $this->audit->log($user['id'], 'api_login', 'users');
            json_response([
                'ok'         => true,
                'user'       => $_SESSION['user'],
                'csrf_token' => $csrfToken,
            ]);
        }

        json_response(['ok' => false, 'error' => 'Unauthorized', 'message' => 'Username atau password salah.'], 401);
    }

    // ── POST /api/auth/logout ─────────────────────────────────────────────────
    public function logout(): void
    {
        api_auth_guard();
        $userId = current_user()['id'] ?? 0;
        if ($userId) {
            $this->audit->log($userId, 'api_logout', 'users');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        json_response(['ok' => true, 'message' => 'Berhasil logout.']);
    }

    // ── POST /api/auth/register ───────────────────────────────────────────────
    /**
     * Body (JSON or form): { nama_lengkap, username, password, role }
     * Role must be one of: Dokter, Perawat, Farmasi, Admin.
     */
    public function register(): void
    {
        $input    = api_input();
        $nama     = trim($input['nama_lengkap'] ?? '');
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $role     = $input['role'] ?? 'Dokter';

        $allowedRoles = ['Dokter', 'Perawat', 'Farmasi', 'Admin'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'Dokter';
        }

        $errors = [];
        if ($nama === '')                { $errors[] = 'Nama lengkap wajib diisi.'; }
        if ($username === '')            { $errors[] = 'Username wajib diisi.'; }
        if (strlen($password) < 6)      { $errors[] = 'Password minimal 6 karakter.'; }

        if (!empty($errors)) {
            json_response(['ok' => false, 'error' => 'Validation', 'messages' => $errors], 422);
        }

        if ($this->users->findByUsername($username)) {
            json_response(['ok' => false, 'error' => 'Conflict', 'message' => "Username \"{$username}\" sudah digunakan."], 409);
        }

        $hash      = password_hash($password, PASSWORD_BCRYPT);
        $newUserId = $this->users->create([
            'username'      => $username,
            'password_hash' => $hash,
            'nama_lengkap'  => $nama,
            'role'          => $role,
        ]);

        $this->audit->log($newUserId, 'api_register_user', 'users');
        json_response(['ok' => true, 'user_id' => $newUserId, 'message' => 'Akun Tenaga Kesehatan berhasil terdaftar.'], 201);
    }
}
