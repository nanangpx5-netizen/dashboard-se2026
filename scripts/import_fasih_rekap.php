<?php

declare(strict_types=1);

use App\Core\Database;

$script = basename(__FILE__);
$execute = in_array('--execute', $argv);
$dryRun = ! $execute;

if ($dryRun) {
    echo "Dry-run: Akan memperbarui `prelist_sls` dan `sipw_import` dengan kolom FASIH assignment.\n";
    echo "Jalankan: php {$script} --execute\n\n";
    exit(0);
}

$db = Database::instance()->pdo();

// Matikan autocommit untuk transaksi atomic
$db->beginTransaction();

try {
    // --- Prelist_SLS (1 row per SLS) ---
    echo "Memperbarui prelist_sls dari Rekap_Jember.xlsx...\n";
    $stmt = $db->prepare("""
        UPDATE prelist_sls ps
        JOIN (
            SELECT
                SUBSTR(idsls, 1, 14) AS kd_sls,
                SUM(COALESCE(NULLIF(REKAP_COL_22, ''), 0)) AS total_fasih,
                SUM(COALESCE(NULLIF(REKAP_COL_25, ''), 0)) AS fasih_kk,
                SUM(COALESCE(NULLIF(REKAP_COL_26, ''), 0)) AS fasih_umk,
                SUM(COALESCE(NULLIF(REKAP_COL_27, ''), 0)) AS fasih_um,
                SUM(COALESCE(NULLIF(REKAP_COL_28, ''), 0)) AS fasih_ub,
                SUM(COALESCE(NULLIF(REKAP_COL_29, ''), 0)) AS fasih_bangunan,
                COALESCE(NULLIF(REKAP_COL_15, ''), '') AS dominan,
                COALESCE(NULLIF(REKAP_COL_30, ''), 0) AS flag_open_pbi,
                COALESCE(NULLIF(REKAP_COL_31, ''), 0) AS kk_open_pbi
            FROM data_r keuangan_rekap_jember
            WHERE SUBSTR(idsls, 1, 14) IS NOT NULL
            GROUP BY SUBSTR(idsls, 1, 14)
        ) AS src ON ps.idsls = src.kd_sls
        SET
            ps.total_fasih = src.total_fasih,
            ps.fasih_kk    = src.fasih_kk,
            ps.fasih_umk   = src.fasih_umk,
            ps.fasih_um    = src.fasih_um,
            ps.fasih_ub    = src.fasih_ub,
            ps.fasih_bangunan = src.fasih_bangunan,
            ps.dominan     = src.dominan,
            ps.flag_open_pbi = src.flag_open_pbi,
            ps.kk_open_pbi    = src.kk_open_pbi;
    """);

    $stmt->execute();
    echo "Diperbarui " . $stmt->rowCount() . " baris di prelist_sls\n";

    // --- Sipw_Import (1 row per subsls) ---
    echo "Memperbarui sipw_import dari Rekap_Jember.xlsx...\n";
    $stmt2 = $db->prepare("""
        UPDATE sipw_import si
        JOIN (
            SELECT
                SUBSTR(idsubsls, 1, 16) AS kdsls,
                SUM(COALESCE(NULLIF(REKAP_COL_22, ''), 0)) AS total_fasih,
                SUM(COALESCE(NULLIF(REKAP_COL_25, ''), 0)) AS fasih_kk,
                SUM(COALESCE(NULLIF(REKAP_COL_26, ''), 0)) AS fasih_umk,
                SUM(COALESCE(NULLIF(REKAP_COL_27, ''), 0)) AS fasih_um,
                SUM(COALESCE(NULLIF(REKAP_COL_28, ''), 0)) AS fasih_ub,
                SUM(COALESCE(NULLIF(REKAP_COL_29, ''), 0)) AS fasih_bangunan,
                COALESCE(NULLIF(REKAP_COL_15, ''), '') AS dominan,
                COALESCE(NULLIF(REKAP_COL_30, ''), 0) AS flag_open_pbi,
                COALESCE(NULLIF(REKAP_COL_31, ''), 0) AS kk_open_pbi
            FROM data_r keuangan_rekap_jember
            WHERE SUBSTR(idsubsls, 1, 16) IS NOT NULL
            GROUP BY SUBSTR(idsubsls, 1, 16)
        ) AS src ON si.idsubsls = src.kdsls
        SET
            si.total_fasih = src.total_fasih,
            si.fasih_kk    = src.fasih_kk,
            si.fasih_umk   = src.fasih_umk,
            si.fasih_um    = src.fasih_um,
            si.fasih_ub    = src.fasih_ub,
            si.fasih_bangunan = src.fasih_bangunan,
            si.dominan     = src.dominan,
            si.flag_open_pbi = src.flag_open_pbi,
            si.kk_open_pbi    = src.kk_open_pbi;
    """);

    $stmt2->execute();
    echo "Diperbarui " . $stmt2->rowCount() . " baris di sipw_import\n";

    $db->commit();
    echo "Selesai. Semua kolom FASIH assignment sudah diperbarui.\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
