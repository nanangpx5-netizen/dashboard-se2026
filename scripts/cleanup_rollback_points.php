<?php
/**
 * scripts/cleanup_rollback_points.php
 *
 * Membersihkan tabel dash_rollback_points:
 *  1. Pindahkan rows is_used=0 ke dash_rollback_points_archive (rollback tidak pernah dipakai)
 *  2. Hapus rows is_used=0 dari main table
 *  3. Pindahkan rows > 30 hari (regardless of is_used) ke archive
 *  4. Hapus rows > 30 hari dari main table
 *  5. OPTIMIZE TABLE untuk reclaim space
 *
 * Usage:
 *   php scripts/cleanup_rollback_points.php                 (dry-run, default)
 *   php scripts/cleanup_rollback_points.php --execute       (eksekusi)
 *   php scripts/cleanup_rollback_points.php --keep-unused   (keep is_used=0, hanya archive yang > 30 hari)
 *
 * Tabel dash_rollback_points menyimpan JSON snapshot of old_data per import.
 * 22 rows = 112 MB. 100% rows is_used=0, never reused → bloat.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;
use App\Helpers\Env;

$db = Database::getInstance();
$envFile = __DIR__ . '/../.env';
Env::load($envFile);

$execute    = in_array('--execute', $argv, true);
$keepUnused = in_array('--keep-unused', $argv, true);
$days       = 30;

echo "=== dash_rollback_points cleanup ===\n";
echo "Mode:        " . ($execute ? 'EXECUTE' : 'DRY-RUN (use --execute to apply)') . "\n";
echo "Threshold:   {$days} days\n";
echo "Keep unused: " . ($keepUnused ? 'YES (only archive > 30d)' : 'NO (archive ALL is_used=0)') . "\n\n";

$totalRows = (int) $db->fetchColumn("SELECT COUNT(*) FROM dash_rollback_points");
$totalSize = (int) $db->fetchColumn(
    "SELECT COALESCE(SUM(LENGTH(old_data) + LENGTH(row_ids)), 0) FROM dash_rollback_points"
);
$oldRows   = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM dash_rollback_points WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
    [$days]
);
$usedRows  = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM dash_rollback_points WHERE is_used = 1"
);
$unusedRows = $totalRows - $usedRows;
$toArchive = $keepUnused ? $oldRows : $unusedRows;

echo "Statistics:\n";
echo "  Total rows:        " . number_format($totalRows) . "\n";
echo "  Total payload:     " . round($totalSize / 1024 / 1024, 2) . " MB\n";
echo "  Used (is_used=1):  " . number_format($usedRows) . "\n";
echo "  Unused (is_used=0): " . number_format($unusedRows) . "\n";
echo "  Old (>{$days}d):       " . number_format($oldRows) . "\n";
echo "  To archive:        " . number_format($toArchive) . " rows (estimate: " . round(($toArchive / max($totalRows, 1)) * $totalSize / 1024 / 1024, 2) . " MB)\n\n";

if ($totalRows === 0) {
    echo "Nothing to clean.\n";
    exit(0);
}

$archiveTableExists = $db->tableExists('dash_rollback_points_archive');

echo "Plan:\n";
if (!$archiveTableExists && $toArchive > 0) {
    echo "  1. CREATE TABLE dash_rollback_points_archive (LIKE dash_rollback_points)\n";
} else {
    echo "  1. Archive table " . ($archiveTableExists ? "exists" : "(will create)") . "\n";
}
echo "  2. Archive " . number_format($toArchive) . " rows (is_used=0" . ($keepUnused ? " AND >{$days}d" : "") . ")\n";
echo "  3. DELETE archived rows from main table\n";
echo "  4. OPTIMIZE TABLE dash_rollback_points\n\n";

if (!$execute) {
    echo "*** DRY-RUN. Use --execute to apply. ***\n";
    exit(0);
}

$db->beginTransaction();
try {
    if (!$archiveTableExists && $toArchive > 0) {
        $db->query("CREATE TABLE dash_rollback_points_archive LIKE dash_rollback_points");
        echo "  [OK] Created dash_rollback_points_archive\n";
    }

    if ($toArchive > 0) {
        $whereClause = $keepUnused
            ? "WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
            : "WHERE is_used = 0";
        $params = $keepUnused ? [$days] : [];

        $archived = $db->query(
            "INSERT INTO dash_rollback_points_archive
             SELECT * FROM dash_rollback_points {$whereClause}",
            $params
        )->rowCount();
        echo "  [OK] Archived {$archived} rows\n";

        $deleted = $db->query(
            "DELETE FROM dash_rollback_points {$whereClause}",
            $params
        )->rowCount();
        echo "  [OK] Deleted {$deleted} rows from main table\n";
    } else {
        echo "  [skip] No rows to archive\n";
    }

    $db->commit();
    echo "  [OK] Transaction committed\n";
} catch (\Throwable $e) {
    $db->rollback();
    echo "  [FAIL] " . $e->getMessage() . "\n";
    echo "  Transaction rolled back.\n";
    exit(1);
}

echo "\n  Running OPTIMIZE TABLE (this may take a moment)...\n";
$db->query("OPTIMIZE TABLE dash_rollback_points");
echo "  [OK] OPTIMIZE TABLE complete\n";

$afterRows = (int) $db->fetchColumn("SELECT COUNT(*) FROM dash_rollback_points");
$afterSize = (int) $db->fetchColumn(
    "SELECT COALESCE(SUM(LENGTH(old_data) + LENGTH(row_ids)), 0) FROM dash_rollback_points"
);
echo "\nAfter cleanup:\n";
echo "  Total rows:    " . number_format($afterRows) . " (was " . number_format($totalRows) . ")\n";
echo "  Total payload: " . round($afterSize / 1024 / 1024, 2) . " MB (was " . round($totalSize / 1024 / 1024, 2) . " MB)\n";
echo "  Saved:         " . round(($totalSize - $afterSize) / 1024 / 1024, 2) . " MB\n";
