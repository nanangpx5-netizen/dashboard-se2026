<?php

declare(strict_types=1);

namespace App\Helpers;

final class Session
{
    private const MAX_LIFETIME = 28800; // 8 hours absolute
    private const IDLE_TIMEOUT = 7200;  // 2 hours idle

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = (int) Env::get('SESSION_LIFETIME', 7200);
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Strict',
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            ]);
            session_start();

            if (self::has('_fingerprint') && !self::verifyFingerprint()) {
                self::destroy();
                self::start();
                return;
            }

            if (self::isExpired()) {
                self::destroy();
                self::start();
                return;
            }

            self::updateActivity();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }

    public static function flashAll(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public static function flashNow(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public static function setFingerprint(): void
    {
        $fingerprint = self::generateFingerprint();
        self::set('_fingerprint', $fingerprint);
    }

    public static function verifyFingerprint(): bool
    {
        $stored = self::get('_fingerprint');
        if (!$stored) {
            return false;
        }
        return hash_equals($stored, self::generateFingerprint());
    }

    public static function isExpired(): bool
    {
        $started = self::get('_started');
        if ($started && (time() - $started) > self::MAX_LIFETIME) {
            return true;
        }

        $activity = self::get('_activity');
        if ($activity && (time() - $activity) > self::IDLE_TIMEOUT) {
            return true;
        }

        return false;
    }

    public static function updateActivity(): void
    {
        if (!self::has('_started')) {
            self::set('_started', time());
        }
        self::set('_activity', time());
    }

    public static function enforceRole(array $allowedRoles): void
    {
        $user = self::get('user');
        $role = $user['role'] ?? '';

        if (!in_array($role, $allowedRoles, true)) {
            self::destroy();
            self::start();
        }
    }

    private static function generateFingerprint(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $secret = Env::get('APP_KEY', '') ?: 'se2026-default-key';
        return hash_hmac('sha256', $ua . '|' . ip2long($ip) . '|' . $acceptLang, $secret);
    }
}
