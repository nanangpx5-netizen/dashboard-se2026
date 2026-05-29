<?php

/**
 * Rollback Import SIPW — CLI Tool
 *
 * Penggunaan:
 *   php scripts/rollback-import.php list              # Daftar import yang bisa di-rollback
 *   php scripts/rollback-import.php info <batch_id>   # Detail import batch
 *   php scripts/rollback-import.php rollback <batch_id>  # Rollback import batch
 *   php scripts/rollback-import.php cleanup            # Hapus rollback point > 30 hari
 *
 * Keamanan:
 *   - Hanya bisa dijalankan dari CLI (cek PHP_SAPI)
 *   - Rollback hanya sekali per batch (is_used = 0)
 *   - Log semua aktivitas ke storage/logs/rollback.log
 */

declare(strict_types=1);

// ─── Bootstrap ────────────────────────────────────────────────────────────
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

// Load environment
$envFile = BASE_PATH . '/.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

define('VIEW_PATH', BASE_PATH . '/views');
define('STORAGE_PATH', BASE_PATH . '/storage');

use App\Core\Database;
use App\Helpers\Backup;
use App\Helpers\Cache;

// ─── CLI check ────────────────────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    die("Error: Script ini hanya bisa dijalankan dari CLI.\n");
}

// ─── Help ─────────────────────────────────────────────────────────────────
function showHelp(): void
{
    echo "\n";
    echo "Rollback Import SIPW — CLI Tool\n";
    echo str_repeat('=', 50) . "\n";
    echo "Penggunaan:\n";
    echo "  php scripts/rollback-import.php list                    Daftar import\n";
    echo "  php scripts/rollback-import.php info <batch_id>         Detail import\n";
    echo "  php scripts/rollback-import.php rollback <batch_id>     Rollback import\n";
    echo "  php scripts/rollback-import.php cleanup                 Hapus point > 30 hari\n";
    echo "\n";
}

// ─── Logging ──────────────────────────────────────────────────────────────
function logMessage(string $message, string $level = 'INFO'): void
{
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] [{$level}] {$message}";
    echo $line . "\n";

    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents(
        $logDir . '/rollback.log',
        $line . "\n",
        FILE_APPEND
    );
}

// ─── DB ───────────────────────────────────────────────────────────────────
function pdo(): \PDO
{
    return Database::instance()->pdo();
}

// ─── Format ───────────────────────────────────────────────────────────────
function formatNumber(int $n): string
{
    return number_format($n, 0, ',', '.');
}

function formatDuration(string $start, ?string $end): string
{
    if (!$end) return '-';
    $diff = strtotime($end) - strtotime($start);
    if ($diff < 60) return $diff . ' detik';
    if ($diff < 3600) return floor($diff / 60) . ' menit';
    return floor($diff / 3600) . ' jam ' . floor(($diff % 3600) / 60) . ' menit';
}

// ─── Commands ─────────────────────────────────────────────────────────────

/**
 * List import batches yang bisa di-rollback
 */
function cmdList(): void
{
    $pdo = pdo();

    echo "\n";
    echo "=== Daftar Import SIPW ===";
    echo "\n\n";

    $rows = $pdo->query("
        SELECT
            l.batch_id,
            l.nama_file,
            l.status,
            l.total_baris,
            l.baris_berhasil,
            l.baris_diupdate,
            l.baris_gagal,
            l.waktu_mulai,
            l.waktu_selesai,
            u.username AS user_name,
            rp.is_used AS rollback_used
        FROM dash_import_log l
        LEFT JOIN users u ON u.id = l.user_id
        LEFT JOIN dash_rollback_points rp ON rp.batch_id = l.batch_id
        WHERE l.status IN ('success', 'partial', 'failed')
        ORDER BY l.created_at DESC
        LIMIT 50
    ")->fetchAll();

    if (empty($rows)) {
        echo "  (Belum ada riwayat import)\n";
        return;
    }

    $fmt = "  %-38s %-25s %-11s %-8s %-8s %-8s %-8s %-10s %s\n";
    printf($fmt, 'BATCH ID', 'FILE', 'STATUS', 'BARIS', 'BARU', 'UPDATE', 'GAGAL', 'ROLLBACK', 'WAKTU');
    printf($fmt, str_repeat('-', 38), str_repeat('-', 25), str_repeat('-', 11),
           str_repeat('-', 8), str_repeat('-', 8), str_repeat('-', 8),
           str_repeat('-', 8), str_repeat('-', 10), str_repeat('-', 15));

    foreach ($rows as $r) {
        $rollbackStatus = match (true) {
            $r['rollback_used'] === null && $r['status'] === 'failed' => 'N/A',
            $r['rollback_used'] === '1' => 'DONE',
            $r['rollback_used'] === '0' => 'READY',
            $r['rollback_used'] === null => 'N/A',
            default => 'N/A',
        };

        $waktu = $r['waktu_mulai']
            ? date('d/m H:i', strtotime($r['waktu_mulai']))
            : '-';

        printf(
            $fmt,
            $r['batch_id'],
            mb_substr($r['nama_file'], 0, 24),
            $r['status'],
            formatNumber((int) $r['total_baris']),
            formatNumber((int) $r['baris_berhasil']),
            formatNumber((int) $r['baris_diupdate']),
            formatNumber((int) $r['baris_gagal']),
            $rollbackStatus,
            $waktu,
        );
    }

    echo "\n";
    echo "Gunakan: php scripts/rollback-import.php info <batch_id>\n";
    echo "         php scripts/rollback-import.php rollback <batch_id>\n";
    echo "\n";
}

/**
 * Info detail import batch
 */
function cmdInfo(string $batchId): void
{
    $pdo = pdo();

    $row = $pdo->prepare("
        SELECT l.*, u.username
        FROM dash_import_log l
        LEFT JOIN users u ON u.id = l.user_id
        WHERE l.batch_id = ?
    ");
    $row->execute([$batchId]);
    $import = $row->fetch();

    if (!$import) {
        echo "Error: Batch ID tidak ditemukan: {$batchId}\n";
        exit(1);
    }

    $rbp = $pdo->prepare("
        SELECT * FROM dash_rollback_points WHERE batch_id = ?
    ");
    $rbp->execute([$batchId]);
    $point = $rbp->fetch();

    echo "\n";
    echo "=== Detail Import Batch ===\n";
    echo str_repeat('-', 50) . "\n";
    echo "  Batch ID    : {$import['batch_id']}\n";
    echo "  File         : {$import['nama_file']}\n";
    echo "  Ukuran       : " . formatNumber((int) $import['ukuran_file'] / 1024) . " KB\n";
    echo "  Status       : {$import['status']}\n";
    echo "  User         : {$import['username']} (ID: {$import['user_id']})\n";
    echo "  IP           : {$import['ip_address']}\n";
    echo "  Mulai        : {$import['waktu_mulai']}\n";
    echo "  Selesai      : {$import['waktu_selesai']}\n";
    echo "  Durasi       : " . formatDuration($import['waktu_mulai'], $import['waktu_selesai']) . "\n";
    echo "\n";
    echo "  Total baris  : " . formatNumber((int) $import['total_baris']) . "\n";
    echo "  Baris baru   : " . formatNumber((int) $import['baris_berhasil']) . "\n";
    echo "  Diupdate     : " . formatNumber((int) $import['baris_diupdate']) . "\n";
    echo "  Gagal        : " . formatNumber((int) $import['baris_gagal']) . "\n";
    echo "\n";

    if ($import['error_message']) {
        echo "  Error        : {$import['error_message']}\n\n";
    }

    if ($point) {
        echo "  Rollback Point:\n";
        echo "    Status  : " . ($point['is_used'] ? 'SUDAH DIPAKAI' : 'READY') . "\n";
        echo "    Baris   : " . formatNumber((int) $point['row_count']) . "\n";
        echo "    Dibuat  : {$point['created_at']}\n";
        if ($point['used_at']) {
            echo "    Dipakai : {$point['used_at']} oleh user #{$point['used_by']}\n";
        }
    } else {
        echo "  Rollback Point: TIDAK ADA (import ini mungkin sudah terlalu lama)\n";
    }

    echo "\n";
}

/**
 * Rollback import batch
 */
function cmdRollback(string $batchId): void
{
    $pdo = pdo();

    // Cek batch ID
    $stmt = $pdo->prepare("SELECT status FROM dash_import_log WHERE batch_id = ?");
    $stmt->execute([$batchId]);
    $import = $stmt->fetch();

    if (!$import) {
        echo "Error: Batch ID tidak ditemukan: {$batchId}\n";
        exit(1);
    }

    echo "\n";
    echo "=== Rollback Import Batch ===\n";
    echo "  Batch ID: {$batchId}\n\n";

    echo "PERINGATAN: Ini akan mengembalikan data ke kondisi SEBELUM import.\n";
    echo "Data assignment terkait baris baru juga akan dihapus.\n";
    echo "Tindakan ini TIDAK BISA DIBATALKAN.\n\n";

    echo "Tekan CTRL+C dalam 5 detik untuk membatalkan...\n";
    sleep(5);

    $result = Backup::rollbackImport($batchId);

    echo "\n";

    if ($result['success']) {
        echo "  ✓ {$result['message']}\n";
        logMessage("Rollback berhasil: {$result['message']}", 'INFO');
    } else {
        echo "  ✗ {$result['message']}\n";
        logMessage("Rollback gagal: {$result['message']}", 'ERROR');
    }

    echo "\n";
}

/**
 * Cleanup rollback point > 30 hari
 */
function cmdCleanup(): void
{
    $pdo = pdo();

    echo "\nMembersihkan rollback point > 30 hari...\n";

    $stmt = $pdo->query("
        DELETE FROM dash_rollback_points
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $count = $stmt->rowCount();

    echo "  {$count} rollback point dihapus.\n";
    logMessage("Cleanup: {$count} rollback point dihapus", 'INFO');

    // Hapus juga import log yang sudah > 90 hari (ringan)
    $stmt = $pdo->query("
        DELETE FROM dash_import_log
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
    ");
    $count2 = $stmt->rowCount();
    echo "  {$count2} import log dihapus ( > 90 hari).\n";

    echo "\n";
}

// ─── Main ──────────────────────────────────────────────────────────────────
$command = $argv[1] ?? 'help';

match ($command) {
    'list'     => cmdList(),
    'info'     => isset($argv[2]) ? cmdInfo($argv[2]) : showHelp(),
    'rollback' => isset($argv[2]) ? cmdRollback($argv[2]) : showHelp(),
    'cleanup'  => cmdCleanup(),
    default    => showHelp(),
};
