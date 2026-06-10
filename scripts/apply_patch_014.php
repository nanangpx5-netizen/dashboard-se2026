<?php
/**
 * Apply Patch 014: Tambah kolom FASIH Assignment ke sipw_import & prelist_sls
 *
 * Usage:
 *   php scripts/apply_patch_014.php          → dry-run (show SQL only)
 *   php scripts/apply_patch_014.php --execute → apply ke database
 */

declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

$execute = in_array('--execute', $argv ?? [], true);

echo "=== Patch 014: FASIH Assignment Columns ===" . PHP_EOL;
echo "Mode: " . ($execute ? 'EXECUTE' : 'DRY-RUN') . PHP_EOL . PHP_EOL;

$db   = App\Core\Database::getInstance();
$pdo  = $db->pdo();

$sqlFile = __DIR__ . '/../database/patch_014_fasih_assignment.sql';
if (!file_exists($sqlFile)) {
    echo "ERROR: File tidak ditemukan: {$sqlFile}" . PHP_EOL;
    exit(1);
}

$sql = file_get_contents($sqlFile);

if (!$execute) {
    echo "SQL yang akan dijalankan:" . PHP_EOL;
    echo str_repeat('-', 60) . PHP_EOL;
    echo $sql . PHP_EOL;
    echo str_repeat('-', 60) . PHP_EOL;
    echo PHP_EOL . "Jalankan ulang dengan --execute untuk apply." . PHP_EOL;
    exit(0);
}

try {
    // Jalankan sebagai multi-statement via PDO exec
    $pdo->exec($sql);
    echo "✓ Patch 014 berhasil diaplikasikan." . PHP_EOL;
} catch (\Throwable $e) {
    echo "✗ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Verifikasi
echo PHP_EOL . "=== Verifikasi Kolom ===" . PHP_EOL;
$checks = [
    ['sipw_import', 'total_fasih'],
    ['sipw_import', 'fasih_kk'],
    ['sipw_import', 'fasih_umk'],
    ['sipw_import', 'fasih_um'],
    ['sipw_import', 'fasih_ub'],
    ['sipw_import', 'fasih_bangunan'],
    ['sipw_import', 'dominan'],
    ['sipw_import', 'flag_open_pbi'],
    ['sipw_import', 'kk_open_pbi'],
    ['prelist_sls', 'total_fasih'],
    ['prelist_sls', 'fasih_kk'],
    ['prelist_sls', 'fasih_umk'],
    ['prelist_sls', 'fasih_um'],
    ['prelist_sls', 'fasih_ub'],
    ['prelist_sls', 'dominan'],
    ['prelist_sls', 'flag_open_pbi'],
    ['prelist_sls', 'kk_open_pbi'],
];

$allOk = true;
foreach ($checks as [$table, $col]) {
    $exists = (int)$db->fetchColumn(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$table, $col]
    );
    $status = $exists ? '✓' : '✗ MISSING';
    echo "  {$table}.{$col}: {$status}" . PHP_EOL;
    if (!$exists) $allOk = false;
}

echo PHP_EOL . ($allOk ? "✓ Semua kolom berhasil ditambahkan." : "✗ Ada kolom yang gagal.") . PHP_EOL;
echo PHP_EOL . "Langkah selanjutnya:" . PHP_EOL;
echo "  php scripts/import_fasih_rekap.php --dry-run" . PHP_EOL;
echo "  php scripts/import_fasih_rekap.php --execute" . PHP_EOL;
