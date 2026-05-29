<?php

namespace App\Core;

use PDO;
use PDOException;
use App\Helpers\Env;

final class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private string $charset = 'utf8mb4';

    private function __construct()
    {
        $host = Env::get('DB_HOST', 'localhost');
        $port = Env::get('DB_PORT', '3306');
        $name = Env::get('DB_NAME', 'bps_jember_se2026');
        $user = Env::get('DB_USER', 'root');
        $pass = (string) Env::get('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE {$this->charset}_unicode_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    public static function connect(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function instance(): self
    {
        return self::connect();
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function quote(string $value): string
    {
        return $this->pdo->quote($value);
    }

    private function __clone() {}
    public function __wakeup(): void
    {
        throw new \RuntimeException('Database singleton cannot be unserialized');
    }
}
