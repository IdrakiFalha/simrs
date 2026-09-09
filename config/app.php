<?php
/**
 * Konstanta aplikasi
 */
if (!defined('APP_NAME')) {
    define('APP_NAME', 'SIMRS');
}
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

function url(string $route = 'dashboard', array $params = []): string
{
    $query = array_merge(['r' => $route], $params);
    return APP_BASE . '/index.php?' . http_build_query($query);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $route, array $params = []): void
{
    header('Location: ' . url($route, $params));
    exit;
}

function flash(string $key, ?string $message = null)
{
    if ($message === null) {
        $val = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $val;
    }
    $_SESSION['flash'][$key] = $message;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        redirect('auth/login');
    }
}

function require_role(array $roles): void
{
    require_login();
    $role = current_user()['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        flash('error', 'Akses ditolak untuk peran Anda.');
        redirect('dashboard');
    }
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * API guard: abort with 401 JSON if user is not authenticated via session.
 * Drop-in at the top of any API method that requires login.
 */
function api_auth_guard(): void
{
    if (!current_user()) {
        json_response(['ok' => false, 'error' => 'Unauthenticated', 'message' => 'Silakan login terlebih dahulu.'], 401);
    }
}

/**
 * API guard: abort with 403 JSON if user's role is not in $roles.
 * Implies api_auth_guard() — will also 401 if not logged in.
 */
function api_role_guard(array $roles): void
{
    api_auth_guard();
    $role = current_user()['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        json_response(['ok' => false, 'error' => 'Forbidden', 'message' => 'Akses ditolak untuk peran Anda.'], 403);
    }
}

/**
 * Parse JSON request body and merge with $_POST.
 * Useful for API endpoints that accept application/json.
 * @return array Merged input data.
 */
function api_input(): array
{
    $body = [];
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $raw  = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? [];
    }
    return array_merge($_POST, $body);
}

function view(string $path, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $user = current_user();
    $viewFile = APP_ROOT . '/views/' . $path . '.php';
    if (!is_file($viewFile)) {
        die('View tidak ditemukan: ' . e($path));
    }
    require $viewFile;
}
