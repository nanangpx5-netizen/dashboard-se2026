<?php

declare(strict_types=1);

use App\Core\Database;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Reader\XLSX\Options;

/**
 * Import FASIH assignment data from Rekap Prelist_Jember.xlsx
 *
 * Usage:
 *   php scripts/import_fasih_rekap.php                    # dry-run
 *   php scripts/import_fasih_rekap.php --execute          # execute
 *   php scripts/import_fasih_rekap.php --file=<path>      # custom file
 *   php scripts/import_fasih_rekap.php --execute --file=data/20260608 Rekap Prelist_35 (1) (1).xlsx  # all kab
 */

require __DIR__ . '/../src/bootstrap.php';

$execute = in_array('--execute', $argv, true);
$dryRun = !$execute;

// Parse --file= argument
$fileArg = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--file=')) {
        $fileArg = substr($arg, 7);
    }
}

$defaultFile = __DIR__ . '/../data/20260608 Rekap Prelist_Jember.xlsx';
$file = $fileArg ? (__DIR__ . '/../' . ltrim($fileArg, '/\\')) : $defaultFile;

if (!is_file($file)) {
    echo "ERROR: File tidak ditemukan: $file\n";
    exit(1);
}

echo "=== Import FASIH Assignment ===\n";
echo "Mode: " . ($dryRun ? 'DRY-RUN' : 'EXECUTE') . "\n";
echo "File: $file (" . number_format(filesize($file)) . " bytes)\n\n";

$db = Database::instance();
$pdo = $db->pdo();

// ─── Read Excel ───────────────────────────────────────────────

$options = new Options();
$options->setTempFolder(__DIR__ . '/../storage/import');
$reader = new Reader($options);
$reader->open($file);

$data = []; // [subsls_16 => [cols...]]
$count = 0;

echo "Reading file...\n";
foreach ($reader->getSheetIterator() as $sheet) {
    $rowNum = 0;
    foreach ($sheet->getRowIterator() as $row) {
        $rowNum++;
        if ($rowNum <= 2) continue; // skip header rows
        $cells = $row->toArray();
        $idsls = trim((string)($cells[3] ?? ''));
        if (empty($idsls) || strlen($idsls) < 14) continue;

        // Extract values
        $data[] = [
            'idsubsls' => $idsls,
            'idsls' => substr($idsls, 0, 14),
            'dominan' => (int)($cells[15] ?? 0),
            'fasih_kk' => (int)($cells[22] ?? 0),
            'fasih_umk' => (int)($cells[23] ?? 0),
            'fasih_um' => (int)($cells[25] ?? 0),
            'fasih_ub' => (int)($cells[26] ?? 0),
            'fasih_bangunan' => (int)($cells[28] ?? 0),
            'total_fasih' => (int)($cells[29] ?? 0),
            'flag_open_pbi' => (int)($cells[30] ?? 0),
            'kk_open_pbi' => (int)($cells[31] ?? 0),
        ];
        $count++;
    }
    break; // only first sheet
}
$reader->close();

echo "  Data rows: " . number_format($count) . "\n\n";

if ($dryRun) {
    echo "Sampel data (3 baris pertama):\n";
    for ($i = 0; $i < min(3, count($data)); $i++) {
        $d = $data[$i];
        echo "  {$d['idsubsls']}: total_fasih={$d['total_fasih']}, kk={$d['fasih_kk']}, umk={$d['fasih_umk']}, um={$d['fasih_um']}, ub={$d['fasih_ub']}, bang={$d['fasih_bangunan']}, pbi={$d['flag_open_pbi']}\n";
    }
    echo "\nJalankan dengan --execute untuk menulis ke database.\n";
    exit(0);
}

// ─── Update prelist_sls ───────────────────────────────────────

echo "Memperbarui prelist_sls...\n";

// Aggregate by 14-digit idsls for prelist_sls
$aggSls = [];
foreach ($data as $d) {
    $key = $d['idsls'];
    if (!isset($aggSls[$key])) {
        $aggSls[$key] = [
            'total_fasih' => 0,
            'fasih_kk' => 0,
            'fasih_umk' => 0,
            'fasih_um' => 0,
            'fasih_ub' => 0,
            'fasih_bangunan' => 0,
            'flag_open_pbi' => 0,
            'kk_open_pbi' => 0,
            'dominan' => $d['dominan'],
        ];
    }
    $aggSls[$key]['total_fasih'] += $d['total_fasih'];
    $aggSls[$key]['fasih_kk'] += $d['fasih_kk'];
    $aggSls[$key]['fasih_umk'] += $d['fasih_umk'];
    $aggSls[$key]['fasih_um'] += $d['fasih_um'];
    $aggSls[$key]['fasih_ub'] += $d['fasih_ub'];
    $aggSls[$key]['fasih_bangunan'] += $d['fasih_bangunan'];
    $aggSls[$key]['flag_open_pbi'] = max($aggSls[$key]['flag_open_pbi'], $d['flag_open_pbi']);
    $aggSls[$key]['kk_open_pbi'] += $d['kk_open_pbi'];
}

$stmtSls = $pdo->prepare(
    'UPDATE prelist_sls SET '
    . 'total_fasih = ?, fasih_kk = ?, fasih_umk = ?, fasih_um = ?, '
    . 'fasih_ub = ?, fasih_bangunan = ?, dominan = ?, flag_open_pbi = ?, kk_open_pbi = ? '
    . 'WHERE idsls = ?'
);

$updatedSls = 0;
$notFoundSls = 0;
$totalSls = count($aggSls);

$pdo->beginTransaction();

foreach ($aggSls as $idsls => $vals) {
    $stmtSls->execute([
        $vals['total_fasih'],
        $vals['fasih_kk'],
        $vals['fasih_umk'],
        $vals['fasih_um'],
        $vals['fasih_ub'],
        $vals['fasih_bangunan'],
        (string)$vals['dominan'],
        $vals['flag_open_pbi'],
        $vals['kk_open_pbi'],
        $idsls,
    ]);
    if ($stmtSls->rowCount() > 0) {
        $updatedSls++;
    } else {
        $notFoundSls++;
    }
}

echo "  Total SLS unik: " . number_format($totalSls) . "\n";
echo "  Diupdate: " . number_format($updatedSls) . "\n";
echo "  Tidak ditemukan: " . number_format($notFoundSls) . "\n";

// ─── Update sipw_import ───────────────────────────────────────

echo "\nMemperbarui sipw_import...\n";

$stmtSi = $pdo->prepare(
    'UPDATE sipw_import SET '
    . 'total_fasih = ?, fasih_kk = ?, fasih_umk = ?, fasih_um = ?, '
    . 'fasih_ub = ?, fasih_bangunan = ?, dominan = ?, flag_open_pbi = ?, kk_open_pbi = ? '
    . 'WHERE idsubsls = ?'
);

$updatedSi = 0;
$notFoundSi = 0;
$totalSi = count($data);

foreach ($data as $d) {
    $stmtSi->execute([
        $d['total_fasih'],
        $d['fasih_kk'],
        $d['fasih_umk'],
        $d['fasih_um'],
        $d['fasih_ub'],
        $d['fasih_bangunan'],
        (string)$d['dominan'],
        $d['flag_open_pbi'],
        $d['kk_open_pbi'],
        $d['idsubsls'],
    ]);
    if ($stmtSi->rowCount() > 0) {
        $updatedSi++;
    } else {
        $notFoundSi++;
    }
}

echo "  Total subsls: " . number_format($totalSi) . "\n";
echo "  Diupdate: " . number_format($updatedSi) . "\n";
echo "  Tidak ditemukan: " . number_format($notFoundSi) . "\n";

$pdo->commit();

echo "\n=== Selesai ===\n";
echo "  prelist_sls: {$updatedSls} updated, {$notFoundSls} not found\n";
echo "  sipw_import: {$updatedSi} updated, {$notFoundSi} not found\n";
