<?php

namespace App\Models;

use App\Core\Database;

class PrelistModel
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::instance()->pdo();
    }

    public function getKpiJatim(): array
    {
        $row = $this->pdo->query("
            SELECT
                COALESCE(SUM(se2016),0)      AS total_se2016,
                COALESCE(SUM(jml_kk),0)      AS total_kk,
                COALESCE(SUM(utp),0)         AS total_utp,
                COALESCE(SUM(subsektor),0)   AS total_subsektor,
                COALESCE(SUM(ub),0)          AS total_ub,
                COALESCE(SUM(um),0)          AS total_um,
                COALESCE(SUM(umk),0)         AS total_umk,
                COALESCE(SUM(ub+um+umk),0)   AS total_usaha,
                COALESCE(SUM(n_sls),0)       AS total_sls,
                COALESCE(SUM(ppl),0)         AS total_ppl,
                COALESCE(SUM(pml),0)         AS total_pml
            FROM prelist_kabkota
        ")->fetch();

        return $row ?: [];
    }

    public function getKomposisiUsahaPerKab(): array
    {
        return $this->pdo->query("
            SELECT nm_kabkota, ub, um, umk, (ub+um+umk) AS total
            FROM prelist_kabkota
            ORDER BY total DESC
        ")->fetchAll();
    }

    public function getPerbandinganSe2016(): array
    {
        return $this->pdo->query("
            SELECT nm_kabkota, se2016,
                   (ub+um+umk) AS se2026,
                   ROUND(((ub+um+umk)-se2016)/NULLIF(se2016,0)*100, 1) AS pct_growth
            FROM prelist_kabkota
            ORDER BY pct_growth DESC
        ")->fetchAll();
    }

    public function getBebanKerjaKecamatan(string $kdKab = '3509'): array
    {
        $stmt = $this->pdo->prepare("
            SELECT nm_kec, muatan_rs, jml_kk, utp, subsektor,
                   (jml_kk+utp+subsektor) AS total_beban,
                   ppl, pml
            FROM prelist_kecamatan
            WHERE kd_kab = ?
            ORDER BY muatan_rs DESC
        ");
        $stmt->execute([$kdKab]);
        return $stmt->fetchAll();
    }

    public function getKecamatanByKab(string $kdKab): array
    {
        $stmt = $this->pdo->prepare("
            SELECT kd_kec, nm_kec, sbr, se2016, rtup, utp,
                   subsektor, jml_kk, wilkerstat, muatan_rs, ppl, pml,
                   ROUND(muatan_rs/NULLIF(ppl,0),0) AS beban_per_ppl
            FROM prelist_kecamatan
            WHERE kd_kab = ?
            ORDER BY nm_kec ASC
        ");
        $stmt->execute([$kdKab]);
        return $stmt->fetchAll();
    }

    public function getWorkloadStats(string $kdKab = '3509'): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*)                        AS total_kecamatan,
                COALESCE(SUM(muatan_rs),0)      AS total_muatan,
                COALESCE(SUM(ppl),0)            AS total_ppl,
                COALESCE(ROUND(AVG(muatan_rs),0),0) AS avg_muatan_kec,
                COALESCE(MAX(muatan_rs),0)      AS max_muatan,
                COALESCE(MIN(muatan_rs),0)      AS min_muatan,
                COALESCE(ROUND(SUM(muatan_rs)/NULLIF(SUM(ppl),0),0),0) AS avg_beban_per_ppl
            FROM prelist_kecamatan
            WHERE kd_kab = ?
        ");
        $stmt->execute([$kdKab]);
        return $stmt->fetch() ?: [];
    }

    public function isImported(): bool
    {
        try {
            return (int) $this->pdo->query("SELECT COUNT(*) FROM prelist_kabkota")->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getImportStatus(): array
    {
        $tables = ['prelist_kabkota', 'prelist_kecamatan', 'prelist_sls'];
        $result = [];
        foreach ($tables as $t) {
            try {
                $result[$t] = (int) $this->pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
            } catch (\Throwable) {
                $result[$t] = 0;
            }
        }
        return $result;
    }

    public function getSlsByKecamatan(string $kdKec, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT idsls, nm_desa, nama_sls, sbr, utp, jml_kk, muatan_rs, ppl_id
            FROM prelist_sls
            WHERE kd_kec = ?
            ORDER BY nm_desa, nama_sls
            LIMIT ?
        ");
        $stmt->execute([$kdKec, $limit]);
        return $stmt->fetchAll();
    }
}
