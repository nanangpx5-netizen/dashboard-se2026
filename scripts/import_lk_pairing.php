<?php
/**
 * scripts/import_lk_pairing.php
 *
 * Import data LK Pairing SE2026.xlsx ke tabel lk_petugas dan lk_pairing.
 * Dry-run default.
 *
 * Dua fase:
 *   Phase 1 — Import lk_petugas (PPL+PML master) + update wilayah_kerja.aktual_*
 *   Phase 2 — Import lk_pairing (Master SLS pairing) — setelah pairing diisi via Google Form
 *
 * Usage:
 *   php scripts/import_lk_pairing.php --phase=1              # dry-run phase 1
 *   php scripts/import_lk_pairing.php --phase=1 --execute    # execute phase 1
 *   php scripts/import_lk_pairing.php --phase=2 --execute    # execute phase 2
 *   php scripts/import_lk_pairing.php --phase=2 --execute --overwrite
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Reader\XLSX\Options;

// ─── Configuration ────────────────────────────────────────────
define('LK_FILE', __DIR__ . '/../data/LK Pairing SE2026.xlsx');

define('BATCH_SIZE', 500);

// Mapping nama kecamatan LK → kd_kec 7-digit
const KEC_MAP = [
    'KENCONG'     => '3509010', 'GUMUKMAS'    => '3509020',
    'PUGER'       => '3509030', 'WULUHAN'     => '3509040',
    'AMBULU'      => '3509050', 'TEMPUREJO'   => '3509060',
    'SILO'        => '3509070', 'MAYANG'      => '3509080',
    'MUMBULSARI'  => '3509090', 'JENGGAWAH'   => '3509100',
    'AJUNG'       => '3509110', 'RAMBIPUJI'   => '3509120',
    'BALUNG'      => '3509130', 'UMBULSARI'   => '3509140',
    'SEMBORO'     => '3509150', 'JOMBANG'     => '3509160',
    'SUMBERBARU'  => '3509170', 'TANGGUL'     => '3509180',
    'BANGSALSARI' => '3509190', 'PANTI'       => '3509200',
    'SUKORAMBI'   => '3509210', 'ARJASA'      => '3509220',
    'PAKUSARI'    => '3509230', 'KALISAT'     => '3509240',
    'LEDOKOMBO'   => '3509250', 'SUMBERJAMBE' => '3509260',
    'SUKOWONO'    => '3509270', 'JELBUK'      => '3509280',
    'KALIWATES'   => '3509710', 'SUMBERSARI'  => '3509720',
    'PATRANG'     => '3509730',
];

// Nama kec LK yang beda dengan DB
const KEC_ALIAS = [
    'GUMUK MAS'  => 'GUMUKMAS',
    'SUMBER BARU' => 'SUMBERBARU',
];

const MISSING_PPL_EMAILS = [
    'iturofiq@gmail.com',
    'anyaninna074@gmail.com',
    'nurlailatuljh.87@gmail.com',
    'bundaiif28@mail.com',
];

// ─── CLI args ─────────────────────────────────────────────────
$phase = '';
$execute = false;
$overwrite = false;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--phase=')) {
        $phase = substr($arg, 8);
    }
    if ($arg === '--execute') $execute = true;
    if ($arg === '--overwrite') $overwrite = true;
}

if (!in_array($phase, ['1', '2'])) {
    echo "Gunakan: php " . basename(__FILE__) . " --phase=1|2 [--execute] [--overwrite]\n";
    echo "  phase=1 : Import lk_petugas (PPL+PML)\n";
    echo "  phase=2 : Import lk_pairing (Master SLS pairing)\n";
    exit(1);
}

$dryRun = !$execute;
$db = Database::instance();
$pdo = $db->pdo();

// ─── Helper ───────────────────────────────────────────────────
function resolveKdKec(string $nmKec): ?string
{
    $nm = strtoupper(trim($nmKec));
    if (isset(KEC_ALIAS[$nm])) $nm = KEC_ALIAS[$nm];
    return KEC_MAP[$nm] ?? null;
}

function resolveUserId(string $email): ?int
{
    global $db;
    $clean = strtolower(trim($email));
    if (empty($clean)) return null;
    return $db->fetchColumn("SELECT id FROM users WHERE LOWER(TRIM(email)) = ? LIMIT 1", [$clean]) ?: null;
}

// ─── Main ─────────────────────────────────────────────────────
echo "=== Import LK Pairing — Phase {$phase} ===\n\n";

if (!is_file(LK_FILE)) {
    echo "ERROR: File tidak ditemukan: " . LK_FILE . "\n";
    exit(1);
}

if ($dryRun) {
    echo "DRY-RUN mode. Jalankan dengan --execute untuk eksekusi.\n\n";
}

$options = new Options();
$options->setTempFolder(__DIR__ . '/../storage/import');
$reader = new Reader($options);
$reader->open(LK_FILE);

// ─── Phase 1: Import lk_petugas ──────────────────────────────
if ($phase === '1') {
    $insertedPPL = 0;
    $insertedPML = 0;
    $missing = [];
    $kecDist = [];
    $duplicates = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        $sheetName = trim($sheet->getName());

        if (!in_array($sheetName, ['PPL', 'PML'])) continue;
        $tipe = $sheetName === 'PPL' ? 'PPL' : 'PML';

        echo "--- Sheet: {$sheetName} ---\n";

        $rows = [];
        $headerSkipped = false;

        foreach ($sheet->getRowIterator() as $row) {
            $cells = $row->toArray();

            if (!$headerSkipped) {
                $headerSkipped = true;
                continue;
            }

            $kode    = trim((string) ($cells[0] ?? ''));
            $nmKec   = trim((string) ($cells[1] ?? ''));
            $nama    = trim((string) ($cells[2] ?? ''));
            $email   = strtolower(trim((string) ($cells[3] ?? '')));

            if (empty($kode) || empty($email)) continue;

            $kdKec = resolveKdKec($nmKec);
            $userId = resolveUserId($email);

            if ($kdKec === null) {
                echo "  WARN: Kecamatan '{$nmKec}' tidak dikenal (kode: {$kode})\n";
            }

            if ($userId === null && !in_array($email, $missing)) {
                $missing[] = $email;
            }

            if ($tipe === 'PPL') {
                $insertedPPL++;
                @$kecDist[$nmKec]['ppl']++;
            } else {
                $insertedPML++;
                @$kecDist[$nmKec]['pml']++;
            }

            $rows[] = [
                'kode_lk' => $kode,
                'tipe'    => $tipe,
                'nm_kec'  => $nmKec,
                'kd_kec'  => $kdKec,
                'nama'    => $nama,
                'email'   => $email,
                'user_id' => $userId,
            ];
        }

        echo "  Total {$tipe}: " . count($rows) . "\n";

        if (!$dryRun && !empty($rows)) {
            $placeholders = [];
            $vals = [];
            foreach ($rows as $r) {
                $placeholders[] = '(?, ?, ?, ?, ?, ?, ?)';
                $vals = array_merge($vals, [
                    $r['kode_lk'], $r['tipe'], $r['nm_kec'], $r['kd_kec'],
                    $r['nama'], $r['email'], $r['user_id'],
                ]);
            }

            // Batch INSERT via chunks
            for ($i = 0; $i < count($rows); $i += BATCH_SIZE) {
                $chunk = array_slice($rows, $i, BATCH_SIZE);
                $ph = [];
                $cv = [];
                foreach ($chunk as $r) {
                    $ph[] = '(?, ?, ?, ?, ?, ?, ?)';
                    $cv = array_merge($cv, [
                        $r['kode_lk'], $r['tipe'], $r['nm_kec'], $r['kd_kec'],
                        $r['nama'], $r['email'], $r['user_id'],
                    ]);
                }
                $sql = "INSERT INTO lk_petugas (kode_lk, tipe, nm_kec, kd_kec, nama, email, user_id) VALUES "
                     . implode(', ', $ph);
                $pdo->prepare($sql)->execute($cv);
            }
            echo "  -> Di-insert ke lk_petugas\n";

            // Update wilayah_kerja.aktual_ppl_lk / aktual_pml_lk
            $stmtUpd = $pdo->prepare("
                UPDATE wilayah_kerja wk
                JOIN prelist_kecamatan pk ON pk.nm_kec = wk.nama_kecamatan
                SET wk.aktual_{$tipe}_lk = (
                    SELECT COUNT(*) FROM lk_petugas lp
                    WHERE lp.kd_kec = pk.kd_kec AND lp.tipe = ?
                )
                WHERE pk.kd_kab = '3509'
            ");
            $stmtUpd->execute([$tipe]);
        }
    }

    echo "\n--- Ringkasan ---\n";
    echo "  PPL: {$insertedPPL}\n";
    echo "  PML: {$insertedPML}\n";

    if (!empty($missing)) {
        echo "\n⚠️  Email tidak ditemukan di users:\n";
        foreach ($missing as $m) {
            echo "  - {$m}\n";
        }
    }

    if (!empty($duplicates)) {
        echo "\n⚠️  Email duplikat (lintas kecamatan):\n";
        foreach ($duplicates as $email => $kecs) {
            echo "  - {$email}: " . implode(', ', $kecs) . "\n";
        }
    }

    echo "\nDistribusi per kecamatan:\n";
    foreach ($kecDist as $kec => $d) {
        echo sprintf("  %-15s PPL: %4d  PML: %3d\n", $kec, $d['ppl'] ?? 0, $d['pml'] ?? 0);
    }
}

// ─── Phase 2: Import lk_pairing ──────────────────────────────
if ($phase === '2') {
    $inserted = 0;
    $paired = 0;
    $totalMuatan = 0;
    $zeroMuatan = 0;
    $notFound = 0;

    foreach ($reader->getSheetIterator() as $sheet) {
        $sheetName = trim($sheet->getName());
        if ($sheetName !== 'Master SLS') continue;

        echo "--- Sheet: {$sheetName} ---\n";

        $rows = [];
        $headerSkipped = false;

        foreach ($sheet->getRowIterator() as $row) {
            $cells = $row->toArray();

            if (!$headerSkipped) {
                $headerSkipped = true;
                continue;
            }

            $idsubsls = trim((string) ($cells[5] ?? ''));
            $muatan   = (int) ($cells[15] ?? 0);
            $muatanKel = (int) ($cells[12] ?? 0);
            $muatanSt  = (int) ($cells[13] ?? 0);
            $muatanBang = (int) ($cells[14] ?? 0);
            $kodePPL  = trim((string) ($cells[16] ?? ''));
            $kodePML  = trim((string) ($cells[19] ?? ''));

            if (empty($idsubsls) || strlen($idsubsls) !== 16) continue;

            $sipwId = $db->fetchColumn(
                "SELECT id FROM sipw_import WHERE idsubsls = ? LIMIT 1",
                [$idsubsls]
            );

            if (!$sipwId) {
                $notFound++;
                continue;
            }

            // Resolve kode PPL/PML → user_id via lk_petugas
            $pplId = null;
            if (!empty($kodePPL)) {
                $pplId = $db->fetchColumn(
                    "SELECT user_id FROM lk_petugas WHERE kode_lk = ? AND tipe = 'PPL' LIMIT 1",
                    [$kodePPL]
                );
                if ($pplId) $paired++;
            }

            $pmlId = null;
            if (!empty($kodePML)) {
                $pmlId = $db->fetchColumn(
                    "SELECT user_id FROM lk_petugas WHERE kode_lk = ? AND tipe = 'PML' LIMIT 1",
                    [$kodePML]
                );
                if ($pmlId) $paired++;
            }

            $totalMuatan += $muatan;
            if ($muatan === 0) $zeroMuatan++;

            $rows[] = [
                'idsubsls'      => $idsubsls,
                'sipw_id'       => $sipwId,
                'kode_ppl'      => empty($kodePPL) ? null : $kodePPL,
                'ppl_id'        => $pplId,
                'kode_pml'      => empty($kodePML) ? null : $kodePML,
                'pml_id'        => $pmlId,
                'muatan'        => $muatan,
                'muatan_kel'    => $muatanKel,
                'muatan_st2023' => $muatanSt,
                'muatan_bang'   => $muatanBang,
                'paired_at'     => (!empty($kodePPL) || !empty($kodePML)) ? date('Y-m-d H:i:s') : null,
            ];

            $inserted++;

            // Batch insert
            if (count($rows) >= BATCH_SIZE) {
                if (!$dryRun) {
                    batchInsertLkPairing($pdo, $rows, $overwrite);
                }
                $rows = [];
            }
        }

        // Sisa rows
        if (!empty($rows) && !$dryRun) {
            batchInsertLkPairing($pdo, $rows, $overwrite);
        }
    }

    echo "\n--- Ringkasan Phase 2 ---\n";
    echo "  Subsls diimport: {$inserted}\n";
    echo "  Subsls tidak ditemukan di sipw_import: {$notFound}\n";
    echo "  Total muatan: " . number_format($totalMuatan) . "\n";
    echo "  Muatan = 0: {$zeroMuatan}\n";
    echo "  Pairing PPL/PML terisi: {$paired}\n";
}

$reader->close();

// ─── Batch insert helper ──────────────────────────────────────
function batchInsertLkPairing(\PDO $pdo, array &$rows, bool $overwrite): void
{
    $ph = [];
    $vals = [];

    foreach ($rows as $r) {
        if ($overwrite) {
            // DELETE existing then INSERT
            $pdo->prepare("DELETE FROM lk_pairing WHERE idsubsls = ?")->execute([$r['idsubsls']]);
        }
        $ph[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $vals = array_merge($vals, [
            $r['idsubsls'], $r['sipw_id'],
            $r['kode_ppl'], $r['ppl_id'],
            $r['kode_pml'], $r['pml_id'],
            $r['muatan'], $r['muatan_kel'], $r['muatan_st2023'], $r['muatan_bang'],
            $r['paired_at'],
        ]);
    }

    $sql = "INSERT INTO lk_pairing (idsubsls, sipw_id, kode_ppl, ppl_id, kode_pml, pml_id, muatan, muatan_kel, muatan_st2023, muatan_bang, paired_at) VALUES "
         . implode(', ', $ph);

    try {
        $pdo->prepare($sql)->execute($vals);
    } catch (Throwable $e) {
        echo "  Batch insert error: " . $e->getMessage() . "\n";
    }

    $rows = [];
}

echo "\nSelesai.\n";
