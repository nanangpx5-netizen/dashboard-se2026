<?php
/**
 * scripts/apply_patch_009.php
 *
 * Apply migration: tambah kolom kecamatan_tugas (1:1) ke tabel users.
 *
 * Usage:
 *   php scripts/apply_patch_009.php
 *
 * Rekomendasi: Pembatasan akses berbasis kecamatan untuk role pegawai.
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$db   = Database::instance();
$pdo  = $db->pdo();

$sql = file_get_contents(__DIR__ . '/../database/patch_009_pegawai_kecamatan.sql');

$statements = parse_sql($sql);

echo "=== Apply patch_009 — users.kecamatan_tugas (1:1 scope) ===\n\n";

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

echo "\n--- Create indexes (idempotent) ---\n";
$indexes = [
    'idx_users_kec_tugas' => 'kecamatan_tugas, role',
];
foreach ($indexes as $name => $col) {
    $exists = (int) $db->fetchColumn("
        SELECT COUNT(*) FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'users'
          AND index_name = ?
    ", [$name]);
    if ($exists > 0) {
        echo "SKIP: $name already exists\n";
        continue;
    }
    try {
        $pdo->exec("CREATE INDEX {$name} ON users ({$col})");
        echo "OK:   $name created on ({$col})\n";
    } catch (Throwable $e) {
        echo "ERR:  $name — " . $e->getMessage() . "\n";
    }
}

echo "\n--- Verifikasi schema users ---\n";
$cols = $pdo->query("DESCRIBE users")->fetchAll();
$relevant = ['kecamatan_bertugas', 'kecamatan_tugas'];
foreach ($cols as $c) {
    if (in_array($c['Field'], $relevant, true)) {
        echo sprintf("  %-30s %s\n", $c['Field'], $c['Type']);
    }
}

echo "\n--- Verifikasi indexes ---\n";
$idx = $pdo->query("
    SELECT INDEX_NAME, COLUMN_NAME
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND INDEX_NAME LIKE 'idx_users_kec%'
    ORDER BY INDEX_NAME, SEQ_IN_INDEX
")->fetchAll();
foreach ($idx as $i) {
    echo "  {$i['INDEX_NAME']} ({$i['COLUMN_NAME']})\n";
}

echo "\n--- Assign default kecamatan_tugas untuk 5 pegawai ---\n";
$assignments = [
    'pegawai_ali'   => '3509010',  // KENCONG
    'pegawai_budi'  => '3509070',  // SILO
    'pegawai_citra' => '3509180',  // TANGGUL
    'pegawai_dani'  => '3509190',  // BANGSALSARI
    'pegawai_erni'  => '3509710',  // KALIWATES
];
foreach ($assignments as $username => $kdkec) {
    $nmkec = $db->fetchColumn("SELECT nm_kec FROM prelist_kecamatan WHERE kd_kec = ?", [$kdkec]);
    try {
        $stmt = $pdo->prepare("UPDATE users SET kecamatan_tugas = ? WHERE username = ?");
        $stmt->execute([$kdkec, $username]);
        $affected = $stmt->rowCount();
        echo sprintf("  %-15s → %s (%s) — %d row(s)\n", $username, $kdkec, $nmkec, $affected);
    } catch (Throwable $e) {
        echo "  $username — ERR: " . $e->getMessage() . "\n";
    }
}

/**
 * Parse SQL with DELIMITER directive (MySQL CLI syntax).
 * Returns array of statements (excluding delimiter changes themselves).
 * Trailing delimiter is stripped.
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
