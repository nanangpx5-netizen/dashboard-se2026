<?php
require __DIR__ . '/../src/bootstrap.php';
use App\Core\Database;
$db = Database::instance()->pdo();

$sql = file_get_contents(__DIR__ . '/../database/patch_006_collation_harmonization.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $stmt) {
    if (empty($stmt) || str_starts_with($stmt, '--')) continue;
    try {
        $start = microtime(true);
        $db->exec($stmt);
        $elapsed = round(microtime(true) - $start, 2);
        $firstLine = strtok($stmt, "\n");
        echo "OK ({$elapsed}s): " . substr($firstLine, 0, 70) . "\n";
    } catch (Throwable $e) {
        echo "ERR: " . $e->getMessage() . "\n";
    }
}

echo "\n--- Verifikasi collations ---\n";
$rows = $db->query("
    SELECT TABLE_NAME, TABLE_COLLATION
    FROM information_schema.tables
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME LIKE 'prelist_%' OR TABLE_NAME LIKE 'sipw_%'
    ORDER BY TABLE_NAME
")->fetchAll();
foreach ($rows as $r) {
    echo sprintf("  %-25s %s\n", $r['TABLE_NAME'], $r['TABLE_COLLATION']);
}
