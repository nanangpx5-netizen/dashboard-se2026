<?php

namespace App\Models;

use App\Core\Database;

class PmlReportModel
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::instance()->pdo();
    }

    /**
     * KPI agregat laporan PML
     */
    public function getStats(?string $periode = null, ?string $kdkec = null): array
    {
        $kecJoin = '';
        $kecWhere = '';
        $params = [];

        if ($kdkec) {
            $kecJoin = 'LEFT JOIN sipw_import si ON sa.sipw_id = si.id';
            $kecWhere = ' AND si.kdkec = ?';
            $params[] = $kdkec;
        }

        // PML aktif
        $totalPml = $this->pdo->query("SELECT COUNT(*) FROM users WHERE role='pml' AND status_akun='active'")->fetchColumn();

        // PML dengan assignment
        $sqlWith = "SELECT COUNT(DISTINCT u.id) FROM users u
                    INNER JOIN sipw_assignment sa ON sa.pengawas_id = u.id
                    $kecJoin
                    WHERE u.role='pml' AND u.status_akun='active' $kecWhere";
        $stmt = $this->pdo->prepare($sqlWith);
        $stmt->execute($params);
        $withAssign = (int) $stmt->fetchColumn();

        // Agregat status
        $sqlStatus = "SELECT
                        COUNT(sa.id) as total_sls,
                        SUM(sa.status = 'selesai') as selesai,
                        SUM(sa.status = 'proses') as proses,
                        SUM(sa.status = 'belum') as belum
                      FROM sipw_assignment sa
                      $kecJoin
                      WHERE sa.pengawas_id IS NOT NULL $kecWhere";
        $stmt = $this->pdo->prepare($sqlStatus);
        $stmt->execute($params);
        $agg = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Laporan yang sudah dikirim (periode ini)
        $reported = 0;
        if ($periode) {
            $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT pml_id) FROM pml_reports WHERE periode = ?");
            $stmt->execute([$periode]);
            $reported = (int) $stmt->fetchColumn();
        }

        return [
            'total_pml'       => (int) $totalPml,
            'with_assignment' => $withAssign,
            'without_assignment' => (int) $totalPml - $withAssign,
            'total_sls'       => (int) ($agg['total_sls'] ?? 0),
            'selesai'         => (int) ($agg['selesai'] ?? 0),
            'proses'          => (int) ($agg['proses'] ?? 0),
            'belum'           => (int) ($agg['belum'] ?? 0),
            'reported'        => $reported,
        ];
    }

    /**
     * DataTable laporan PML (server-side)
     */
    public function getReports(array $filters, int $page = 1, int $perPage = 25): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        [$where, $params] = $this->buildWhere($filters);

        $sql = "
            SELECT
                u.id, u.nama_lengkap, u.username, u.email, u.status_akun,
                COALESCE(SUM(sa.status = 'selesai'), 0) as selesai,
                COALESCE(SUM(sa.status = 'proses'), 0) as proses,
                COALESCE(SUM(sa.status = 'belum'), 0) as belum,
                COUNT(sa.id) as total_assigned,
                GROUP_CONCAT(DISTINCT si.nmkec ORDER BY si.nmkec SEPARATOR ', ') as kecamatan_list,
                GROUP_CONCAT(DISTINCT si.nmdesa ORDER BY si.nmdesa SEPARATOR ', ') as desa_list,
                pr.id as report_id,
                pr.periode as report_periode,
                pr.submitted_at as report_submitted
            FROM users u
            LEFT JOIN sipw_assignment sa ON sa.pengawas_id = u.id
            LEFT JOIN sipw_import si ON si.id = sa.sipw_id
            LEFT JOIN (
                SELECT pml_id, MAX(id) as max_id
                FROM pml_reports
                GROUP BY pml_id
            ) pr_last ON pr_last.pml_id = u.id
            LEFT JOIN pml_reports pr ON pr.id = pr_last.max_id
            $where
            GROUP BY u.id
            ORDER BY u.nama_lengkap
            LIMIT ? OFFSET ?
        ";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countReports(array $filters): int
    {
        [$where, $params] = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) FROM users u
                LEFT JOIN sipw_assignment sa ON sa.pengawas_id = u.id
                $where
                GROUP BY u.id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return count($stmt->fetchAll());
    }

    /**
     * Detail per-SLS untuk PML tertentu
     */
    public function getDetail(int $pmlId, ?string $kdkec = null): array
    {
        $sql = "
            SELECT si.nmsls, si.nmdesa, si.nmkec, si.nama_ketua,
                   sa.status, sa.tanggal_mulai, sa.tanggal_selesai, sa.catatan
            FROM sipw_assignment sa
            JOIN sipw_import si ON si.id = sa.sipw_id
            WHERE sa.pengawas_id = ?
        ";
        $params = [$pmlId];
        if ($kdkec) {
            $sql .= " AND si.kdkec = ?";
            $params[] = $kdkec;
        }
        $sql .= " ORDER BY si.nmkec, si.nmdesa, si.nmsls";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Cek apakah PML punya alokasi SLS
     */
    public function countPmlAssignments(int $pmlId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM sipw_assignment WHERE pengawas_id = ?");
        $stmt->execute([$pmlId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cek duplikasi laporan
     */
    public function getReportByPmlPeriode(int $pmlId, string $periode): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pml_reports WHERE pml_id = ? AND periode = ? LIMIT 1");
        $stmt->execute([$pmlId, $periode]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    /**
     * Simpan laporan PML
     */
    public function createReport(int $pmlId, string $periode, array $stats, ?string $catatan): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO pml_reports (pml_id, periode, total_assigned, total_selesai, total_proses, total_belum, catatan, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $pmlId,
            $periode,
            $stats['total'],
            $stats['selesai'],
            $stats['proses'],
            $stats['belum'],
            $catatan,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function getExportData(?string $periode, ?string $kdkec = null): array
    {
        $where = "u.role='pml' AND u.status_akun='active'";
        $params = [];
        if ($kdkec) {
            $where .= " AND si.kdkec = ?";
            $params[] = $kdkec;
        }

        $sql = "
            SELECT u.nama_lengkap, u.username, u.email,
                   COUNT(sa.id) as total_assigned,
                   COALESCE(SUM(sa.status = 'selesai'), 0) as selesai,
                   COALESCE(SUM(sa.status = 'proses'), 0) as proses,
                   COALESCE(SUM(sa.status = 'belum'), 0) as belum
            FROM users u
            LEFT JOIN sipw_assignment sa ON sa.pengawas_id = u.id
            " . ($kdkec ? "LEFT JOIN sipw_import si ON si.id = sa.sipw_id" : "") . "
            WHERE $where
            GROUP BY u.id
            ORDER BY u.nama_lengkap
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function buildWhere(array $filters): array
    {
        $sql = "WHERE u.role='pml' AND u.status_akun='active'";
        $params = [];

        if (!empty($filters['kdkec'])) {
            $sql .= " AND sa.sipw_id IN (SELECT id FROM sipw_import WHERE kdkec = ?)";
            $params[] = $filters['kdkec'];
        }

        if (!empty($filters['status_assign'])) {
            if ($filters['status_assign'] === 'tanpa_alokasi') {
                $sql .= " AND u.id NOT IN (SELECT DISTINCT pengawas_id FROM sipw_assignment WHERE pengawas_id IS NOT NULL)";
            } elseif ($filters['status_assign'] === 'sudah_lapor') {
                $sql .= " AND u.id IN (SELECT DISTINCT pml_id FROM pml_reports)";
            } elseif ($filters['status_assign'] === 'belum_lapor') {
                $sql .= " AND u.id NOT IN (SELECT DISTINCT pml_id FROM pml_reports)";
            }
        }

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $sql .= " AND (u.nama_lengkap LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $params = array_merge($params, [$s, $s, $s]);
        }

        return [$sql, $params];
    }
}
