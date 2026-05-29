<?php

namespace App\Middleware;

use App\Helpers\Security;
use App\Helpers\Session;

class CsrfMiddleware extends BaseMiddleware
{
    public function handle(mixed $param = null): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            return;
        }

        $token = $_POST['csrf_token'] ?? '';
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_XSRF_TOKEN'] ?? '';

        $validToken = $token ?: $headerToken;

        if (!Security::validateCsrf($validToken)) {
            Session::flash('error', 'Token CSRF tidak valid. Silakan refresh halaman.');
            http_response_code(403);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) === 'XMLHTTPREQUEST') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid']);
                exit;
            }
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '?page=login'));
            exit;
        }
    }
}
