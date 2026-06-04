<?php
/**
 * scripts/apply_patch_008.php
 *
 * Apply migration: tambah tabel petugas_wilayah.
 *
 * Usage:
 *   php scripts/apply_patch_008.php
 *
 * Rekomendasi: R1.2 dari Laporan Analisis Pegawai Organik.
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$db = Database::instance()->pdo();

$sql = file_get_contents(__DIR__ . '/../database/patch_008_petugas_wilayah.sql');

echo "=== Apply patch_008 — tabel petugas_wilayah ===\n\n";

try {
    $start = microtime(true);
    $db->exec($sql);
    $elapsed = round(microtime(true) - $start, 2);
    echo "OK ({$elapsed}s): CREATE TABLE petugas_wilayah\n";
} catch (Throwable $e) {
    echo "ERR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n--- Verifikasi schema ---\n";
$cols = $db->query("DESCRIBE petugas_wilayah")->fetchAll();
foreach ($cols as $c) {
    echo sprintf("  %-18s %s\n", $c['Field'], $c['Type']);
}

echo "\n--- Verifikasi indexes ---\n";
$idx = $db->query("SHOW INDEX FROM petugas_wilayah")->fetchAll();
$seen = [];
foreach ($idx as $i) {
    $key = $i['Key_name'];
    if (!isset($seen[$key])) {
        $seen[$key] = true;
        echo "  {$key}\n";
    }
}
