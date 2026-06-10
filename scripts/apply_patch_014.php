<?php
declare(strict_types=1);

/**
 * Apply Patch 014: Tambah kolom FASIH Assignment ke sipw_import & prelist_sls
 *
 * Usage:
 *   php scripts/apply_patch_014.php               → dry-run
 *   php scripts/apply_patch_014.php --execute      → apply ke database
 */

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$execute = in_array('--execute', $argv ?? [], true);

echo "=== Patch 014: FASIH Assignment Columns ===" . PHP_EOL;
echo "Mode: " . ($execute ? 'EXECUTE' : 'DRY-RUN') . PHP_EOL . PHP_EOL;

$db   = Database::instance();
$pdo  = $db->pdo();

$sqlFile = __DIR__ . '/../database/patch_014_fasih_assignment.sql';
if (!file_exists($sqlFile)) {
    echo "ERROR: File tidak ditemukan: {$sqlFile}" . PHP_EOL;
    exit(1);
}

$raw = file_get_contents($sqlFile);

// Parse SQL: remove DELIMITER lines, split statements
function parse_sql_fasih(string $sql): array
{
    $sql = preg_replace('/^DELIMITER\s+\/\/\s*$/m', '', $sql);
    $sql = preg_replace('/^DELIMITER\s+;\s*$/m', '', $sql);
    $lines = preg_split('/\R/', $sql);
    $statements = [];
    $current = '';
    $inProcedure = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) {
            if (!empty($current)) $current .= "\n";
            continue;
        }
        if (str_starts_with($trimmed, '--')) {
            if (!empty($current)) $current .= "\n";
            continue;
        }
        if (str_starts_with($trimmed, 'CREATE PROCEDURE')) {
            $inProcedure = true;
        }
        $current .= $line . "\n";
        if ($inProcedure && str_ends_with(trim($line), '//')) {
            $statements[] = rtrim($current, " \n\r\t\v\0/") . ';';
            $current = '';
            $inProcedure = false;
        } elseif (!$inProcedure && str_ends_with(trim($line), ';')) {
            $statements[] = $current;
            $current = '';
        }
    }

    if (!empty(trim($current))) {
        $statements[] = $current;
    }

    return $statements;
}

if (!$execute) {
    echo "DRY-RUN: Akan menambah 9 kolom ke prelist_sls dan 9 kolom ke sipw_import." . PHP_EOL;
    echo "Jalankan: php scripts/apply_patch_014.php --execute" . PHP_EOL . PHP_EOL;
    exit(0);
}

$statements = parse_sql_fasih($raw);

echo "Menjalankan " . count($statements) . " statement SQL..." . PHP_EOL . PHP_EOL;

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    try {
        $start = microtime(true);
        $pdo->exec($stmt);
        $elapsed = round(microtime(true) - $start, 2);
        $firstLine = strtok($stmt, "\n");
        echo "  OK ({$elapsed}s): " . substr($firstLine, 0, 90) . PHP_EOL;
    } catch (Throwable $e) {
        echo "  ERR: " . $e->getMessage() . PHP_EOL;
    }
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
    ['prelist_sls', 'fasih_bangunan'],
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
    $status = $exists ? 'OK' : 'MISSING';
    if (!$exists) $allOk = false;
    echo "  {$table}.{$col}: {$status}" . PHP_EOL;
}

echo PHP_EOL . ($allOk ? "✓ Semua kolom berhasil ditambahkan." : "✗ Ada kolom yang gagal.") . PHP_EOL;
echo PHP_EOL . "Langkah selanjutnya:" . PHP_EOL;
echo "  php scripts/import_fasih_rekap.php --dry-run" . PHP_EOL;
echo "  php scripts/import_fasih_rekap.php --execute" . PHP_EOL;
