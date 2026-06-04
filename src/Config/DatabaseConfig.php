<?php

namespace App\Config;

use App\Helpers\Env;

final class DatabaseConfig
{
    private static ?array $config = null;

    public static function load(): void
    {
        if (self::$config !== null) {
            return;
        }

        self::$config = [
            'driver'   => 'mysql',
            'host'     => Env::get('DB_HOST', 'localhost'),
            'port'     => Env::get('DB_PORT', '3306'),
            'database' => Env::get('DB_DATABASE', Env::get('DB_NAME', 'bps_jember_se2026')),
            'username' => Env::get('DB_USERNAME', Env::get('DB_USER', 'root')),
            'password' => Env::get('DB_PASSWORD', Env::get('DB_PASS', '')),
            'charset'  => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        return self::$config[$key] ?? $default;
    }

    public static function all(): array
    {
        self::load();
        return self::$config;
    }

    public static function dsn(): string
    {
        self::load();

        return sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            self::$config['host'],
            self::$config['port'],
            self::$config['database'],
            self::$config['charset']
        );
    }
}
