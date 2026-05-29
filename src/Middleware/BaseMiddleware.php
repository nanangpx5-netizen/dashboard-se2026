<?php

namespace App\Middleware;

abstract class BaseMiddleware
{
    abstract public function handle(mixed $param = null): void;

    protected function redirectToLogin(): void
    {
        header('Location: ' . BASE_URL . '?page=login');
        exit;
    }

    protected function forbidden(): void
    {
        http_response_code(403);
        $viewPath = defined('VIEW_PATH') ? VIEW_PATH : __DIR__ . '/../../views';
        require $viewPath . '/errors/403.php';
        exit;
    }

    protected function unauthorized(): void
    {
        http_response_code(401);
        $viewPath = defined('VIEW_PATH') ? VIEW_PATH : __DIR__ . '/../../views';
        require $viewPath . '/errors/401.php';
        exit;
    }
}
