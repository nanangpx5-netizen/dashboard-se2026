<?php

/**
 * Front Controller — Dashboard SE2026
 * Semua request masuk melalui file ini.
 * Routing: ?page=xxx&sub=yyy
 */

$_REQUEST_START = microtime(true);

// ─── Autoload ───────────────────────────────────────────────────────────────
$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoload)) {
    die('Composer autoload not found. Run: composer install');
}
require $autoload;

// ─── Bootstrap ──────────────────────────────────────────────────────────────
require __DIR__ . '/config/config.php';
require __DIR__ . '/config/constants.php';

use App\Core\App;
use App\Core\Request;
use App\Helpers\Session;
use App\Helpers\Security;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\CsrfMiddleware;

$app = App::instance();
$app->boot();

$request = new Request();
$page    = $request->page();
$sub     = $request->sub();

// ─── Route: Logout ──────────────────────────────────────────────────────────
if ($page === 'logout') {
    require_once __DIR__ . '/src/Controllers/AuthController.php';
    $c = new \App\Controllers\AuthController();
    $c->logout();
    exit;
}

// ─── Route: Login (public) ─────────────────────────────────────────────────
if ($page === 'login') {
    require_once __DIR__ . '/src/Controllers/AuthController.php';
    $c = new \App\Controllers\AuthController();
    if ($request->isPost()) {
        $c->login();
    } else {
        $c->loginForm();
    }
    exit;
}

// ─── Route: API /auth/me (check session) ───────────────────────────────────
if ($page === 'auth' && $sub === 'me') {
    require_once __DIR__ . '/src/Controllers/AuthController.php';
    $c = new \App\Controllers\AuthController();
    $c->me();
    exit;
}

// ─── Auth Middleware ────────────────────────────────────────────────────────
$authMw = new AuthMiddleware();
try {
    $authMw->handle();
} catch (\Throwable $e) {
    Session::flash('error', 'Sesi tidak valid. Silakan login ulang.');
    header('Location: ?page=login');
    exit;
}

// ─── CSRF Middleware (POST only) ────────────────────────────────────────────
if ($request->isPost()) {
    $csrfMw = new CsrfMiddleware();
    try {
        $csrfMw->handle();
    } catch (\Throwable $e) {
        Session::flash('error', 'Token CSRF tidak valid.');
        if ($request->isAjax()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'CSRF token invalid']);
            exit;
        }
        header('Location: ?page=dashboard');
        exit;
    }
}

// ─── Role Middleware ────────────────────────────────────────────────────────
$allowedRoles = PAGE_ACCESS[$page][$sub] ?? PAGE_ACCESS[$page][''] ?? DASHBOARD_ROLES;
$roleMw = new RoleMiddleware();
try {
    $roleMw->handle(implode(',', (array) $allowedRoles));
} catch (\Throwable $e) {
    http_response_code(403);
    require __DIR__ . '/views/errors/403.php';
    exit;
}

// ─── Route Protected Pages ──────────────────────────────────────────────────
$controllerMap = [
    'dashboard' => [
        ''           => ['DashboardController', 'index'],
        'import'     => ['ImportController', 'index'],
        'assignment' => ['AssignmentController', 'index'],
        'monitoring' => ['MonitoringController', 'index'],
        'workload'   => ['WorkloadController', 'index'],
        'wilayah'    => ['WilayahController', 'index'],
        'petugas'           => ['PetugasController', 'index'],
        'petugas-lapangan'  => ['PclPmlTfController', 'index'],
        'pml-report'        => ['PmlReportController', 'index'],
        'audit'             => ['AuditLogController', 'index'],
        'report'     => ['ReportController', 'index'],
        'insight'    => ['InsightController', 'index'],
        'pegawai-activity' => ['PegawaiActivityController', 'index'],
    ],
];

$controllerName = $controllerMap[$page][$sub][0] ?? null;
$methodName     = $controllerMap[$page][$sub][1] ?? 'index';

if ($controllerName === null) {
    http_response_code(404);
    require __DIR__ . '/views/errors/404.php';
    exit;
}

$controllerClass = 'App\\Controllers\\' . $controllerName;
$controllerFile  = __DIR__ . '/src/Controllers/' . $controllerName . '.php';

if (!is_file($controllerFile)) {
    http_response_code(404);
    require __DIR__ . '/views/errors/404.php';
    exit;
}

require_once $controllerFile;
$controller = new $controllerClass();
$controller->$methodName();
