<?php
/**
 * Import full hosting DB ke localhost + merge activity_logs + apply patch
 *
 * Usage:
 *   php scripts/import_hosting_db.php <dump_file.sql> [options]
 *
 * Options:
 *   --target-db=<name>     Nama database lokal tujuan (default: bps_jember_se2026_hosting)
 *   --merge-logs           Merge activity_logs dari dump ke local DB utama
 *   --apply-patches        Apply patch_007..013 setelah import
 *   --drop-target          Drop target DB jika sudah ada lalu buat ulang
 *   --dry-run              Hanya simulasi, tidak eksekusi
 *
 * Examples:
 *   # Import ke DB baru + apply patches
 *   php scripts/import_hosting_db.php dump_hosting_bpsjembe_se2026_jember_2026-06-07_1714.sql --apply-patches
 *
 *   # Merge activity_logs ke local DB utama
 *   php scripts/import_hosting_db.php dump_hosting_*.sql --merge-logs --dry-run
 *
 *   # Full: import + patches + merge logs
 *   php scripts/import_hosting_db.php dump_hosting_*.sql --drop-target --apply-patches --merge-logs
 */

$args = $_SERVER['argv'] ?? [];
$dumpFile = $args[1] ?? null;

if (!$dumpFile || in_array($dumpFile, ['--help', '-h'])) {
    echo "Usage: php scripts/import_hosting_db.php <dump_file.sql> [options]\n\n";
    echo "Options:\n";
    echo "  --target-db=<name>   Target database name (default: bps_jember_se2026_hosting)\n";
    echo "  --merge-logs         Merge activity_logs from dump into local main DB\n";
    echo "  --apply-patches      Apply patch_007..013 after import\n";
    echo "  --drop-target        Drop & recreate target DB before import\n";
    echo "  --dry-run            Simulate only, no execution\n\n";
    echo "Examples:\n";
    echo "  php scripts/import_hosting_db.php dump.sql --apply-patches\n";
    echo "  php scripts/import_hosting_db.php dump.sql --merge-logs --dry-run\n";
    exit(1);
}

if (!file_exists($dumpFile)) {
    die("Error: File '$dumpFile' tidak ditemukan.\n");
}

// ─── Parse options ────────────────────────────────────────────

$options = [];
foreach (array_slice($args, 2) as $arg) {
    if (str_starts_with($arg, '--')) {
        $parts = explode('=', substr($arg, 2), 2);
        $options[$parts[0]] = $parts[1] ?? true;
    }
}

$targetDb      = $options['target-db'] ?? 'bps_jember_se2026_hosting';
$mergeLogs     = !empty($options['merge-logs']);
$applyPatches  = !empty($options['apply-patches']);
$dropTarget    = !empty($options['drop-target']);
$dryRun        = !empty($options['dry-run']);

$localDbName   = 'bps_jember_se2026'; // DB lokal utama
$dumpFileReal  = realpath($dumpFile);
$dumpSize      = filesize($dumpFileReal);

// ─── Display plan ─────────────────────────────────────────────

echo "==========================================\n";
echo " IMPORT HOSTING DB TO LOCAL\n";
echo "==========================================\n";
echo "  Dump file   : $dumpFileReal\n";
echo "  Size        : " . round($dumpSize / 1024 / 1024, 2) . " MB\n";
echo "  Target DB   : $targetDb\n";
echo "  Drop target : " . ($dropTarget ? 'YES' : 'NO') . "\n";
echo "  Apply patch : " . ($applyPatches ? 'YES' : 'NO') . "\n";
echo "  Merge logs  : " . ($mergeLogs ? 'YES (→ ' . $localDbName . ')' : 'NO') . "\n";
echo "  Dry run     : " . ($dryRun ? 'YES' : 'NO') . "\n";
echo "------------------------------------------\n";

if ($dryRun) {
    echo "  [DRY RUN] Tidak ada perubahan yang dieksekusi.\n";
    echo "==========================================\n";
    exit(0);
}

// ─── Confirmation ─────────────────────────────────────────────

echo "\nLanjutkan? (y/N): ";
$handle = fopen('php://stdin', 'r');
$confirm = trim(fgets($handle));
fclose($handle);

if (!in_array(strtolower($confirm), ['y', 'yes'])) {
    echo "Dibatalkan.\n";
    exit(0);
}

// ─── Database connection (local root) ─────────────────────────

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("Error: Gagal konek MySQL lokal — " . $e->getMessage() . "\n");
}

// ─── Step 1: Drop & create target DB ─────────────────────────

if ($dropTarget) {
    echo "\n== [1/4] Drop & recreate target DB: $targetDb ==\n";
    $pdo->exec("DROP DATABASE IF EXISTS `$targetDb`");
    $pdo->exec("CREATE DATABASE `$targetDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "  Done.\n";
} else {
    // Cek apakah DB sudah ada
    $exists = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$targetDb'")->fetch();
    if ($exists) {
        echo "\n== [1/4] Target DB '$targetDb' sudah ada. (skip) ==\n";
    } else {
        echo "\n== [1/4] Create target DB: $targetDb ==\n";
        $pdo->exec("CREATE DATABASE `$targetDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "  Done.\n";
    }
}

// ─── Step 2: Import dump ─────────────────────────────────────

echo "\n== [2/4] Import dump ke $targetDb ==\n";

$cmd = sprintf(
    'mysql -u %s %s %s < %s 2>&1',
    escapeshellarg($user),
    $pass ? '-p' . escapeshellarg($pass) : '',
    escapeshellarg($targetDb),
    escapeshellarg($dumpFileReal)
);

echo "  Running: mysql ... < " . basename($dumpFileReal) . "\n";

$startTime = microtime(true);
exec($cmd, $output, $exitCode);

if ($exitCode !== 0) {
    echo "  ⚠️  Import selesai dengan peringatan:\n";
    foreach ($output as $line) {
        echo "    $line\n";
    }
} else {
    echo "  ✅ Import sukses.\n";
}

$elapsed = round(microtime(true) - $startTime, 2);
echo "  Waktu: {$elapsed}s\n";

// ─── Step 3: Apply patches ───────────────────────────────────

if ($applyPatches) {
    echo "\n== [3/4] Apply patch_007..013 ke $targetDb ==\n";

    $patchFiles = [
        '007' => __DIR__ . '/../database/patch_007_assignment_audit.sql',
        '008' => __DIR__ . '/../database/patch_008_petugas_wilayah.sql',
        '009' => __DIR__ . '/../database/patch_009_pegawai_kecamatan.sql',
        '010' => __DIR__ . '/../database/patch_010_pml_reports.sql',
        '010_sipw_klas' => __DIR__ . '/../database/patch_010_sipw_klas.sql',
        '011' => __DIR__ . '/../database/patch_011_prelist_new_params.sql',
        '012' => __DIR__ . '/../database/patch_012_mitra_details.sql',
        '013' => __DIR__ . '/../database/patch_013_user_posisi_tugas.sql',
    ];

    foreach ($patchFiles as $label => $file) {
        if (!file_exists($file)) {
            echo "  ⚠️  patch_$label.sql tidak ditemukan (skip)\n";
            continue;
        }

        $cmd = sprintf(
            'mysql -u %s %s %s < %s 2>&1',
            escapeshellarg($user),
            $pass ? '-p' . escapeshellarg($pass) : '',
            escapeshellarg($targetDb),
            escapeshellarg($file)
        );

        exec($cmd, $output, $exitCode);
        if ($exitCode === 0) {
            echo "  ✅ patch_$label applied\n";
        } else {
            echo "  ❌ patch_$label gagal:\n";
            foreach ($output as $line) {
                echo "     $line\n";
            }
        }
    }
} else {
    echo "\n== [3/4] Apply patches: SKIP (--apply-patches tidak diberikan) ==\n";
}

// ─── Step 4: Merge activity_logs ─────────────────────────────

if ($mergeLogs) {
    echo "\n== [4/4] Merge activity_logs dari $targetDb ke $localDbName ==\n";

    // Cek max ID di local
    $localMax = $pdo->query("SELECT COALESCE(MAX(id), 0) FROM `$localDbName`.activity_logs")->fetchColumn();
    echo "  Max ID di $localDbName.activity_logs = $localMax\n";

    // Cek berapa baris yang > localMax di target
    $newCount = $pdo->query("SELECT COUNT(*) FROM `$targetDb`.activity_logs WHERE id > $localMax")->fetchColumn();
    echo "  Baris baru di hosting (id > $localMax): $newCount\n";

    if ($newCount > 0) {
        $stmt = $pdo->query("SELECT * FROM `$targetDb`.activity_logs WHERE id > $localMax ORDER BY id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $inserted = 0;
        $pdo->beginTransaction();
        try {
            $insertStmt = $pdo->prepare(
                "INSERT INTO `$localDbName`.activity_logs (id, user_id, `action`, module, detail, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            foreach ($rows as $row) {
                $insertStmt->execute([
                    $row['id'],
                    $row['user_id'],
                    $row['action'],
                    $row['module'],
                    $row['detail'],
                    $row['ip_address'],
                    $row['user_agent'],
                    $row['created_at'],
                ]);
                $inserted++;
            }

            $pdo->commit();
            echo "  ✅ $inserted baris baru di-insert ke $localDbName.activity_logs\n";
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "  ❌ Gagal merge: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  ℹ️  Tidak ada baris baru (hosting sudah tertinggal atau sama).\n";
    }

    // Update auto_increment
    $maxId = $pdo->query("SELECT COALESCE(MAX(id), 0) FROM `$localDbName`.activity_logs")->fetchColumn();
    $pdo->exec("ALTER TABLE `$localDbName`.activity_logs AUTO_INCREMENT = " . ($maxId + 1));
    echo "  Auto increment diset ke " . ($maxId + 1) . "\n";
} else {
    echo "\n== [4/4] Merge logs: SKIP (--merge-logs tidak diberikan) ==\n";
}

// ─── Summary ─────────────────────────────────────────────────

$targetCount = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$targetDb'")->fetchColumn();

echo "\n==========================================\n";
echo " SUMMARY\n";
echo "==========================================\n";
echo "  Target DB $targetDb : $targetCount tables\n";
echo "  Logs merged        : " . ($mergeLogs ? 'YES' : 'NO') . "\n";
echo "  Patches applied    : " . ($applyPatches ? 'YES' : 'NO') . "\n";
echo "==========================================\n";
echo "\n";
echo "Selanjutnya:\n";
echo "  1. Bandingkan struktur: mysqldiff atau SHOW TABLES\n";
echo "  2. Untuk sync ke hosting: scp patch_*.sql lalu jalankan\n";
echo "     di terminal hosting.\n";
echo "  3. Atau jalankan deploy: ./deploy_dashboard_se2026.sh --with-data\n";
echo "\n";
