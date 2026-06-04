<?php

namespace App\Core;

use PDO;
use PDOException;
use App\Helpers\Env;
use App\Config\DatabaseConfig;

final class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private string $charset = 'utf8mb4';

    private int $queryCount = 0;
    private array $queryLog = [];

    private function __construct()
    {
        $config = DatabaseConfig::all();

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $this->charset
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::ATTR_CASE               => PDO::CASE_NATURAL,
            PDO::MYSQL_ATTR_INIT_COMMAND => sprintf(
                'SET NAMES %s COLLATE %s',
                $this->charset,
                $config['collation'] ?? 'utf8mb4_unicode_ci'
            ),
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_TIMEOUT            => 5,
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            self::logError('DATABASE_CONNECTION_FAILED', $e->getMessage(), $e->getFile(), $e->getLine());
            throw new \RuntimeException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    public static function connect(): self
    {
        return self::getInstance();
    }

    public static function instance(): self
    {
        return self::getInstance();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
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

    // ────────────────────────────────────────────────────────────
    //  REAL-TIME SHARED DATABASE PROOF METHODS
    // ────────────────────────────────────────────────────────────

    public function getCurrentDatabase(): string
    {
        return (string) $this->fetchColumn('SELECT DATABASE()');
    }

    public function getConnectionId(): int
    {
        return (int) $this->fetchColumn('SELECT CONNECTION_ID()');
    }

    public function serverInfo(): string
    {
        return (string) $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function serverTime(): string
    {
        return (string) $this->fetchColumn('SELECT NOW()');
    }

    public function sessionTimezone(): string
    {
        return (string) $this->fetchColumn('SELECT @@session.time_zone');
    }

    public function activeConnectionCount(): int
    {
        return (int) $this->fetchColumn(
            'SELECT COUNT(*) FROM information_schema.PROCESSLIST WHERE DB = ?',
            [$this->getCurrentDatabase()]
        );
    }

    // ────────────────────────────────────────────────────────────
    //  QUERY METHODS
    // ────────────────────────────────────────────────────────────

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $this->queryCount++;
        $this->queryLog[] = [
            'sql'    => $sql,
            'params' => $params,
            'time'   => microtime(true),
        ];

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    public function count(string $table, string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$table}";
        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }
        return (int) $this->fetchColumn($sql, $params);
    }

    public function insert(string $table, array $data): string
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Cannot insert empty data');
        }
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function tableExists(string $table): bool
    {
        $count = (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
            [$this->getCurrentDatabase(), $table]
        );
        return $count > 0;
    }

    public function getTableColumns(string $table): array
    {
        return $this->fetchAll(
            "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY,
                    EXTRA, COLUMN_DEFAULT
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION",
            [$this->getCurrentDatabase(), $table]
        );
    }

    public function isConnected(): bool
    {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    // ────────────────────────────────────────────────────────────
    //  ERROR LOGGING
    // ────────────────────────────────────────────────────────────

    public static function logError(string $type, string $message, string $file = '', int $line = 0): void
    {
        $logDir = defined('LOG_PATH') ? LOG_PATH : dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/database.log';
        $timestamp = date('Y-m-d H:i:s');
        $entry = sprintf(
            "[%s]\nERROR_TYPE: %s\nMESSAGE: %s\nFILE: %s\nLINE: %d\n%s\n",
            $timestamp,
            $type,
            $message,
            $file,
            $line,
            str_repeat('-', 60)
        );

        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    private function __clone() {}
    public function __wakeup(): void
    {
        throw new \RuntimeException('Database singleton cannot be unserialized');
    }
}
