<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=bps_jember_se2026;charset=utf8mb4', 'root', '');

// Find SLS with many sub-SLS where ALL have KK=0
$stmt = $pdo->query("
    SELECT SUBSTRING(idsubsls,1,14) AS idsls, nmkec, nmdesa, nmsls,
           COUNT(*) AS total_baris,
           COUNT(CASE WHEN kk = 0 THEN 1 END) AS kk_zero,
           COUNT(CASE WHEN muatan = bku AND bku = usaha AND btt = 0 THEN 1 END) AS identical,
           SUM(kk) AS sum_kk,
           SUM(muatan) AS sum_muatan,
           SUM(bku) AS sum_bku,
           SUM(usaha) AS sum_usaha
    FROM sipw_import
    WHERE kdkab = '09'
    GROUP BY idsls, nmkec, nmdesa, nmsls
    HAVING total_baris > 1 AND kk_zero = total_baris
    ORDER BY total_baris DESC
    LIMIT 10
");
$rows = $stmt->fetchAll();
echo "=== SLS with ALL sub-SLS having KK=0 ===\n";
echo "Total: " . count($rows) . " SLS\n";
$t = ['sls'=>0, 'baris'=>0];
foreach ($rows as $r) {
    $t['sls']++;
    $t['baris'] += $r['total_baris'];
    echo "{$r['idsls']} | {$r['nmkec']} | {$r['nmdesa']} | {$r['nmsls']}\n";
    echo "  total_baris={$r['total_baris']}, kk_zero={$r['kk_zero']}, identical={$r['identical']}\n";
    echo "  sum_kk={$r['sum_kk']}, sum_muatan={$r['sum_muatan']}, sum_bku={$r['sum_bku']}, sum_usaha={$r['sum_usaha']}\n";
}
echo "\nTotal: {$t['sls']} SLS, {$t['baris']} baris\n";

// Also check SLS with partial KK=0
$stmt = $pdo->query("
    SELECT COUNT(*) AS sls, SUM(total_baris) AS baris,
           SUM(kk_zero) AS total_kk_zero, SUM(identical) AS total_identical
    FROM (
        SELECT COUNT(*) AS total_baris,
               COUNT(CASE WHEN kk = 0 THEN 1 END) AS kk_zero,
               COUNT(CASE WHEN muatan = bku AND bku = usaha AND btt = 0 THEN 1 END) AS identical
        FROM sipw_import
        WHERE kdkab = '09'
        GROUP BY SUBSTRING(idsubsls,1,14)
        HAVING total_baris > 1
    ) AS sub
");
$r = $stmt->fetch();
echo "\n=== Multi-SLS Summary ===\n";
echo "Total SLS dengan multi-sub: {$r['sls']}\n";
echo "Total baris: {$r['baris']}\n";
echo "Sub-SLS dengan KK=0: {$r['total_kk_zero']}\n";
echo "Sub-SLS identik (muatan=bku=usaha): {$r['total_identical']}\n";
echo "Sub-SLS sehat: " . ($r['baris'] - $r['total_kk_zero']) . "\n";
