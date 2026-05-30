<?php
/**
 * Prelist SE2026 Import Script
 *
 * Usage:
 *   php scripts/import_prelist.php <file.xlsx> [--kab=3509]
 *   php scripts/import_prelist.php <file.xlsx> --quick       (skip kabkota/kecamatan)
 *   php scripts/import_prelist.php <file.xlsx> --subsektor   (only subsektorA)
 *
 * File: PRELIST SE2026.xlsx (format BPS Jatim, 46 sheets)
 *   Sheet 1  = "Prelist SE2026"              -> prelist_kabkota
 *   Sheet 2  = "Prelist SE2026 kecamatan"    -> prelist_kecamatan
 *   Sheet 3  = "Prelist SE2026 desa"         -> skipped
 *   Sheet 5  = "subsektorA"                  -> prelist_subsektor (+ update prelist_sls)
 *   Sheet 7+ = "Prelist SE2026_35XX"         -> prelist_sls (per-kab SLS detail)
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';

use App\Core\App;
use App\Core\Database;
use OpenSpout\Reader\XLSX\Reader;

$app = App::instance();
$app->boot();

// ─── Parse CLI args (manual: getopt unreliable on this PHP build) ──────────
$kabFilter    = null;
$quickMode    = false;
$subsektorMode = false;
$batchSize    = 500;
$filePath     = null;

for ($i = 1, $n = count($argv); $i < $n; $i++) {
    $arg = $argv[$i];
    if (str_starts_with($arg, '--kab=')) {
        $kabFilter = substr($arg, 6);
    } elseif ($arg === '--quick') {
        $quickMode = true;
    } elseif ($arg === '--subsektor') {
        $subsektorMode = true;
    } elseif (str_starts_with($arg, '--batch=')) {
        $batchSize = (int) substr($arg, 8);
    } elseif (!str_starts_with($arg, '--')) {
        $filePath = $arg;
    }
}

if (!$filePath || !is_file($filePath)) {
    echo "ERROR: File not found: " . ($filePath ?? 'none') . PHP_EOL;
    echo "Usage: php scripts/import_prelist.php <file.xlsx> [--kab=3509] [--quick] [--subsektor] [--batch=500]" . PHP_EOL;
    exit(1);
}

$pdo = Database::instance()->pdo();

echo "=== PRELIST SE2026 IMPORT ===" . PHP_EOL;
echo "File: {$filePath}" . PHP_EOL;
echo "Batch: {$batchSize}" . PHP_EOL;
if ($kabFilter) echo "Kab filter: {$kabFilter}" . PHP_EOL;
if ($quickMode) echo "Mode: quick (skip kabkota/kecamatan)" . PHP_EOL;
if ($subsektorMode) echo "Mode: subsektor only" . PHP_EOL;
echo PHP_EOL;

$reader = new Reader();
$reader->open($filePath);

$totalKabkota   = 0;
$totalKecamatan = 0;
$totalSls       = 0;
$totalSkipped   = 0;

foreach ($reader->getSheetIterator() as $sheetIdx => $sheet) {
    $sheetName = $sheet->getName();

    if ($subsektorMode) {
        // Only import Sheet 5 (subsektorA)
        if ($sheetIdx === 5) {
            echo "[Sheet {$sheetIdx}] {$sheetName}..." . PHP_EOL;
            $r = importSubsektor($pdo, $sheet, $batchSize);
            echo "  -> {$r['inserted']} subsektor, {$r['skipped']} skipped" . PHP_EOL;
            echo "Updating prelist_sls.subsektor from prelist_subsektor..." . PHP_EOL;
            $updated = updateSubsektorInSls($pdo);
            echo "  -> {$updated} SLS updated" . PHP_EOL;
        }
        continue;
    }

    // Normal mode
    if ($sheetIdx === 1 && !$quickMode) {
        echo "[Sheet {$sheetIdx}] {$sheetName} — kabkota..." . PHP_EOL;
        $r = importKabkota($pdo, $sheet);
        $totalKabkota = $r['inserted'];
        $totalSkipped = $r['skipped'];
        echo "  -> {$r['inserted']} inserted, {$r['skipped']} skipped" . PHP_EOL;
        continue;
    }

    if ($sheetIdx === 2 && !$quickMode) {
        echo "[Sheet {$sheetIdx}] {$sheetName} — kecamatan..." . PHP_EOL;
        $r = importKecamatan($pdo, $sheet, $batchSize);
        $totalKecamatan = $r['inserted'];
        echo "  -> {$r['inserted']} inserted" . PHP_EOL;
        continue;
    }

    if ($sheetIdx >= 7) {
        if (!str_starts_with($sheetName, 'Prelist SE2026_')) {
            echo "[Sheet {$sheetIdx}] {$sheetName} — skipped" . PHP_EOL;
            continue;
        }

        $kabCode = substr($sheetName, strlen('Prelist SE2026_'));
        if ($kabFilter && $kabCode !== $kabFilter) {
            echo "[Sheet {$sheetIdx}] {$sheetName} — skipped (filter {$kabFilter})" . PHP_EOL;
            continue;
        }

        echo "[Sheet {$sheetIdx}] {$sheetName}..." . PHP_EOL;
        $r = importSlsDetail($pdo, $sheet, $kabCode, $batchSize);
        $totalSls += $r['inserted'];
        $totalSkipped += $r['skipped'];
        echo "  -> {$r['inserted']} SLS, {$r['skipped']} skipped" . PHP_EOL;
        continue;
    }
}

$reader->close();

if (!$subsektorMode) {
    echo PHP_EOL . "=== IMPORT COMPLETE ===" . PHP_EOL;
    echo "Kab/Kota : {$totalKabkota}" . PHP_EOL;
    echo "Kecamatan: {$totalKecamatan}" . PHP_EOL;
    echo "SLS      : {$totalSls}" . PHP_EOL;
    echo "Skipped  : {$totalSkipped}" . PHP_EOL;
}

// ─── Helper Functions ───────────────────────────────────────────────────────

function importKabkota(\PDO $pdo, $sheet): array
{
    $inserted = 0;
    $skipped  = 0;
    $stmt = $pdo->prepare("
        INSERT INTO prelist_kabkota
            (kd_kab, nm_kabkota, se2016, jml_kk, utp, subsektor, ub, um, umk, n_sls, ppl, pml)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            nm_kabkota=VALUES(nm_kabkota), se2016=VALUES(se2016),
            jml_kk=VALUES(jml_kk), utp=VALUES(utp),
            subsektor=VALUES(subsektor), ub=VALUES(ub),
            um=VALUES(um), umk=VALUES(umk), n_sls=VALUES(n_sls),
            ppl=VALUES(ppl), pml=VALUES(pml),
            imported_at=CURRENT_TIMESTAMP
    ");

    foreach ($sheet->getRowIterator() as $rowIdx => $row) {
        if ($rowIdx <= 3) continue;

        $cells = rowToArray($row);
        $first = trim((string) ($cells[0] ?? ''));

        if ($first === '' || $first === 'JAWA TIMUR' || str_starts_with($first, 'Catatan')) continue;
        if (!preg_match('/^(\d{1,2})\s+(.+)$/', $first, $m)) { $skipped++; continue; }

        $kdKab = '35' . str_pad($m[1], 2, '0', STR_PAD_LEFT);

        $stmt->execute([
            $kdKab,
            trim($m[2]),
            (int) ($cells[1] ?? 0),
            (int) ($cells[2] ?? 0),
            (int) ($cells[6] ?? 0),
            (int) ($cells[7] ?? 0),
            (int) ($cells[8] ?? 0),
            (int) ($cells[10] ?? 0),
            (int) ($cells[11] ?? 0),
            (int) ($cells[13] ?? 0),
            (int) ($cells[14] ?? 0),
            (int) ($cells[24] ?? 0),
        ]);
        $inserted++;
    }

    return ['inserted' => $inserted, 'skipped' => $skipped];
}

function importKecamatan(\PDO $pdo, $sheet, int $batchSize): array
{
    $inserted = 0;

    $stmt = $pdo->prepare("
        INSERT INTO prelist_kecamatan
            (kd_kec, kd_kab, nm_kec, upb_utl, sbr, se2016, rtup, utp, subsektor, jml_kk, wilkerstat, muatan_rs)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            nm_kec=VALUES(nm_kec), upb_utl=VALUES(upb_utl),
            sbr=VALUES(sbr), se2016=VALUES(se2016),
            rtup=VALUES(rtup), utp=VALUES(utp),
            subsektor=VALUES(subsektor), jml_kk=VALUES(jml_kk),
            wilkerstat=VALUES(wilkerstat), muatan_rs=VALUES(muatan_rs),
            imported_at=CURRENT_TIMESTAMP
    ");

    $batch = [];
    foreach ($sheet->getRowIterator() as $rowIdx => $row) {
        if ($rowIdx <= 3) continue;

        $cells = rowToArray($row);
        $kdKec = trim((string) ($cells[1] ?? ''));

        if (!preg_match('/^\d{7}$/', $kdKec)) continue;

        $batch[] = [
            $kdKec,
            substr($kdKec, 0, 4),
            trim((string) ($cells[2] ?? '')),
            (int) ($cells[3] ?? 0),
            (int) ($cells[4] ?? 0),
            (int) ($cells[5] ?? 0),
            (int) ($cells[6] ?? 0),
            (int) ($cells[7] ?? 0),
            (int) ($cells[8] ?? 0),
            (int) ($cells[9] ?? 0),
            (int) ($cells[10] ?? 0),
            (int) ($cells[11] ?? 0),
        ];

        if (count($batch) >= $batchSize) {
            $pdo->beginTransaction();
            foreach ($batch as $r) { $stmt->execute($r); }
            $pdo->commit();
            $inserted += count($batch);
            $batch = [];
        }
    }

    if (!empty($batch)) {
        $pdo->beginTransaction();
        foreach ($batch as $r) { $stmt->execute($r); }
        $pdo->commit();
        $inserted += count($batch);
    }

    return ['inserted' => $inserted];
}

function importSlsDetail(\PDO $pdo, $sheet, string $kabCode, int $batchSize): array
{
    $inserted = 0;
    $skipped  = 0;

    $stmt = $pdo->prepare("
        INSERT INTO prelist_sls
            (idsls, kd_kab, kd_kec, kd_desa, nm_kec, nm_desa, nama_sls,
             sbr, rtup, utp, subsektor, jml_kk, wilkerstat, muatan_rs,
             rtup_st2023, utp_st2023, usaha_wilkerstat,
             ada_mall, ada_pasar, ada_kdm)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            nm_kec=VALUES(nm_kec), nm_desa=VALUES(nm_desa),
            nama_sls=VALUES(nama_sls), sbr=VALUES(sbr),
            rtup=VALUES(rtup), utp=VALUES(utp),
            subsektor=VALUES(subsektor), jml_kk=VALUES(jml_kk),
            wilkerstat=VALUES(wilkerstat), muatan_rs=VALUES(muatan_rs),
            rtup_st2023=VALUES(rtup_st2023), utp_st2023=VALUES(utp_st2023),
            usaha_wilkerstat=VALUES(usaha_wilkerstat),
            ada_mall=VALUES(ada_mall), ada_pasar=VALUES(ada_pasar),
            ada_kdm=VALUES(ada_kdm),
            imported_at=CURRENT_TIMESTAMP
    ");

    $batch = [];
    foreach ($sheet->getRowIterator() as $rowIdx => $row) {
        if ($rowIdx <= 3) continue;

        $cells = rowToArray($row);
        $idslsVal = $cells[0] ?? null;

        if ($idslsVal === null || $idslsVal === '') { $skipped++; continue; }

        $idsls = (string) (is_float($idslsVal) ? (int) $idslsVal : $idslsVal);

        if (!preg_match('/^\d{14}$/', $idsls)) { $skipped++; continue; }

        $kd_kab = substr($idsls, 0, 4);
        $kd_kec = substr($idsls, 4, 3);
        $kd_desa = substr($idsls, 7, 3);

        $ada_mall = (int) ($cells[15] ?? 0);
        $ada_pasar = (int) ($cells[16] ?? 0);
        $ada_kdm = (int) ($cells[17] ?? 0);

        $batch[] = [
            $idsls,
            $kd_kab, $kd_kec, $kd_desa,
            trim((string) ($cells[6] ?? '')),
            trim((string) ($cells[7] ?? '')),
            trim((string) ($cells[1] ?? '-')),
            (int) ($cells[8] ?? 0),
            (int) ($cells[9] ?? 0),
            (int) ($cells[10] ?? 0),
            0, // subsektor (updated later from prelist_subsektor)
            (int) ($cells[12] ?? 0),
            (int) ($cells[13] ?? 0),
            (int) ($cells[14] ?? 0),
            // new columns
            (int) ($cells[9] ?? 0),   // rtup_st2023 = same as rtup
            (int) ($cells[10] ?? 0),  // utp_st2023 = same as utp
            (int) ($cells[13] ?? 0),  // usaha_wilkerstat = same as wilkerstat
            $ada_mall,
            $ada_pasar,
            $ada_kdm,
        ];

        if (count($batch) >= $batchSize) {
            $pdo->beginTransaction();
            foreach ($batch as $r) { $stmt->execute($r); }
            $pdo->commit();
            $inserted += count($batch);
            $batch = [];
        }
    }

    if (!empty($batch)) {
        $pdo->beginTransaction();
        foreach ($batch as $r) { $stmt->execute($r); }
        $pdo->commit();
        $inserted += count($batch);
    }

    return ['inserted' => $inserted, 'skipped' => $skipped];
}

function importSubsektor(\PDO $pdo, $sheet, int $batchSize): array
{
    $inserted = 0;
    $skipped  = 0;

    $stmt = $pdo->prepare("
        INSERT INTO prelist_subsektor (idsls, subsektor, kdkec, kddeskel)
        VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE
            kdkec=VALUES(kdkec), kddeskel=VALUES(kddeskel),
            created_at=CURRENT_TIMESTAMP
    ");

    $batch = [];
    foreach ($sheet->getRowIterator() as $rowIdx => $row) {
        if ($rowIdx <= 1) continue;

        $cells = rowToArray($row);
        $idslsVal = $cells[0] ?? null;

        if ($idslsVal === null || $idslsVal === '') { $skipped++; continue; }

        $idsls = (string) (is_float($idslsVal) ? (int) $idslsVal : $idslsVal);
        if (!preg_match('/^\d{14}$/', $idsls)) { $skipped++; continue; }

        $batch[] = [
            $idsls,
            (int) ($cells[1] ?? 0),
            trim((string) ($cells[2] ?? '')),
            trim((string) ($cells[3] ?? '')),
        ];

        if (count($batch) >= $batchSize) {
            $pdo->beginTransaction();
            foreach ($batch as $r) { $stmt->execute($r); }
            $pdo->commit();
            $inserted += count($batch);
            $batch = [];
        }
    }

    if (!empty($batch)) {
        $pdo->beginTransaction();
        foreach ($batch as $r) { $stmt->execute($r); }
        $pdo->commit();
        $inserted += count($batch);
    }

    return ['inserted' => $inserted, 'skipped' => $skipped];
}

function updateSubsektorInSls(\PDO $pdo): int
{
    $stmt = $pdo->prepare("
        UPDATE prelist_sls s
        JOIN (
            SELECT idsls, subsektor
            FROM prelist_subsektor
            GROUP BY idsls
        ) ps ON s.idsls = ps.idsls
        SET s.subsektor = ps.subsektor
    ");
    $stmt->execute();
    return $stmt->rowCount();
}

function rowToArray($row): array
{
    $result = [];
    foreach ($row->getCells() as $cell) {
        $v = $cell->getValue();
        $result[] = $v;
    }
    return $result;
}
