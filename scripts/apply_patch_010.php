<?php
/**
 * Apply patch_010 — Tambah tabel pml_reports
 * Idempotent: cek information_schema.tables sebelum CREATE TABLE.
 *
 * Usage: php scripts/apply_patch_010.php
 * Dry-run default: php scripts/apply_patch_010.php
 * Execute:         php scripts/apply_patch_010.php --execute
 */

$dryRun = !in_array('--execute', $argv ?? [], true);

require __DIR__ . '/../vendor/autoload.php';

$pdo = App\Core\Database::instance()->pdo();
$dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();

echo "=== Patch 010: pml_reports table ===\n";
echo "Database: $dbName\n";
echo "Mode: " . ($dryRun ? 'DRY-RUN (add --execute to apply)' : 'EXECUTE') . "\n\n";

// Cek apakah tabel sudah ada
$stmt = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = 'pml_reports'");
$stmt->execute([$dbName]);
$exists = (bool) $stmt->fetchColumn();

if ($exists) {
    echo "OK: tabel pml_reports sudah ada.\n";
    exit(0);
}

if ($dryRun) {
    echo "DRY-RUN: Akan membuat tabel pml_reports dengan kolom:\n";
    echo "  id, pml_id, periode, total_assigned, total_selesai, total_proses,\n";
    echo "  total_belum, catatan, submitted_at, ip_address\n";
    echo "  + INDEX idx_pml_report_pml (pml_id, periode)\n";
    echo "  + INDEX idx_pml_report_periode (periode)\n";
    echo "  + FK fk_pml_report_user → users(id)\n";
    echo "\nJalankan dengan --execute untuk menerapkan.\n";
    exit(0);
}

echo "Membuat tabel pml_reports...\n";

$pdo->exec("
    CREATE TABLE pml_reports (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        pml_id          INT NOT NULL COMMENT 'FK → users.id (role=pml)',
        periode         VARCHAR(7) NOT NULL COMMENT 'Periode laporan YYYY-MM',
        total_assigned  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total SLS dialokasikan saat laporan',
        total_selesai   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'SLS selesai dikerjakan',
        total_proses    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'SLS dalam proses',
        total_belum     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'SLS belum dikerjakan',
        catatan         TEXT NULL COMMENT 'Catatan dari PML',
        submitted_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pengiriman',
        ip_address      VARCHAR(45) NULL,
        INDEX idx_pml_report_pml (pml_id, periode),
        INDEX idx_pml_report_periode (periode),
        CONSTRAINT fk_pml_report_user FOREIGN KEY (pml_id)
            REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "OK: tabel pml_reports berhasil dibuat.\n";
