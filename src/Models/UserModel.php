<?php

namespace App\Models;

use App\Core\Database;
use App\Helpers\Security;

/**
 * UserModel — Shared user CRUD for petugas/pcl-pml-tf controllers
 */
class UserModel
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::instance()->pdo();
    }

    // ─── READ ───────────────────────────────────────────────────────

    public function getUsers(array $roles, ?string $roleFilter = null): array
    {
        if ($roleFilter && in_array($roleFilter, $roles, true)) {
            $sql = "SELECT id, email, username, nama_lengkap, role, status_akun,
                           id_sobat, nik, kecamatan_tugas, last_login_at, created_at
                    FROM users WHERE role = ?";
            $params = [$roleFilter];
        } else {
            $placeholders = implode(',', array_fill(0, count($roles), '?'));
            $sql = "SELECT id, email, username, nama_lengkap, role, status_akun,
                           id_sobat, nik, kecamatan_tugas, last_login_at, created_at
                    FROM users WHERE role IN ({$placeholders})";
            $params = $roles;
        }

        $sql .= ' ORDER BY role, id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getRoleCounts(array $roles): array
    {
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $stmt = $this->pdo->prepare("
            SELECT role, COUNT(*) AS total,
                   SUM(CASE WHEN status_akun = 'active' THEN 1 ELSE 0 END) AS aktif
            FROM users WHERE role IN ({$placeholders})
            GROUP BY role ORDER BY role
        ");
        $stmt->execute($roles);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public function existsByUsername(string $username): bool
    {
        return $this->findByUsername($username) !== null;
    }

    // ─── CREATE ─────────────────────────────────────────────────────

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, password, role, status_akun, nama_lengkap, kecamatan_tugas)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['username'],
            $data['email'] ?? '',
            $data['password'] ? Security::hashPassword($data['password']) : '',
            $data['role'],
            $data['status_akun'] ?? 'active',
            $data['nama_lengkap'] ?? '',
            $data['kecamatan_tugas'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    // ─── UPDATE ─────────────────────────────────────────────────────

    public function update(int $id, array $data): void
    {
        $fields = [];
        $params = [];
        foreach (['nama_lengkap', 'email', 'role', 'status_akun', 'kecamatan_tugas'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "{$col} = ?";
                $params[] = $data[$col];
            }
        }
        if (empty($fields)) return;
        $params[] = $id;
        $stmt = $this->pdo->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
    }

    public function updatePassword(int $id, string $password): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([Security::hashPassword($password), $id]);
    }

    // ─── KECAMATAN SCOPE HELPERS ────────────────────────────────────

    /**
     * Daftar kecamatan (untuk dropdown pilih scope 1:1 di form CRUD user).
     * Filter kd_kab='3509' (Jember) — bisa di-extend untuk multi-kab.
     */
    public function getKecamatanList(string $kdKab = '3509'): array
    {
        $stmt = $this->pdo->prepare("
            SELECT kd_kec, nm_kec
            FROM prelist_kecamatan
            WHERE kd_kab = ?
            ORDER BY nm_kec
        ");
        $stmt->execute([$kdKab]);
        return $stmt->fetchAll();
    }

    /**
     * Resolve kd_kec (7-digit atau 3-digit) → nama kecamatan.
     * Return null jika tidak ditemukan.
     */
    public function getKecamatanName(string $kdKec): ?string
    {
        $stmt = $this->pdo->prepare("
            SELECT nm_kec FROM prelist_kecamatan
            WHERE kd_kec = ? OR kd_kec = ?
            LIMIT 1
        ");
        // Coba match 7-digit langsung, atau 3-digit suffix
        $kd3 = strlen($kdKec) === 7 ? substr($kdKec, -3) : $kdKec;
        $kd7 = strlen($kdKec) === 3 ? '3509' . $kdKec : $kdKec;
        $stmt->execute([$kd7, $kd3]);
        $r = $stmt->fetch();
        return $r ? (string) $r['nm_kec'] : null;
    }
}
