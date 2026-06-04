<?php
/**
 * scripts/compare_jember_sources.php
 * Cross-check database vs source files (PRELIST + per-kecamatan SIPW)
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;
use OpenSpout\Reader\XLSX\Reader;

$db = Database::getInstance();

echo "═══════════════════════════════════════════════════════════════\n";
echo "  CROSS-CHECK: DATABASE vs SOURCE FILES (Jember)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ─── 1. SIPW (database) vs PRELIST.KECAMATAN.MUATAN_RS ─────────────
echo "═══ 1. SIPW TOTAL vs PRELIST.MUATAN_RS per kecamatan (Jember) ═══\n";

$rows = $db->fetchAll("
    SELECT
        pk.kd_kec,
        pk.nm_kec,
        pk.muatan_rs AS prelist_muatan,
        pk.sbr       AS prelist_sbr,
        pk.subsektor AS prelist_subsektor,
        pk.jml_kk    AS prelist_kk,
        COALESCE(sip.total_muatan, 0) AS db_muatan,
        COALESCE(sip.total_sls, 0)    AS db_sls,
        COALESCE(sip.total_kk, 0)     AS db_kk
    FROM prelist_kecamatan pk
    LEFT JOIN (
        SELECT kdkec, nmkec,
               COUNT(*) AS total_sls,
               SUM(muatan) AS total_muatan,
               SUM(kk) AS total_kk
        FROM sipw_import
        WHERE kdkab = '09'
        GROUP BY kdkec, nmkec
    ) sip ON sip.kdkec = pk.kd_kec + 0  -- numeric compare
    WHERE pk.kd_kab = '3509'
    ORDER BY pk.kd_kec
");

printf("  %-8s %-15s %10s %10s %10s %10s %10s %10s %10s\n",
    'kd_kec', 'nm_kec', 'pre_mu', 'db_mu', 'diff', 'pre_sls', 'db_sls', 'pre_kk', 'db_kk');
echo "  " . str_repeat('─', 100) . "\n";

$totalPreMu = 0;
$totalDbMu  = 0;
$totalPreKk = 0;
$totalDbKk  = 0;
foreach ($rows as $r) {
    $diff = (int)$r['prelist_muatan'] - (int)$r['db_muatan'];
    $totalPreMu += (int)$r['prelist_muatan'];
    $totalDbMu  += (int)$r['db_muatan'];
    $totalPreKk += (int)$r['prelist_kk'];
    $totalDbKk  += (int)$r['db_kk'];
    printf("  %-8s %-15s %10s %10s %10s %10s %10s %10s %10s\n",
        $r['kd_kec'],
        substr($r['nm_kec'], 0, 15),
        number_format($r['prelist_muatan']),
        number_format($r['db_muatan']),
        ($diff > 0 ? "+{$diff}" : $diff),
        number_format($r['prelist_subsektor']),
        number_format($r['db_sls']),
        number_format($r['prelist_kk']),
        number_format($r['db_kk'])
    );
}
echo "  " . str_repeat('─', 100) . "\n";
printf("  %-8s %-15s %10s %10s %10s %10s %10s %10s %10s\n",
    'TOTAL', '',
    number_format($totalPreMu),
    number_format($totalDbMu),
    number_format($totalPreMu - $totalDbMu),
    '-',
    '-',
    number_format($totalPreKk),
    number_format($totalDbKk)
);

// ─── 2. PER KECAMATAN: DB SLS vs FILE EXCEL ─────────────────────────
echo "\n═══ 2. SLS COUNT: DB sipw_import vs FILE EXCEL per kecamatan ═══\n";

$dataKecPath = __DIR__ . '/../data/sipw/Kecamatan';
$files = glob($dataKecPath . '/*.xlsx');
sort($files);

printf("  %-8s %-25s %12s %12s %10s\n", 'kd_kec', 'nm_kec', 'file_sls', 'db_sls', 'selisih');
echo "  " . str_repeat('─', 70) . "\n";

$totalFile = 0;
$totalDb   = 0;
foreach ($files as $file) {
    $basename = basename($file, '.xlsx');
    $parts = explode(' ', $basename, 2);
    $kdKec = $parts[0] ?? '';
    $nmKec = ucwords(strtolower($parts[1] ?? ''));

    // Hitung baris Excel (header excluded)
    $reader = new Reader();
    $reader->open($file);
    $count = 0;
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $idx => $row) {
            if ($idx > 1) $count++;  // skip header
        }
        break;
    }
    $reader->close();

    $dbRow = $db->fetchOne("
        SELECT COUNT(*) AS jml FROM sipw_import WHERE kdkab = '09' AND kdkec = ?
    ", [$kdKec]);
    $dbCount = (int) ($dbRow['jml'] ?? 0);
    $diff = $count - $dbCount;
    $totalFile += $count;
    $totalDb   += $dbCount;
    $status = $diff === 0 ? 'OK' : ($diff > 0 ? "+{$diff}" : "{$diff}");
    printf("  %-8s %-25s %12s %12s %10s\n",
        $kdKec,
        substr($nmKec, 0, 25),
        number_format($count),
        number_format($dbCount),
        $status
    );
}
echo "  " . str_repeat('─', 70) . "\n";
printf("  %-8s %-25s %12s %12s %10s\n",
    'TOTAL', '',
    number_format($totalFile),
    number_format($totalDb),
    number_format($totalFile - $totalDb)
);

// ─── 3. HEALTH CHECK SUMMARY ────────────────────────────────────────
echo "\n═══ 3. HEALTH CHECK ═══\n";

$checks = [
    'sipw_import'              => $db->count('sipw_import'),
    'prelist_kabkota (3509)'   => (int) $db->fetchColumn("SELECT COUNT(*) FROM prelist_kabkota WHERE kd_kab = '3509'"),
    'prelist_kecamatan (3509)' => (int) $db->fetchColumn("SELECT COUNT(*) FROM prelist_kecamatan WHERE kd_kab = '3509'"),
    'prelist_sls (3509)'       => (int) $db->fetchColumn("SELECT COUNT(*) FROM prelist_sls WHERE kd_kab = '3509'"),
    'prelist_subsektor (3509)' => (int) $db->fetchColumn("SELECT COUNT(*) FROM prelist_subsektor WHERE LEFT(idsls, 4) = '3509'"),
    'sipw_assignment'          => $db->count('sipw_assignment'),
    'wilayah_kerja'            => $db->count('wilayah_kerja'),
    'users (active)'           => (int) $db->fetchColumn("SELECT COUNT(*) FROM users WHERE status_akun = 'active'"),
    'activity_logs (today)'    => (int) $db->fetchColumn("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()"),
];

foreach ($checks as $label => $val) {
    $icon = $val > 0 ? '✓' : '⚠';
    printf("  %s  %-30s : %s\n", $icon, $label, number_format($val));
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  Selesai.\n";
echo "═══════════════════════════════════════════════════════════════\n";
