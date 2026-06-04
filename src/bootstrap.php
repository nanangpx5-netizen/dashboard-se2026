<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('Asia/Jakarta');

define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', APP_ROOT . '/src');
define('PUBLIC_PATH', APP_ROOT . '/public');
define('STORAGE_PATH', APP_ROOT . '/storage');
define('LOG_PATH', STORAGE_PATH . '/logs');
define('VIEW_PATH', APP_ROOT . '/views');

// ─── Composer autoloader (OpenSpout, Dompdf, dll) ────────────────────
$composerAutoload = APP_ROOT . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

// ─── Load .env ──────────────────────────────────────────────────────
$envFile = APP_ROOT . '/.env';
if (!is_file($envFile)) {
    $msg = '.env file not found. Copy .env.example menjadi .env lalu isi konfigurasi database.';
    \App\Core\Database::logError('ENV_FILE_NOT_FOUND', $msg, __FILE__, __LINE__);
    http_response_code(500);
    die('ERROR: ' . $msg);
}

try {
    \App\Helpers\Env::load($envFile);
} catch (\Throwable $e) {
    \App\Core\Database::logError('ENV_LOAD_ERROR', $e->getMessage(), $e->getFile(), $e->getLine());
    http_response_code(500);
    die('ERROR: Gagal memuat .env: ' . htmlspecialchars($e->getMessage()));
}

// ─── Verify DB_DATABASE exists ─────────────────────────────────────
$dbName = \App\Helpers\Env::get('DB_DATABASE', \App\Helpers\Env::get('DB_NAME', ''));
if (empty($dbName)) {
    $msg = 'DB_DATABASE tidak ditemukan di .env. Pastikan variabel DB_DATABASE=bps_jember_se2026 ada.';
    \App\Core\Database::logError('DB_NAME_MISSING', $msg, __FILE__, __LINE__);
    http_response_code(500);
    die('ERROR: ' . $msg);
}

// ─── Security headers (skipped if App::boot() will send them) ──────
$cspEnabled = filter_var(\App\Helpers\Env::get('CSP_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
if ($cspEnabled && !headers_sent() && PHP_SAPI !== 'cli') {
    $csp = \App\Helpers\Env::get(
        'CSP_DIRECTIVES',
        "default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net code.jquery.com cdn.datatables.net unpkg.com cdnjs.cloudflare.com; "
        . "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net cdn.datatables.net unpkg.com cdnjs.cloudflare.com fonts.googleapis.com; "
        . "img-src 'self' data: blob: https:; "
        . "font-src 'self' data: cdnjs.cloudflare.com fonts.gstatic.com; "
        . "connect-src 'self'; "
        . "frame-ancestors 'self'; "
        . "form-action 'self'; "
        . "base-uri 'self'"
    );
    header('Content-Security-Policy: ' . $csp);
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// ─── Initialize Database Singleton ─────────────────────────────────
try {
    \App\Config\DatabaseConfig::load();
    \App\Core\Database::getInstance();
} catch (\Throwable $e) {
    http_response_code(500);
    $safeMsg = htmlspecialchars($e->getMessage());
    echo <<<HTML
    <!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
    <title>Database Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head><body class="bg-light">
    <div class="container py-5">
    <div class="alert alert-danger shadow-sm">
    <h5 class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Koneksi Database Gagal</h5>
    <p>{$safeMsg}</p>
    <hr>
    <h6>Troubleshooting:</h6>
    <ol class="small mb-0">
        <li>Pastikan MySQL berjalan (start Laragon / <code>net start mysql</code>)</li>
        <li>Pastikan file <code>.env</code> ada di root project</li>
        <li>Pastikan <code>DB_DATABASE=bps_jember_se2026</code> di .env</li>
        <li>Pastikan kredensial database benar</li>
        <li>Jalankan: <code>mysql -u root -e "SELECT 1" bps_jember_se2026</code></li>
        <li>Cek log: <code>storage/logs/database.log</code> untuk detail</li>
    </ol>
    </div></div></body></html>
    HTML;
    exit;
}
