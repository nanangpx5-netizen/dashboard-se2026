<?php

namespace App\Models;

use App\Core\Database;

class AuditLogModel
{
    private \PDO $pdo;

    /** Mapping kolom DataTables ke SQL */
    private array $dtColumns = [
        'created_at',
        'username',
        'action',
        'module',
        'description',
        'detail_json',
    ];

    public function __construct()
    {
        $this->pdo = Database::instance()->pdo();
    }

    /**
     * UNION query — menggabungkan 3 sumber log:
     *   1. activity_logs  (auth, import, petugas CRUD)
     *   2. dash_assignment_log  (assignment edit/delete/status)
     *   3. dash_import_log  (import detail stats)
     */
    private function baseUnion(): string
    {
        return "
            SELECT
                al.created_at,
                COALESCE(u.username, '(system)') AS username,
                al.action,
                al.module,
                al.detail                          AS description,
                al.detail                          AS detail_json,
                al.user_id,
                'activity_logs'                     AS source
            FROM activity_logs al
            LEFT JOIN users u ON u.id = al.user_id

            UNION ALL

            SELECT
                dal.created_at,
                COALESCE(u.username, '(system)') AS username,
                CONCAT('assignment_', LOWER(dal.action)) AS action,
                'assignment'                     AS module,
                COALESCE(dal.change_note, dal.action) AS description,
                JSON_OBJECT(
                    'old_data', dal.old_data,
                    'new_data', dal.new_data,
                    'sipw_id', dal.sipw_id,
                    'assignment_id', dal.assignment_id
                )                                AS detail_json,
                dal.changed_by                    AS user_id,
                'dash_assignment_log'             AS source
            FROM dash_assignment_log dal
            LEFT JOIN users u ON u.id = dal.changed_by

            UNION ALL

            SELECT
                dil.created_at,
                COALESCE(u.username, '(system)') AS username,
                CONCAT('import_', dil.status)    AS action,
                'import'                         AS module,
                CONCAT(
                    dil.nama_file, ' — ',
                    dil.baris_berhasil, ' baru, ',
                    dil.baris_diupdate, ' update, ',
                    dil.baris_gagal, ' gagal'
                )                                AS description,
                JSON_OBJECT(
                    'batch_id', dil.batch_id,
                    'nama_file', dil.nama_file,
                    'total_baris', dil.total_baris,
                    'baris_berhasil', dil.baris_berhasil,
                    'baris_diupdate', dil.baris_diupdate,
                    'baris_gagal', dil.baris_gagal,
                    'status', dil.status
                )                                AS detail_json,
                dil.user_id,
                'dash_import_log'                 AS source
            FROM dash_import_log dil
            LEFT JOIN users u ON u.id = dil.user_id
        ";
    }

    /**
     * Build WHERE dari filters
     */
    private function buildWhere(array $filters, array &$params): string
    {
        $conditions = [];

        if (!empty($filters['module'])) {
            $conditions[] = 'module = ?';
            $params[] = $filters['module'];
        }

        if (!empty($filters['action'])) {
            $conditions[] = 'action LIKE ?';
            $params[] = '%' . $filters['action'] . '%';
        }

        if (!empty($filters['user_id'])) {
            $conditions[] = 'user_id = ?';
            $params[] = (int) $filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = 'created_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $conditions[] = '(username LIKE ? OR action LIKE ? OR description LIKE ?)';
            $params = array_merge($params, [$s, $s, $s]);
        }

        return empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
    }

    /**
     * Total records (tanpa filter)
     */
    public function totalCount(): int
    {
        $sql = "SELECT COUNT(*) FROM ({$this->baseUnion()}) AS unified";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    /**
     * Filtered count
     */
    public function filteredCount(array $filters): int
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);

        $sql = "SELECT COUNT(*) FROM ({$this->baseUnion()}) AS unified {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get page of audit data
     */
    public function getDataTable(array $filters, int $start, int $length, array $order): array
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);

        $orderBy = 'ORDER BY created_at DESC';
        if (!empty($order)) {
            $dir = strtoupper($order['dir']) === 'ASC' ? 'ASC' : 'DESC';
            $colIdx = (int) $order['column'];
            if (isset($this->dtColumns[$colIdx])) {
                $col = $this->dtColumns[$colIdx];
                $orderBy = "ORDER BY {$col} {$dir}";
            }
        }

        $sql = "
            SELECT * FROM ({$this->baseUnion()}) AS unified
            {$where}
            {$orderBy}
            LIMIT {$length} OFFSET {$start}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Daftar module unik (untuk dropdown filter)
     */
    public function getModules(): array
    {
        return $this->pdo->query("
            SELECT DISTINCT module FROM activity_logs
            UNION
            SELECT DISTINCT 'assignment' FROM dual
            UNION
            SELECT DISTINCT 'import' FROM dual
            ORDER BY module
        ")->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Daftar user yang pernah tercatat (untuk dropdown filter)
     */
    public function getAuditedUsers(): array
    {
        return $this->pdo->query("
            SELECT DISTINCT u.id, u.username
            FROM (
                SELECT user_id FROM activity_logs WHERE user_id IS NOT NULL
                UNION
                SELECT changed_by FROM dash_assignment_log
                UNION
                SELECT user_id FROM dash_import_log WHERE user_id IS NOT NULL
            ) AS ids
            JOIN users u ON u.id = ids.user_id
            ORDER BY u.username
        ")->fetchAll();
    }

    /**
     * Label aksi (Indonesia)
     */
    public static function actionLabel(string $action): string
    {
        $map = [
            'login'               => 'Login',
            'logout'              => 'Logout',
            'login_failed'        => 'Login Gagal',
            'petugas_create'      => 'Tambah Petugas',
            'petugas_update'      => 'Edit Petugas',
            'petugas_toggle_status' => 'Ubah Status Petugas',
            'petugas_reset_password' => 'Reset Password',
            'import_start'        => 'Import Dimulai',
            'import_complete'     => 'Import Selesai',
            'import_success'      => 'Import Sukses',
            'import_partial'      => 'Import Sebagian',
            'import_failed'       => 'Import Gagal',
            'import_cancelled'    => 'Import Dibatalkan',
            'assignment_insert'   => 'Assignment Baru',
            'assignment_update'   => 'Ubah Assignment',
            'assignment_delete'   => 'Hapus Assignment',
            'assignment_status_change' => 'Ubah Status',
        ];
        return $map[$action] ?? $action;
    }
}
