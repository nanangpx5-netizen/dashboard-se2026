<?php

namespace App\Helpers;

/**
 * Cache — simple file-based cache with TTL
 *
 * Gunakan untuk data agregat yang tidak berubah setiap detik
 * (dashboard stats, kecamatan list, dll).
 *
 * Struktur file: {CACHE_PATH}/{key}.cache — berisi serialized data + expiry.
 */
final class Cache
{
    private static string $dir;

    private static function init(): void
    {
        self::$dir = defined('CACHE_PATH') ? CACHE_PATH : dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir(self::$dir)) {
            mkdir(self::$dir, 0755, true);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::init();
        $path = self::$dir . '/' . $key . '.cache';
        if (!is_file($path)) return $default;

        $data = @file_get_contents($path);
        if ($data === false) return $default;

        $payload = @unserialize($data);
        if ($payload === false || !isset($payload['expires'], $payload['value'])) {
            @unlink($path);
            return $default;
        }

        if (time() > $payload['expires']) {
            @unlink($path);
            return $default;
        }

        return $payload['value'];
    }

    public static function set(string $key, mixed $value, int $ttlSeconds = 60): void
    {
        self::init();
        $path = self::$dir . '/' . $key . '.cache';
        $payload = serialize([
            'expires' => time() + $ttlSeconds,
            'value'   => $value,
            'created' => time(),
        ]);
        @file_put_contents($path, $payload, LOCK_EX);
    }

    public static function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        $cached = self::get($key);
        if ($cached !== null) return $cached;

        $value = $callback();
        self::set($key, $value, $ttlSeconds);
        return $value;
    }

    public static function forget(string $key): void
    {
        self::init();
        $path = self::$dir . '/' . $key . '.cache';
        if (is_file($path)) @unlink($path);
    }

    public static function flush(): void
    {
        self::init();
        $files = glob(self::$dir . '/*.cache');
        foreach ($files as $f) {
            @unlink($f);
        }
    }
}
