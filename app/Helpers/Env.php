<?php

namespace App\Helpers;

class Env
{
    private static array $loaded = [];

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            throw new \RuntimeException('.env file not found: ' . $path);
        }

        if (isset(self::$loaded[$path])) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            $value = match (true) {
                $value === 'true'  => true,
                $value === 'false' => false,
                $value === 'null'  => null,
                is_numeric($value) && str_contains($value, '.') => (float) $value,
                is_numeric($value) => (int) $value,
                default            => trim($value, '"\' '),
            };

            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
            }

            putenv("$key=$value");
        }

        self::$loaded[$path] = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }

    public static function required(string $key): string
    {
        $value = self::get($key);

        if ($value === null || $value === '') {
            throw new \RuntimeException(
                "Missing required environment variable: {$key}"
            );
        }

        return (string) $value;
    }
}
