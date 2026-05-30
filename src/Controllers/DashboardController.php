<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Helpers\Cache;
use App\Models\PrelistModel;

class DashboardController extends Controller
{
    private ?PrelistModel $prelist = null;

    private function prelist(): PrelistModel
    {
        if ($this->prelist === null) {
            $this->prelist = new PrelistModel();
        }
        return $this->prelist;
    }

    public function index(): void
    {
        $pdo = Database::instance()->pdo();

        $stats = $this->getStats($pdo);
        $wilayahData = $this->getWilayahData($pdo);
        $bebanPencacah = $this->bebanPencacah($pdo);

        // Prelist SE2026 data
        $prelistImported = $this->prelist()->isImported();
        $prelistKpi = [];
        $prelistKomposisi = [];
        $prelistPerbandingan = [];
        $prelistAnomali = [];
        $prelistAnomaliSls = [];
        $prelistAnomaliSummary = [];
        $prelistMapKec = [];
        if ($prelistImported) {
            $prelistKpi = $this->prelist()->getKpiJatim();
            $prelistKomposisi = $this->prelist()->getKomposisiUsahaPerKab();
            $prelistPerbandingan = $this->prelist()->getPerbandinganSe2016();
            $prelistAnomali = $this->prelist()->getAnomaliKecamatan('3509');
            $prelistAnomaliSls = $this->prelist()->getAnomaliSls('3509');
            $prelistAnomaliSummary = $this->prelist()->getAnomaliSummary('3509');
            $prelistMapKec = $this->prelist()->getMapKecamatan('3509');
        }

        // Kecamatan filter
        $kdkecFilter = $_GET['kdkec'] ?? '';
        $kecamatanList = $this->getKecamatanList($pdo);
        $perbandingan = null;
        $duplicateInfo = null;

        if ($kdkecFilter) {
            $perbandingan = $this->getPerbandingan($pdo, $kdkecFilter);
        }

        $this->data['page_title'] = 'Dashboard SE2026';
        $this->render('dashboard/index', [
            'stats'               => $stats,
            'muatan_per_kec'      => $wilayahData,
            'beban_pencacah'      => $bebanPencacah,
            'progress_wilayah'    => $wilayahData,
            'kecamatan_list'      => $kecamatanList,
            'kdkec_filter'        => $kdkecFilter,
            'perbandingan'        => $perbandingan,
            'prelist_imported'    => $prelistImported,
            'prelist_kpi'         => $prelistKpi,
            'prelist_komposisi'   => $prelistKomposisi,
            'prelist_perbandingan'  => $prelistPerbandingan,
            'prelist_anomali'       => $prelistAnomali,
            'prelist_anomali_sls'   => $prelistAnomaliSls,
            'prelist_anomali_summary' => $prelistAnomaliSummary,
            'prelist_map_kec'       => $prelistMapKec,
            'js'                    => ['dashboard', 'dashboard-map'],
        ]);
    }

    private function getStats(\PDO $pdo): array
    {
        return Cache::remember('dashboard_stats', 60, function () use ($pdo): array {
            $sipw = $pdo->query("
                SELECT
                    COUNT(DISTINCT kdkec)                         AS total_kecamatan,
                    COUNT(DISTINCT CONCAT(kdkec, kddesa))         AS total_desa,
                    COUNT(DISTINCT idsubsls)                      AS total_sls,
                    COALESCE(SUM(COALESCE(kk,0)),0)               AS total_kk,
                    COALESCE(SUM(COALESCE(usaha,0)),0)            AS total_usaha,
                    COALESCE(SUM(COALESCE(muatan,0)),0)           AS total_muatan,
                    COALESCE(SUM(COALESCE(btt,0)),0)              AS total_bstt,
                    COALESCE(SUM(COALESCE(bbtt_nonusaha,0)),0)    AS total_bsbtt,
                    COALESCE(SUM(COALESCE(bttk,0)),0)             AS total_bsttk,
                    COALESCE(SUM(COALESCE(bku,0)),0)              AS total_bku,
                    COALESCE(SUM(CASE WHEN klas=1 THEN 1 ELSE 0 END),0) AS total_sls_urban,
                    COALESCE(SUM(CASE WHEN klas=2 THEN 1 ELSE 0 END),0) AS total_sls_rural
                FROM sipw_import
            ")->fetch();

            $roleCounts = $pdo->query("
                SELECT role, COUNT(*) AS total
                FROM users
                WHERE status_akun = 'active' AND role IN ('pcl','pml','task_force')
                GROUP BY role
            ")->fetchAll();

            $sipw['total_pencacah']    = 0;
            $sipw['total_pengawas']    = 0;
            $sipw['total_task_force']  = 0;
            foreach ($roleCounts as $r) {
                $key = match ($r['role']) {
                    'pcl'        => 'total_pencacah',
                    'pml'        => 'total_pengawas',
                    'task_force' => 'total_task_force',
                };
                $sipw[$key] = (int) $r['total'];
            }

            return $sipw;
        });
    }

    private function getWilayahData(\PDO $pdo): array
    {
        return Cache::remember('dashboard_wilayah', 60, function () use ($pdo): array {
            return $pdo->query("
                SELECT
                    COALESCE(mfd.nama_kecamatan, CONCAT('Kec. ', si.kdkec)) AS label,
                    si.kdkec,
                    COUNT(si.id)                            AS total_sls,
                    COALESCE(SUM(si.kk), 0)                 AS total_kk,
                    COALESCE(SUM(si.usaha), 0)              AS total_usaha,
                    COALESCE(SUM(si.muatan), 0)             AS total_muatan,
                    COALESCE(SUM(si.btt), 0)                AS total_btt,
                    COALESCE(SUM(si.bku), 0)                AS total_bku,
                    COALESCE(SUM(si.bbtt_nonusaha), 0)      AS total_bsbtt,
                    COALESCE(SUM(si.bttk), 0)               AS total_bsttk,
                    COALESCE(SUM(CASE WHEN si.klas=1 THEN 1 ELSE 0 END), 0) AS sls_urban,
                    COALESCE(SUM(CASE WHEN si.klas=2 THEN 1 ELSE 0 END), 0) AS sls_rural,
                    COALESCE(SUM(CASE WHEN sa.status='proses'  THEN 1 ELSE 0 END), 0) AS proses,
                    COALESCE(SUM(CASE WHEN sa.status='selesai' THEN 1 ELSE 0 END), 0) AS selesai
                FROM sipw_import si
                LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
                LEFT JOIN mfd_kec mfd ON mfd.kode_kecamatan = CONCAT(SUBSTRING(si.kdprov, 1, 2), SUBSTRING(si.kdkab, 1, 2), si.kdkec)
                GROUP BY si.kdkec, mfd.urutan, mfd.nama_kecamatan
                ORDER BY mfd.urutan
            ")->fetchAll();
        });
    }

    private function bebanPencacah(\PDO $pdo): array
    {
        return Cache::remember('dashboard_beban', 60, function () use ($pdo): array {
            return $pdo->query("
                SELECT
                    u.id,
                    u.username,
                    u.role,
                    COUNT(sa.id) AS total_assign,
                    SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END) AS selesai,
                    SUM(CASE WHEN sa.status = 'proses'  THEN 1 ELSE 0 END) AS proses,
                    SUM(CASE WHEN sa.status = 'belum'   THEN 1 ELSE 0 END) AS belum
                FROM users u
                INNER JOIN sipw_assignment sa ON sa.pencacah_id = u.id
                WHERE u.status_akun = 'active'
                  AND u.role IN ('pcl', 'admin', 'operator')
                GROUP BY u.id, u.username, u.role
                ORDER BY total_assign DESC
                LIMIT 15
            ")->fetchAll();
        });
    }

    private function getKecamatanList(\PDO $pdo): array
    {
        return Cache::remember('dashboard_kecamatan_list', 300, function () use ($pdo): array {
            return $pdo->query("
                SELECT DISTINCT si.kdkec, si.nmkec
                FROM sipw_import si
                ORDER BY si.nmkec
            ")->fetchAll();
        });
    }

    /**
     * Bandingkan master_sls (referensi) vs sipw_import (database) per kecamatan
     */
    private function getPerbandingan(\PDO $pdo, string $kdkec): array
    {
        $nmKec = $pdo->prepare("SELECT DISTINCT si.nmkec FROM sipw_import si WHERE si.kdkec = ?");
        $nmKec->execute([$kdkec]);
        $nmKec = $nmKec->fetchColumn();

        if (!$nmKec) {
            return [
                'kdkec'        => $kdkec,
                'nmkec'        => 'Tidak ditemukan',
                'master_count' => 0,
                'sipw_count'   => 0,
                'selisih'      => 0,
                'missing'      => [],
                'dup_kode'     => 0,
                'dup_rows'     => 0,
            ];
        }

        $masterSt = $pdo->prepare("SELECT COUNT(*) FROM master_sls WHERE kecamatan = ?");
        $masterSt->execute([$nmKec]);
        $masterCount = (int) $masterSt->fetchColumn();

        $sipwSt = $pdo->prepare("SELECT COUNT(*) FROM sipw_import WHERE kdkec = ?");
        $sipwSt->execute([$kdkec]);
        $sipwCount = (int) $sipwSt->fetchColumn();

        // JOIN langsung menggunakan idsubsls (16 digit) — master_sls.kode = sipw_import.idsubsls
        $missing = [];
        if ($masterCount > 0 && $masterCount !== $sipwCount) {
            $missSt = $pdo->prepare("
                SELECT ms.kode, ms.sls, ms.desa
                FROM master_sls ms
                LEFT JOIN sipw_import si ON si.idsubsls = ms.kode
                WHERE ms.kecamatan = ? AND si.id IS NULL
                LIMIT 50
            ");
            $missSt->execute([$nmKec]);
            $missing = $missSt->fetchAll();
        }

        return [
            'kdkec'        => $kdkec,
            'nmkec'        => $nmKec,
            'master_count' => $masterCount,
            'sipw_count'   => $sipwCount,
            'selisih'      => $masterCount - $sipwCount,
            'missing'      => $missing,
            'dup_kode'     => 0,
            'dup_rows'     => 0,
        ];
    }
}
