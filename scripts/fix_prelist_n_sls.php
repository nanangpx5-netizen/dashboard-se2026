<?php
/**
 * scripts/fix_prelist_n_sls.php
 *
 * Fix inflated n_sls column di prelist_kabkota.
 * Source data: COUNT(*) dari prelist_sls aktual (bukan SUM(n_sls) yg stale).
 *
 * Temuan (Jun 2026):
 *   prelist_kabkota.n_sls total = 250,494
 *   prelist_sls row count       = 234,180
 *   Selisih = 16,314 SLS (6,5% overstated)
 *   - Bojonegoro: 9,327 vs 2,000 (78,6% error) — paling parah
 *   - Surabaya:  11,439 vs 9,514 (16,8% error)
 *   - Jember:    16,772 vs 16,538 (1,4% error)
 *
 * Usage:
 *   php scripts/fix_prelist_n_sls.php              (dry-run, default)
 *   php scripts/fix_prelist_n_sls.php --execute    (eksekusi UPDATE)
 *
 * Rekomendasi: R1.4 dari Laporan Analisis Pegawai Organik.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$db = Database::getInstance();
$execute = in_array('--execute', $argv, true);

echo "=== Fix prelist_kabkota.n_sls ===\n";
echo "Mode: " . ($execute ? 'EXECUTE' : 'DRY-RUN (use --execute to apply)') . "\n\n";

$rows = $db->query("
    SELECT
        k.kd_kab,
        k.nm_kabkota,
        k.n_sls                     AS current_n_sls,
        COALESCE(s.actual, 0)       AS actual_count,
        k.n_sls - COALESCE(s.actual, 0) AS diff
    FROM prelist_kabkota k
    LEFT JOIN (
        SELECT kd_kab, COUNT(*) AS actual
        FROM prelist_sls
        GROUP BY kd_kab
    ) s ON s.kd_kab = k.kd_kab
    ORDER BY ABS(k.n_sls - COALESCE(s.actual, 0)) DESC
")->fetchAll();

$totalCurrent = 0;
$totalActual  = 0;
$mismatchCnt  = 0;

echo str_pad("KAB", 6) . " " . str_pad("NM_KABKOTA", 16) . " " . str_pad("CURRENT", 10, ' ', STR_PAD_LEFT) . " " . str_pad("ACTUAL", 10, ' ', STR_PAD_LEFT) . " " . str_pad("DIFF", 10, ' ', STR_PAD_LEFT) . "\n";
echo str_repeat("-", 64) . "\n";

foreach ($rows as $r) {
    $totalCurrent += (int) $r['current_n_sls'];
    $totalActual  += (int) $r['actual_count'];
    if ((int) $r['diff'] !== 0) {
        $mismatchCnt++;
    }
    printf(
        "%s %-16s %10s %10s %+10d%s\n",
        $r['kd_kab'],
        substr($r['nm_kabkota'], 0, 16),
        number_format((int) $r['current_n_sls']),
        number_format((int) $r['actual_count']),
        (int) $r['diff'],
        ((int) $r['diff'] !== 0) ? ' *' : ''
    );
}

echo str_repeat("-", 64) . "\n";
printf(
    "%s %-16s %10s %10s %+10d\n",
    '',
    'TOTAL',
    number_format($totalCurrent),
    number_format($totalActual),
    $totalCurrent - $totalActual
);
echo "\nMismatch count: $mismatchCnt / " . count($rows) . " kab\n\n";

if ($mismatchCnt === 0) {
    echo "All rows match. Nothing to fix.\n";
    exit(0);
}

if (!$execute) {
    echo "DRY-RUN: tidak ada perubahan.\n";
    echo "Jalankan dengan --execute untuk apply UPDATE:\n";
    echo "  UPDATE prelist_kabkota k\n";
    echo "    JOIN (SELECT kd_kab, COUNT(*) AS actual FROM prelist_sls GROUP BY kd_kab) s\n";
    echo "      ON s.kd_kab = k.kd_kab\n";
    echo "    SET k.n_sls = s.actual;\n";
    exit(0);
}

echo "Executing UPDATE...\n";
$db->beginTransaction();
try {
    $stmt = $db->query("
        UPDATE prelist_kabkota k
        JOIN (SELECT kd_kab, COUNT(*) AS actual FROM prelist_sls GROUP BY kd_kab) s
          ON s.kd_kab = k.kd_kab
        SET k.n_sls = s.actual
    ");
    $affected = $stmt->rowCount();
    $db->commit();
    echo "OK: $affected rows updated.\n";
} catch (Throwable $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

$verify = (int) $db->fetchColumn("SELECT COALESCE(SUM(n_sls),0) FROM prelist_kabkota");
$actual = (int) $db->fetchColumn("SELECT COUNT(*) FROM prelist_sls");
echo "Post-update check: SUM(n_sls) = $verify, COUNT(prelist_sls) = $actual";
echo ($verify === $actual) ? " ✓\n" : " ✗ STILL MISMATCH\n";
