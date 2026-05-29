<?php

namespace App\Models;

use App\Core\Database;
use App\Helpers\Cache;

class WorkloadModel
{
    private \PDO $pdo;

    private array $workloadRoles = ['pcl', 'pml', 'task_force'];

    public function __construct()
    {
        $this->pdo = Database::instance()->pdo();
    }

    public function getRanking(?string $role = null, ?string $kdkec = null): array
    {
        $roleCondition = match ($role) {
            'pcl'        => "AND sa.pencacah_id = u.id",
            'pml'        => "AND sa.pengawas_id = u.id",
            'task_force' => "AND sa.task_force_id = u.id",
            default      => "AND (sa.pencacah_id = u.id OR sa.pengawas_id = u.id OR sa.task_force_id = u.id)",
        };

        $roleFilter = '';
        $params = [];
        if ($role && in_array($role, $this->workloadRoles, true)) {
            $roleFilter = 'AND u.role = ?';
            $params[] = $role;
        }

        $kecJoin = 'JOIN sipw_import si ON si.id = sa.sipw_id';
        $kecWhere = '';
        if ($kdkec) {
            $kecWhere = 'AND si.kdkec = ?';
            $params[] = $kdkec;
        }

        $sql = "
            SELECT
                u.id,
                u.username,
                u.role,
                COUNT(DISTINCT sa.sipw_id) AS jumlah_sls,
                COALESCE(SUM(si.kk), 0)    AS total_kk,
                COALESCE(SUM(si.usaha), 0) AS total_usaha,
                COALESCE(SUM(si.muatan), 0) AS total_muatan
            FROM users u
            LEFT JOIN sipw_assignment sa
                ON ({$this->assignmentOrClause()})
                {$roleCondition}
            {$kecJoin}
            WHERE u.status_akun = 'active'
              AND u.role IN ('pcl', 'pml', 'task_force')
              {$roleFilter}
              {$kecWhere}
            GROUP BY u.id, u.username, u.role
            ORDER BY total_muatan DESC, jumlah_sls DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getDetail(int $userId, string $role, ?string $kdkec = null): array
    {
        $roleCol = match ($role) {
            'pcl'        => 'sa.pencacah_id',
            'pml'        => 'sa.pengawas_id',
            'task_force' => 'sa.task_force_id',
            default      => throw new \InvalidArgumentException("Invalid role: {$role}"),
        };

        $params = [$userId];
        $kecWhere = '';
        if ($kdkec) {
            $kecWhere = 'AND si.kdkec = ?';
            $params[] = $kdkec;
        }

        $sql = "
            SELECT
                si.id,
                si.nmkec,
                si.nmdesa,
                si.nmsls,
                si.nama_ketua,
                COALESCE(si.kk, 0)      AS kk,
                COALESCE(si.usaha, 0)   AS usaha,
                COALESCE(si.muatan, 0)  AS muatan,
                COALESCE(sa.status, 'belum') AS status,
                sa.updated_at AS tgl_status
            FROM sipw_assignment sa
            JOIN sipw_import si ON si.id = sa.sipw_id
            WHERE {$roleCol} = ?
              {$kecWhere}
            ORDER BY si.nmkec, si.nmdesa, si.nmsls
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getKecamatan(): array
    {
        return Cache::remember('kecamatan_list', 300, function (): array {
            return $this->pdo->query("
                SELECT DISTINCT si.kdkec, si.nmkec
                FROM sipw_import si
                ORDER BY si.nmkec
            ")->fetchAll();
        });
    }

    public function getAvailableRoles(): array
    {
        $stmt = $this->pdo->query("
            SELECT DISTINCT u.role
            FROM users u
            WHERE u.status_akun = 'active'
              AND u.role IN ('pcl', 'pml', 'task_force')
            ORDER BY FIELD(u.role, 'pcl', 'pml', 'task_force')
        ");
        return $stmt->fetchAll();
    }

    private function assignmentOrClause(): string
    {
        return 'sa.pencacah_id = u.id OR sa.pengawas_id = u.id OR sa.task_force_id = u.id';
    }
}
