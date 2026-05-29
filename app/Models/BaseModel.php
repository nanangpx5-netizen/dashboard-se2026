<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;

abstract class BaseModel
{
    protected Database $db;
    protected PDO $pdo;

    public function __construct()
    {
        $this->db = Database::instance();
        $this->pdo = $this->db->pdo();
    }

    protected function query(string $sql, array $params = []): \PDOStatement
    {
        return $this->db->query($sql, $params);
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->db->fetchAll($sql, $params);
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        return $this->db->fetchOne($sql, $params);
    }

    protected function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->db->fetchColumn($sql, $params);
    }

    protected function fetchPairs(string $sql, array $params = []): array
    {
        return $this->db->fetchPairs($sql, $params);
    }

    protected function insert(string $table, array $data): string
    {
        return $this->db->insert($table, $data);
    }

    protected function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        return $this->db->update($table, $data, $where, $whereParams);
    }

    protected function delete(string $table, string $where, array $params = []): int
    {
        return $this->db->delete($table, $where, $params);
    }

    protected function count(string $table, string $where = '', array $params = []): int
    {
        return $this->db->count($table, $where, $params);
    }

    protected function tableExists(string $table): bool
    {
        return $this->db->tableExists($table);
    }

    protected function getTableColumns(string $table): array
    {
        return $this->db->getTableColumns($table);
    }

    protected function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    protected function commit(): void
    {
        $this->db->commit();
    }

    protected function rollback(): void
    {
        $this->db->rollback();
    }

    public function getDb(): Database
    {
        return $this->db;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function databaseName(): string
    {
        return $this->db->databaseName();
    }

    protected function tableName(string $table): string
    {
        return $table;
    }
}
