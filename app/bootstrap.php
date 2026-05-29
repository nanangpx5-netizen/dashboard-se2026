<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('Asia/Jakarta');

define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', APP_ROOT . '/app');
define('PUBLIC_PATH', APP_ROOT . '/public');
define('STORAGE_PATH', APP_ROOT . '/storage');
define('LOG_PATH', STORAGE_PATH . '/logs');
define('VIEW_PATH', APP_PATH . '/Views');

// ─── Composer autoloader (OpenSpout, Dompdf, dll) ────────────────────
$composerAutoload = APP_ROOT . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

// ─── Load .env ──────────────────────────────────────────────────────
$envFile = APP_ROOT . '/.env';
if (!is_file($envFile)) {
    $msg = '.env file not found. Copy .env.example menjadi .env lalu isi konfigurasi database.';
    \App\Helpers\Database::logError('ENV_FILE_NOT_FOUND', $msg, __FILE__, __LINE__);
    http_response_code(500);
    die('ERROR: ' . $msg);
}

try {
    \App\Helpers\Env::load($envFile);
} catch (\Throwable $e) {
    \App\Helpers\Database::logError('ENV_LOAD_ERROR', $e->getMessage(), $e->getFile(), $e->getLine());
    http_response_code(500);
    die('ERROR: Gagal memuat .env: ' . htmlspecialchars($e->getMessage()));
}

// ─── Verify DB_DATABASE exists ─────────────────────────────────────
$dbName = \App\Helpers\Env::get('DB_DATABASE', \App\Helpers\Env::get('DB_NAME', ''));
if (empty($dbName)) {
    $msg = 'DB_DATABASE tidak ditemukan di .env. Pastikan variabel DB_DATABASE=bps_jember_se2026 ada.';
    \App\Helpers\Database::logError('DB_NAME_MISSING', $msg, __FILE__, __LINE__);
    http_response_code(500);
    die('ERROR: ' . $msg);
}

// ─── Initialize Database Singleton ─────────────────────────────────
try {
    \App\Config\Database::load();
    $db = \App\Helpers\Database::getInstance();
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
        <li>Cek log: <code>storage/logs/database.log</code></li>
    </ol>
    </div></div></body></html>
    HTML;
    exit;
}
