<?php
/**
 * scripts/seed_pegawai_organik.php
 *
 * Seed 5 user `pegawai` organik dengan kecamatan_bertugas.
 * Alokasi: 31 kecamatan Jember / 5 pegawai = 6-7 kecamatan per pegawai.
 *
 * Temuan (Jun 2026):
 *   Hanya 1 user `pegawai` (pegawai3509, placeholder, no wilayah).
 *   Single point of failure untuk workflow penugasan SLS.
 *
 * Usage:
 *   php scripts/seed_pegawai_organik.php              (dry-run, default)
 *   php scripts/seed_pegawai_organik.php --execute    (eksekusi INSERT)
 *   php scripts/seed_pegawai_organik.php --reset      (hapus dulu, baru insert)
 *
 * Rekomendasi: R3.1 dari Laporan Analisis Pegawai Organik.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;
use App\Helpers\Security;

$db = Database::getInstance();
$execute = in_array('--execute', $argv, true);
$reset   = in_array('--reset', $argv, true);

$defaultPassword = 'Pegawai@2026';

$pegawaiList = [
    [
        'username'   => 'pegawai_ali',
        'nama'       => 'Ali Sodikin, S.ST',
        'email'      => 'ali.sodikin@bps.go.id',
        'kecamatan'  => ['KENCONG', 'GUMUK MAS', 'PUGER', 'WULUHAN', 'AMBULU', 'TEMPUREJO'],
    ],
    [
        'username'   => 'pegawai_budi',
        'nama'       => 'Budi Santoso, S.ST',
        'email'      => 'budi.santoso@bps.go.id',
        'kecamatan'  => ['SILO', 'MAYANG', 'MUMBULSARI', 'JENGGAWAH', 'AJUNG', 'RAMBIPUJI'],
    ],
    [
        'username'   => 'pegawai_citra',
        'nama'       => 'Citra Larasati, S.Si',
        'email'      => 'citra.larasati@bps.go.id',
        'kecamatan'  => ['BALUNG', 'UMBULSARI', 'SEMBORO', 'JOMBANG', 'SUMBER BARU', 'TANGGUL'],
    ],
    [
        'username'   => 'pegawai_dani',
        'nama'       => 'Dani Rahmadi, S.ST',
        'email'      => 'dani.rahmadi@bps.go.id',
        'kecamatan'  => ['BANGSALSARI', 'PANTI', 'SUKORAMBI', 'ARJASA', 'PAKUSARI', 'KALISAT'],
    ],
    [
        'username'   => 'pegawai_erni',
        'nama'       => 'Erni Wulandari, S.ST',
        'email'      => 'erni.wulandari@bps.go.id',
        'kecamatan'  => ['LEDOKOMBO', 'SUMBERJAMBE', 'SUKOWONO', 'JELBUK', 'KALIWATES', 'SUMBERSARI', 'PATRANG'],
    ],
];

echo "=== Seed 5 Pegawai Organik (BPS) — Jember ===\n";
echo "Mode: " . ($execute ? 'EXECUTE' : 'DRY-RUN (use --execute to apply)') . "\n";
echo "Reset: " . ($reset ? 'YES (hapus dulu existing)' : 'NO (skip jika sudah ada)') . "\n";
echo "Default password: {$defaultPassword}\n\n";

if ($reset && $execute) {
    $placeholders = implode(',', array_fill(0, count($pegawaiList), '?'));
    $stmt = $db->query("DELETE FROM users WHERE username IN ({$placeholders})", array_column($pegawaiList, 'username'));
    $deleted = $stmt->rowCount();
    echo "Reset: $deleted rows deleted.\n\n";
}

foreach ($pegawaiList as $p) {
    $existing = $db->fetchOne("SELECT id, status_akun, kecamatan_bertugas FROM users WHERE username = ?", [$p['username']]);

    if ($existing) {
        $kcList = $p['kecamatan'];
        echo "  SKIP: {$p['username']} ({$p['nama']}) — already exists (id={$existing['id']}, status={$existing['status_akun']}, kec={$existing['kecamatan_bertugas']})\n";
        echo "         Will be assigned to: " . implode(', ', $kcList) . "\n";
        continue;
    }

    echo "  NEW:  {$p['username']} ({$p['nama']})\n";
    echo "         Email: {$p['email']}\n";
    echo "         Kecamatan: " . implode(', ', $p['kecamatan']) . " (" . count($p['kecamatan']) . " kec)\n";

    if (!$execute) {
        echo "         (dry-run: not inserted)\n\n";
        continue;
    }

    $kecStr = implode(', ', $p['kecamatan']);
    $sql = "
        INSERT INTO users
            (username, email, nama_lengkap, password, role, status_akun, kecamatan_bertugas, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, 'pegawai', 'active', ?, NOW(), NOW())
    ";
    $params = [
        $p['username'],
        $p['email'],
        $p['nama'],
        Security::hashPassword($defaultPassword),
        $kecStr,
    ];
    $db->query($sql, $params);
    $newId = $db->pdo()->lastInsertId();
    echo "         INSERTED as id={$newId}\n\n";
}

echo "=== Final state ===\n";
$rows = $db->query("
    SELECT id, username, nama_lengkap, email, status_akun, kecamatan_bertugas, last_login_at
    FROM users WHERE role = 'pegawai'
    ORDER BY id
")->fetchAll();
foreach ($rows as $r) {
    $last = $r['last_login_at'] ?? 'never';
    echo "  [{$r['id']}] {$r['username']} | {$r['nama_lengkap']} | {$r['status_akun']} | last_login: {$last}\n";
    echo "       kec: {$r['kecamatan_bertugas']}\n";
}

if (!$execute) {
    echo "\nDRY-RUN: tidak ada perubahan.\n";
    echo "Jalankan dengan --execute untuk apply.\n";
}
