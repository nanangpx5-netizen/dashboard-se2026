<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Helpers\AuditLog;
use App\Helpers\Session;
use App\Helpers\Security;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public function loginForm(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect($this->roleHome());
        }

        $this->layout = 'auth';
        $this->render('auth/login');
    }

    public function login(): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect($this->roleHome());
        }

        if ($this->request->isAjax() || $this->request->isJson()) {
            $this->loginAjax();
            return;
        }

        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';

        if (!Security::validateCsrf($csrfToken)) {
            Session::flash('error', 'Token CSRF tidak valid. Refresh halaman dan coba lagi.');
            $this->redirect('?page=login');
        }

        if (empty($username) || empty($password)) {
            Session::flash('error', 'Username dan password wajib diisi.');
            $this->redirect('?page=login');
        }

        try {
            $pdo = Database::instance()->pdo();

            if ($this->isLockedOut($pdo, $username)) {
                Session::flash('error', 'Akun diblokir sementara karena terlalu banyak percobaan login. Coba lagi dalam ' . self::LOCKOUT_MINUTES . ' menit.');
                $this->redirect('?page=login');
            }

            $stmt = $pdo->prepare("
                SELECT id, username, password, role, status_akun
                FROM users
                WHERE username = ?
                LIMIT 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->recordFailedAttempt($pdo, $username);
                Session::flash('error', 'Username atau password salah.');
                $this->redirect('?page=login');
            }

            if (!Security::verifyPassword($password, $user['password'])) {
                $this->recordFailedAttempt($pdo, $username);
                Session::flash('error', 'Username atau password salah.');
                $this->redirect('?page=login');
            }

            if ($user['status_akun'] !== 'active') {
                Session::flash('error', 'Akun Anda tidak aktif. Hubungi administrator.');
                $this->redirect('?page=login');
            }

            $dashboardRoles = DASHBOARD_ROLES;
            if (!in_array($user['role'], $dashboardRoles, true)) {
                Session::flash('error', 'Akun Anda tidak memiliki akses ke dashboard.');
                $this->redirect('?page=login');
            }

            $this->doLogin($pdo, $user);

        } catch (\Throwable $e) {
            Session::flash('error', 'Terjadi kesalahan sistem. Silakan coba lagi.');
            $this->redirect('?page=login');
        }
    }

    private function loginAjax(): void
    {
        $input = $this->request->jsonBody() ?? $_POST;
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $token = $input['csrf_token'] ?? '';

        if (!Security::validateCsrf($token)) {
            $this->error('Token CSRF tidak valid', 403);
        }

        if (empty($username) || empty($password)) {
            $this->error('Username dan password wajib diisi', 422);
        }

        try {
            $pdo = Database::instance()->pdo();

            if ($this->isLockedOut($pdo, $username)) {
                $this->error('Akun diblokir sementara. Coba lagi dalam ' . self::LOCKOUT_MINUTES . ' menit.', 429);
            }

            $stmt = $pdo->prepare("
                SELECT id, username, password, role, status_akun
                FROM users WHERE username = ? LIMIT 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !Security::verifyPassword($password, $user['password'])) {
                $this->recordFailedAttempt($pdo, $username);
                $this->error('Username atau password salah', 401);
            }

            if ($user['status_akun'] !== 'active') {
                $this->error('Akun tidak aktif. Hubungi administrator.', 403);
            }

            if (!in_array($user['role'], DASHBOARD_ROLES, true)) {
                $this->error('Akun tidak memiliki akses dashboard.', 403);
            }

            $this->doLogin($pdo, $user, true);

        } catch (\Throwable $e) {
            $this->error('Terjadi kesalahan server', 500);
        }
    }

    private function doLogin(\PDO $pdo, array $user, bool $isAjax = false): never
    {
        Session::set('user', [
            'id'        => (int) $user['id'],
            'username'  => $user['username'],
            'role'      => $user['role'],
            'role_label'=> ROLE_LABELS[$user['role']] ?? $user['role'],
            'login_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::setFingerprint();
        Session::regenerate();
        Security::generateCsrfToken();
        $this->clearFailedAttempts($pdo, $user['username']);

        $stmt = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        AuditLog::log('login', 'auth', $user['username'], (int) $user['id']);

        if ($isAjax) {
            $home = ROLE_HOME[$user['role']] ?? '?page=dashboard';
            $this->success([
                'user'     => $user['username'],
                'role'     => $user['role'],
                'redirect' => $home,
            ], 'Login berhasil');
        }

        $redirect = Session::get('_redirect') ?: $this->roleHome();
        Session::remove('_redirect');
        $this->redirect($redirect);
    }

    public function logout(): never
    {
        $userId = $this->userId();
        $username = $this->currentUser()['username'] ?? '';
        if ($userId) {
            AuditLog::log('logout', 'auth', $username, $userId);
            try {
                $pdo = Database::instance()->pdo();
                $stmt = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
                $stmt->execute([$userId]);
            } catch (\Throwable $e) {
            }
        }
        Session::destroy();
        $this->redirect('?page=login');
    }

    public function me(): void
    {
        $this->requireAuth();
        $this->success($this->currentUser());
    }

    private function isLockedOut(\PDO $pdo, string $username): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as cnt
            FROM activity_logs
            WHERE action = 'login_failed'
              AND detail = ?
              AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");
        $stmt->execute([$username, self::LOCKOUT_MINUTES]);
        return (int) $stmt->fetchColumn() >= self::MAX_LOGIN_ATTEMPTS;
    }

    private function recordFailedAttempt(\PDO $pdo, string $username): void
    {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, module, detail, ip_address, created_at)
                VALUES (NULL, 'login_failed', 'auth', ?, ?, NOW())
            ");
            $stmt->execute([$username, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);
        } catch (\Throwable $e) {
            // Non-critical — login tetap berjalan
        }
    }

    private function clearFailedAttempts(\PDO $pdo, string $username): void
    {
        try {
            $stmt = $pdo->prepare("
                DELETE FROM activity_logs
                WHERE action = 'login_failed' AND detail = ?
            ");
            $stmt->execute([$username]);
        } catch (\Throwable $e) {
            // Non-critical
        }
    }
}
