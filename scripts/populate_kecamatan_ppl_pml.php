<?php
/**
 * scripts/populate_kecamatan_ppl_pml.php
 *
 * Distribusi PPL & PML dari prelist_kabkota ke prelist_kecamatan.
 * Basis distribusi: wilkerstat (Wilayah Kerja Statistik = jumlah SLS
 * per kecamatan — proxy terbaik untuk beban kerja PPL).
 *
 * Temuan (Jun 2026):
 *   prelist_kabkota.ppl/pml ada (Jember: ppl=2,148, pml=280)
 *   prelist_kecamatan.ppl/pml NULL/0 untuk semua 31 kecamatan
 *   getWorkloadStats() report total_ppl=0 → beban per PPL = 0
 *
 * Algoritma:
 *   1. Untuk setiap kecamatan, hitung proporsi = wilkerstat_kec / SUM(wilkerstat)
 *   2. ppl_kec = round(total_ppl * proporsi)
 *   3. pml_kec = round(total_pml * proporsi)
 *   4. Adjust: jika ada selisih karena rounding, distribute ke kecamatan dengan wilkerstat terbesar
 *
 * Usage:
 *   php scripts/populate_kecamatan_ppl_pml.php              (dry-run, default)
 *   php scripts/populate_kecamatan_ppl_pml.php --execute    (eksekusi UPDATE)
 *   php scripts/populate_kecamatan_ppl_pml.php --kd_kab=3509 (target kabupaten)
 *
 * Rekomendasi: R1.3 dari Laporan Analisis Pegawai Organik.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$db = Database::getInstance();
$execute = false;
$kdKab = '3509';

foreach ($argv as $a) {
    if ($a === '--execute') $execute = true;
    if (str_starts_with($a, '--kd_kab=')) $kdKab = substr($a, 9);
}

echo "=== Populate prelist_kecamatan.ppl/pml (kd_kab={$kdKab}) ===\n";
echo "Mode: " . ($execute ? 'EXECUTE' : 'DRY-RUN (use --execute to apply)') . "\n\n";

$kab = $db->query("SELECT * FROM prelist_kabkota WHERE kd_kab = ?", [$kdKab])->fetch();
if (!$kab) {
    die("ERROR: kd_kab={$kdKab} not found in prelist_kabkota\n");
}

$totalPpl = (int) $kab['ppl'];
$totalPml = (int) $kab['pml'];
$totalSlsKab = (int) $kab['n_sls'];

echo "Source from prelist_kabkota:\n";
echo "  kd_kab       = {$kab['kd_kab']}\n";
echo "  nm_kabkota   = {$kab['nm_kabkota']}\n";
echo "  total ppl    = {$totalPpl}\n";
echo "  total pml    = {$totalPml}\n";
echo "  n_sls (kab)  = " . number_format($totalSlsKab) . "\n\n";

$kecs = $db->query("
    SELECT kd_kec, nm_kec, wilkerstat, muatan_rs, ppl AS ppl_cur, pml AS pml_cur
    FROM prelist_kecamatan
    WHERE kd_kab = ?
    ORDER BY wilkerstat DESC
", [$kdKab])->fetchAll();

$sumWil = 0;
foreach ($kecs as $k) $sumWil += (int) $k['wilkerstat'];

echo "Kecamatan count: " . count($kecs) . "\n";
echo "Sum wilkerstat (kec) = " . number_format($sumWil) . "\n\n";

$proposed = [];
$pplDist = 0; $pmlDist = 0;
foreach ($kecs as $k) {
    $ratio = $sumWil > 0 ? (int) $k['wilkerstat'] / $sumWil : 0;
    $ppl = (int) round($totalPpl * $ratio);
    $pml = (int) round($totalPml * $ratio);
    if ($totalPpl > 0 && (int) $k['wilkerstat'] > 0 && $ppl < 1) $ppl = 1;
    if ($totalPml > 0 && (int) $k['wilkerstat'] > 0 && $pml < 1) $pml = 1;
    $proposed[] = [
        'kd_kec'     => $k['kd_kec'],
        'nm_kec'     => $k['nm_kec'],
        'wilkerstat' => (int) $k['wilkerstat'],
        'ppl_new'    => $ppl,
        'pml_new'    => $pml,
        'ppl_cur'    => (int) $k['ppl_cur'],
        'pml_cur'    => (int) $k['pml_cur'],
    ];
    $pplDist += $ppl;
    $pmlDist += $pml;
}

$pplDiff = $totalPpl - $pplDist;
$pmlDiff = $totalPml - $pmlDist;
echo "After initial distribution: ppl_sum={$pplDist} (target {$totalPpl}, diff {$pplDiff})\n";
echo "                          pml_sum={$pmlDist} (target {$totalPml}, diff {$pmlDiff})\n\n";

if ($pplDiff !== 0 || $pmlDiff !== 0) {
    echo "Adjusting via largest wilkerstat kecamatan...\n";
    for ($i = 0; ($pplDiff > 0 || $pmlDiff > 0) && $i < count($proposed); $i++) {
        if ($pplDiff > 0) {
            $proposed[$i]['ppl_new']++;
            $pplDiff--;
        }
        if ($pmlDiff > 0) {
            $proposed[$i]['pml_new']++;
            $pmlDiff--;
        }
    }
    for ($i = count($proposed) - 1; ($pplDiff < 0 || $pmlDiff < 0) && $i >= 0; $i--) {
        if ($pplDiff < 0 && $proposed[$i]['ppl_new'] > 0) {
            $proposed[$i]['ppl_new']--;
            $pplDiff++;
        }
        if ($pmlDiff < 0 && $proposed[$i]['pml_new'] > 0) {
            $proposed[$i]['pml_new']--;
            $pmlDiff++;
        }
    }
}

echo str_pad("KD_KEC", 10) . " " . str_pad("NM_KEC", 18) . " " . str_pad("WIL", 7, ' ', STR_PAD_LEFT) . " " . str_pad("PPL_OLD", 7, ' ', STR_PAD_LEFT) . " " . str_pad("PPL_NEW", 7, ' ', STR_PAD_LEFT) . " " . str_pad("PML_OLD", 7, ' ', STR_PAD_LEFT) . " " . str_pad("PML_NEW", 7, ' ', STR_PAD_LEFT) . "\n";
echo str_repeat("-", 70) . "\n";

$totalNewPpl = 0;
$totalNewPml = 0;
$updated = 0;
foreach ($proposed as $p) {
    $flag = ($p['ppl_new'] != $p['ppl_cur'] || $p['pml_new'] != $p['pml_cur']) ? ' *' : '';
    printf("%s %-18s %7d %7d %7d %7d %7d%s\n",
        $p['kd_kec'], substr($p['nm_kec'], 0, 18), $p['wilkerstat'],
        $p['ppl_cur'], $p['ppl_new'], $p['pml_cur'], $p['pml_new'],
        $flag
    );
    $totalNewPpl += $p['ppl_new'];
    $totalNewPml += $p['pml_new'];
    if ($flag) $updated++;
}
echo str_repeat("-", 70) . "\n";
echo "TOTAL: ppl_new=" . number_format($totalNewPpl) . " (target {$totalPpl}) | pml_new=" . number_format($totalNewPml) . " (target {$totalPml}) | rows to update: {$updated}\n\n";

if (!$execute) {
    echo "DRY-RUN: tidak ada perubahan.\n";
    echo "Jalankan dengan --execute untuk apply.\n";
    exit(0);
}

echo "Executing UPDATE...\n";
$db->beginTransaction();
try {
    $updated = 0;
    foreach ($proposed as $p) {
        if ($p['ppl_new'] != $p['ppl_cur'] || $p['pml_new'] != $p['pml_cur']) {
            $db->query("UPDATE prelist_kecamatan SET ppl = ?, pml = ? WHERE kd_kab = ? AND kd_kec = ?", [$p['ppl_new'], $p['pml_new'], $kdKab, $p['kd_kec']]);
            $updated++;
        }
    }
    $db->commit();
    echo "OK: $updated rows updated.\n";
} catch (Throwable $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

$sumCheck = $db->query("SELECT COALESCE(SUM(ppl),0) AS s, COALESCE(SUM(pml),0) AS m FROM prelist_kecamatan WHERE kd_kab = ?", [$kdKab])->fetch();
echo "Post-update: ppl_sum=" . number_format($sumCheck['s']) . " pml_sum=" . number_format($sumCheck['m']) . "\n";
