<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
$root = 'C:/laragon/www/dashboard-se2026';
require $root . '/src/bootstrap.php';
use App\Core\Database;

$db = Database::getInstance();
$file = $root . '/data/rekap-sls.xlsx';
echo "DB: " . $db->getCurrentDatabase() . "\n";

// ─── Buat tabel ─────────────────────────────────────────────────
$db->pdo()->exec("DROP TABLE IF EXISTS master_sls");
$db->pdo()->exec("
    CREATE TABLE master_sls (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        no          INT DEFAULT NULL,
        kode        VARCHAR(20) DEFAULT NULL,
        sls         VARCHAR(255) DEFAULT NULL,
        kode_sls    VARCHAR(10) DEFAULT NULL,
        desa        VARCHAR(100) DEFAULT NULL,
        kecamatan   VARCHAR(100) DEFAULT NULL,
        kabupaten   VARCHAR(100) DEFAULT 'JEMBER',
        provinsi    VARCHAR(100) DEFAULT 'JAWA TIMUR',
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_kode (kode),
        KEY idx_kecamatan (kecamatan),
        KEY idx_desa (desa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
echo "Table master_sls created\n";

// ─── Import ─────────────────────────────────────────────────────
$reader = new OpenSpout\Reader\XLSX\Reader();
$reader->open($file);

$stmt = $db->pdo()->prepare(
    "INSERT INTO master_sls (no, kode, sls, kode_sls, desa, kecamatan, kabupaten, provinsi)
     VALUES (?, ?, ?, ?, ?, ?, 'JEMBER', 'JAWA TIMUR')
     ON DUPLICATE KEY UPDATE
         sls=VALUES(sls), kode_sls=VALUES(kode_sls),
         desa=VALUES(desa), kecamatan=VALUES(kecamatan)"
);

$total = 0; $ins = 0; $err = 0;
$batch = [];
$detailSheetProcessed = false;

foreach ($reader->getSheetIterator() as $sheet) {
    // Skip sheet "Tabel Pivot 1" (rekap)
    if ($sheet->getName() === 'Tabel Pivot 1') {
        echo "Skipping sheet: {$sheet->getName()}\n";
        continue;
    }

    echo "Processing sheet: {$sheet->getName()}\n";
    $detailSheetProcessed = true;

    foreach ($sheet->getRowIterator() as $i => $row) {
        if ($i === 1) {
            // Verify header
            $h = json_encode($row->toArray(), JSON_UNESCAPED_UNICODE);
            if ($i === 1) echo "  Header: $h\n";
            continue;
        }

        $c = $row->toArray();
        if (empty(array_filter($c))) continue;
        $total++;

        $d = [
            (int)($c[0] ?? 0),
            (string)($c[1] ?? ''),
            (string)($c[2] ?? ''),
            (string)($c[3] ?? ''),
            (string)($c[4] ?? ''),
            (string)($c[5] ?? ''),
        ];
        $batch[] = $d;

        if (count($batch) >= 500) {
            foreach ($batch as $bd) {
                try { $stmt->execute($bd); $ins++; }
                catch (\Throwable $e) { $err++; }
            }
            $batch = [];
            echo "  $total rows imported...\n";
        }
    }
}

$reader->close();

if ($batch) {
    foreach ($batch as $bd) {
        try { $stmt->execute($bd); $ins++; }
        catch (\Throwable $e) { $err++; }
    }
}

if (!$detailSheetProcessed) {
    echo "ERROR: Tidak menemukan sheet detail SLS\n";
    exit(1);
}

$final = $db->count('master_sls');
echo "\n=== IMPORT MASTER SLS SELESAI ===\n";
echo "Total rows in file: " . number_format($total) . "\n";
echo "Inserted: " . number_format($ins) . "\n";
echo "Errors: $err\n";
echo "master_sls count: " . number_format($final) . "\n";

// ─── Perbandingan ───────────────────────────────────────────────
echo "\n=== PERBANDINGAN PER KECAMATAN ===\n";
echo str_pad("KECAMATAN", 18) . str_pad("MASTER", 10) . str_pad("SIPW", 10) . str_pad("SELISIH", 10) . "\n";
echo str_repeat("-", 48) . "\n";

$masterKec = $db->fetchAll("SELECT kecamatan, COUNT(*) as cnt FROM master_sls GROUP BY kecamatan ORDER BY kecamatan");
$sipwKec = $db->fetchAll("SELECT nmkec, COUNT(*) as cnt FROM sipw_import GROUP BY nmkec ORDER BY nmkec");

$masterMap = [];
foreach ($masterKec as $r) $masterMap[strtoupper(trim($r['kecamatan']))] = $r['cnt'];
$sipwMap = [];
foreach ($sipwKec as $r) $sipwMap[strtoupper(trim($r['nmkec']))] = $r['cnt'];

$allKec = array_unique(array_merge(array_keys($masterMap), array_keys($sipwMap)));
sort($allKec);

$totalMaster = 0; $totalSipw = 0;
foreach ($allKec as $k) {
    $m = $masterMap[$k] ?? 0;
    $s = $sipwMap[$k] ?? 0;
    $diff = $s - $m;
    echo str_pad($k, 18) . str_pad(number_format($m), 10) . str_pad(number_format($s), 10) . str_pad(($diff > 0 ? '+' : '') . number_format($diff), 10) . "\n";
    $totalMaster += $m;
    $totalSipw += $s;
}
echo str_repeat("-", 48) . "\n";
echo str_pad("TOTAL", 18) . str_pad(number_format($totalMaster), 10) . str_pad(number_format($totalSipw), 10) . str_pad(number_format($totalSipw - $totalMaster), 10) . "\n";
