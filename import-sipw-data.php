<?php
/**
 * import-sipw-data.php — Import semua file data/sipw/*.xlsx ke sipw_import
 * 
 * Menggunakan OpenSpout streaming reader + batch UPSERT.
 * 
 * Usage:
 *   php import-sipw-data.php
 *   php import-sipw-data.php --file=1-sipw.xlsx
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
$root = __DIR__;
require $root . '/app/bootstrap.php';
use App\Helpers\Database;

// ─── Konfig ──────────────────────────────────────────────────────
$batchSize   = 500;
$dataDir     = $root . '/data/sipw';
$excelIdx    = ['idfrs'=>0,'semester'=>1,'idsubsls'=>2,'nmsls'=>3,'nama_ketua'=>4,
                'kdprov'=>6,'kdkab'=>7,'kdkec'=>8,'kddesa'=>9,'kdsls'=>10,
                'nmprov'=>13,'nmkab'=>14,'nmkec'=>15,'nmdesa'=>16,
                'kk'=>17,'btt'=>18,'bttk'=>19,'bku'=>20,'bbtt_nonusaha'=>21,'usaha'=>22,'muatan'=>23];
// Urutan harus SAMA dengan urutan kolom di SQL INSERT
$sipwColumns = ['idfrs','semester','idsubsls','kdprov','kdkab','kdkec','kddesa','kdsls',
                'nmprov','nmkab','nmkec','nmdesa','nmsls','nama_ketua',
                'kk','btt','bttk','bku','bbtt_nonusaha','usaha','muatan'];
$numericCols = ['kk','btt','bttk','bku','bbtt_nonusaha','usaha','muatan'];
$singleFile   = '';
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--file=')) $singleFile = substr($arg, 7);
}

$db = Database::getInstance();
if (!$db->tableExists('sipw_import')) { echo "ERROR: tabel sipw_import tidak ditemukan\n"; exit(1); }

$before   = $db->count('sipw_import');
$files    = $singleFile ? [$dataDir . '/' . $singleFile] : glob($dataDir . '/*.xlsx');
sort($files);
$totalF   = count($files);
echo "File: $totalF, Baris awal: " . number_format($before) . "\n\n";

$stmt = $db->pdo()->prepare(
    "INSERT INTO sipw_import (idfrs,semester,idsubsls,kdprov,kdkab,kdkec,kddesa,kdsls,
                               nmprov,nmkab,nmkec,nmdesa,nmsls,nama_ketua,
                               kk,btt,bttk,bku,bbtt_nonusaha,usaha,muatan)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE
       semester=VALUES(semester),idsubsls=VALUES(idsubsls),
       kdprov=VALUES(kdprov),kdkab=VALUES(kdkab),kdkec=VALUES(kdkec),
       kddesa=VALUES(kddesa),kdsls=VALUES(kdsls),
       nmprov=VALUES(nmprov),nmkab=VALUES(nmkab),nmkec=VALUES(nmkec),
       nmdesa=VALUES(nmdesa),nmsls=VALUES(nmsls),nama_ketua=VALUES(nama_ketua),
       kk=VALUES(kk),btt=VALUES(btt),bttk=VALUES(bttk),bku=VALUES(bku),
       bbtt_nonusaha=VALUES(bbtt_nonusaha),usaha=VALUES(usaha),muatan=VALUES(muatan),
       updated_at=NOW()"
);

$totalIns = 0; $totalUpd = 0; $totalErr = 0; $totalRow = 0;
$startAll = microtime(true);

foreach ($files as $fi => $fp) {
    $fn   = basename($fp);
    $fnum = $fi + 1;
    echo "[$fnum/$totalF] $fn ... ";
    $t = microtime(true);

    $reader = new OpenSpout\Reader\XLSX\Reader();
    $reader->open($fp);
    $fIns = 0; $fUpd = 0; $fErr = 0; $fRow = 0;
    $batch = [];

    try {
        $db->pdo()->beginTransaction();

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $i => $row) {
                if ($i === 1) continue; // header
                $c = $row->toArray();
                if (empty(array_filter($c))) continue;
                $fRow++;
                $d = [];
                foreach ($sipwColumns as $col) {
                    $v = $c[$excelIdx[$col]] ?? null;
                    if (in_array($col, $numericCols, true)) $v = (int)($v ?? 0);
                    elseif ($v !== null) $v = (string) $v;
                    $d[] = $v;
                }
                $batch[] = $d;
                if (count($batch) >= $batchSize) {
                    foreach ($batch as $bd) {
                        try { $stmt->execute($bd); $rc = $stmt->rowCount(); if ($rc===1) $fIns++; elseif ($rc===2) $fUpd++; }
                        catch (\Throwable $e) { $fErr++; }
                    }
                    $batch = []; gc_collect_cycles();
                }
            }
            break;
        }
        $reader->close();

        if ($batch) {
            foreach ($batch as $bd) {
                try { $stmt->execute($bd); $rc = $stmt->rowCount(); if ($rc===1) $fIns++; elseif ($rc===2) $fUpd++; }
                catch (\Throwable $e) { $fErr++; }
            }
            $batch = [];
        }

        $db->pdo()->commit();
        $el = round(microtime(true)-$t, 1);
        echo "{$fRow} rows, +$fIns new, $fUpd upd, $fErr err ({$el}s)\n";
        $totalRow += $fRow; $totalIns += $fIns; $totalUpd += $fUpd; $totalErr += $fErr;
    } catch (\Throwable $e) {
        if ($db->pdo()->inTransaction()) $db->pdo()->rollBack();
        $reader->close();
        echo "FAILED: {$e->getMessage()}\n";
        $totalErr += count($batch);
    }
}

$after    = $db->count('sipw_import');
$elAll    = round(microtime(true)-$startAll, 1);
echo "\n=== IMPORT SELESAI ===\n";
echo "File: $totalF, Rows: $totalRow, Insert: $totalIns, Update: $totalUpd, Error: $totalErr\n";
echo "sipw_import: " . number_format($before) . " → " . number_format($after) . " (+" . number_format($after-$before) . ")\n";
echo "Waktu: {$elAll}s\n";
