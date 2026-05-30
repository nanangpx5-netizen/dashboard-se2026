<?php

namespace App\Middleware;

abstract class BaseMiddleware
{
    abstract public function handle(mixed $param = null): void;

    protected function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    protected function redirectToLogin(): void
    {
        if ($this->isAjax()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Sesi tidak valid. Silakan login ulang.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        header('Location: ' . BASE_URL . '?page=login');
        exit;
    }

    protected function forbidden(): void
    {
        http_response_code(403);
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Akses ditolak.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $viewPath = defined('VIEW_PATH') ? VIEW_PATH : __DIR__ . '/../../views';
        require $viewPath . '/errors/403.php';
        exit;
    }

    protected function unauthorized(): void
    {
        http_response_code(401);
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $viewPath = defined('VIEW_PATH') ? VIEW_PATH : __DIR__ . '/../../views';
        require $viewPath . '/errors/401.php';
        exit;
    }
}
