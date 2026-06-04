<?php

namespace App\Models;

use App\Core\Database;
use App\Helpers\Cache;

class ReportModel
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::instance()->pdo();
    }

    // ─── 1. DASHBOARD SNAPSHOT ───────────────────────────────────────────

    public function dashboardSnapshot(): array
    {
        $summary   = $this->pdo->query("
            SELECT
                COUNT(DISTINCT si.kdkec)          AS total_kecamatan,
                COUNT(DISTINCT CONCAT(si.kdkec, si.kddesa)) AS total_desa,
                COUNT(si.id)                      AS total_sls,
                COALESCE(SUM(si.muatan), 0)       AS total_muatan,
                COALESCE(SUM(si.kk), 0)           AS total_kk,
                COALESCE(SUM(si.usaha), 0)        AS total_usaha
            FROM sipw_import si
        ")->fetch();

        $assignment = $this->pdo->query("
            SELECT
                COALESCE(SUM(CASE WHEN sa.id IS NOT NULL THEN 1 ELSE 0 END), 0)   AS assigned,
                COALESCE(SUM(CASE WHEN sa.status = 'proses'  THEN 1 ELSE 0 END), 0) AS proses,
                COALESCE(SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END), 0) AS selesai
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
        ")->fetch();

        $petugas = $this->pdo->query("
            SELECT
                COALESCE(SUM(CASE WHEN role = 'pcl' THEN 1 ELSE 0 END), 0)        AS total_pcl,
                COALESCE(SUM(CASE WHEN role = 'pml' THEN 1 ELSE 0 END), 0)        AS total_pml,
                COALESCE(SUM(CASE WHEN role = 'task_force' THEN 1 ELSE 0 END), 0) AS total_tf
            FROM users
            WHERE status_akun = 'active' AND role IN ('pcl','pml','task_force')
        ")->fetch();

        return array_merge($summary, $assignment, $petugas);
    }

    // ─── 2. REKAP PER KECAMATAN ──────────────────────────────────────────

    public function rekapKecamatan(): array
    {
        return $this->pdo->query("
            SELECT
                COALESCE(wk.nama_kecamatan, CONCAT('Kec. ', si.kdkec)) AS kecamatan,
                si.kdkec,
                COUNT(si.id)                                            AS total_sls,
                COALESCE(SUM(CASE WHEN sa.id IS NOT NULL THEN 1 ELSE 0 END), 0)   AS assigned,
                COALESCE(SUM(CASE WHEN sa.status = 'proses'  THEN 1 ELSE 0 END), 0) AS proses,
                COALESCE(SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END), 0) AS selesai,
                COALESCE(SUM(si.kk), 0)                                 AS total_kk,
                COALESCE(SUM(si.usaha), 0)                              AS total_usaha,
                COALESCE(SUM(si.muatan), 0)                             AS total_muatan,
                COALESCE(SUM(si.btt), 0)                                AS total_btt,
                COALESCE(SUM(si.bku), 0)                                AS total_bku,
                COUNT(DISTINCT sa.pencacah_id)                          AS jumlah_pcl,
                COUNT(DISTINCT sa.pengawas_id)                          AS jumlah_pml
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
            LEFT JOIN wilayah_kerja wk ON wk.kode_kecamatan = si.kdkec
            GROUP BY si.kdkec, wk.nama_kecamatan
            ORDER BY wk.nama_kecamatan
        ")->fetchAll();
    }

    // ─── 3. REKAP PER PENCACAH (PCL) ────────────────────────────────────

    public function rekapPencacah(): array
    {
        return $this->pdo->query("
            SELECT
                u.id,
                u.username,
                COUNT(sa.id)                                               AS total_sls,
                COALESCE(SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END), 0) AS selesai,
                COALESCE(SUM(CASE WHEN sa.status = 'proses'  THEN 1 ELSE 0 END), 0) AS proses,
                COALESCE(SUM(CASE WHEN sa.status = 'belum'   THEN 1 ELSE 0 END), 0) AS belum,
                COALESCE(SUM(si.kk), 0)                                   AS total_kk,
                COALESCE(SUM(si.usaha), 0)                                AS total_usaha,
                COALESCE(SUM(si.muatan), 0)                               AS total_muatan,
                GROUP_CONCAT(DISTINCT si.nmkec ORDER BY si.nmkec SEPARATOR ', ') AS kecamatan
            FROM users u
            JOIN sipw_assignment sa ON sa.pencacah_id = u.id
            JOIN sipw_import si ON si.id = sa.sipw_id
            WHERE u.status_akun = 'active' AND u.role IN ('pcl', 'admin')
            GROUP BY u.id, u.username
            ORDER BY total_sls DESC
        ")->fetchAll();
    }

    // ─── 4. REKAP PER PENGAWAS (PML) ────────────────────────────────────

    public function rekapPengawas(): array
    {
        return $this->pdo->query("
            SELECT
                u.id,
                u.username,
                COUNT(sa.id)                                               AS total_sls,
                COALESCE(SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END), 0) AS selesai,
                COALESCE(SUM(CASE WHEN sa.status = 'proses'  THEN 1 ELSE 0 END), 0) AS proses,
                COALESCE(SUM(CASE WHEN sa.status = 'belum'   THEN 1 ELSE 0 END), 0) AS belum,
                COALESCE(SUM(si.kk), 0)                                   AS total_kk,
                COALESCE(SUM(si.usaha), 0)                                AS total_usaha,
                COALESCE(SUM(si.muatan), 0)                               AS total_muatan,
                GROUP_CONCAT(DISTINCT si.nmkec ORDER BY si.nmkec SEPARATOR ', ') AS kecamatan
            FROM users u
            JOIN sipw_assignment sa ON sa.pengawas_id = u.id
            JOIN sipw_import si ON si.id = sa.sipw_id
            WHERE u.status_akun = 'active' AND u.role IN ('pml', 'admin')
            GROUP BY u.id, u.username
            ORDER BY total_sls DESC
        ")->fetchAll();
    }

    // ─── 5. FILTERED RECAP DATA (untuk export) ──────────────────────────

    public function rekapKecamatanFiltered(?string $kdkec = null): array
    {
        $params = [];
        $where = '';
        if ($kdkec) {
            $where = 'AND si.kdkec = ?';
            $params[] = $kdkec;
        }
        $stmt = $this->pdo->prepare("
            SELECT
                COALESCE(wk.nama_kecamatan, CONCAT('Kec. ', si.kdkec)) AS kecamatan,
                si.nmdesa AS desa,
                si.nmsls AS sls,
                si.nama_ketua,
                si.kk,
                si.usaha,
                si.muatan,
                COALESCE(pc.username, '-') AS pencacah,
                COALESCE(pw.username, '-') AS pengawas,
                COALESCE(sa.status, 'belum') AS status
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
            LEFT JOIN users pc ON pc.id = sa.pencacah_id
            LEFT JOIN users pw ON pw.id = sa.pengawas_id
            LEFT JOIN wilayah_kerja wk ON wk.kode_kecamatan = si.kdkec
            WHERE 1=1 {$where}
            ORDER BY wk.nama_kecamatan, si.nmdesa, si.nmsls
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ─── 6. SUMMARY FOR EXECUTIVE ──────────────────────────────────────

    public function executiveSummary(): array
    {
        $total = $this->pdo->query("
            SELECT
                COUNT(DISTINCT si.kdkec)  AS total_kec,
                COUNT(DISTINCT CONCAT(si.kdkec, si.kddesa)) AS total_desa,
                COUNT(si.id)              AS total_sls,
                COALESCE(SUM(si.muatan), 0) AS total_muatan,
                COALESCE(SUM(si.kk), 0)     AS total_kk,
                COALESCE(SUM(si.usaha), 0)  AS total_usaha
            FROM sipw_import si
        ")->fetch();

        $progress = $this->pdo->query("
            SELECT
                COALESCE(SUM(CASE WHEN sa.id IS NOT NULL THEN 1 ELSE 0 END), 0) AS assigned,
                COALESCE(SUM(CASE WHEN sa.status IN ('proses','selesai') THEN 1 ELSE 0 END), 0) AS dikerjakan,
                COALESCE(SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END), 0) AS selesai
            FROM sipw_import si
            LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
        ")->fetch();

        $cap = $this->pdo->query("
            SELECT
                COALESCE(SUM(kebutuhan_pcl), 0) AS kebutuhan_pcl,
                COALESCE(SUM(terisi_pcl), 0)    AS terisi_pcl,
                COALESCE(SUM(kebutuhan_pml), 0) AS kebutuhan_pml,
                COALESCE(SUM(terisi_pml), 0)    AS terisi_pml
            FROM wilayah_kerja
        ")->fetch();

        return array_merge($total, $progress, $cap);
    }

    public function kecamatanList(): array
    {
        return Cache::remember('kecamatan_list', 300, function (): array {
            return $this->pdo->query("
                SELECT si.kdkec, COALESCE(wk.nama_kecamatan, CONCAT('Kec. ', si.kdkec)) AS nmkec
                FROM sipw_import si
                LEFT JOIN wilayah_kerja wk ON wk.kode_kecamatan = si.kdkec
                GROUP BY si.kdkec, wk.nama_kecamatan
                ORDER BY wk.nama_kecamatan
            ")->fetchAll();
        });
    }

    // ─── 7. REKAP PER TASK FORCE ─────────────────────────────────────────

    public function rekapTaskForce(): array
    {
        return $this->pdo->query("
            SELECT
                u.id,
                u.username,
                COUNT(sa.id)                                               AS total_sls,
                COALESCE(SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END), 0) AS selesai,
                COALESCE(SUM(CASE WHEN sa.status = 'proses'  THEN 1 ELSE 0 END), 0) AS proses,
                COALESCE(SUM(CASE WHEN sa.status = 'belum'   THEN 1 ELSE 0 END), 0) AS belum,
                COALESCE(SUM(si.muatan), 0)                               AS total_muatan,
                GROUP_CONCAT(DISTINCT si.nmkec ORDER BY si.nmkec SEPARATOR ', ')     AS kecamatan
            FROM users u
            JOIN sipw_assignment sa ON sa.task_force_id = u.id
            JOIN sipw_import si ON si.id = sa.sipw_id
            WHERE u.status_akun = 'active' AND u.role = 'task_force'
            GROUP BY u.id, u.username
            ORDER BY total_sls DESC
        ")->fetchAll();
    }

    // ─── 8. PRELIST SUMMARY ──────────────────────────────────────────────

    public function prelistSummary(): array
    {
        return $this->pdo->query("
            SELECT
                COUNT(DISTINCT kd_kab)          AS total_kabkota,
                COUNT(DISTINCT kd_kec)          AS total_kecamatan,
                COUNT(DISTINCT iddesa)          AS total_desa,
                COUNT(*)                        AS total_sls,
                COALESCE(SUM(jml_kk), 0)        AS total_kk,
                COALESCE(SUM(utp), 0)           AS total_utp,
                COALESCE(SUM(muatan_rs), 0)     AS total_muatan
            FROM prelist_sls
            WHERE kd_kab = '3509'
        ")->fetch();
    }

    // ─── 9. PRELIST PER KECAMATAN ────────────────────────────────────────

    public function prelistPerKec(): array
    {
        return $this->pdo->query("
            SELECT
                ps.nm_kec                                           AS kecamatan,
                COUNT(*)                                            AS total_sls,
                COALESCE(SUM(ps.jml_kk), 0)                         AS total_kk,
                COALESCE(SUM(ps.utp), 0)                            AS total_utp,
                COALESCE(SUM(ps.muatan_rs), 0)                      AS total_muatan,
                COALESCE(SUM(ps.usaha_se2016), 0)                   AS usaha_se2016,
                COUNT(DISTINCT ps.iddesa)                           AS total_desa
            FROM prelist_sls ps
            WHERE ps.kd_kab = '3509'
            GROUP BY ps.nm_kec
            ORDER BY ps.nm_kec
        ")->fetchAll();
    }
}
