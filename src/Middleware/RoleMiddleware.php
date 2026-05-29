<?php

namespace App\Middleware;

use App\Helpers\Session;

class RoleMiddleware extends BaseMiddleware
{
    public function handle(mixed $param = null): void
    {
        if ($param === null) {
            return;
        }

        $user = Session::get('user');
        $allowedRoles = explode(',', (string) $param);

        if (!$user || !in_array($user['role'], $allowedRoles, true)) {
            $this->forbidden();
        }
    }
}
