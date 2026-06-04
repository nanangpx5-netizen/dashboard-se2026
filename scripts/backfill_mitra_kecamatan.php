<?php
/**
 * scripts/backfill_mitra_kecamatan.php
 *
 * Backfill kecamatan_bertugas untuk users.role='mitra' yang masih NULL.
 * Sumber: users.id_sobat (BPS regional code, 12 digit).
 *
 * Format id_sobat:  PP KK EE DDD SSS
 *                   35 09 23 110 436
 *                   ^province (2)
 *                     ^kab (2)    → concat '3509' = 4 digit
 *                        ^kec (2) → append '0' → '230'
 *                          ^desa (3)  [encoding differs from prelist_sls]
 *                             ^sls (3)
 *
 * Map ke prelist_kecamatan:
 *   id_sobat[0..3]   = kd_kab
 *   id_sobat[4..5]   = kd_kec suffix → append '0'
 *   composite key:   kd_kab + kd_kec
 *
 * Coverage:
 *   2,148 active mitra dengan id_sobat LIKE '35%'
 *   2,144 dapat di-match ke prelist_kecamatan (99.8%)
 *
 * Usage:
 *   php scripts/backfill_mitra_kecamatan.php              (dry-run, default)
 *   php scripts/backfill_mitra_kecamatan.php --execute    (eksekusi UPDATE)
 *
 * Rekomendasi: R1.1 dari Laporan Analisis Pegawai Organik.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$db = Database::getInstance();
$execute = in_array('--execute', $argv, true);

echo "=== Backfill users.kecamatan_bertugas dari id_sobat ===\n";
echo "Mode: " . ($execute ? 'EXECUTE' : 'DRY-RUN (use --execute to apply)') . "\n\n";

$totalMitra = (int) $db->fetchColumn("
    SELECT COUNT(*) FROM users WHERE role='mitra' AND status_akun='active'
");
$withIdSobat = (int) $db->fetchColumn("
    SELECT COUNT(*) FROM users
    WHERE role='mitra' AND status_akun='active'
      AND id_sobat IS NOT NULL AND id_sobat != ''
      AND id_sobat LIKE '35%'
");
$withKecBertugas = (int) $db->fetchColumn("
    SELECT COUNT(*) FROM users
    WHERE role='mitra' AND status_akun='active'
      AND kecamatan_bertugas IS NOT NULL AND kecamatan_bertugas != ''
");
$willUpdate = (int) $db->fetchColumn("
    SELECT COUNT(*)
    FROM users u
    INNER JOIN prelist_kecamatan pk
      ON pk.kd_kab = SUBSTRING(u.id_sobat, 1, 4)
     AND pk.kd_kec = CONCAT(SUBSTRING(u.id_sobat, 1, 4), SUBSTRING(u.id_sobat, 5, 2), '0')
    WHERE u.role='mitra' AND u.status_akun='active'
      AND u.id_sobat IS NOT NULL AND u.id_sobat != ''
      AND u.id_sobat LIKE '35%'
      AND (u.kecamatan_bertugas IS NULL OR u.kecamatan_bertugas = '')
");

echo "Summary:\n";
echo "  Active mitra total:                " . number_format($totalMitra) . "\n";
echo "  Active mitra with valid id_sobat:  " . number_format($withIdSobat) . "\n";
echo "  Active mitra with kecamatan set:   " . number_format($withKecBertugas) . "\n";
echo "  Mitra to be updated:               " . number_format($willUpdate) . "\n";
echo "  Match rate:                        " . ($withIdSobat > 0 ? round($willUpdate / $withIdSobat * 100, 1) : 0) . "%\n\n";

if ($willUpdate === 0) {
    echo "Nothing to update.\n";
    exit(0);
}

echo "Top 10 kecamatan to be assigned (preview):\n";
$rows = $db->query("
    SELECT pk.nm_kec, COUNT(*) as cnt
    FROM users u
    INNER JOIN prelist_kecamatan pk
      ON pk.kd_kab = SUBSTRING(u.id_sobat, 1, 4)
     AND pk.kd_kec = CONCAT(SUBSTRING(u.id_sobat, 1, 4), SUBSTRING(u.id_sobat, 5, 2), '0')
    WHERE u.role='mitra' AND u.status_akun='active'
      AND u.id_sobat IS NOT NULL AND u.id_sobat != ''
      AND u.id_sobat LIKE '35%'
      AND (u.kecamatan_bertugas IS NULL OR u.kecamatan_bertugas = '')
    GROUP BY pk.nm_kec
    ORDER BY cnt DESC
    LIMIT 10
")->fetchAll();
foreach ($rows as $r) {
    printf("  %-25s %5d mitra\n", $r['nm_kec'], $r['cnt']);
}
echo "\n";

if (!$execute) {
    echo "DRY-RUN: tidak ada perubahan.\n";
    echo "Jalankan dengan --execute untuk apply:\n";
    echo "  UPDATE users u\n";
    echo "    JOIN prelist_kecamatan pk\n";
    echo "      ON pk.kd_kab = SUBSTRING(u.id_sobat, 1, 4)\n";
    echo "     AND pk.kd_kec = CONCAT(SUBSTRING(u.id_sobat, 1, 4), SUBSTRING(u.id_sobat, 5, 2), '0')\n";
    echo "    SET u.kecamatan_bertugas = pk.nm_kec\n";
    echo "    WHERE u.role='mitra' AND u.status_akun='active'\n";
    echo "      AND u.id_sobat LIKE '35%'\n";
    echo "      AND (u.kecamatan_bertugas IS NULL OR u.kecamatan_bertugas = '');\n";
    exit(0);
}

echo "Executing UPDATE...\n";
$db->beginTransaction();
try {
    $stmt = $db->query("
        UPDATE users u
        JOIN prelist_kecamatan pk
          ON pk.kd_kab = SUBSTRING(u.id_sobat, 1, 4)
         AND pk.kd_kec = CONCAT(SUBSTRING(u.id_sobat, 1, 4), SUBSTRING(u.id_sobat, 5, 2), '0')
        SET u.kecamatan_bertugas = pk.nm_kec
        WHERE u.role='mitra' AND u.status_akun='active'
          AND u.id_sobat IS NOT NULL AND u.id_sobat != ''
          AND u.id_sobat LIKE '35%'
          AND (u.kecamatan_bertugas IS NULL OR u.kecamatan_bertugas = '')
    ");
    $affected = $stmt->rowCount();
    $db->commit();
    echo "OK: $affected rows updated.\n";
} catch (Throwable $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

$verify = (int) $db->fetchColumn("
    SELECT COUNT(*) FROM users
    WHERE role='mitra' AND status_akun='active'
      AND kecamatan_bertugas IS NOT NULL AND kecamatan_bertugas != ''
");
$total = (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role='mitra' AND status_akun='active'");
echo "Post-update: $verify / $total active mitra with kecamatan_bertugas set.\n";
