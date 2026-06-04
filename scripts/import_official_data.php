<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

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
use App\Helpers\Cache;
use OpenSpout\Reader\XLSX\Reader;

if (PHP_SAPI !== 'cli') {
    die("Error: Script ini hanya bisa dijalankan dari CLI.\n");
}

function logMsg(string $msg, string $level = 'INFO'): void
{
    $ts = date('H:i:s');
    echo "  [{$ts}] [{$level}] {$msg}\n";
}

function formatNumber(int $n): string
{
    return number_format($n, 0, ',', '.');
}

function pdo(): \PDO
{
    return Database::instance()->pdo();
}

function showHelp(): void
{
    echo "\n";
    echo "Import Data Master Resmi Semester 1 2025\n";
    echo str_repeat('=', 50) . "\n";
    echo "Mengimpor data MFD dan MSUBSLS resmi ke database.\n\n";
    echo "Penggunaan:\n";
    echo "  php scripts/import_official_data.php              # Jalankan import\n";
    echo "  php scripts/import_official_data.php --dry-run    # Simulasi saja\n";
    echo "  php scripts/import_official_data.php --help       # Bantuan ini\n";
    echo "\n";
}

function getExcelRows(string $path): \Generator
{
    $reader = new Reader();
    $reader->open($path);
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $idx => $row) {
            if ($idx === 1) continue;
            $cells = $row->toArray();
            if (empty(array_filter($cells))) continue;
            yield $cells;
        }
    }
    $reader->close();
}

const BATCH_SIZE = 500;
const MFD_FILE = BASE_PATH . '/data/mfd/sm22025/mfd_25_2_3509.xlsx';
const MSUBSLS_FILE = BASE_PATH . '/data/mfd/sm22025/msubsls_25_2_3509.xlsx';

function cmdImport(bool $dryRun): void
{
    $pdo = pdo();

    if (!is_file(MSUBSLS_FILE)) {
        logMsg("File tidak ditemukan: " . MSUBSLS_FILE, 'ERROR');
        exit(1);
    }
    if (!is_file(MFD_FILE)) {
        logMsg("File tidak ditemukan: " . MFD_FILE, 'ERROR');
        exit(1);
    }

    logMsg("==============================================");
    logMsg("IMPORT DATA MASTER RESMI SEMESTER 1 2025");
    logMsg("==============================================");
    if ($dryRun) {
        logMsg(">> DRY RUN MODE - Tidak ada perubahan database <<");
    }
    logMsg("");

    // ── Hitung baris aktual ──
    logMsg("Menghitung jumlah baris di MSUBSLS...");
    $msubslsTotal = 0;
    foreach (getExcelRows(MSUBSLS_FILE) as $_) { $msubslsTotal++; }
    logMsg("  MSUBSLS: " . formatNumber($msubslsTotal) . " baris");

    logMsg("Menghitung jumlah baris di MFD...");
    $mfdTotal = 0;
    foreach (getExcelRows(MFD_FILE) as $_) { $mfdTotal++; }
    logMsg("  MFD: " . formatNumber($mfdTotal) . " baris");
    logMsg("");

    if ($msubslsTotal === 0) {
        logMsg("Tidak ada data MSUBSLS yang ditemukan. Batal.", 'ERROR');
        exit(1);
    }

    // ── Backup tables ──
    if (!$dryRun) {
        logMsg("Membuat backup tabel sipw_import...");
        $pdo->exec("DROP TABLE IF EXISTS sipw_import_bkp");
        $pdo->exec("CREATE TABLE sipw_import_bkp LIKE sipw_import");
        $pdo->exec("INSERT INTO sipw_import_bkp SELECT * FROM sipw_import");
        $bkpCount = $pdo->query("SELECT COUNT(*) FROM sipw_import_bkp")->fetchColumn();
        logMsg("  Backup sipw_import: " . formatNumber((int)$bkpCount) . " baris");

        logMsg("Membuat backup tabel master_sls...");
        $pdo->exec("DROP TABLE IF EXISTS master_sls_bkp");
        $pdo->exec("CREATE TABLE master_sls_bkp LIKE master_sls");
        $pdo->exec("INSERT INTO master_sls_bkp SELECT * FROM master_sls");
        $bkpCount2 = $pdo->query("SELECT COUNT(*) FROM master_sls_bkp")->fetchColumn();
        logMsg("  Backup master_sls: " . formatNumber((int)$bkpCount2) . " baris");

        // Backup sipw_assignment + simpan mapping id lama → idsubsls
        logMsg("Membuat backup assignment...");
        $pdo->exec("DROP TABLE IF EXISTS sipw_assignment_bkp");
        $pdo->exec("CREATE TABLE sipw_assignment_bkp LIKE sipw_assignment");
        $pdo->exec("INSERT INTO sipw_assignment_bkp SELECT * FROM sipw_assignment");
        $bkpCount3 = $pdo->query("SELECT COUNT(*) FROM sipw_assignment_bkp")->fetchColumn();
        if ((int)$bkpCount3 > 0) {
            // Isi kolom idsubsls di backup dari sipw_import saat ini
            $pdo->exec("UPDATE sipw_assignment_bkp sab
                JOIN sipw_import si ON si.id = sab.sipw_id
                SET sab.idsubsls = si.idsubsls");
        }
        logMsg("  Backup sipw_assignment: " . formatNumber((int)$bkpCount3) . " baris");
        logMsg("");
    }

    // ── TRUNCATE target tables ──
    if (!$dryRun) {
        logMsg("Mengosongkan tabel sipw_import, master_sls...");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE sipw_import");
        $pdo->exec("TRUNCATE TABLE master_sls");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        logMsg("  OK");
    }

    // ── Import MSUBSLS ke sipw_import ──
    logMsg("Mengimpor MSUBSLS ke sipw_import...");
    $insertSipw = $pdo->prepare("
        INSERT IGNORE INTO sipw_import
            (idfrs, semester, idsubsls, kdprov, nmprov, kdkab, nmkab, kdkec, nmkec,
             kddesa, nmdesa, kdsls, klas, nmsls, nama_ketua,
             kk, btt, bbtt_nonusaha, bttk, bku, usaha, muatan, dominan)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $sipwInserted = 0;
    $batch = [];

    $rowNum = 0;
    foreach (getExcelRows(MSUBSLS_FILE) as $cells) {
        $rowNum++;
        if (count($cells) < 25) continue;

        $idfrs       = (int) ($cells[0] ?? 0);
        $semester    = (string) ($cells[1] ?? '');
        $idsubsls    = (string) ($cells[2] ?? '');
        $nmsls       = (string) ($cells[3] ?? '');
        $nama_ketua  = (string) ($cells[4] ?? '');
        $kdprov      = (string) ($cells[6] ?? '');
        $nmprov      = (string) ($cells[7] ?? '');
        $kdkab       = (string) ($cells[8] ?? '');
        $nmkab       = (string) ($cells[9] ?? '');
        $kdkec       = (string) ($cells[10] ?? '');
        $nmkec       = (string) ($cells[11] ?? '');
        $kddesa      = (string) ($cells[12] ?? '');
        $nmdesa      = (string) ($cells[13] ?? '');
        $kdsls       = (string) ($cells[14] ?? '');
        $klas        = (int) ($cells[16] ?? 0);
        $kk          = (int) ($cells[17] ?? 0);
        $btt         = (int) ($cells[18] ?? 0);
        $bsbtt       = (int) ($cells[19] ?? 0);
        $bsttk       = (int) ($cells[20] ?? 0);
        $bku         = (int) ($cells[21] ?? 0);
        $usaha       = (int) ($cells[22] ?? 0);
        $muatan      = (int) ($cells[23] ?? 0);
        $dominan     = (int) ($cells[24] ?? 0);

        if (empty($idsubsls)) continue;

        $batch[] = [
            $idfrs, $semester, $idsubsls, $kdprov, $nmprov,
            $kdkab, $nmkab, $kdkec, $nmkec, $kddesa,
            $nmdesa, $kdsls, $klas, $nmsls, $nama_ketua,
            $kk, $btt, $bsbtt, $bsttk, $bku, $usaha, $muatan, $dominan,
        ];

        if (count($batch) >= BATCH_SIZE) {
            $sipwInserted += flushBatch($pdo, $insertSipw, $batch, $dryRun);
            $batch = [];
            echo "  Progress: " . formatNumber($rowNum) . " / " . formatNumber($msubslsTotal) . "\r";
        }
    }

    if (!empty($batch)) {
        $sipwInserted += flushBatch($pdo, $insertSipw, $batch, $dryRun);
    }

    logMsg("  Selesai: " . formatNumber($sipwInserted) . " baris ke sipw_import");
    logMsg("");

    // ── Import MSUBSLS ke master_sls ──
    logMsg("Mengimpor MSUBSLS ke master_sls...");
    $insertMaster = $pdo->prepare("
        INSERT IGNORE INTO master_sls (kode, sls, desa, kecamatan, kabupaten, provinsi)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $masterInserted = 0;
    $batch2 = [];
    $rowNum2 = 0;

    foreach (getExcelRows(MSUBSLS_FILE) as $cells) {
        $rowNum2++;
        if (count($cells) < 25) continue;

        $idsubsls  = (string) ($cells[2] ?? '');
        $nmsls     = (string) ($cells[3] ?? '');
        $nmdesa    = (string) ($cells[13] ?? '');
        $nmkec     = (string) ($cells[11] ?? '');
        $nmkab     = (string) ($cells[9] ?? '');
        $nmprov    = (string) ($cells[7] ?? '');

        if (empty($idsubsls)) continue;

        $batch2[] = [
            $idsubsls,
            $nmsls,
            $nmdesa,
            $nmkec,
            $nmkab ?: 'JEMBER',
            $nmprov ?: 'JAWA TIMUR',
        ];

        if (count($batch2) >= BATCH_SIZE) {
            $masterInserted += flushBatch($pdo, $insertMaster, $batch2, $dryRun);
            $batch2 = [];
        }
    }

    if (!empty($batch2)) {
        $masterInserted += flushBatch($pdo, $insertMaster, $batch2, $dryRun);
    }

    logMsg("  Selesai: " . formatNumber($masterInserted) . " baris ke master_sls");
    logMsg("");

    // ── Update mfd_kec dari MFD ──
    logMsg("Mengupdate mfd_kec dari MFD...");
    $updateMfd = $pdo->prepare("
        UPDATE mfd_kec
        SET urutan    = ?,
            nama_kecamatan = ?
        WHERE kode_kecamatan = ?
    ");

    $mfdUpdated = 0;
    $urutan = 0;

    foreach (getExcelRows(MFD_FILE) as $cells) {
        if (count($cells) < 20) continue;
        $urutan++;

        $kdkec     = (string) ($cells[8] ?? '');
        $nmkec     = (string) ($cells[9] ?? '');
        $kdprov    = (string) ($cells[4] ?? '');
        $kdkab     = (string) ($cells[6] ?? '');
        $kodeKec   = $kdprov . $kdkab . $kdkec;

        if (empty($kodeKec)) continue;

        if (!$dryRun) {
            $updateMfd->execute([$urutan, $nmkec, $kodeKec]);
            if ($updateMfd->rowCount() > 0) {
                $mfdUpdated++;
            } else {
                $pdo->prepare("INSERT IGNORE INTO mfd_kec (urutan, kode_kecamatan, nama_kecamatan) VALUES (?, ?, ?)")
                    ->execute([$urutan, $kodeKec, $nmkec]);
                $mfdUpdated++;
            }
        }
    }

    logMsg("  Selesai: " . formatNumber($mfdUpdated) . " kecamatan diupdate");
    logMsg("");

    // ── Restore assignment via idsubsls mapping ──
    if (!$dryRun) {
        $assignCount = $pdo->query("SELECT COUNT(*) FROM sipw_assignment_bkp")->fetchColumn();
        if ((int)$assignCount > 0) {
            logMsg("Meremap assignment ke ID sipw_import baru...");
            // Hapus assignment yang sudah ada (orphan dari data lama)
            $pdo->exec("TRUNCATE TABLE sipw_assignment");
            // Remap: backup.idsubsls → sipw_import baru.id, lalu insert
            $pdo->exec("
                INSERT INTO sipw_assignment (sipw_id, idsubsls, pencacah_id, pengawas_id, task_force_id, status, created_at, updated_at)
                SELECT si.id, sab.idsubsls, sab.pencacah_id, sab.pengawas_id, sab.task_force_id, sab.status, sab.created_at, sab.updated_at
                FROM sipw_assignment_bkp sab
                JOIN sipw_import si ON si.idsubsls = sab.idsubsls
            ");
            $restored = $pdo->query("SELECT COUNT(*) FROM sipw_assignment")->fetchColumn();
            logMsg("  Assignment direstore: " . formatNumber((int)$restored) . " baris");
        } else {
            logMsg("Tidak ada assignment untuk direstore.");
        }
    }

    // ── Hapus cache ──
    if (!$dryRun) {
        logMsg("Membersihkan cache dashboard...");
        Cache::flush();
        logMsg("  OK");
    }

    // ── Ringkasan ──
    logMsg("==============================================");
    logMsg("IMPORT SELESAI");
    logMsg("==============================================");
    logMsg("  MSUBSLS -> sipw_import : " . formatNumber($sipwInserted) . " baris");
    logMsg("  MSUBSLS -> master_sls  : " . formatNumber($masterInserted) . " baris");
    logMsg("  MFD     -> mfd_kec     : " . formatNumber($mfdUpdated) . " kecamatan");
    if ($dryRun) {
        logMsg(">> DRY RUN - Tidak ada perubahan nyata <<");
    } else {
        logMsg("  Cache dashboard telah dibersihkan.");
        logMsg("  Backup tersedia di: sipw_import_bkp, master_sls_bkp, sipw_assignment_bkp");
    }
    logMsg("");
}

function flushBatch(\PDO $pdo, \PDOStatement $stmt, array &$batch, bool $dryRun): int
{
    if ($dryRun) {
        $count = count($batch);
        $batch = [];
        return $count;
    }

    $count = 0;
    $pdo->beginTransaction();
    try {
        foreach ($batch as $row) {
            $stmt->execute($row);
            $count++;
        }
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        logMsg("Batch error: " . $e->getMessage(), 'ERROR');
        throw $e;
    }
    $batch = [];
    return $count;
}

$args = $argv ?? [];
$dryRun = false;

foreach ($args as $arg) {
    if ($arg === '--dry-run') $dryRun = true;
    if ($arg === '--help' || $arg === '-h') { showHelp(); exit(0); }
}

cmdImport($dryRun);
