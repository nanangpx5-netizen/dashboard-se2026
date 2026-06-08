<?php

declare(strict_types=1);

namespace App\Helpers;

final class Cache
{
    private static string $dir;
    private static int $gcProbability = 100;

    private const GC_MAX_LIFETIME = 86400;

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

        $fh = @fopen($path, 'rb');
        if (!$fh) return $default;

        flock($fh, LOCK_SH);
        $data = stream_get_contents($fh);
        flock($fh, LOCK_UN);
        fclose($fh);

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

        $fh = @fopen($path, 'wb');
        if (!$fh) return;

        flock($fh, LOCK_EX);
        fwrite($fh, $payload);
        fflush($fh);
        flock($fh, LOCK_UN);
        fclose($fh);
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

    public static function gc(): array
    {
        self::init();
        $stats = ['deleted' => 0, 'freed_bytes' => 0, 'remaining' => 0];

        $files = glob(self::$dir . '/*.cache');
        $now = time();

        foreach ($files as $path) {
            $mtime = @filemtime($path);
            if ($mtime === false) continue;

            if ($mtime < $now - self::GC_MAX_LIFETIME) {
                $size = @filesize($path) ?: 0;
                if (@unlink($path)) {
                    $stats['deleted']++;
                    $stats['freed_bytes'] += $size;
                }
                continue;
            }

            $fh = @fopen($path, 'rb');
            if (!$fh) continue;

            flock($fh, LOCK_SH);
            $data = stream_get_contents($fh);
            flock($fh, LOCK_UN);
            fclose($fh);

            if ($data === false) continue;

            $payload = @unserialize($data);
            if ($payload === false || !isset($payload['expires'])) {
                $size = @filesize($path) ?: 0;
                if (@unlink($path)) {
                    $stats['deleted']++;
                    $stats['freed_bytes'] += $size;
                }
            } elseif ($now > $payload['expires']) {
                $size = @filesize($path) ?: 0;
                if (@unlink($path)) {
                    $stats['deleted']++;
                    $stats['freed_bytes'] += $size;
                }
            } else {
                $stats['remaining']++;
            }
        }

        return $stats;
    }

    public static function setGcProbability(int $probability): void
    {
        self::$gcProbability = max(1, min(1000, $probability));
    }

    public static function maybeGc(): ?array
    {
        if (mt_rand(1, self::$gcProbability) === 1) {
            return self::gc();
        }
        return null;
    }

    public static function dir(): string
    {
        self::init();
        return self::$dir;
    }
}
