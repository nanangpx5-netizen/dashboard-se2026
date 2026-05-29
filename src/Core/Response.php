<?php

namespace App\Core;

final class Response
{
    private static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): never
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function error(string $message = 'Terjadi kesalahan', int $status = 400, mixed $errors = null): never
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        self::json($payload, $status);
    }

    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public static function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $safeUrl = (str_starts_with($referer, '?') || str_starts_with($referer, '/')) ? $referer : '?page=dashboard';
        self::redirect($safeUrl);
    }

    public static function withFlash(string $url, string $type, string $message): never
    {
        \App\Helpers\Session::flash($type, $message);
        self::redirect($url);
    }

    public static function html(string $content, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
        exit;
    }

    public static function download(string $filePath, ?string $filename = null): never
    {
        if (!is_file($filePath)) {
            self::error('File tidak ditemukan', 404);
        }

        $filename = $filename ?: basename($filePath);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($filePath);
        exit;
    }

    public static function statusText(int $code): string
    {
        return self::$statusTexts[$code] ?? 'Unknown Status';
    }

    public static function setCache(int $seconds = 3600): void
    {
        header("Cache-Control: public, max-age={$seconds}");
        header("Expires: " . gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
    }

    public static function noCache(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        header('Pragma: no-cache');
    }

    public static function setContentType(string $mime): void
    {
        header("Content-Type: {$mime}; charset=utf-8");
    }
}
