<?php
/**
 * scripts/populate_petugas_wilayah.php
 *
 * Populate tabel petugas_wilayah dari users.kecamatan_bertugas (CSV string).
 * Mendekompose string "KENCONG, GUMUK MAS, PUGER" menjadi 3 row terpisah.
 *
 * Pre-requisite:
 *   - patch_008 applied (tabel petugas_wilayah exists)
 *   - R3.1 applied (5 pegawai dengan kecamatan_bertugas)
 *   - R1.1 applied (mitra dengan kecamatan_bertugas)
 *
 * Usage:
 *   php scripts/populate_petugas_wilayah.php              (dry-run, default)
 *   php scripts/populate_petugas_wilayah.php --execute    (eksekusi INSERT)
 *   php scripts/populate_petugas_wilayah.php --role=mitra (filter role)
 *
 * Rekomendasi: R1.2 dari Laporan Analisis Pegawai Organik.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$db = Database::getInstance();
$execute = false;
$filterRole = null;

foreach ($argv as $a) {
    if ($a === '--execute') $execute = true;
    if (str_starts_with($a, '--role=')) $filterRole = substr($a, 7);
}

echo "=== Populate petugas_wilayah dari users.kecamatan_bertugas ===\n";
echo "Mode:  " . ($execute ? 'EXECUTE' : 'DRY-RUN (use --execute to apply)') . "\n";
echo "Role:  " . ($filterRole ?? 'all') . "\n\n";

$tableExists = (int) $db->fetchColumn("
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'petugas_wilayah'
");
if (!$tableExists) {
    die("ERROR: tabel petugas_wilayah belum ada. Jalankan: php scripts/apply_patch_008.php\n");
}

$where = "status_akun = 'active' AND kecamatan_bertugas IS NOT NULL AND kecamatan_bertugas != ''";
$params = [];
if ($filterRole) {
    $where .= " AND role = ?";
    $params[] = $filterRole;
}

$users = $db->fetchAll("
    SELECT id, username, nama_lengkap, role, kecamatan_bertugas
    FROM users
    WHERE $where
    ORDER BY role, id
", $params);

echo "Users to process: " . count($users) . "\n\n";

$proposed = [];
foreach ($users as $u) {
    $kecList = array_filter(array_map('trim', explode(',', $u['kecamatan_bertugas'])));
    foreach ($kecList as $nmKec) {
        $kec = $db->fetchOne("
            SELECT kd_kab, kd_kec FROM prelist_kecamatan WHERE nm_kec = ? LIMIT 1
        ", [$nmKec]);
        if (!$kec) {
            echo "  WARN: user={$u['username']} kecamatan='{$nmKec}' not found in prelist_kecamatan\n";
            continue;
        }
        $exists = (int) $db->fetchColumn("
            SELECT COUNT(*) FROM petugas_wilayah
            WHERE user_id = ? AND kd_kab = ? AND kd_kec = ?
        ", [$u['id'], $kec['kd_kab'], $kec['kd_kec']]);
        if ($exists > 0) {
            continue;
        }
        $proposed[] = [
            'user_id'       => $u['id'],
            'kd_kab'        => $kec['kd_kab'],
            'kd_kec'        => $kec['kd_kec'],
            'role_snapshot' => $u['role'],
            'username'      => $u['username'],
            'nm_kec'        => $nmKec,
        ];
    }
}

echo "Proposed new rows: " . count($proposed) . "\n";
if (count($proposed) === 0) {
    echo "Nothing to insert.\n";
    exit(0);
}

echo "\nBy role:\n";
$byRole = [];
foreach ($proposed as $p) $byRole[$p['role_snapshot']] = ($byRole[$p['role_snapshot']] ?? 0) + 1;
foreach ($byRole as $r => $cnt) {
    echo "  $r: $cnt rows\n";
}

echo "\nBy kecamatan (top 10):\n";
$byKec = [];
foreach ($proposed as $p) $byKec[$p['nm_kec']] = ($byKec[$p['nm_kec']] ?? 0) + 1;
arsort($byKec);
$i = 0;
foreach ($byKec as $nm => $cnt) {
    if ($i++ >= 10) break;
    printf("  %-25s %d user(s)\n", $nm, $cnt);
}

if (!$execute) {
    echo "\nDRY-RUN: tidak ada perubahan.\n";
    echo "Jalankan dengan --execute untuk apply.\n";
    exit(0);
}

echo "\nExecuting INSERT...\n";
$db->beginTransaction();
try {
    $stmt = $db->query("
        INSERT INTO petugas_wilayah (user_id, kd_kab, kd_kec, role_snapshot)
        VALUES (?, ?, ?, ?)
    ");
    $count = 0;
    foreach ($proposed as $p) {
        $stmt = $db->query("
            INSERT INTO petugas_wilayah (user_id, kd_kab, kd_kec, role_snapshot)
            VALUES (?, ?, ?, ?)
        ", [$p['user_id'], $p['kd_kab'], $p['kd_kec'], $p['role_snapshot']]);
        $count++;
    }
    $db->commit();
    echo "OK: $count rows inserted.\n";
} catch (Throwable $e) {
    $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

$total = (int) $db->fetchColumn("SELECT COUNT(*) FROM petugas_wilayah");
echo "Total rows in petugas_wilayah: $total\n";
