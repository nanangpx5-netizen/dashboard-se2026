<?php
/**
 * scripts/apply_patch_015.php
 *
 * Apply migration: lk_petugas + lk_pairing tables + wilayah_kerja columns.
 * Idempotent via information_schema + DELIMITER/CREATE PROCEDURE pattern.
 * Dry-run default.
 *
 * Usage:
 *   php scripts/apply_patch_015.php              # dry-run
 *   php scripts/apply_patch_015.php --execute     # apply
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$execute = in_array('--execute', $argv);
$dryRun = !$execute;

$db = Database::instance();
$pdo = $db->pdo();

echo "=== Apply patch_015 — LK Pairing tables ===\n\n";

function parse_sql(string $sql): array
{
    $sql = preg_replace('/^DELIMITER\s+\/\/\s*/m', '', $sql);
    $sql = preg_replace('/^DELIMITER\s+;\s*/m', '', $sql);
    $sql = str_replace('DELIMITER //', '', $sql);
    $sql = str_replace('DELIMITER ;', '', $sql);

    $statements = [];
    $current = '';
    $inProcedure = false;

    foreach (preg_split('/\R/', $sql) as $line) {
        $trimmed = trim($line);
        if (empty($trimmed) || str_starts_with($trimmed, '--')) {
            if (!empty($current)) $current .= "\n";
            continue;
        }

        if (str_starts_with($trimmed, 'CREATE PROCEDURE') || str_starts_with($trimmed, 'CREATE')) {
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

if ($dryRun) {
    echo "DRY-RUN: Akan membuat tabel lk_petugas, lk_pairing, dan kolom wilayah_kerja.\n";
    echo "Jalankan: php " . basename(__FILE__) . " --execute\n\n";
    echo "Tabel yang akan dibuat:\n";
    echo "  - lk_petugas (PPL+PML master dari file LK Pairing)\n";
    echo "  - lk_pairing (Pairing PPL↔SLS↔PML)\n";
    echo "  - wilayah_kerja.aktual_ppl_lk\n";
    echo "  - wilayah_kerja.aktual_pml_lk\n\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/../database/patch_015_lk_pairing.sql');
$statements = parse_sql($sql);

echo "Menjalankan patch...\n\n";

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt)) continue;
    try {
        $start = microtime(true);
        $pdo->exec($stmt);
        $elapsed = round(microtime(true) - $start, 2);
        $firstLine = strtok($stmt, "\n");
        echo "  OK ({$elapsed}s): " . substr($firstLine, 0, 80) . "\n";
    } catch (Throwable $e) {
        echo "  ERR: " . $e->getMessage() . "\n";
    }
}

echo "\n--- Verifikasi schema ---\n";

$tables = ['lk_petugas', 'lk_pairing'];
foreach ($tables as $tbl) {
    $exists = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?",
        [$tbl]
    );
    echo sprintf("  %-15s: %s\n", $tbl, $exists ? 'EXISTS' : 'MISSING');
}

$cols = ['aktual_ppl_lk', 'aktual_pml_lk'];
foreach ($cols as $col) {
    $exists = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='wilayah_kerja' AND COLUMN_NAME=?",
        [$col]
    );
    echo sprintf("  wilayah_kerja.%-15s: %s\n", $col, $exists ? 'EXISTS' : 'MISSING');
}

echo "\nSelesai.\n";
