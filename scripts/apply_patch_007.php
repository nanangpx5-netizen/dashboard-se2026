<?php
/**
 * scripts/apply_patch_007.php
 *
 * Apply migration: tambah audit trail + progress tracking ke sipw_assignment.
 * Handle DELIMITER directive + CREATE PROCEDURE + idempotent via information_schema.
 *
 * Usage:
 *   php scripts/apply_patch_007.php
 *
 * Rekomendasi: R2.1 dari Laporan Analisis Pegawai Organik.
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$db = Database::instance();
$pdo = $db->pdo();

$sql = file_get_contents(__DIR__ . '/../database/patch_007_assignment_audit.sql');

$statements = parse_sql($sql);

echo "=== Apply patch_007 — sipw_assignment audit & progress ===\n\n";

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt) || str_starts_with($stmt, '--')) continue;
    try {
        $start = microtime(true);
        $pdo->exec($stmt);
        $elapsed = round(microtime(true) - $start, 2);
        $firstLine = strtok($stmt, "\n");
        echo "OK ({$elapsed}s): " . substr($firstLine, 0, 80) . "\n";
    } catch (Throwable $e) {
        $firstLine = strtok($stmt, "\n");
        echo "ERR: " . substr($firstLine, 0, 80) . "\n";
        echo "     " . $e->getMessage() . "\n";
    }
}

echo "\n--- Verifikasi schema sipw_assignment ---\n";
$cols = $pdo->query("DESCRIBE sipw_assignment")->fetchAll();
foreach ($cols as $c) {
    echo sprintf("  %-22s %s\n", $c['Field'], $c['Type']);
}

echo "\n--- Create indexes (idempotent) ---\n";
$indexes = [
    'idx_assignment_created_by' => 'created_by',
    'idx_assignment_status'      => 'status',
    'idx_assignment_progress'    => 'progress_pct',
];
foreach ($indexes as $name => $col) {
    $exists = (int) $db->fetchColumn("
        SELECT COUNT(*) FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'sipw_assignment'
          AND index_name = ?
    ", [$name]);
    if ($exists > 0) {
        echo "SKIP: $name already exists\n";
        continue;
    }
    try {
        $pdo->exec("CREATE INDEX {$name} ON sipw_assignment ({$col})");
        echo "OK:   $name created on ($col)\n";
    } catch (Throwable $e) {
        echo "ERR:  $name — " . $e->getMessage() . "\n";
    }
}

echo "\n--- Verifikasi indexes (idx_*) ---\n";
$idx = $pdo->query("
    SELECT INDEX_NAME, COLUMN_NAME
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'sipw_assignment'
      AND INDEX_NAME LIKE 'idx_%'
    ORDER BY INDEX_NAME, SEQ_IN_INDEX
")->fetchAll();
foreach ($idx as $i) {
    echo "  {$i['INDEX_NAME']} ({$i['COLUMN_NAME']})\n";
}

/**
 * Parse SQL with DELIMITER directive (MySQL CLI syntax).
 * Returns array of statements (excluding the delimiter changes themselves).
 * Trailing delimiter on each statement is stripped.
 */
function parse_sql(string $sql): array
{
    $delimiter = ';';
    $buffer = '';
    $out = [];
    $lines = preg_split('/\R/', $sql);
    foreach ($lines as $line) {
        $trim = trim($line);
        if (preg_match('/^DELIMITER\s+(.+)$/i', $trim, $m)) {
            if (trim($buffer) !== '') {
                $out[] = rtrim($buffer);
                $buffer = '';
            }
            $delimiter = trim($m[1]);
            continue;
        }
        $buffer .= $line . "\n";
        if ($delimiter !== '' && substr($trim, -strlen($delimiter)) === $delimiter) {
            $out[] = substr(rtrim($buffer), 0, -strlen($delimiter));
            $buffer = '';
        }
    }
    if (trim($buffer) !== '') {
        $out[] = rtrim($buffer);
    }
    return $out;
}
