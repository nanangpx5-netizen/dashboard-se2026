<?php

namespace App\Helpers;

use App\Config\Database as DbConfig;
use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private int $queryCount = 0;
    private array $queryLog = [];

    private function __construct()
    {
        $config = DbConfig::all();
        $dsn = DbConfig::dsn();

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::ATTR_CASE               => PDO::CASE_NATURAL,
            PDO::MYSQL_ATTR_INIT_COMMAND => sprintf(
                "SET NAMES %s COLLATE %s",
                $config['charset'],
                $config['collation']
            ),
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_PERSISTENT => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            self::logError('DATABASE_CONNECTION_FAILED', $e->getMessage(), $e->getFile(), $e->getLine());
            throw new \RuntimeException(
                'Database connection failed. Cek MySQL dan konfigurasi .env',
                (int) $e->getCode()
            );
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Backward compatibility alias */
    public static function instance(): self
    {
        return self::getInstance();
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    // ────────────────────────────────────────────────────────────
    //  REAL-TIME SHARED DATABASE PROOF METHODS
    // ────────────────────────────────────────────────────────────

    /** Bukti #1: SELECT DATABASE() — must return bps_jember_se2026 */
    public function getCurrentDatabase(): string
    {
        return (string) $this->fetchColumn("SELECT DATABASE()");
    }

    /** Bukti #2: SELECT CONNECTION_ID() — MySQL connection identifier */
    public function getConnectionId(): int
    {
        return (int) $this->fetchColumn("SELECT CONNECTION_ID()");
    }

    /** Bukti #3: MySQL server version */
    public function serverInfo(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /** Bukti #4: Server time from MySQL (real-time clock) */
    public function serverTime(): string
    {
        return (string) $this->fetchColumn("SELECT NOW()");
    }

    /** Bukti #5: Session timezone */
    public function sessionTimezone(): string
    {
        return (string) $this->fetchColumn("SELECT @@session.time_zone");
    }

    /** Bukti #6: Active connection count to this database (processlist) */
    public function activeConnectionCount(): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.PROCESSLIST
             WHERE DB = ?",
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

    // ────────────────────────────────────────────────────────────
    //  TRANSACTION
    // ────────────────────────────────────────────────────────────

    public function beginTransaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void { $this->pdo->commit(); }
    public function rollback(): void { $this->pdo->rollBack(); }
    public function lastInsertId(): string { return $this->pdo->lastInsertId(); }

    // ────────────────────────────────────────────────────────────
    //  ERROR LOGGING
    // ────────────────────────────────────────────────────────────

    public static function logError(string $type, string $message, string $file = '', int $line = 0): void
    {
        $logDir = defined('LOG_PATH') ? LOG_PATH : (dirname(__DIR__, 2) . '/storage/logs');
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

    // ────────────────────────────────────────────────────────────
    //  DEBUG
    // ────────────────────────────────────────────────────────────

    public function getQueryCount(): int { return $this->queryCount; }
    public function getQueryLog(): array { return $this->queryLog; }

    public function isConnected(): bool
    {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException) {
            return false;
        }
    }

    private function __clone() {}
    public function __wakeup(): void
    {
        throw new \RuntimeException('Database singleton cannot be unserialized');
    }
}
