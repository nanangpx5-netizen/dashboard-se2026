<?php
/**
 * CLI test for TestConnectionController
 * Verifies the refactored controller works without going through HTTP.
 */

$projectRoot = dirname(__DIR__);
define('APP_ROOT', $projectRoot);
define('VIEW_PATH', APP_ROOT . '/views');
define('STORAGE_PATH', APP_ROOT . '/storage');
define('LOG_PATH', STORAGE_PATH . '/logs');
define('APP_PATH', APP_ROOT . '/app');
define('PUBLIC_PATH', APP_ROOT . '/public');
define('BASE_URL', '/dashboard-se2026/');
define('APP_NAME', 'Dashboard SE2026');

date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

require_once APP_ROOT . '/vendor/autoload.php';

use App\Helpers\Env;
use App\Core\Database;
use App\Config\DatabaseConfig;
use App\Controllers\TestConnectionController;

try {
    Env::load(APP_ROOT . '/.env');
    DatabaseConfig::load();
    $db = Database::getInstance();
    echo "DB Connected: " . $db->getCurrentDatabase() . PHP_EOL;
    echo "Connection ID: " . $db->getConnectionId() . PHP_EOL;
    echo "Server: " . $db->serverInfo() . PHP_EOL;

    $ctrl = new TestConnectionController();
    ob_start();
    $ctrl->index();
    $output = ob_get_clean();

    echo "=== Output length: " . strlen($output) . " ===" . PHP_EOL;
    echo "=== Test patterns ===" . PHP_EOL;
    echo "Has DOCTYPE: " . (strpos($output, '<!DOCTYPE html>') !== false ? 'YES' : 'NO') . PHP_EOL;
    echo "Has navbar: " . (strpos($output, 'Shared Database Validator') !== false ? 'YES' : 'NO') . PHP_EOL;
    echo "Has table counts: " . (strpos($output, 'Live Table Counts') !== false ? 'YES' : 'NO') . PHP_EOL;
    echo "Has users sample: " . (strpos($output, 'Sample Data: users') !== false ? 'YES' : 'NO') . PHP_EOL;
    echo "Has realtime proof: " . (strpos($output, 'REAL-TIME SHARED DATABASE') !== false ? 'YES' : 'NO') . PHP_EOL;
    echo "All PHP syntax OK" . PHP_EOL;
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "AT: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
