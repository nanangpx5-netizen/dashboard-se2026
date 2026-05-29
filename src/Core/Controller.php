<?php

namespace App\Core;

use App\Helpers\Session;
use App\Helpers\Security;

abstract class Controller
{
    protected Request $request;
    protected array $data = [];
    protected string $layout = 'main';

    public function __construct()
    {
        $this->request = new Request();
        $this->initSharedData();
    }

    private function initSharedData(): void
    {
        $this->data['base_url']     = defined('BASE_URL') ? BASE_URL : '/dashboard-se2026/';
        $this->data['app_name']     = defined('APP_NAME') ? APP_NAME : 'Dashboard SE2026';
        $this->data['current_user'] = Session::get('user');
        $this->data['csrf_token']   = Security::csrfToken();
        $this->data['csrf_field']   = Security::csrfField();
        $this->data['flash']        = Session::flashAll();
        $this->data['page_title']   = 'Dashboard SE2026';
        $this->data['page']         = $this->request->page();
        $this->data['sub']          = $this->request->sub();
    }

    protected function render(string $view, array $data = []): void
    {
        $this->data = array_merge($this->data, $data);

        if ($this->request->isAjax()) {
            $this->renderPartial($view);
            return;
        }

        $contentPath = $this->resolveViewPath($view);
        $contentHtml = $this->captureView($contentPath, $this->data);

        $layoutPath = VIEW_PATH . '/layouts/' . $this->layout . '.php';
        if (!is_file($layoutPath)) {
            throw new \RuntimeException("Layout not found: {$layoutPath}");
        }

        $this->data['content'] = $contentHtml;
        extract($this->data);
        require $layoutPath;
    }

    protected function renderPartial(string $view, array $data = []): void
    {
        $path = $this->resolveViewPath($view);
        extract(array_merge($this->data, $data));
        require $path;
    }

    protected function json(mixed $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function success(mixed $data = null, string $message = 'OK'): never
    {
        Response::success($data, $message);
    }

    protected function error(string $message = 'Terjadi kesalahan', int $status = 400, mixed $errors = null): never
    {
        Response::error($message, $status, $errors);
    }

    protected function redirect(string $url): never
    {
        Response::redirect($url);
    }

    protected function back(): never
    {
        Response::back();
    }

    protected function redirectWithFlash(string $url, string $type, string $message): never
    {
        Session::flash($type, $message);
        Response::redirect($url);
    }

    protected function redirectWith(string $url, string $key, mixed $value): never
    {
        Session::set($key, $value);
        Response::redirect($url);
    }

    protected function validate(array $rules): array
    {
        $errors = $this->request->validate($rules);
        if (!empty($errors)) {
            $this->error('Validasi gagal', 422, $errors);
        }
        return $this->request->only(array_keys($rules));
    }

    protected function requireAuth(): void
    {
        if (!Session::has('user')) {
            Session::flash('error', 'Silakan login terlebih dahulu');
            Response::redirect(BASE_URL . '?page=login');
        }
    }

    protected function requireRole(string|array $roles): void
    {
        $this->requireAuth();
        $user = Session::get('user');
        $roles = (array) $roles;

        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            $viewPath = VIEW_PATH . '/errors/403.php';
            if (is_file($viewPath)) {
                require $viewPath;
            } else {
                echo '<h1>403 - Forbidden</h1>';
            }
            exit;
        }
    }

    protected function isLoggedIn(): bool
    {
        return Session::has('user');
    }

    protected function currentUser(): ?array
    {
        return Session::get('user');
    }

    protected function userId(): ?int
    {
        $user = Session::get('user');
        return $user['id'] ?? null;
    }

    protected function currentRole(): ?string
    {
        $user = Session::get('user');
        return $user['role'] ?? null;
    }

    protected function roleHome(): string
    {
        $role = $this->currentRole();
        return ROLE_HOME[$role] ?? '?page=dashboard';
    }

    private function resolveViewPath(string $view): string
    {
        $path = VIEW_PATH . '/' . $view . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException("View not found: {$path}");
        }
        return $path;
    }

    private function captureView(string $path, array $data): string
    {
        ob_start();
        extract($data);
        require $path;
        return ob_get_clean();
    }
}
