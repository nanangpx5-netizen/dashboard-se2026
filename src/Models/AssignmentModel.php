<?php

namespace App\Models;

use App\Core\Database;
use App\Helpers\Cache;

/**
 * AssignmentModel — encapsulasi semua query terkait assignment petugas ke SLS
 *
 * Mapping:
 *   sipw_import → sipw_assignment → users (pencacah/pengawas/task_force)
 */
class AssignmentModel
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::instance()->pdo();
    }

    /**
     * Semua assignment dengan join users (DataTables-ready)
     */
    public function getAll(array $filters = []): array
    {
        $sql = "
            SELECT
                sa.id,
                sa.sipw_id,
                sa.pencacah_id,
                sa.pengawas_id,
                sa.task_force_id,
                sa.status,
                sa.created_at,
                sa.updated_at,
                si.nmsls,
                si.nmdesa,
                si.nmkec,
                si.kdkec,
                si.kddesa,
                si.nama_ketua,
                si.muatan,
                pc.username  AS pencacah,
                pw.username  AS pengawas,
                tf.username  AS task_force
            FROM sipw_assignment sa
            JOIN sipw_import si ON si.id = sa.sipw_id
            LEFT JOIN users pc ON pc.id = sa.pencacah_id
            LEFT JOIN users pw ON pw.id = sa.pengawas_id
            LEFT JOIN users tf ON tf.id = sa.task_force_id
            LEFT JOIN mfd_kec mfd ON mfd.kode_kecamatan = CONCAT(SUBSTRING(si.kdprov, 1, 2), SUBSTRING(si.kdkab, 1, 2), si.kdkec)
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['kdkec'])) {
            $sql .= " AND si.kdkec = ?";
            $params[] = $filters['kdkec'];
        }
        if (!empty($filters['kddesa'])) {
            $sql .= " AND si.kddesa = ?";
            $params[] = $filters['kddesa'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND sa.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $sql .= " AND (si.nmsls LIKE ? OR si.nmkec LIKE ? OR si.nmdesa LIKE ? OR pc.username LIKE ? OR pw.username LIKE ?)";
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }

        $sql .= " ORDER BY mfd.urutan, si.nmdesa, si.nmsls";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Assigned — paginated
     */
    public function getAllPaginated(array $filters, int $page = 1, int $perPage = 25): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        [$where, $params] = $this->buildAssignedWhere($filters);
        $sql = "
            SELECT
                sa.id, sa.sipw_id, sa.pencacah_id, sa.pengawas_id, sa.task_force_id,
                sa.status, sa.created_at, sa.updated_at,
                si.nmsls, si.nmdesa, si.nmkec, si.kdkec, si.kddesa, si.nama_ketua, si.muatan,
                pc.username AS pencacah,
                pw.username AS pengawas,
                tf.username AS task_force
            FROM sipw_assignment sa
            JOIN sipw_import si ON si.id = sa.sipw_id
            LEFT JOIN users pc ON pc.id = sa.pencacah_id
            LEFT JOIN users pw ON pw.id = sa.pengawas_id
            LEFT JOIN users tf ON tf.id = sa.task_force_id
            LEFT JOIN mfd_kec mfd ON mfd.kode_kecamatan = CONCAT(SUBSTRING(si.kdprov, 1, 2), SUBSTRING(si.kdkab, 1, 2), si.kdkec)
            {$where}
            ORDER BY mfd.urutan, si.nmdesa, si.nmsls
            LIMIT ? OFFSET ?
        ";
        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count all assigned (for pagination)
     */
    public function countAll(array $filters): int
    {
        [$where, $params] = $this->buildAssignedWhere($filters);
        $sql = "
            SELECT COUNT(*)
            FROM sipw_assignment sa
            JOIN sipw_import si ON si.id = sa.sipw_id
            LEFT JOIN users pc ON pc.id = sa.pencacah_id
            LEFT JOIN users pw ON pw.id = sa.pengawas_id
            {$where}
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Build WHERE clause for assigned queries
     */
    private function buildAssignedWhere(array $filters): array
    {
        $sql = "WHERE 1=1";
        $params = [];
        if (!empty($filters['kdkec'])) {
            $sql .= " AND si.kdkec = ?";
            $params[] = $filters['kdkec'];
        }
        if (!empty($filters['kddesa'])) {
            $sql .= " AND si.kddesa = ?";
            $params[] = $filters['kddesa'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND sa.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $sql .= " AND (si.nmsls LIKE ? OR si.nmkec LIKE ? OR si.nmdesa LIKE ? OR pc.username LIKE ? OR pw.username LIKE ?)";
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }
        return [$sql, $params];
    }

    /**
     * Assignment detail by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT sa.*, si.nmsls, si.nmkec, si.nmdesa
            FROM sipw_assignment sa
            JOIN sipw_import si ON si.id = sa.sipw_id
            WHERE sa.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Assignment detail by sipw_id (untuk backup log)
     */
    public function findBySipwId(int $sipwId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT sa.*, si.nmsls, si.nmkec, si.nmdesa
            FROM sipw_assignment sa
            JOIN sipw_import si ON si.id = sa.sipw_id
            WHERE sa.sipw_id = ?
        ");
        $stmt->execute([$sipwId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Assign single SLS
     */
    public function assign(int $sipwId, ?int $pencacahId, ?int $pengawasId, ?int $taskForceId): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO sipw_assignment (sipw_id, pencacah_id, pengawas_id, task_force_id, status)
            VALUES (?, ?, ?, ?, 'belum')
        ");
        return $stmt->execute([$sipwId, $pencacahId, $pengawasId, $taskForceId]);
    }

    /**
     * Cek apakah SLS sudah di-assign
     */
    public function exists(int $sipwId): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM sipw_assignment WHERE sipw_id = ?");
        $stmt->execute([$sipwId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Update assignment (ganti petugas) by sipw_id
     */
    public function update(int $sipwId, ?int $pencacahId, ?int $pengawasId, ?int $taskForceId): bool
    {
        $sql = "UPDATE sipw_assignment SET
                    pencacah_id   = ?,
                    pengawas_id   = ?,
                    task_force_id = ?,
                    updated_at    = NOW()
                WHERE sipw_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$pencacahId, $pengawasId, $taskForceId, $sipwId]);
    }

    /**
     * Update status assignment
     */
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare("UPDATE sipw_assignment SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Hapus assignment
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM sipw_assignment WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Hapus semua assignment untuk SLS tertentu
     */
    public function deleteBySipw(int $sipwId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM sipw_assignment WHERE sipw_id = ?");
        return $stmt->execute([$sipwId]);
    }

    /**
     * Bulk assign — semua SLS yang belum di-assign di kecamatan tertentu
     */
    public function bulkAssign(string $kdkec, ?int $pencacahId, ?int $pengawasId, ?int $taskForceId): int
    {
        $unassigned = $this->getUnassigned($kdkec);
        if (empty($unassigned)) return 0;

        $sql = "INSERT IGNORE INTO sipw_assignment
                (sipw_id, pencacah_id, pengawas_id, task_force_id, status)
                VALUES (?, ?, ?, ?, 'belum')";
        $stmt = $this->pdo->prepare($sql);

        $count = 0;
        $this->pdo->beginTransaction();
        try {
            foreach ($unassigned as $sls) {
                $stmt->execute([$sls['id'], $pencacahId, $pengawasId, $taskForceId]);
                if ($stmt->rowCount() > 0) $count++;
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $count;
    }

    /**
     * SLS yang belum di-assign dengan filter
     */
    public function getUnassigned(?string $kdkec = null, ?string $kddesa = null, string $search = ''): array
    {
        $sql = "
            SELECT si.id, si.kdkec, si.kddesa, si.nmkec, si.nmdesa,
                   si.nmsls, si.nama_ketua, si.muatan, si.kk, si.btt, si.bku, si.usaha
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
            LEFT JOIN mfd_kec mfd ON mfd.kode_kecamatan = CONCAT(SUBSTRING(si.kdprov, 1, 2), SUBSTRING(si.kdkab, 1, 2), si.kdkec)
            WHERE sa.id IS NULL
        ";
        $params = [];

        if ($kdkec) {
            $sql .= " AND si.kdkec = ?";
            $params[] = $kdkec;
        }
        if ($kddesa) {
            $sql .= " AND si.kddesa = ?";
            $params[] = $kddesa;
        }
        if ($search) {
            $s = '%' . $search . '%';
            $sql .= " AND (si.nmsls LIKE ? OR si.nmkec LIKE ? OR si.nmdesa LIKE ?)";
            $params = array_merge($params, [$s, $s, $s]);
        }

        $sql .= " ORDER BY mfd.urutan, si.nmdesa, si.nmsls";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Unassigned — paginated
     */
    public function getUnassignedPaginated(?string $kdkec, ?string $kddesa, string $search, int $page = 1, int $perPage = 25): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        [$where, $params] = $this->buildUnassignedWhere($kdkec, $kddesa, $search);
        $sql = "
            SELECT si.id, si.kdkec, si.kddesa, si.nmkec, si.nmdesa,
                   si.nmsls, si.nama_ketua, si.muatan, si.kk, si.btt, si.bku, si.usaha
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
            LEFT JOIN mfd_kec mfd ON mfd.kode_kecamatan = CONCAT(SUBSTRING(si.kdprov, 1, 2), SUBSTRING(si.kdkab, 1, 2), si.kdkec)
            {$where}
            ORDER BY mfd.urutan, si.nmdesa, si.nmsls
            LIMIT ? OFFSET ?
        ";
        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count unassigned (for pagination)
     */
    public function countUnassigned(?string $kdkec, ?string $kddesa, string $search): int
    {
        [$where, $params] = $this->buildUnassignedWhere($kdkec, $kddesa, $search);
        $sql = "SELECT COUNT(*) FROM sipw_import si LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Build WHERE for unassigned queries
     */
    private function buildUnassignedWhere(?string $kdkec, ?string $kddesa, string $search): array
    {
        $sql = "WHERE sa.id IS NULL";
        $params = [];
        if ($kdkec) {
            $sql .= " AND si.kdkec = ?";
            $params[] = $kdkec;
        }
        if ($kddesa) {
            $sql .= " AND si.kddesa = ?";
            $params[] = $kddesa;
        }
        if ($search) {
            $s = '%' . $search . '%';
            $sql .= " AND (si.nmsls LIKE ? OR si.nmkec LIKE ? OR si.nmdesa LIKE ?)";
            $params = array_merge($params, [$s, $s, $s]);
        }
        return [$sql, $params];
    }

    /**
     * Daftar users untuk dropdown petugas (dengan search)
     */
    public function getPetugas(?string $search = null): array
    {
        $sql = "
            SELECT id, username, role, email
            FROM users
            WHERE status_akun = 'active'
        ";
        $params = [];

        if ($search) {
            $s = '%' . $search . '%';
            $sql .= " AND (username LIKE ? OR email LIKE ? OR role LIKE ?)";
            $params = [$s, $s, $s];
        }

        $sql .= " ORDER BY role, username";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Daftar petugas per role (untuk dropdown di modal)
     * Variadic: getPetugasByRole('pcl', 'pml') → satu query dengan IN(...)
     */
    public function getPetugasByRole(string ...$roles): array
    {
        $roles = array_intersect($roles, ['admin', 'operator', 'pegawai', 'mitra', 'pcl', 'pml', 'task_force', 'pegawai']);
        if (empty($roles)) return [];

        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $stmt = $this->pdo->prepare("
            SELECT id, username, email, role
            FROM users
            WHERE status_akun = 'active' AND role IN ({$placeholders})
            ORDER BY role, username
        ");
        $stmt->execute(array_values($roles));
        return $stmt->fetchAll();
    }

    /**
     * Daftar kecamatan yang punya data SLS
     */
    public function getKecamatan(): array
    {
        return Cache::remember('kecamatan_list', 300, function (): array {
            return $this->pdo->query("
                SELECT DISTINCT si.kdkec, si.nmkec, mfd.urutan
                FROM sipw_import si
                LEFT JOIN mfd_kec mfd ON mfd.kode_kecamatan = CONCAT(SUBSTRING(si.kdprov, 1, 2), SUBSTRING(si.kdkab, 1, 2), si.kdkec)
                ORDER BY mfd.urutan
            ")->fetchAll();
        });
    }

    public function getDesa(string $kdkec): array
    {
        return Cache::remember('desa_list_' . $kdkec, 300, function () use ($kdkec): array {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT si.kddesa, si.nmdesa
                FROM sipw_import si
                WHERE si.kdkec = ?
                ORDER BY si.nmdesa
            ");
            $stmt->execute([$kdkec]);
            return $stmt->fetchAll();
        });
    }

    /**
     * Count summary
     */
    public function countSummary(): array
    {
        return $this->pdo->query("
            SELECT
                COUNT(DISTINCT si.id)                                                   AS total_sls,
                COUNT(DISTINCT sa.id)                                                   AS total_assign,
                COALESCE(SUM(CASE WHEN sa.status = 'belum'   THEN 1 ELSE 0 END), 0)     AS status_belum,
                COALESCE(SUM(CASE WHEN sa.status = 'proses'  THEN 1 ELSE 0 END), 0)     AS status_proses,
                COALESCE(SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END), 0)     AS status_selesai,
                COALESCE(SUM(CASE WHEN sa.id IS NULL THEN 1 ELSE 0 END), 0)             AS belum_assign,
                COUNT(DISTINCT sa.pencacah_id)                                           AS pcl_aktif,
                COUNT(DISTINCT sa.pengawas_id)                                           AS pml_aktif,
                COUNT(DISTINCT sa.task_force_id)                                         AS tf_aktif
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
        ")->fetch();
    }

    /**
     * Total assigned per petugas
     */
    public function getPetugasLoad(): array
    {
        $sql = "
            SELECT
                u.id,
                u.username,
                u.role,
                SUM(CASE WHEN sa.pencacah_id = u.id THEN 1 ELSE 0 END) AS as_pencacah,
                SUM(CASE WHEN sa.pengawas_id = u.id THEN 1 ELSE 0 END) AS as_pengawas,
                SUM(CASE WHEN sa.task_force_id = u.id THEN 1 ELSE 0 END) AS as_task_force,
                SUM(CASE WHEN sa.pencacah_id = u.id AND sa.status = 'selesai' THEN 1 ELSE 0 END) AS selesai_pencacah,
                SUM(CASE WHEN sa.pengawas_id = u.id AND sa.status = 'selesai' THEN 1 ELSE 0 END) AS selesai_pengawas
            FROM users u
            LEFT JOIN sipw_assignment sa ON sa.pencacah_id = u.id
                OR sa.pengawas_id = u.id
                OR sa.task_force_id = u.id
            WHERE u.status_akun = 'active'
            GROUP BY u.id, u.username, u.role
            HAVING as_pencacah > 0 OR as_pengawas > 0 OR as_task_force > 0
            ORDER BY as_pencacah + as_pengawas + as_task_force DESC
        ";
        return $this->pdo->query($sql)->fetchAll();
    }
}
