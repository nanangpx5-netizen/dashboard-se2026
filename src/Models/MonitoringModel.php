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
        $where = "WHERE si.kdkab = '09'";

        if (!empty($filters['kdkec'])) {
            $where .= ' AND si.kdkec = ?';
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
                MAX(sa.updated_at)                             AS last_update,
                CASE WHEN COUNT(DISTINCT sa.id) > 0 THEN 1 ELSE 0 END AS is_active
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
        $params = [];
        $where = "WHERE si.kdkab = '09'";
        if ($kdkec !== '') {
            $where .= " AND si.kdkec = ?";
            $params[] = $kdkec;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                si.kdkec,
                si.nmkec,
                si.kddesa,
                si.nmdesa,
                COUNT(DISTINCT si.id)                          AS total_sls,
                COUNT(DISTINCT sa.id)                          AS assigned_sls,
                COALESCE(SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END), 0) AS selesai_sls,
                COUNT(DISTINCT CASE WHEN sa.id IS NULL THEN si.id END)               AS unassigned_sls,
                MAX(sa.updated_at)                             AS last_update,
                CASE WHEN COUNT(DISTINCT sa.id) = COUNT(DISTINCT si.id) THEN 1 ELSE 0 END AS is_complete
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
            {$where}
            GROUP BY si.kdkec, si.nmkec, si.kddesa, si.nmdesa
            ORDER BY si.nmkec, si.nmdesa
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Paginated list of SLS units (RT/RW/DUSUN)
     */
    public function getSlsData(array $filters, int $start = 0, int $length = 25): array
    {
        $params = [];
        $where = "WHERE si.nmsls REGEXP 'RT[0-9 ]|RW[0-9 ]|DUSUN|dusun' AND si.kdkab = '09'";
        $where .= $this->buildSlsWhere($filters, $params);

        $sql = "
            SELECT
                si.id, si.kdkec, si.nmkec, si.kddesa, si.nmdesa, si.nmsls,
                si.kk, si.usaha, si.muatan, si.subsektor_st2023, si.jml_kk, si.usaha_wilkerstat,
                COALESCE(pc.username, '-')     AS pencacah,
                COALESCE(pw.username, '-')     AS pengawas,
                COALESCE(tf.username, '-')     AS task_force,
                COALESCE(sa.status, 'belum')   AS status,
                sa.updated_at                  AS tgl_assign
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
            LEFT JOIN users pc ON pc.id = sa.pencacah_id
            LEFT JOIN users pw ON pw.id = sa.pengawas_id
            LEFT JOIN users tf ON tf.id = sa.task_force_id
            {$where}
            ORDER BY si.nmkec, si.nmdesa, si.nmsls
            LIMIT {$length} OFFSET {$start}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count of SLS units
     */
    public function countSlsData(array $filters): int
    {
        $params = [];
        $where = "WHERE si.nmsls REGEXP 'RT[0-9 ]|RW[0-9 ]|DUSUN|dusun' AND si.kdkab = '09'";
        $where .= $this->buildSlsWhere($filters, $params);

        $sql = "SELECT COUNT(*) FROM sipw_import si LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Paginated list of Non-SLS units
     */
    public function getNonSlsData(array $filters, int $start = 0, int $length = 25): array
    {
        $params = [];
        $where = "WHERE si.nmsls NOT REGEXP 'RT[0-9 ]|RW[0-9 ]|DUSUN|dusun' AND si.kdkab = '09'";
        $where .= $this->buildSlsWhere($filters, $params);

        $sql = "
            SELECT
                si.id, si.kdkec, si.nmkec, si.kddesa, si.nmdesa, si.nmsls,
                si.kk, si.usaha, si.muatan, si.subsektor_st2023, si.jml_kk, si.usaha_wilkerstat,
                COALESCE(pc.username, '-')     AS pencacah,
                COALESCE(pw.username, '-')     AS pengawas,
                COALESCE(tf.username, '-')     AS task_force,
                COALESCE(sa.status, 'belum')   AS status,
                sa.updated_at                  AS tgl_assign
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
            LEFT JOIN users pc ON pc.id = sa.pencacah_id
            LEFT JOIN users pw ON pw.id = sa.pengawas_id
            LEFT JOIN users tf ON tf.id = sa.task_force_id
            {$where}
            ORDER BY si.nmkec, si.nmdesa, si.nmsls
            LIMIT {$length} OFFSET {$start}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count of Non-SLS units
     */
    public function countNonSlsData(array $filters): int
    {
        $params = [];
        $where = "WHERE si.nmsls NOT REGEXP 'RT[0-9 ]|RW[0-9 ]|DUSUN|dusun' AND si.kdkab = '09'";
        $where .= $this->buildSlsWhere($filters, $params);

        $sql = "SELECT COUNT(*) FROM sipw_import si LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id {$where}";
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
                ps.imported_at,
                ps.total_fasih,
                ps.fasih_kk,
                ps.fasih_umk,
                ps.fasih_um,
                ps.fasih_ub,
                ps.fasih_bangunan,
                ps.dominan,
                ps.flag_open_pbi,
                ps.kk_open_pbi
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
     * Get summary FASIH (assignment) data
     */
    public function getFasihSummary(string $kdkab = '3509', string $kdkec = ''): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total_sls,
                COALESCE(SUM(total_fasih), 0) AS total_fasih,
                COALESCE(SUM(fasih_kk), 0) AS fasih_kk,
                COALESCE(SUM(fasih_umk), 0) AS fasih_umk,
                COALESCE(SUM(fasih_um), 0) AS fasih_um,
                COALESCE(SUM(fasih_ub), 0) AS fasih_ub,
                COALESCE(SUM(fasih_bangunan), 0) AS fasih_bangunan,
                COALESCE(SUM(flag_open_pbi), 0) AS sls_pbi,
                COALESCE(SUM(kk_open_pbi), 0) AS kk_pbi
            FROM prelist_sls ps
            WHERE ps.kd_kab = ?
        ";
        $params = [$kdkab];
        
        if (!empty($kdkec)) {
            $sql .= " AND ps.kd_kec = ?";
            $params[] = $kdkec;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: [];
    }

    /**
     * Get FASIH distribution per kecamatan
     */
    public function getFasihPerKecamatan(string $kdkab = '3509'): array
    {
        $sql = 'SELECT ps.kd_kec, ps.nm_kec, COUNT(*) AS total_sls, '
             . 'COALESCE(SUM(total_fasih), 0) AS total_fasih, '
             . 'COALESCE(SUM(fasih_kk), 0) AS fasih_kk, '
             . 'COALESCE(SUM(fasih_umk), 0) AS fasih_umk, '
             . 'COALESCE(SUM(fasih_um), 0) AS fasih_um, '
             . 'COALESCE(SUM(fasih_ub), 0) AS fasih_ub, '
             . 'COALESCE(SUM(fasih_bangunan), 0) AS fasih_bangunan, '
             . 'COALESCE(SUM(flag_open_pbi), 0) AS sls_pbi, '
             . 'COALESCE(SUM(kk_open_pbi), 0) AS kk_pbi '
             . 'FROM prelist_sls ps '
             . 'WHERE ps.kd_kab = ? '
             . 'GROUP BY ps.kd_kec, ps.nm_kec '
             . 'ORDER BY total_fasih DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kdkab]);
        return $stmt->fetchAll();
    }

    /**
     * Get FASIH distribution per desa
     */
    public function getFasihPerDesa(string $kdkab = '3509', string $kdkec = ''): array
    {
        $sql = 'SELECT ps.kd_kec, ps.kd_desa, ps.nm_desa, COUNT(*) AS total_sls, '
             . 'COALESCE(SUM(total_fasih), 0) AS total_fasih, '
             . 'COALESCE(SUM(fasih_kk), 0) AS fasih_kk, '
             . 'COALESCE(SUM(fasih_umk), 0) AS fasih_umk, '
             . 'COALESCE(SUM(fasih_um), 0) AS fasih_um, '
             . 'COALESCE(SUM(fasih_ub), 0) AS fasih_ub, '
             . 'COALESCE(SUM(fasih_bangunan), 0) AS fasih_bangunan, '
             . 'COALESCE(SUM(flag_open_pbi), 0) AS sls_pbi, '
             . 'COALESCE(SUM(kk_open_pbi), 0) AS kk_pbi '
             . 'FROM prelist_sls ps '
             . 'WHERE ps.kd_kab = ?';
        
        $params = [$kdkab];
        
        if (!empty($kdkec)) {
            $sql .= " AND ps.kd_kec = ?";
            $params[] = $kdkec;
        }
        
        $sql .= " GROUP BY ps.kd_kec, ps.kd_desa, ps.nm_desa ORDER BY total_fasih DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
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

    // ─── LK Pairing Queries ──────────────────────────────────

    /**
     * Status progress pairing dari lk_pairing
     */
    public function getPairingProgress(): array
    {
        return $this->pdo->query("
            SELECT
                COUNT(*) AS total_subsls,
                COALESCE(SUM(CASE WHEN ppl_id IS NOT NULL OR kode_ppl IS NOT NULL THEN 1 ELSE 0 END), 0) AS paired_ppl,
                COALESCE(SUM(CASE WHEN pml_id IS NOT NULL OR kode_pml IS NOT NULL THEN 1 ELSE 0 END), 0) AS paired_pml,
                COALESCE(SUM(CASE WHEN ppl_id IS NOT NULL AND pml_id IS NOT NULL THEN 1 ELSE 0 END), 0) AS paired_both,
                COALESCE(SUM(muatan), 0) AS total_muatan,
                COALESCE(SUM(CASE WHEN muatan = 0 THEN 1 ELSE 0 END), 0) AS zero_muatan
            FROM lk_pairing
        ")->fetch() ?: [];
    }

    /**
     * Beban kerja per PPL dari lk_pairing
     */
    public function getBebanPerPpl(): array
    {
        return $this->pdo->query("
            SELECT
                lp.nama,
                lp.nm_kec,
                lp.email,
                COUNT(lk.id) AS n_sls,
                COALESCE(SUM(lk.muatan), 0) AS total_muatan,
                COALESCE(ROUND(AVG(lk.muatan)), 0) AS avg_muatan,
                COALESCE(SUM(lk.muatan_kel), 0) AS muatan_kel,
                COALESCE(SUM(lk.muatan_st2023), 0) AS muatan_st2023,
                COALESCE(SUM(lk.muatan_bang), 0) AS muatan_bang
            FROM lk_pairing lk
            JOIN lk_petugas lp ON lp.kode_lk = lk.kode_ppl
            GROUP BY lk.kode_ppl
            ORDER BY total_muatan DESC
        ")->fetchAll();
    }

    /**
     * Beban kerja per PML dari lk_pairing
     */
    public function getBebanPerPml(): array
    {
        return $this->pdo->query("
            SELECT
                lp.nama,
                lp.nm_kec,
                lp.email,
                COUNT(lk.id) AS n_sls,
                COALESCE(SUM(lk.muatan), 0) AS total_muatan,
                COALESCE(ROUND(AVG(lk.muatan)), 0) AS avg_muatan
            FROM lk_pairing lk
            JOIN lk_petugas lp ON lp.kode_lk = lk.kode_pml
            GROUP BY lk.kode_pml
            ORDER BY total_muatan DESC
        ")->fetchAll();
    }

    /**
     * Pairing distribution per kecamatan
     */
    public function getPairingPerKecamatan(): array
    {
        return $this->pdo->query("
            SELECT
                lp.nm_kec,
                lp.kd_kec,
                COUNT(DISTINCT lk.id) AS total_subsls,
                COALESCE(SUM(lk.muatan), 0) AS total_muatan,
                COUNT(DISTINCT CASE WHEN lp.tipe = 'PPL' THEN lp.id END) AS total_ppl,
                COUNT(DISTINCT CASE WHEN lp.tipe = 'PML' THEN lp.id END) AS total_pml,
                COALESCE(SUM(CASE WHEN lk.ppl_id IS NOT NULL THEN 1 ELSE 0 END), 0) AS paired_ppl
            FROM lk_petugas lp
            LEFT JOIN lk_pairing lk ON lk.kode_ppl = lp.kode_lk OR lk.kode_pml = lp.kode_lk
            GROUP BY lp.nm_kec, lp.kd_kec
            ORDER BY lp.nm_kec
        ")->fetchAll();
    }

    /**
     * Daftar email PPL yang tidak ditemukan di users
     */
    public function getMissingPplEmails(): array
    {
        return $this->pdo->query("
            SELECT lp.nama, lp.email, lp.kode_lk, lp.nm_kec
            FROM lk_petugas lp
            WHERE lp.tipe = 'PPL' AND lp.user_id IS NULL
            ORDER BY lp.nm_kec, lp.nama
        ")->fetchAll();
    }

    /**
     * Summary per kecamatan: kebutuhan vs aktual dari LK
     */
    public function getCoveragePerKecamatan(): array
    {
        return $this->pdo->query("
            SELECT
                wk.nama_kecamatan,
                wk.kebutuhan_pcl,
                wk.kebutuhan_pml,
                COALESCE(wk.aktual_ppl_lk, 0) AS aktual_ppl,
                COALESCE(wk.aktual_pml_lk, 0) AS aktual_pml,
                COALESCE(wk.aktual_ppl_lk, 0) - wk.kebutuhan_pcl AS selisih_ppl,
                COALESCE(wk.aktual_pml_lk, 0) - wk.kebutuhan_pml AS selisih_pml
            FROM wilayah_kerja wk
            ORDER BY selisih_ppl ASC
        ")->fetchAll();
    }
}
