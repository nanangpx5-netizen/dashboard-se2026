<?php
declare(strict_types=1);

/**
 * Health check endpoint for uptime monitoring (e.g. UptimeRobot, Prometheus).
 *
 * Usage:
 *   GET /health.php
 *   GET /health.php?format=json  (default)
 *   GET /health.php?format=prom  (Prometheus text)
 *
 * Returns HTTP 200 if database is reachable, 503 otherwise.
 */

error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../app/bootstrap.php';

$format = $_GET['format'] ?? 'json';
$start  = microtime(true);
$http   = 200;
$checks = [];

// ─── PHP Version ─────────────────────────────────────────────
$checks['php_version'] = PHP_VERSION;

// ─── Database Check ──────────────────────────────────────────
$dbOk = false;
$dbTime = 0;
try {
    $db     = \App\Helpers\Database::getInstance();
    $dbOk   = $db->isConnected();
    $dbTime = round((microtime(true) - $start) * 1000, 1);
} catch (\Throwable $e) {
    $dbOk = false;
    $dbTime = round((microtime(true) - $start) * 1000, 1);
}

$checks['database'] = $dbOk ? 'connected' : 'unreachable';
$checks['database_time_ms'] = $dbTime;
$checks['database_name'] = $dbOk ? $db->getCurrentDatabase() : null;

if (!$dbOk) {
    $http = 503;
}

// ─── App Info ────────────────────────────────────────────────
$checks['app_name']    = 'Dashboard SE2026';
$checks['app_env']     = $_ENV['APP_ENV'] ?? 'production';
$checks['timestamp']   = date('c');
$checks['response_ms'] = round((microtime(true) - $start) * 1000, 1);

// ─── Output ──────────────────────────────────────────────────
http_response_code($http);

if ($format === 'prom') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "# HELP dashboard_se2026_up Database reachability (1=ok, 0=down)\n";
    echo "# TYPE dashboard_se2026_up gauge\n";
    echo "dashboard_se2026_up " . ($dbOk ? 1 : 0) . "\n";
    echo "dashboard_se2026_db_time_ms " . $checks['database_time_ms'] . "\n";
    echo "dashboard_se2026_response_ms " . $checks['response_ms'] . "\n";
    echo "dashboard_se2026_info{app=\"" . $checks['app_name'] . "\",env=\"" . $checks['app_env'] . "\",php=\"" . $checks['php_version'] . "\"} 1\n";
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
