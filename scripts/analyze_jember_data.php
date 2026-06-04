<?php
/**
 * scripts/analyze_jember_data.php
 * Membandingkan data di database vs file Excel + memberi summary statistik
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;
use OpenSpout\Reader\XLSX\Reader;

$db = Database::getInstance();

echo "═══════════════════════════════════════════════════════════════\n";
echo "  ANALISIS DATA KABUPATEN JEMBER — Dashboard SE2026\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ─── 1. DATA PRELIST (database) ─────────────────────────────────────
echo "═══ 1. DATA PRELIST (database bps_jember_se2026) ═══\n";

$stats = $db->fetchAll("
    SELECT
        (SELECT COUNT(*) FROM prelist_kabkota)         AS kab_total,
        (SELECT COUNT(*) FROM prelist_kabkota WHERE kd_kab = '09')  AS kab_jember,
        (SELECT COUNT(*) FROM prelist_kecamatan)        AS kec_total,
        (SELECT COUNT(*) FROM prelist_kecamatan WHERE kd_kab = '09') AS kec_jember,
        (SELECT COUNT(*) FROM prelist_sls)              AS sls_total,
        (SELECT COUNT(*) FROM prelist_sls WHERE kd_kab = '09')        AS sls_jember,
        (SELECT COUNT(*) FROM prelist_subsektor)        AS sub_total,
        (SELECT COUNT(*) FROM prelist_subsektor WHERE LEFT(idsls,2) = '09') AS sub_jember
");
$s = $stats[0];
echo "  prelist_kabkota      : " . number_format($s['kab_total']) . " total, " . $s['kab_jember'] . " Jember\n";
echo "  prelist_kecamatan    : " . number_format($s['kec_total']) . " total, " . $s['kec_jember'] . " Jember\n";
echo "  prelist_sls          : " . number_format($s['sls_total']) . " total, " . number_format($s['sls_jember']) . " Jember\n";
echo "  prelist_subsektor    : " . number_format($s['sub_total']) . " total, " . number_format($s['sub_jember']) . " Jember\n\n";

// ─── 2. DATA SIPW (database) ────────────────────────────────────────
echo "═══ 2. DATA SIPW (database) ═══\n";

$sipwStats = $db->fetchAll("
    SELECT
        COUNT(*) AS total_sls,
        SUM(kk) AS total_kk,
        SUM(btt) AS total_btt,
        SUM(bttk) AS total_bttk,
        SUM(bku) AS total_bku,
        SUM(usaha) AS total_usaha,
        SUM(muatan) AS total_muatan,
        COUNT(DISTINCT kdkec) AS total_kec,
        COUNT(DISTINCT CONCAT(kdkec, kddesa)) AS total_desa
    FROM sipw_import
");
$jemberStats = $db->fetchAll("
    SELECT
        COUNT(*) AS total_sls,
        SUM(kk) AS total_kk,
        SUM(btt) AS total_btt,
        SUM(bttk) AS total_bttk,
        SUM(bku) AS total_bku,
        SUM(usaha) AS total_usaha,
        SUM(muatan) AS total_muatan,
        COUNT(DISTINCT kdkec) AS total_kec,
        COUNT(DISTINCT CONCAT(kdkec, kddesa)) AS total_desa
    FROM sipw_import
    WHERE kdkab = '09'
");

echo "  SELURUH DATA (semua kabupaten):\n";
foreach (['total_sls', 'total_kk', 'total_btt', 'total_bttk', 'total_bku', 'total_usaha', 'total_muatan', 'total_kec', 'total_desa'] as $k) {
    echo "    " . str_pad(str_replace('total_', '', $k), 12) . " : " . number_format($sipwStats[0][$k] ?? 0) . "\n";
}

echo "\n  KABUPATEN JEMBER (kdkab = '09'):\n";
foreach (['total_sls', 'total_kk', 'total_btt', 'total_bttk', 'total_bku', 'total_usaha', 'total_muatan', 'total_kec', 'total_desa'] as $k) {
    echo "    " . str_pad(str_replace('total_', '', $k), 12) . " : " . number_format($jemberStats[0][$k] ?? 0) . "\n";
}

// ─── 3. PER KECAMATAN (Jember) ──────────────────────────────────────
echo "\n═══ 3. PER KECAMATAN (Jember) ═══\n";

$perKec = $db->fetchAll("
    SELECT
        kdkec,
        nmkec,
        COUNT(*) AS jml_sls,
        SUM(kk) AS jml_kk,
        SUM(btt) AS jml_btt,
        SUM(bku) AS jml_bku,
        SUM(usaha) AS jml_usaha,
        SUM(muatan) AS jml_muatan
    FROM sipw_import
    WHERE kdkab = '09'
    GROUP BY kdkec, nmkec
    ORDER BY kdkec
");

printf("  %-8s %-25s %8s %10s %10s %10s %10s %10s\n",
    'kdkec', 'nmkec', 'SLS', 'KK', 'BTT', 'BKU', 'Usaha', 'Muatan');
echo "  " . str_repeat('─', 100) . "\n";
foreach ($perKec as $r) {
    printf("  %-8s %-25s %8s %10s %10s %10s %10s %10s\n",
        $r['kdkec'],
        substr($r['nmkec'] ?? '-', 0, 25),
        number_format($r['jml_sls']),
        number_format($r['jml_kk']),
        number_format($r['jml_btt']),
        number_format($r['jml_bku']),
        number_format($r['jml_usaha']),
        number_format($r['jml_muatan'])
    );
}

// ─── 4. RATA-RATA MUATAN PER SLS (benchmark) ────────────────────────
echo "\n═══ 4. STATISTIK DESKRIPTIF MUATAN (Jember) ═══\n";

$statMuatan = $db->fetchAll("
    SELECT
        MIN(muatan) AS min_muatan,
        MAX(muatan) AS max_muatan,
        AVG(muatan) AS avg_muatan,
        STDDEV(muatan) AS std_muatan,
        COUNT(CASE WHEN muatan = 0 THEN 1 END) AS muatan_nol,
        COUNT(CASE WHEN muatan < 10 THEN 1 END) AS muatan_kecil,
        COUNT(CASE WHEN muatan > 100 THEN 1 END) AS muatan_besar,
        COUNT(*) AS total
    FROM sipw_import
    WHERE kdkab = '09'
");
$m = $statMuatan[0];
echo "  SLS Total         : " . number_format($m['total']) . "\n";
echo "  Muatan MIN        : " . number_format($m['min_muatan'], 0) . "\n";
echo "  Muatan MAX        : " . number_format($m['max_muatan'], 0) . "\n";
echo "  Muatan AVG        : " . number_format($m['avg_muatan'], 1) . "\n";
echo "  Muatan STDDEV     : " . number_format($m['std_muatan'], 1) . "\n";
echo "  Muatan = 0        : " . number_format($m['muatan_nol']) . " SLS (" . round($m['muatan_nol']/$m['total']*100, 1) . "%)\n";
echo "  Muatan < 10       : " . number_format($m['muatan_kecil']) . " SLS\n";
echo "  Muatan > 100      : " . number_format($m['muatan_besar']) . " SLS\n";

// ─── 5. ASSIGNMENT vs SIPW (gap analysis) ───────────────────────────
echo "\n═══ 5. ASSIGNMENT COVERAGE (Jember) ═══\n";

$assign = $db->fetchAll("
    SELECT
        COUNT(*) AS total_sipw,
        COUNT(CASE WHEN sa.id IS NOT NULL THEN 1 END) AS assigned,
        COUNT(CASE WHEN sa.pencacah_id IS NOT NULL THEN 1 END) AS punya_pcl,
        COUNT(CASE WHEN sa.pengawas_id IS NOT NULL THEN 1 END) AS punya_pml,
        COUNT(CASE WHEN sa.task_force_id IS NOT NULL THEN 1 END) AS punya_tf
    FROM sipw_import si
    LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
    WHERE si.kdkab = '09'
");
$a = $assign[0];
echo "  Total SLS              : " . number_format($a['total_sipw']) . "\n";
echo "  Ada Assignment         : " . number_format($a['assigned']) . " (" . round($a['assigned']/$a['total_sipw']*100, 1) . "%)\n";
echo "  Punya PCL (pencacah)   : " . number_format($a['punya_pcl']) . " (" . round($a['punya_pcl']/$a['total_sipw']*100, 1) . "%)\n";
echo "  Punya PML (pengawas)   : " . number_format($a['punya_pml']) . " (" . round($a['punya_pml']/$a['total_sipw']*100, 1) . "%)\n";
echo "  Punya TF (task force)  : " . number_format($a['punya_tf']) . " (" . round($a['punya_tf']/$a['total_sipw']*100, 1) . "%)\n";

// ─── 6. PETUGAS STATS (Jember) ──────────────────────────────────────
echo "\n═══ 6. PETUGAS AKTIF (Jember) ═══\n";

$petugas = $db->fetchAll("
    SELECT
        role,
        status_akun,
        COUNT(*) AS jumlah
    FROM users
    WHERE kecamatan_bertugas IN (SELECT nmkec FROM sipw_import WHERE kdkab = '09' GROUP BY nmkec)
       OR role IN ('admin', 'operator', 'pegawai')
    GROUP BY role, status_akun
    ORDER BY role, status_akun
");
foreach ($petugas as $p) {
    printf("  %-15s %-10s : %s\n", $p['role'], $p['status_akun'], number_format($p['jumlah']));
}

// ─── 7. FILE-FILE DI data/ ──────────────────────────────────────────
echo "\n═══ 7. FILE DI data/ ═══\n";

$dataPath = __DIR__ . '/../data';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dataPath, RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($rii as $f) {
    if ($f->isDir()) continue;
    if (basename($f->getPathname()) === '.gitkeep') continue;
    if (basename($f->getPathname()) === '3C2AB340') continue;
    $size = $f->getSize();
    echo "  " . str_pad(round($size/1024, 1) . " KB", 10) . " " . $f->getPathname() . "\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  Selesai.\n";
echo "═══════════════════════════════════════════════════════════════\n";
