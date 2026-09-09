<?php
/**
 * SIMRS Front Controller / Router
 */
declare(strict_types=1);

session_start();

define('APP_ROOT', __DIR__);

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') {
    $base = '';
} else {
    $base = rtrim($scriptDir, '/');
}
define('APP_BASE', $base);

require_once APP_ROOT . '/config/app.php';

spl_autoload_register(function (string $class): void {
    $map = [
        'Database' => APP_ROOT . '/config/database.php',
    ];
    if (isset($map[$class])) {
        require_once $map[$class];
        return;
    }
    $paths = [
        APP_ROOT . '/models/' . $class . '.php',
        APP_ROOT . '/controllers/' . $class . '.php',
    ];
    foreach ($paths as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

$publicRoutes = ['home', 'auth/login', 'auth/doLogin', 'auth/register', 'auth/doRegister', 'pesan/store'];
$route = trim((string) ($_GET['r'] ?? 'home'), '/');
if ($route === '') {
    $route = 'home';
}

if (!in_array($route, $publicRoutes, true)) {
    require_login();
}

$routes = [
    'home'                => [HomeController::class, 'index'],
    'pesan/store'         => [HomeController::class, 'storePesan'],
    'auth/login'          => [AuthController::class, 'login'],
    'auth/doLogin'        => [AuthController::class, 'doLogin'],
    'auth/register'       => [AuthController::class, 'register'],
    'auth/doRegister'     => [AuthController::class, 'doRegister'],
    'auth/logout'         => [AuthController::class, 'logout'],
    'dashboard'           => [DashboardController::class, 'index'],
    'admisi'              => [AdmisiController::class, 'index'],
    'admisi/store'        => [AdmisiController::class, 'store'],
    'admisi/toggleConsent'=> [AdmisiController::class, 'toggleConsent'],
    'klinis/periksa'      => [KlinisController::class, 'periksa'],
    'klinis/store'        => [KlinisController::class, 'store'],
    'klinis/cariObat'     => [KlinisController::class, 'cariObat'],
    'integrasi'           => [IntegrasiController::class, 'index'],
    'farmasi'             => [FarmasiController::class, 'index'],
    'farmasi/store'       => [FarmasiController::class, 'store'],
    'audit'               => [AuditController::class, 'index'],
];

if ($route === 'sync') {
    header('Location: ' . APP_BASE . '/public/sync.html');
    exit;
}

if (!isset($routes[$route])) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:2rem;background:#0f0c29;color:#fff;">';
    echo '<h1>404</h1><p>Rute tidak ditemukan.</p><p><a href="' . e(url('dashboard')) . '" style="color:#00c9a7">Kembali</a></p></body></html>';
    exit;
}

[$class, $method] = $routes[$route];
$controller = new $class();
$controller->$method();
