<?php

namespace App\Core;

use PDO;
use PDOStatement;
use RuntimeException;

abstract class Model
{
    protected PDO $pdo;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->pdo = Database::instance()->pdo();
    }

    protected function execute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function findAll(?string $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $sql = "SELECT * FROM {$this->table}";

        if ($orderBy) {
            $sql .= " ORDER BY " . $this->safeIdentifier($orderBy);
        }
        if ($limit) {
            $sql .= " LIMIT " . (int) $limit;
        }
        if ($offset) {
            $sql .= " OFFSET " . (int) $offset;
        }

        return $this->execute($sql)->fetchAll();
    }

    public function findById(int|string $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE " . $this->safeIdentifier($this->primaryKey) . " = ? LIMIT 1";
        $result = $this->execute($sql, [$id])->fetch();
        return $result ?: null;
    }

    public function findBy(string $column, mixed $value, string $operator = '='): array
    {
        $safeCol = $this->safeIdentifier($column);
        $safeOp  = in_array($operator, ['=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'], true) ? $operator : '=';
        $sql = "SELECT * FROM {$this->table} WHERE {$safeCol} {$safeOp} ?";
        return $this->execute($sql, [$value])->fetchAll();
    }

    public function findOneBy(string $column, mixed $value, string $operator = '='): ?array
    {
        $safeCol = $this->safeIdentifier($column);
        $safeOp  = in_array($operator, ['=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE'], true) ? $operator : '=';
        $sql = "SELECT * FROM {$this->table} WHERE {$safeCol} {$safeOp} ? LIMIT 1";
        $result = $this->execute($sql, [$value])->fetch();
        return $result ?: null;
    }

    public function insert(array $data): string
    {
        if (empty($data)) {
            throw new RuntimeException('Cannot insert empty data');
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, array_values($data));

        return $this->pdo->lastInsertId();
    }

    public function insertBatch(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $columns = implode(', ', array_keys($rows[0]));
        $placeholders = [];
        $values = [];

        foreach ($rows as $row) {
            $placeholders[] = '(' . implode(', ', array_fill(0, count($row), '?')) . ')';
            $values = array_merge($values, array_values($row));
        }

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES " . implode(', ', $placeholders);
        $this->execute($sql, $values);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int|string $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $sets = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;

        $sql = "UPDATE {$this->table} SET {$sets} WHERE {$this->primaryKey} = ?";
        $stmt = $this->execute($sql, $values);

        return $stmt->rowCount() > 0;
    }

    public function updateWhere(array $data, string $where, array $whereParams = []): int
    {
        if (empty($data)) {
            return 0;
        }

        $sets = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql = "UPDATE {$this->table} SET {$sets} WHERE {$where}";

        $stmt = $this->execute($sql, array_merge(array_values($data), $whereParams));
        return $stmt->rowCount();
    }

    public function delete(int|string $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        $stmt = $this->execute($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteWhere(string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$this->table} WHERE {$where}";
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }

    public function count(?string $where = null, array $params = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        return (int) $this->execute($sql, $params)->fetchColumn();
    }

    public function paginate(int $page = 1, int $perPage = 20, ?string $where = null, array $params = [], ?string $orderBy = null): array
    {
        $total = $this->count($where, $params);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM {$this->table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        $sql .= " LIMIT " . (int) $perPage . " OFFSET " . (int) $offset;

        $data = $this->execute($sql, $params)->fetchAll();

        return [
            'data'        => $data,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => $totalPages,
        ];
    }

    public function raw(string $sql, array $params = []): PDOStatement
    {
        return $this->execute($sql, $params);
    }

    public function table(): string
    {
        return $this->table;
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Sanitize identifier (column/table name) for safe SQL interpolation.
     * Only allow alphanumeric, underscore, and dot (for table.column).
     */
    private function safeIdentifier(string $name): string
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $name)) {
            return $name;
        }
        throw new \InvalidArgumentException("Potentially unsafe identifier: {$name}");
    }
}
