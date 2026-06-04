<?php
/**
 * scripts/final_jember_analysis.php
 * Final comprehensive analysis with proper COLLATE handling
 */

require __DIR__ . '/../src/bootstrap.php';
use App\Core\Database;
$db = Database::getInstance();

echo "═══════════════════════════════════════════════════════════════\n";
echo "  ANALISIS DATA KABUPATEN JEMBER — FINAL\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ─── 1. WEB-LEVEL TOTALS (dashboard cards) ──────────────────────────
echo "═══ 1. ANGKA DI WEB (DASHBOARD) — KONSISTENSI ═══\n\n";

$web = $db->fetchAll("
    SELECT
        (SELECT COUNT(*) FROM sipw_import)              AS total_sipw_rows,
        (SELECT COUNT(DISTINCT kdkec) FROM sipw_import) AS total_kec,
        (SELECT COALESCE(SUM(kk), 0)    FROM sipw_import) AS total_kk,
        (SELECT COALESCE(SUM(muatan), 0) FROM sipw_import) AS total_muatan,
        (SELECT COALESCE(SUM(btt), 0)   FROM sipw_import) AS total_btt,
        (SELECT COALESCE(SUM(bku), 0)   FROM sipw_import) AS total_bku,
        (SELECT COALESCE(SUM(usaha), 0) FROM sipw_import) AS total_usaha,
        (SELECT COUNT(*) FROM prelist_sls WHERE kd_kab = '3509') AS prelist_sls_jbr,
        (SELECT COUNT(*) FROM prelist_sls)                    AS prelist_sls_all
");
$w = $web[0];
echo "  Tabel sipw_import:\n";
printf("    %-30s %s baris\n", "SLS (semua kab)", number_format($w['total_sipw_rows']));
printf("    %-30s %s kecamatan\n", "Kecamatan unik", number_format($w['total_kec']));
printf("    %-30s %s KK\n", "Total KK", number_format($w['total_kk']));
printf("    %-30s %s muatan\n", "Total muatan", number_format($w['total_muatan']));
printf("    %-30s %s BTT\n", "Total BTT", number_format($w['total_btt']));
printf("    %-30s %s BKU\n", "Total BKU", number_format($w['total_bku']));
printf("    %-30s %s Usaha\n", "Total Usaha", number_format($w['total_usaha']));
echo "\n  Tabel prelist_sls:\n";
printf("    %-30s %s (semua 38 kab)\n", "Total", number_format($w['prelist_sls_all']));
printf("    %-30s %s (Jember saja)\n", "Total", number_format($w['prelist_sls_jbr']));

$delta = (int)$w['total_sipw_rows'] - (int)$w['prelist_sls_jbr'];
printf("\n  ⚠ SELISIH: sipw_import=%s, prelist_jember=%s, delta=%s SLS\n",
    number_format($w['total_sipw_rows']),
    number_format($w['prelist_sls_jbr']),
    number_format($delta)
);

// ─── 2. PER KECAMATAN JEMBER — DATA vs PRELIST ──────────────────────
echo "\n═══ 2. PER KECAMATAN JEMBER (35) — DETAIL ═══\n\n";

// Resolve collation mismatch with COLLATE clause
$rows = $db->fetchAll("
    SELECT
        pk.kd_kec,
        pk.nm_kec,
        pk.muatan_rs   AS prelist_muatan,
        pk.sbr         AS prelist_sbr,
        pk.subsektor   AS prelist_subsektor,
        pk.jml_kk      AS prelist_kk,
        pk.wilkerstat  AS prelist_wilkerstat,
        COALESCE(sip.cnt, 0) AS sipw_sls,
        COALESCE(sip.mu, 0)  AS sipw_muatan,
        COALESCE(sip.kk, 0)  AS sipw_kk
    FROM prelist_kecamatan pk
    LEFT JOIN (
        SELECT kdkec,
               COUNT(*) AS cnt,
               SUM(muatan) AS mu,
               SUM(kk) AS kk
        FROM sipw_import
        WHERE kdkab = '09'
        GROUP BY kdkec
    ) sip ON sip.kdkec COLLATE utf8mb4_unicode_ci = SUBSTRING(pk.kd_kec, 5)
    WHERE pk.kd_kab = '3509'
    ORDER BY pk.kd_kec
");

printf("  %-9s %-13s %8s %9s %9s %9s %9s %9s %8s\n",
    'kd_kec', 'nm_kec', 'pre_sls', 'sipw_sls', 'pre_kk', 'sipw_kk', 'pre_mu', 'sipw_mu', 'selisih');
echo "  " . str_repeat('-', 95) . "\n";

$t = ['pre_sls'=>0,'sipw_sls'=>0,'pre_kk'=>0,'sipw_kk'=>0,'pre_mu'=>0,'sipw_mu'=>0];
foreach ($rows as $r) {
    $diff = (int)$r['prelist_muatan'] - (int)$r['sipw_muatan'];
    $t['pre_sls']   += (int)$r['prelist_subsektor'];
    $t['sipw_sls']  += (int)$r['sipw_sls'];
    $t['pre_kk']    += (int)$r['prelist_kk'];
    $t['sipw_kk']   += (int)$r['sipw_kk'];
    $t['pre_mu']    += (int)$r['prelist_muatan'];
    $t['sipw_mu']   += (int)$r['sipw_muatan'];
    printf("  %-9s %-13s %8s %9s %9s %9s %9s %9s %8s\n",
        $r['kd_kec'],
        substr($r['nm_kec'], 0, 13),
        number_format($r['prelist_subsektor']),
        number_format($r['sipw_sls']),
        number_format($r['prelist_kk']),
        number_format($r['sipw_kk']),
        number_format($r['prelist_muatan']),
        number_format($r['sipw_muatan']),
        ($diff > 0 ? "+{$diff}" : $diff)
    );
}
echo "  " . str_repeat('-', 95) . "\n";
printf("  %-9s %-13s %8s %9s %9s %9s %9s %9s %8s\n",
    'TOTAL', '',
    number_format($t['pre_sls']),
    number_format($t['sipw_sls']),
    number_format($t['pre_kk']),
    number_format($t['sipw_kk']),
    number_format($t['pre_mu']),
    number_format($t['sipw_mu']),
    number_format($t['pre_mu'] - $t['sipw_mu'])
);

// ─── 3. FILE vs DB: 16 KECAMATAN (010-160) ─────────────────────────
echo "\n═══ 3. CROSS-CHECK: FILE EXCEL vs DATABASE (16 kecamatan tersedia) ═══\n\n";
$dataKecPath = __DIR__ . '/../data/sipw/Kecamatan';
$files = glob($dataKecPath . '/*.xlsx');
sort($files);

$totalFile = 0; $totalDb = 0;
printf("  %-7s %-15s %10s %10s %10s\n", 'kd_kec', 'nm_kec', 'file_sls', 'db_sls', 'status');
echo "  " . str_repeat('-', 60) . "\n";
foreach ($files as $file) {
    $basename = basename($file, '.xlsx');
    $parts = explode(' ', $basename, 2);
    $kdKec = $parts[0] ?? '';
    $nmKec = $parts[1] ?? '';

    // Count Excel rows
    $reader = new \OpenSpout\Reader\XLSX\Reader();
    $reader->open($file);
    $count = 0; $seen = 0;
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $seen++;
            if ($seen > 1) $count++;
        }
        break;
    }
    $reader->close();

    $dbRow = $db->fetchOne("SELECT COUNT(*) AS jml FROM sipw_import WHERE kdkab='09' AND kdkec=?", [$kdKec]);
    $dbCount = (int)($dbRow['jml'] ?? 0);
    $totalFile += $count;
    $totalDb   += $dbCount;
    $status = $count === $dbCount ? 'OK' : "Δ " . ($count - $dbCount);
    printf("  %-7s %-15s %10s %10s %10s\n",
        $kdKec, substr($nmKec, 0, 15),
        number_format($count), number_format($dbCount), $status
    );
}
echo "  " . str_repeat('-', 60) . "\n";
printf("  %-7s %-15s %10s %10s %10s\n",
    'TOTAL', '',
    number_format($totalFile), number_format($totalDb),
    ($totalFile === $totalDb ? 'OK' : 'Δ ' . ($totalFile - $totalDb))
);

// ─── 4. ANOMALI MUATAN ──────────────────────────────────────────────
echo "\n═══ 4. ANOMALI MUATAN (db kd_kab='09') ═══\n\n";

$anom = $db->fetchAll("
    SELECT
        kdkec, nmkec,
        COUNT(*) AS sls,
        SUM(CASE WHEN muatan = 0 THEN 1 ELSE 0 END) AS mu_zero,
        SUM(CASE WHEN muatan < 10 THEN 1 ELSE 0 END) AS mu_low,
        SUM(CASE WHEN kk = 0 THEN 1 ELSE 0 END) AS kk_zero,
        ROUND(AVG(muatan), 1) AS avg_mu,
        MIN(muatan) AS min_mu,
        MAX(muatan) AS max_mu,
        ROUND(STDDEV(muatan), 1) AS std_mu
    FROM sipw_import
    WHERE kdkab = '09'
    GROUP BY kdkec, nmkec
    ORDER BY mu_zero DESC, mu_low DESC
");
printf("  %-7s %-15s %5s %5s %5s %5s %8s %8s %8s %8s\n",
    'kdkec', 'nmkec', 'sls', 'mu=0', 'mu<10', 'kk=0', 'avg_mu', 'min_mu', 'max_mu', 'std_mu');
echo "  " . str_repeat('-', 90) . "\n";
foreach ($anom as $r) {
    printf("  %-7s %-15s %5s %5s %5s %5s %8s %8s %8s %8s\n",
        $r['kdkec'],
        substr($r['nmkec'], 0, 15),
        number_format($r['sls']),
        number_format($r['mu_zero']),
        number_format($r['mu_low']),
        number_format($r['kk_zero']),
        $r['avg_mu'],
        $r['min_mu'],
        $r['max_mu'],
        $r['std_mu']
    );
}

// ─── 5. ASSIGNMENT COVERAGE ─────────────────────────────────────────
echo "\n═══ 5. ASSIGNMENT vs SLS (kondisi assignment) ═══\n\n";
$assign = $db->fetchAll("
    SELECT
        (SELECT COUNT(*) FROM sipw_assignment) AS total,
        (SELECT COUNT(*) FROM sipw_assignment WHERE status = 'active') AS active,
        (SELECT COUNT(*) FROM sipw_assignment WHERE id_pcl IS NOT NULL) AS with_pcl,
        (SELECT COUNT(*) FROM sipw_assignment WHERE id_pml IS NOT NULL) AS with_pml
");
$a = $assign[0];
printf("  Total assignment : %s\n", number_format($a['total']));
printf("  Active           : %s\n", number_format($a['active']));
printf("  With PCL         : %s\n", number_format($a['with_pcl']));
printf("  With PML         : %s\n", number_format($a['with_pml']));

// ─── 6. USER POOL ──────────────────────────────────────────────────
echo "\n═══ 6. USER POOL (siapa yang bisa di-assign) ═══\n\n";
$users = $db->fetchAll("
    SELECT role, status_akun, COUNT(*) AS cnt
    FROM users
    GROUP BY role, status_akun
    ORDER BY role
");
printf("  %-15s %-10s %s\n", 'role', 'status', 'jumlah');
echo "  " . str_repeat('-', 40) . "\n";
foreach ($users as $r) {
    printf("  %-15s %-10s %s\n", $r['role'], $r['status_akun'], number_format($r['cnt']));
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  Selesai.\n";
echo "═══════════════════════════════════════════════════════════════\n";
