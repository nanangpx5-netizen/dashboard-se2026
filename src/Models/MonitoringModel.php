<?php

namespace App\Models;

use App\Core\Database;
use App\Helpers\Cache;

/**
 * MonitoringModel — DataTables server-side, filter data, export
 *
 * Kolom tampilan: kecamatan, desa, sls, kk, usaha, muatan,
 *                 pencacah, pengawas, task_force, status
 */
class MonitoringModel
{
    private \PDO $pdo;

    /** Mapping kolom DataTables → field SQL */
    private array $dtColumns = [
        'nmkec',
        'nmdesa',
        'nmsls',
        'kk',
        'usaha',
        'muatan',
        'pencacah',
        'pengawas',
        'task_force',
        'status',
    ];

    public function __construct()
    {
        $this->pdo = Database::instance()->pdo();
    }

    /**
     * Base query untuk DataTables & export
     */
    private function baseQuery(): string
    {
        return "
            SELECT
                si.id,
                COALESCE(si.nmkec, '-')        AS nmkec,
                COALESCE(si.kdkec, '-')        AS kdkec,
                COALESCE(si.nmdesa, '-')       AS nmdesa,
                COALESCE(si.kddesa, '-')       AS kddesa,
                COALESCE(si.nmsls, '-')        AS nmsls,
                COALESCE(si.nama_ketua, '-')   AS nama_ketua,
                COALESCE(si.kk, 0)             AS kk,
                COALESCE(si.usaha, 0)          AS usaha,
                COALESCE(si.muatan, 0)         AS muatan,
                COALESCE(pc.username, '-')     AS pencacah,
                COALESCE(pw.username, '-')     AS pengawas,
                COALESCE(tf.username, '-')     AS task_force,
                COALESCE(sa.status, 'belum')   AS status,
                sa.updated_at                  AS tgl_status
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
            LEFT JOIN users pc ON pc.id = sa.pencacah_id
            LEFT JOIN users pw ON pw.id = sa.pengawas_id
            LEFT JOIN users tf ON tf.id = sa.task_force_id
        ";
    }

    /**
     * Build WHERE clause dari filters
     */
    private function buildWhere(array $filters, array &$params): string
    {
        $conditions = [];

        if (!empty($filters['kdkec'])) {
            $conditions[] = 'si.kdkec = ?';
            $params[] = $filters['kdkec'];
        }

        if (!empty($filters['kddesa'])) {
            $conditions[] = 'si.kddesa = ?';
            $params[] = $filters['kddesa'];
        }

        if (!empty($filters['pencacah'])) {
            $conditions[] = 'sa.pencacah_id = ?';
            $params[] = (int) $filters['pencacah'];
        }

        if (!empty($filters['pengawas'])) {
            $conditions[] = 'sa.pengawas_id = ?';
            $params[] = (int) $filters['pengawas'];
        }

        if (!empty($filters['task_force'])) {
            $conditions[] = 'sa.task_force_id = ?';
            $params[] = (int) $filters['task_force'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'sa.status = ?';
            $params[] = $filters['status'];
        }

        $where = '';
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        return $where;
    }

    /**
     * Build search condition untuk DataTables global search
     */
    private function buildSearch(string $search, array &$params): string
    {
        if ($search === '') return '';

        $s = '%' . $search . '%';
        $cols = ['si.nmkec', 'si.nmdesa', 'si.nmsls', 'si.nama_ketua',
                 'pc.username', 'pw.username', 'tf.username', 'sa.status'];
        $parts = [];
        foreach ($cols as $c) {
            $parts[] = "$c LIKE ?";
            $params[] = $s;
        }

        return '(' . implode(' OR ', $parts) . ')';
    }

    /**
     * Build ORDER BY dari DataTables order params
     */
    private function buildOrder(array $order): string
    {
        if (empty($order)) return 'ORDER BY si.nmkec, si.nmdesa, si.nmsls';

        $dir = strtoupper($order['dir']) === 'ASC' ? 'ASC' : 'DESC';
        $colIdx = (int) $order['column'];

        if (isset($this->dtColumns[$colIdx])) {
            $col = $this->dtColumns[$colIdx];

            if ($col === 'pencacah') $col = 'pc.username';
            elseif ($col === 'pengawas') $col = 'pw.username';
            elseif ($col === 'task_force') $col = 'tf.username';
            elseif ($col === 'status') $col = 'sa.status';

            return "ORDER BY {$col} {$dir}";
        }

        return 'ORDER BY si.nmkec, si.nmdesa, si.nmsls';
    }

    /**
     * DataTables: total records (tanpa filter)
     */
    public function totalCount(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM sipw_import")->fetchColumn();
    }

    /**
     * DataTables: filtered count
     */
    public function filteredCount(array $filters, string $search): int
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);
        $searchWhere = $this->buildSearch($search, $params);

        $sql = "SELECT COUNT(*) FROM (
                    SELECT si.id
                    FROM sipw_import si
                    LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
                    LEFT JOIN users pc ON pc.id = sa.pencacah_id
                    LEFT JOIN users pw ON pw.id = sa.pengawas_id
                    LEFT JOIN users tf ON tf.id = sa.task_force_id
                    {$where}
        ";

        if ($searchWhere) {
            $sql .= ($where === '' ? 'WHERE ' : 'AND ') . $searchWhere;
        }

        $sql .= ") AS filtered";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * DataTables: get page of data
     */
    public function getDataTable(array $filters, string $search, int $start, int $length, array $order): array
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);
        $searchWhere = $this->buildSearch($search, $params);
        $orderBy = $this->buildOrder($order);

        $sql = $this->baseQuery() . "\n{$where}";

        if ($searchWhere) {
            $sql .= ($where === '' ? 'WHERE ' : ' AND ') . $searchWhere;
        }

        $sql .= "\n{$orderBy}\nLIMIT {$length} OFFSET {$start}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Export: get ALL records (unpaginated)
     */
    public function exportAll(array $filters): array
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);

        $sql = $this->baseQuery() . "\n{$where} ORDER BY si.nmkec, si.nmdesa, si.nmsls";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Summary cards
     */
    public function getSummary(): array
    {
        return $this->pdo->query("
            SELECT
                COUNT(DISTINCT si.id)                                                    AS total_sls,
                COALESCE(SUM(CASE WHEN sa.id IS NOT NULL THEN 1 ELSE 0 END), 0)          AS assigned_sls,
                COALESCE(SUM(CASE WHEN sa.status = 'proses'  THEN 1 ELSE 0 END), 0)     AS progress_sls,
                COALESCE(SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END), 0)     AS completed_sls
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
        ")->fetch();
    }

    /**
     * Daftar kecamatan (untuk dropdown filter)
     */
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
     * Daftar petugas semua role — 1 query UNION DISTINCT

    /**
     * Daftar pencacah yang aktif di assignment
     */
    public function getPetugasLists(): array
    {
        $rows = $this->pdo->query("
            SELECT 'pencacah' AS grp, u.id, u.username
            FROM sipw_assignment sa
            JOIN users u ON u.id = sa.pencacah_id
            WHERE u.status_akun = 'active'
            UNION DISTINCT
            SELECT 'pengawas', u.id, u.username
            FROM sipw_assignment sa
            JOIN users u ON u.id = sa.pengawas_id
            WHERE u.status_akun = 'active'
            UNION DISTINCT
            SELECT 'task_force', u.id, u.username
            FROM sipw_assignment sa
            JOIN users u ON u.id = sa.task_force_id
            WHERE u.status_akun = 'active'
            ORDER BY grp, username
        ")->fetchAll();

        $result = ['pencacah' => [], 'pengawas' => [], 'task_force' => []];
        foreach ($rows as $r) {
            $result[$r['grp']][] = $r;
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    //  NEW: Monitoring summary queries (kecamatan, desa, SLS, prelist)
    // ─────────────────────────────────────────────────────────────

    /**
     * Summary per kecamatan — assigned SLS count, status, last update
     */
    public function getKecamatanSummary(array $filters = []): array
    {
        $params = [];
        $where = '';

        if (!empty($filters['kdkec'])) {
            $where = 'WHERE si.kdkec = ?';
            $params[] = $filters['kdkec'];
        }

        $sql = "
            SELECT
                si.kdkec,
                si.nmkec,
                COUNT(DISTINCT si.id)                          AS total_sls,
                COUNT(DISTINCT sa.id)                          AS assigned_sls,
                COALESCE(SUM(CASE WHEN sa.status = 'proses'  THEN 1 ELSE 0 END), 0) AS proses_sls,
                COALESCE(SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END), 0) AS selesai_sls,
                MAX(sa.updated_at)                             AS last_update
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
            {$where}
            GROUP BY si.kdkec, si.nmkec
            ORDER BY si.nmkec
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Summary per desa — can filter by kecamatan
     */
    public function getDesaSummary(string $kdkec = ''): array
    {
        if ($kdkec === '') return [];

        $stmt = $this->pdo->prepare("
            SELECT
                si.kddesa,
                si.nmdesa,
                COUNT(DISTINCT si.id)                          AS total_sls,
                COUNT(DISTINCT sa.id)                          AS assigned_sls,
                COALESCE(SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END), 0) AS selesai_sls,
                COUNT(DISTINCT CASE WHEN sa.id IS NULL THEN si.id END)               AS unassigned_sls,
                MAX(sa.updated_at)                             AS last_update
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
            WHERE si.kdkec = ?
            GROUP BY si.kddesa, si.nmdesa
            ORDER BY si.nmdesa
        ");
        $stmt->execute([$kdkec]);
        return $stmt->fetchAll();
    }

    /**
     * Paginated list of assigned SLS (from sipw_import + sipw_assignment)
     */
    public function getSlsAssigned(array $filters, int $start = 0, int $length = 25): array
    {
        $params = [];
        $where = 'WHERE sa.id IS NOT NULL';
        $where .= $this->buildSlsWhere($filters, $params);

        $sql = "
            SELECT
                si.id,
                si.kdkec,
                si.nmkec,
                si.kddesa,
                si.nmdesa,
                si.nmsls,
                si.kk,
                si.usaha,
                si.muatan,
                COALESCE(pc.username, '-')     AS pencacah,
                COALESCE(pw.username, '-')     AS pengawas,
                COALESCE(tf.username, '-')     AS task_force,
                sa.status,
                sa.updated_at                  AS tgl_assign
            FROM sipw_import si
            JOIN sipw_assignment sa ON sa.sipw_id = si.id
            LEFT JOIN users pc ON pc.id = sa.pencacah_id
            LEFT JOIN users pw ON pw.id = sa.pengawas_id
            LEFT JOIN users tf ON tf.id = sa.task_force_id
            {$where}
            ORDER BY sa.updated_at DESC, si.nmkec, si.nmdesa, si.nmsls
            LIMIT {$length} OFFSET {$start}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count of assigned SLS
     */
    public function countSlsAssigned(array $filters): int
    {
        $params = [];
        $where = 'WHERE sa.id IS NOT NULL';
        $where .= $this->buildSlsWhere($filters, $params);

        $sql = "
            SELECT COUNT(DISTINCT si.id)
            FROM sipw_import si
            JOIN sipw_assignment sa ON sa.sipw_id = si.id
            LEFT JOIN users pc ON pc.id = sa.pencacah_id
            LEFT JOIN users pw ON pw.id = sa.pengawas_id
            LEFT JOIN users tf ON tf.id = sa.task_force_id
            {$where}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Paginated list of prelist SLS (non-SLS view)
     */
    public function getPrelistSls(string $kdkab = '3509', string $search = '', int $start = 0, int $length = 25, string $kdkec = ''): array
    {
        $params = [$kdkab];
        $where = 'WHERE ps.kd_kab = ?';

        if ($kdkec !== '') {
            $where .= ' AND ps.kd_kec = ?';
            $params[] = $kdkec;
        }

        if ($search !== '') {
            $where .= ' AND (ps.nama_sls LIKE ? OR ps.nm_kec LIKE ? OR ps.nm_desa LIKE ? OR ps.idsls LIKE ?)';
            $s = '%' . $search . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        $sql = "
            SELECT
                ps.idsls,
                ps.kd_kec,
                ps.kd_desa,
                ps.nm_kec,
                ps.nm_desa,
                ps.nama_sls,
                ps.jml_kk,
                ps.utp,
                ps.muatan_rs,
                ps.subsektor,
                ps.usaha_se2016,
                ps.usaha_wilkerstat,
                ps.imported_at
            FROM prelist_sls ps
            {$where}
            ORDER BY ps.nm_kec, ps.nm_desa, ps.nama_sls
            LIMIT {$length} OFFSET {$start}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count of prelist SLS
     */
    public function countPrelistSls(string $kdkab = '3509', string $search = '', string $kdkec = ''): int
    {
        $params = [$kdkab];
        $where = 'WHERE ps.kd_kab = ?';

        if ($kdkec !== '') {
            $where .= ' AND ps.kd_kec = ?';
            $params[] = $kdkec;
        }

        if ($search !== '') {
            $where .= ' AND (ps.nama_sls LIKE ? OR ps.nm_kec LIKE ? OR ps.nm_desa LIKE ? OR ps.idsls LIKE ?)';
            $s = '%' . $search . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM prelist_sls ps {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Kecamatan list from prelist_sls
     */
    public function getPrelistKecamatan(string $kdkab = '3509'): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT ps.kd_kec, ps.nm_kec
            FROM prelist_sls ps
            WHERE ps.kd_kab = ?
            ORDER BY ps.nm_kec
        ");
        $stmt->execute([$kdkab]);
        return $stmt->fetchAll();
    }

    /**
     * Additional WHERE filters for SLS assigned query
     */
    private function buildSlsWhere(array $filters, array &$params): string
    {
        $conditions = [];

        if (!empty($filters['kdkec'])) {
            $conditions[] = 'si.kdkec = ?';
            $params[] = $filters['kdkec'];
        }
        if (!empty($filters['kddesa'])) {
            $conditions[] = 'si.kddesa = ?';
            $params[] = $filters['kddesa'];
        }
        if (!empty($filters['pencacah'])) {
            $conditions[] = 'sa.pencacah_id = ?';
            $params[] = (int) $filters['pencacah'];
        }
        if (!empty($filters['pengawas'])) {
            $conditions[] = 'sa.pengawas_id = ?';
            $params[] = (int) $filters['pengawas'];
        }
        if (!empty($filters['task_force'])) {
            $conditions[] = 'sa.task_force_id = ?';
            $params[] = (int) $filters['task_force'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = 'sa.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $conditions[] = '(si.nmkec LIKE ? OR si.nmdesa LIKE ? OR si.nmsls LIKE ? OR pc.username LIKE ? OR pw.username LIKE ?)';
            $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
        }

        return empty($conditions) ? '' : ' AND ' . implode(' AND ', $conditions);
    }

    /**
     * Daftar pengawas yang aktif di assignment
     */
    public function getPengawasList(): array
    {
        return $this->pdo->query("
            SELECT DISTINCT u.id, u.username
            FROM sipw_assignment sa
            JOIN users u ON u.id = sa.pengawas_id
            WHERE u.status_akun = 'active'
            ORDER BY u.username
        ")->fetchAll();
    }

    /**
     * Daftar task force yang aktif di assignment
     */
    public function getTaskForceList(): array
    {
        return $this->pdo->query("
            SELECT DISTINCT u.id, u.username
            FROM sipw_assignment sa
            JOIN users u ON u.id = sa.task_force_id
            WHERE u.status_akun = 'active'
            ORDER BY u.username
        ")->fetchAll();
    }
}
