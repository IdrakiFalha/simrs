<?php
/**
 * api/index.php
 * REST API Entry Point — SIMRS PWA
 *
 * All requests matching /api/* are rewritten here by .htaccess.
 * This router parses the URI, authenticates via PHP Session, and
 * dispatches to the correct Api controller method.
 */
declare(strict_types=1);

session_start();

// ── Bootstrap ────────────────────────────────────────────────────────────────
define('APP_ROOT', dirname(__DIR__));

$scriptDir = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')));
if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') {
    $base = '';
} else {
    $base = rtrim($scriptDir, '/');
}
define('APP_BASE', $base);

require_once APP_ROOT . '/config/app.php';
require_once APP_ROOT . '/config/satusehat_api.php';

// ── Autoloader ───────────────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $map = [
        'Database' => APP_ROOT . '/config/database.php',
    ];
    if (isset($map[$class])) {
        require_once $map[$class];
        return;
    }
    $paths = [
        APP_ROOT . '/models/'          . $class . '.php',
        APP_ROOT . '/controllers/'     . $class . '.php',
        APP_ROOT . '/controllers/Api/' . $class . '.php',
    ];
    foreach ($paths as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

// ── CORS Headers (for local dev / SPA on same origin) ────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
// Adjust origin in production to your actual domain.
$allowedOrigins = ['http://localhost', 'http://127.0.0.1'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true) || str_starts_with($origin, 'http://localhost:')) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
}

// ── Preflight ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Parse Path ───────────────────────────────────────────────────────────────
// Raw URI:  /simrs/api/pasien/5/consent
// We strip the base prefix and the leading /api/ segment.
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestUri = str_replace('\\', '/', $requestUri);

// Remove APP_BASE + /api prefix to get the resource path
$prefix = rtrim($base, '/') . '/api';
if (str_starts_with($requestUri, $prefix)) {
    $path = substr($requestUri, strlen($prefix));
} else {
    $path = $requestUri;
}
$path   = '/' . ltrim($path, '/');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ── Route Table ──────────────────────────────────────────────────────────────
// Format: [METHOD, regex_pattern, ControllerClass, method, named_capture_groups...]
$routes = [
    // ── Auth ─────────────────────────────────────────────────────────────
    ['GET',   '#^/csrf-token$#',               'AuthApiController',       'csrfToken'],
    ['GET',   '#^/auth/me$#',                  'AuthApiController',       'me'],
    ['POST',  '#^/auth/login$#',               'AuthApiController',       'login'],
    ['POST',  '#^/auth/logout$#',              'AuthApiController',       'logout'],
    ['POST',  '#^/auth/register$#',            'AuthApiController',       'register'],

    // ── Dashboard ────────────────────────────────────────────────────────
    ['GET',   '#^/dashboard$#',                'DashboardApiController',  'index'],

    // ── Admisi / Pasien ───────────────────────────────────────────────────
    ['GET',   '#^/pasien$#',                   'AdmisiApiController',     'index'],
    ['POST',  '#^/pasien$#',                   'AdmisiApiController',     'store'],
    ['PATCH', '#^/pasien/(?P<id>\d+)/consent$#','AdmisiApiController',    'toggleConsent'],

    // ── Klinis / Kunjungan ────────────────────────────────────────────────
    ['GET',   '#^/kunjungan$#',                'KlinisApiController',     'index'],
    ['POST',  '#^/kunjungan$#',                'KlinisApiController',     'store'],
    ['POST',  '#^/kunjungan/dur$#',            'KlinisApiController',     'cekDur'],

    // ── Obat ──────────────────────────────────────────────────────────────
    ['GET',   '#^/obat$#',                     'KlinisApiController',     'cariObat'],

    // ── Integrasi SATUSEHAT ───────────────────────────────────────────────
    ['GET',   '#^/integrasi/token$#',          'IntegrasiApiController',  'getToken'],
    ['POST',  '#^/integrasi/encounter$#',      'IntegrasiApiController',  'pushEncounter'],
    ['POST',  '#^/integrasi/sync$#',           'IntegrasiApiController',  'batchSync'],

    // ── Audit ─────────────────────────────────────────────────────────────
    ['GET',   '#^/audit$#',                    'AuditApiController',      'index'],

    // ── Farmasi ───────────────────────────────────────────────────────────
    ['GET',   '#^/farmasi/resep$#',            'FarmasiApiController',    'index'],
    ['GET',   '#^/farmasi/obat$#',             'FarmasiApiController',    'obatList'],
];

// ── Dispatch ─────────────────────────────────────────────────────────────────
foreach ($routes as [$routeMethod, $pattern, $controllerClass, $action]) {
    if ($routeMethod !== $method) continue;
    if (!preg_match($pattern, $path, $matches)) continue;

    // Extract named captures (e.g. 'id') and put in $_GET for controller access
    foreach ($matches as $key => $value) {
        if (is_string($key)) {
            $_GET[$key] = $value;
        }
    }

    $controller = new $controllerClass();
    $controller->$action();
    exit;
}

// ── 404 ───────────────────────────────────────────────────────────────────────
json_response([
    'ok'      => false,
    'error'   => 'Not Found',
    'message' => "Endpoint [{$method} /api{$path}] tidak ditemukan.",
], 404);
