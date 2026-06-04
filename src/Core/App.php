<?php

namespace App\Core;

use App\Helpers\Env;
use App\Helpers\Session;

final class App
{
    private static ?App $instance = null;
    private bool $booted = false;

    private function __construct() {}

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->loadEnvironment();
        $this->sendSecurityHeaders();
        $this->setErrorHandler();
        $this->setTimezone();
        $this->startSession();
        $this->setBaseUrl();

        $this->booted = true;
    }

    private function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        $cspEnabled = filter_var(Env::get('CSP_ENABLED', true), FILTER_VALIDATE_BOOLEAN);
        if (!$cspEnabled) {
            return;
        }

        $csp = Env::get(
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

    private function loadEnvironment(): void
    {
        $envPath = dirname(__DIR__, 2) . '/.env';
        Env::load($envPath);
    }

    private function setErrorHandler(): void
    {
        $debug = Env::get('APP_DEBUG', true);
        $logDir = dirname(__DIR__, 2) . '/storage/logs';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        set_exception_handler(function (\Throwable $e) use ($logDir, $debug): void {
            $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
            $message = sprintf(
                "[%s] %s: %s in %s:%d\n%s\n",
                date('Y-m-d H:i:s'),
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
            error_log($message, 3, $logFile);

            if ($this->isAjax()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => $debug ? $e->getMessage() : 'Terjadi kesalahan server',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $viewPath = defined('VIEW_PATH') ? VIEW_PATH : dirname(__DIR__, 2) . '/views';
            $errorView = $viewPath . '/errors/500.php';

            http_response_code(500);
            if (is_file($errorView)) {
                require $errorView;
            } else {
                echo '<h1>500 - Internal Server Error</h1>';
                if ($debug) {
                    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                }
            }
            exit;
        });

        set_error_handler(function (int $level, string $message, string $file, int $line) use ($logDir): bool {
            if (!(error_reporting() & $level)) {
                return false;
            }
            $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
            $entry = sprintf(
                "[%s] Error %d: %s in %s:%d\n",
                date('Y-m-d H:i:s'),
                $level,
                $message,
                $file,
                $line
            );
            error_log($entry, 3, $logFile);
            return false;
        });
    }

    private function setTimezone(): void
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    private function startSession(): void
    {
        Session::start();
    }

    private function setBaseUrl(): void
    {
        $baseUrl = Env::get('BASE_URL', '/dashboard-se2026/');
        $baseUrl = rtrim($baseUrl, '/') . '/';
        defined('BASE_URL') || define('BASE_URL', $baseUrl);
    }

    public function run(): void
    {
        $this->boot();

        $db = Database::connect();
        $request = new Request();
        $router = Router::instance();

        $this->registerRoutes($router);

        $router->resolveLegacy($request);
    }

    private function registerRoutes(Router $router): void
    {
        $router->get('login', \App\Controllers\AuthController::class . '::loginForm');
        $router->post('login', \App\Controllers\AuthController::class . '::login');
        $router->get('logout', \App\Controllers\AuthController::class . '::logout');

        $router->group(['middleware' => ['AuthMiddleware']], function (Router $router) {
            $router->get('dashboard', \App\Controllers\DashboardController::class . '::index');
            $router->get('dashboard/import', \App\Controllers\ImportController::class . '::index');
            $router->post('dashboard/import', \App\Controllers\ImportController::class . '::upload');
            $router->get('dashboard/assignment', \App\Controllers\AssignmentController::class . '::index');
            $router->get('dashboard/monitoring', \App\Controllers\MonitoringController::class . '::index');
            $router->get('dashboard/wilayah', \App\Controllers\WilayahController::class . '::index');
            $router->get('dashboard/petugas', \App\Controllers\PetugasController::class . '::index');
        });
    }

    private function isAjax(): bool
    {
        $header = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return strtoupper($header) === 'XMLHTTPREQUEST'
            || (isset($_GET['ajax']) && $_GET['ajax'] === '1');
    }
}
