<?php

namespace App\Middleware;

use App\Helpers\Session;

class AuthMiddleware extends BaseMiddleware
{
    public function handle(mixed $param = null): void
    {
        if (!Session::has('user')) {
            Session::set('_redirect', $_SERVER['REQUEST_URI'] ?? '?page=dashboard');
            $this->redirectToLogin();
        }

        if (!Session::verifyFingerprint()) {
            Session::destroy();
            Session::flash('error', 'Sesi tidak valid. Silakan login ulang.');
            $this->redirectToLogin();
        }

        $user = Session::get('user');
        if (!isset($user['id'], $user['username'], $user['role'])) {
            Session::destroy();
            $this->redirectToLogin();
        }
    }
}
