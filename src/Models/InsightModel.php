<?php

namespace App\Models;

use App\Core\Database;

/**
 * InsightModel — Halaman Insight & Analisa
 *
 * Aggregasi data lintas tabel (sipw_import, prelist_*, users, sipw_assignment)
 * untuk mendukung widget dashboard analitik. Semua JOIN sudah menggunakan
 * COLLATE utf8mb4_unicode_ci untuk kompatibilitas dengan collation tabel sipw_*.
 */
class InsightModel
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::instance()->pdo();
    }

    /**
     * Executive summary — KPI utama lintas semua sumber data
     * Menggunakan 3 query (sipw_import, prelist, assignment) bukan 11 subquery.
     */
    public function getExecutiveSummary(): array
    {
        // 1) Aggregate sipw_import in one pass + add index-sargable WHERE
        $stmt = $this->pdo->query("
            SELECT
                COUNT(*)                      AS total_sls,
                COUNT(DISTINCT kdkec)         AS total_kec,
                COALESCE(SUM(kk), 0)          AS total_kk,
                COALESCE(SUM(muatan), 0)      AS total_muatan,
                COALESCE(SUM(btt), 0)         AS total_btt,
                COALESCE(SUM(bku), 0)         AS total_bku,
                COALESCE(SUM(usaha), 0)       AS total_usaha
            FROM sipw_import
            WHERE kdkab = '09'
        ");
        $row = $stmt->fetch();
        if (!$row) $row = [];

        // 2) Prelist counts
        $pl = $this->pdo->query("
            SELECT
                (SELECT COUNT(*) FROM prelist_sls WHERE kd_kab = '3509') AS prelist_sls,
                (SELECT COUNT(*) FROM prelist_kecamatan WHERE kd_kab = '3509') AS prelist_kec
        ")->fetch();
        if ($pl) {
            $row['prelist_sls'] = (int) $pl['prelist_sls'];
            $row['prelist_kec'] = (int) $pl['prelist_kec'];
        } else {
            $row['prelist_sls'] = 0;
            $row['prelist_kec'] = 0;
        }

        // 3) Assignment counts
        $sa = $this->pdo->query("
            SELECT
                COUNT(*) AS total_assignment,
                COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) AS active_assignment
            FROM sipw_assignment
        ")->fetch();
        if ($sa) {
            $row['total_assignment']  = (int) $sa['total_assignment'];
            $row['active_assignment'] = (int) $sa['active_assignment'];
        } else {
            $row['total_assignment']  = 0;
            $row['active_assignment'] = 0;
        }

        $totalSls = (int) ($row['total_sls'] ?? 0);
        $row['coverage_pct']         = $totalSls > 0 ? round(($row['active_assignment'] / $totalSls) * 100, 1) : 0;
        $row['assignment_pct']       = $totalSls > 0 ? round(($row['total_assignment'] / $totalSls) * 100, 1) : 0;
        $row['delta_sls_vs_prelist'] = $totalSls - (int) ($row['prelist_sls'] ?? 0);
        $row['avg_muatan']           = $totalSls > 0 ? round(($row['total_muatan'] ?? 0) / $totalSls, 1) : 0;
        $row['avg_kk']               = $totalSls > 0 ? round(($row['total_kk'] ?? 0) / $totalSls, 1) : 0;
        $row['avg_btt']              = $totalSls > 0 ? round(($row['total_btt'] ?? 0) / $totalSls, 1) : 0;

        return $row;
    }

    /**
     * Anomali per kecamatan: SLS dengan muatan=0, kk=0, muatan tinggi
     */
    public function getAnomaliPerKecamatan(string $kdKab = '09'): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                kdkec, nmkec,
                COUNT(*)                                                  AS sls,
                SUM(CASE WHEN muatan = 0 THEN 1 ELSE 0 END)               AS mu_zero,
                SUM(CASE WHEN muatan < 10 THEN 1 ELSE 0 END)              AS mu_low,
                SUM(CASE WHEN kk = 0 THEN 1 ELSE 0 END)                   AS kk_zero,
                SUM(CASE WHEN muatan > 200 THEN 1 ELSE 0 END)             AS mu_extreme,
                ROUND(AVG(muatan), 1)                                     AS avg_mu,
                MIN(muatan)                                               AS min_mu,
                MAX(muatan)                                               AS max_mu,
                ROUND(STDDEV(muatan), 1)                                  AS std_mu,
                COALESCE(ROUND(SUM(CASE WHEN muatan = 0 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*),0), 1), 0) AS anomali_pct
            FROM sipw_import
            WHERE kdkab = ?
            GROUP BY kdkec, nmkec
            ORDER BY mu_zero DESC, kk_zero DESC
        ");
        $stmt->execute([$kdKab]);
        return $stmt->fetchAll();
    }

    /**
     * Beban kerja per kecamatan (avg + std) — untuk identifikasi kecamatan
     * dengan variabilitas tinggi (kemungkinan SLS non-rutin)
     */
    public function getBebanKerjaPerKecamatan(string $kdKab = '09'): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                kdkec, nmkec,
                COUNT(*)                      AS sls,
                ROUND(AVG(muatan), 1)         AS avg_mu,
                ROUND(STDDEV(muatan), 1)      AS std_mu,
                ROUND(AVG(kk), 1)             AS avg_kk,
                ROUND(AVG(btt), 1)            AS avg_btt,
                ROUND(AVG(bku), 1)            AS avg_bku,
                ROUND(AVG(usaha), 1)          AS avg_usaha,
                COALESCE(SUM(muatan),0)       AS total_muatan,
                COALESCE(SUM(kk),0)           AS total_kk,
                CASE
                    WHEN AVG(muatan) < 50 THEN 'RINGAN'
                    WHEN AVG(muatan) < 100 THEN 'SEDANG'
                    ELSE 'BERAT'
                END AS kategori_beban
            FROM sipw_import
            WHERE kdkab = ?
            GROUP BY kdkec, nmkec
            ORDER BY avg_mu DESC
        ");
        $stmt->execute([$kdKab]);
        return $stmt->fetchAll();
    }

    /**
     * Distribusi muatan (histogram) — untuk memahami sebaran beban SLS
     */
    public function getDistribusiMuatan(string $kdKab = '09'): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                CASE
                    WHEN muatan = 0      THEN '0'
                    WHEN muatan BETWEEN 1 AND 10   THEN '1-10'
                    WHEN muatan BETWEEN 11 AND 30  THEN '11-30'
                    WHEN muatan BETWEEN 31 AND 60  THEN '31-60'
                    WHEN muatan BETWEEN 61 AND 100 THEN '61-100'
                    WHEN muatan BETWEEN 101 AND 150 THEN '101-150'
                    WHEN muatan BETWEEN 151 AND 200 THEN '151-200'
                    WHEN muatan > 200              THEN '>200'
                END AS bin,
                CASE
                    WHEN muatan = 0      THEN 0
                    WHEN muatan BETWEEN 1 AND 10   THEN 1
                    WHEN muatan BETWEEN 11 AND 30  THEN 2
                    WHEN muatan BETWEEN 31 AND 60  THEN 3
                    WHEN muatan BETWEEN 61 AND 100 THEN 4
                    WHEN muatan BETWEEN 101 AND 150 THEN 5
                    WHEN muatan BETWEEN 151 AND 200 THEN 6
                    WHEN muatan > 200              THEN 7
                END AS bin_order,
                COUNT(*) AS sls,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM sipw_import WHERE kdkab = ?), 1) AS pct
            FROM sipw_import
            WHERE kdkab = ?
            GROUP BY bin, bin_order
            ORDER BY bin_order
        ");
        $stmt->execute([$kdKab, $kdKab]);
        return $stmt->fetchAll();
    }

    /**
     * Coverage gap — perbandingan prelist vs actual
     * JOIN aman setelah patch_006: semua tabel sudah utf8mb4_unicode_ci
     */
    public function getCoverageGap(string $kdKab = '3509'): array
    {
        $sql = "
            SELECT
                pk.kd_kec,
                pk.nm_kec,
                COALESCE(sip.cnt, 0) AS sipw_sls,
                COALESCE(sip.mu, 0)  AS sipw_muatan,
                pk.muatan_rs         AS prelist_muatan,
                pk.subsektor         AS prelist_subsektor,
                (COALESCE(sip.mu,0) - pk.muatan_rs) AS diff_muatan
            FROM prelist_kecamatan pk
            LEFT JOIN (
                SELECT kdkec,
                       COUNT(*) AS cnt,
                       SUM(muatan) AS mu
                FROM sipw_import
                WHERE kdkab = '09'
                GROUP BY kdkec
            ) sip ON sip.kdkec = SUBSTRING(pk.kd_kec, 5)
            WHERE pk.kd_kab = ?
            ORDER BY pk.kd_kec
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kdKab]);
        return $stmt->fetchAll();
    }

    /**
     * Rekomendasi otomatis — di-generate dari rules sederhana
     */
    public function getRekomendasi(array $summary, array $anomali, array $beban): array
    {
        $rekomendasi = [];

        if ($summary['delta_sls_vs_prelist'] > 0) {
            $rekomendasi[] = [
                'level' => 'warning',
                'icon'  => 'fa-exclamation-triangle',
                'title' => 'Selisih ' . number_format($summary['delta_sls_vs_prelist']) . ' SLS',
                'desc'  => 'Terdapat ' . number_format($summary['delta_sls_vs_prelist']) . ' SLS di SIPW yang tidak ada di prelist. Periksa apakah SLS non-prelist atau data prelist perlu di-update.',
            ];
        }

        $anomaliCount = 0;
        $topAnomaliKec = '';
        $maxAnomali = 0;
        foreach ($anomali as $row) {
            $count = (int) $row['mu_zero'] + (int) $row['kk_zero'];
            if ($count > $maxAnomali) {
                $maxAnomali = $count;
                $topAnomaliKec = $row['nmkec'];
            }
            $anomaliCount += (int) $row['mu_zero'];
        }
        if ($anomaliCount > 0) {
            $rekomendasi[] = [
                'level' => 'danger',
                'icon'  => 'fa-bug',
                'title' => number_format($anomaliCount) . ' SLS dengan muatan=0',
                'desc'  => "Kecamatan paling banyak anomali: <strong>{$topAnomaliKec}</strong>. Investigasi apakah SLS benar-benar kosong atau ada masalah input data.",
            ];
        }

        if (($summary['total_assignment'] ?? 0) == 0) {
            $rekomendasi[] = [
                'level' => 'info',
                'icon'  => 'fa-user-plus',
                'title' => 'Belum ada assignment',
                'desc'  => '0 PCL/PML yang di-assign ke ' . number_format($summary['total_sls']) . ' SLS. Gunakan halaman Assignment untuk mendistribusikan petugas.',
            ];
        }

        $beratCount = 0;
        $beratKecs = [];
        foreach ($beban as $row) {
            if ($row['kategori_beban'] === 'BERAT') {
                $beratCount++;
                $beratKecs[] = $row['nmkec'];
            }
        }
        if ($beratCount > 0) {
            $rekomendasi[] = [
                'level' => 'warning',
                'icon'  => 'fa-weight-hanging',
                'title' => $beratCount . ' kecamatan dengan beban BERAT',
                'desc'  => 'Kecamatan dengan rata-rata muatan > 100: ' . implode(', ', array_slice($beratKecs, 0, 5)) . ($beratCount > 5 ? '...' : '') . '. Pertimbangkan penambahan PCL.',
            ];
        }

        $kks = (int) $summary['total_kk'];
        $muatans = (int) $summary['total_muatan'];
        $rasio = $kks > 0 ? round($muatans / $kks, 3) : 0;
        $rekomendasi[] = [
            'level' => $rasio > 0 && $rasio < 0.5 ? 'warning' : 'success',
            'icon'  => 'fa-balance-scale',
            'title' => 'Rasio muatan/KK = ' . $rasio,
            'desc'  => "Setiap KK diharapkan punya ~1 muatan. Rasio saat ini: {$rasio}. " .
                       ($rasio < 0.5 ? 'Kemungkinan ada KK yang belum terdata atau SLS belum lengkap.' : 'Konsisten dengan ekspektasi.'),
        ];

        return $rekomendasi;
    }

    /**
     * Delta SLS detail — SLS dengan multiple sub-SLS (multi-sektor).
     * Menampilkan per kecamatan dan per SLS breakdown.
     */
    public function getDeltaSlsDetail(): array
    {
        return $this->pdo->query("
            SELECT
                kdkec, nmkec,
                COUNT(*)                                                   AS total_baris,
                COUNT(DISTINCT SUBSTRING(idsubsls,1,14))                   AS sls_unik,
                COUNT(DISTINCT idsubsls) - COUNT(DISTINCT SUBSTRING(idsubsls,1,14)) AS sls_extra
            FROM sipw_import
            WHERE kdkab = '09'
            GROUP BY kdkec, nmkec
            HAVING sls_extra > 0
            ORDER BY sls_extra DESC, sls_unik DESC
        ")->fetchAll();
    }

    /**
     * Detail sub-SLS per SLS — menampilkan SLS yang memiliki lebih dari 1 sub-SLS.
     * Dilengkapi flag kualitas untuk membedakan sub-SLS sah vs error parsing.
     */
    public function getDeltaSlsDetailSls(?string $kdkec = null): array
    {
        $where = "kdkab = '09'";
        $params = [];
        if ($kdkec !== null && $kdkec !== '') {
            $where .= " AND kdkec = ?";
            $params[] = $kdkec;
        }
        $sql = "
            SELECT
                SUBSTRING(idsubsls,1,14) AS idsls,
                nmdesa, nmsls, nmkec, kdkec,
                COUNT(*)                                     AS total_baris,
                GROUP_CONCAT(idsubsls ORDER BY idsubsls SEPARATOR ', ') AS daftar_idsubsls,
                GROUP_CONCAT(CONCAT('id=',id) ORDER BY idsubsls SEPARATOR '; ') AS daftar_id,
                COALESCE(SUM(kk),0)                          AS total_kk,
                COALESCE(SUM(muatan),0)                      AS total_muatan,
                COALESCE(SUM(btt),0)                         AS total_btt,
                COALESCE(SUM(usaha),0)                       AS total_usaha,
                COUNT(CASE WHEN kk = 0 THEN 1 END)           AS sub_kk_zero,
                COUNT(CASE WHEN muatan = bku AND bku = usaha AND btt = 0 THEN 1 END) AS sub_identical
            FROM sipw_import
            WHERE {$where}
            GROUP BY idsls, nmkec, kdkec, nmdesa, nmsls
            HAVING total_baris > 1
            ORDER BY total_baris DESC, nmkec, nmdesa
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Detail baris sub-SLS untuk SLS tertentu — untuk tabel expand.
     */
    public function getDeltaSlsRows(string $idsls): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, idsubsls, RIGHT(idsubsls,2) AS sub_ke, kk, muatan, btt, bku, usaha,
                   CASE WHEN kk = 0 THEN 1 ELSE 0 END AS flag_kk_zero,
                   CASE WHEN muatan = bku AND bku = usaha AND btt = 0 THEN 1 ELSE 0 END AS flag_identical
            FROM sipw_import
            WHERE kdkab = '09' AND SUBSTRING(idsubsls,1,14) = ?
            ORDER BY idsubsls
        ");
        $stmt->execute([$idsls]);
        return $stmt->fetchAll();
    }

    /**
     * Kecamatan list yang memiliki multi-sub-SLS — untuk dropdown filter
     */
    public function getDeltaKecamatanList(): array
    {
        return $this->pdo->query("
            SELECT DISTINCT kdkec, nmkec
            FROM sipw_import
            WHERE kdkab = '09'
              AND SUBSTRING(idsubsls,1,14) IN (
                  SELECT idsls FROM (
                      SELECT SUBSTRING(idsubsls,1,14) AS idsls
                      FROM sipw_import
                      WHERE kdkab = '09'
                      GROUP BY idsls
                      HAVING COUNT(*) > 1
                  ) AS dup
              )
            ORDER BY nmkec
        ")->fetchAll();
    }

    /**
     * Top SLS anomali (detail list, untuk tabel alert)
     */
    public function getTopSlsAnomali(string $kdKab = '09', int $limit = 50, string $type = 'muatan_zero'): array
    {
        $order = match ($type) {
            'muatan_zero'   => 'ORDER BY muatan ASC, kk DESC',
            'kk_zero'       => 'ORDER BY kk ASC, muatan DESC',
            'muatan_extreme'=> 'ORDER BY muatan DESC',
            default         => 'ORDER BY muatan ASC',
        };

        $sql = "
            SELECT kdkec, nmkec, idsubsls, nmsls, kk, muatan, btt, bku, usaha,
                   (kk + muatan + btt + bku + usaha) AS total_isian
            FROM sipw_import
            WHERE kdkab = ?
            {$order}
            LIMIT {$limit}
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$kdKab]);
        return $stmt->fetchAll();
    }

    /**
     * User pool — ketersediaan PCL/PML/Task Force untuk assignment
     */
    public function getUserPool(): array
    {
        return $this->pdo->query("
            SELECT
                role,
                status_akun,
                COUNT(*) AS jumlah
            FROM users
            WHERE role IN ('pcl','pml','task_force')
            GROUP BY role, status_akun
            ORDER BY role, status_akun
        ")->fetchAll();
    }

    /**
     * Data quality indicators
     */
    public function getDataQuality(): array
    {
        $result = [];

        $rows = $this->pdo->query("
            SELECT TABLE_NAME, TABLE_COLLATION
            FROM information_schema.tables
            WHERE TABLE_SCHEMA = DATABASE()
              AND (TABLE_NAME LIKE 'prelist_%' OR TABLE_NAME LIKE 'sipw_%')
            ORDER BY TABLE_NAME
        ")->fetchAll();
        $collations = [];
        foreach ($rows as $r) {
            $collations[$r['TABLE_NAME']] = $r['TABLE_COLLATION'];
        }
        $result['table_collations'] = $collations;
        $result['collation_consistent'] = count(array_unique($collations)) <= 1;

        $result['last_import'] = $this->pdo->query("
            SELECT MAX(imported_at) AS last_at, COUNT(DISTINCT DATE(imported_at)) AS days
            FROM prelist_kecamatan
        ")->fetch();

        $result['assignment_count'] = (int) $this->pdo->query("SELECT COUNT(*) FROM sipw_assignment")->fetchColumn();
        $result['user_count_active'] = (int) $this->pdo->query("SELECT COUNT(*) FROM users WHERE status_akun = 'active'")->fetchColumn();
        $result['activity_today'] = (int) $this->pdo->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();

        $emptyTables = $this->pdo->query("
            SELECT TABLE_NAME, TABLE_ROWS
            FROM information_schema.tables
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_ROWS = 0
              AND TABLE_NAME NOT IN (
                  'migrations','failed_jobs','password_resets','personal_access_tokens',
                  'dash_rollback_points','dash_rollback_points_archive'
              )
        ")->fetchAll();
        $result['empty_tables'] = array_column($emptyTables, 'TABLE_NAME');

        return $result;
    }
}
