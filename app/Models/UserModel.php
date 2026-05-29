<?php

namespace App\Models;

class UserModel extends BaseModel
{
    protected string $table = 'users';

    public function getTableName(): string
    {
        return $this->table;
    }

    public function totalUsers(): int
    {
        return $this->count($this->table);
    }

    public function getRecent(int $limit = 10): array
    {
        return $this->fetchAll(
            "SELECT id, username, email, role, status_akun, last_login_at, created_at
             FROM {$this->table}
             ORDER BY id ASC
             LIMIT ?",
            [$limit]
        );
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1",
            [$id]
        );
    }

    public function getByUsername(string $username): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE username = ? LIMIT 1",
            [$username]
        );
    }

    public function countByRole(string $role): int
    {
        return $this->count($this->table, 'role = ?', [$role]);
    }

    public function countActive(): int
    {
        return $this->count($this->table, "status_akun = 'active'");
    }

    public function countByRoleAndStatus(string $role, string $status): int
    {
        return $this->count(
            $this->table,
            'role = ? AND status_akun = ?',
            [$role, $status]
        );
    }

    public function getRoleDistribution(): array
    {
        return $this->fetchAll(
            "SELECT role, COUNT(*) as total
             FROM {$this->table}
             GROUP BY role
             ORDER BY total DESC"
        );
    }

    public function getStatusDistribution(): array
    {
        return $this->fetchAll(
            "SELECT status_akun, COUNT(*) as total
             FROM {$this->table}
             GROUP BY status_akun"
        );
    }

    public function getColumns(): array
    {
        return $this->getTableColumns($this->table);
    }
}
