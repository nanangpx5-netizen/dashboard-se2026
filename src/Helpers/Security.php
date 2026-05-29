<?php

namespace App\Helpers;

final class Security
{
    public static function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
        return $token;
    }

    public static function csrfToken(): string
    {
        if (!Session::has('csrf_token')) {
            return self::generateCsrfToken();
        }
        return Session::get('csrf_token');
    }

    public static function validateCsrf(string $token): bool
    {
        $stored = Session::get('csrf_token');
        if ($stored === null) {
            return false;
        }
        return hash_equals($stored, $token);
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">';
    }

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTRICT_XML, 'UTF-8');
    }

    public static function escapeArray(array $data): array
    {
        $escaped = [];
        foreach ($data as $key => $value) {
            $escaped[$key] = is_string($value) ? self::escape($value) : $value;
        }
        return $escaped;
    }

    public static function sanitizeFilename(string $filename): string
    {
        $filename = mb_ereg_replace("([^\w\s\-_~,;\[\]\(\).])", '', $filename);
        $filename = mb_ereg_replace("([\.]{2,})", '.', $filename);
        return trim($filename);
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function generateRememberToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function sanitizeInput(string $input): string
    {
        return strip_tags(trim($input));
    }
}
