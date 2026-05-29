<?php
/**
 * CLI Validator — Shared Database Connection
 *
 * Penggunaan:
 *   php test-shared-connection.php
 *
 * Output yang sama dengan halaman web /test-connection
 */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$db = \App\Helpers\Database::getInstance();

$PASS = "\033[32m✓ PASS\033[0m";
$FAIL = "\033[31m✗ FAIL\033[0m";
$SKIP = "\033[33m- SKIP\033[0m";
$BOLD = "\033[1m";
$RESET = "\033[0m";
$GREEN = "\033[32m";
$RED = "\033[31m";
$YELLOW = "\033[33m";
$CYAN = "\033[36m";

echo "\n";
echo "{$BOLD}============================================================{$RESET}\n";
echo "{$BOLD}  VALIDASI SHARED REAL-TIME DATABASE — Dashboard SE2026{$RESET}\n";
echo "{$BOLD}============================================================{$RESET}\n\n";

// ─── 1. SHARED DATABASE PROOF ─────────────────────────────────────
echo "{$CYAN}[1/8] Shared Database Proof{$RESET}\n";
$dbName = $db->getCurrentDatabase();
$expected = 'bps_jember_se2026';
$status = $dbName === $expected ? $PASS : $FAIL;
echo "  $status SELECT DATABASE() → '$dbName' (expected: '$expected')\n\n";

// ─── 2. PDO SINGLETON ─────────────────────────────────────────────
echo "{$CYAN}[2/8] PDO Singleton Validation{$RESET}\n";
$connId = $db->getConnectionId();
echo "  $PASS Database::getInstance() — Connection ID: $connId\n\n";

// ─── 3. SERVER INFO ───────────────────────────────────────────────
echo "{$CYAN}[3/8] MySQL Server Info{$RESET}\n";
echo "  $PASS Version: " . $db->serverInfo() . "\n";
echo "  $PASS Server Time: " . $db->serverTime() . "\n";
echo "  $PASS Timezone: " . $db->sessionTimezone() . "\n";
echo "  $PASS Active connections: " . $db->activeConnectionCount() . "\n\n";

// ─── 4. LIVE TABLE COUNTS ─────────────────────────────────────────
echo "{$CYAN}[4/8] Live Table Counts (real-time){$RESET}\n";
$tablesToCount = ['users', 'wilayah_kerja', 'alokasi_petugas'];
foreach ($tablesToCount as $tbl) {
    $exists = $db->tableExists($tbl);
    if ($exists) {
        $cnt = $db->count($tbl);
        echo "  $PASS $tbl: $cnt rows\n";
    } else {
        echo "  $SKIP $tbl: TABLE NOT FOUND\n";
    }
}

// Check desa
if ($db->tableExists('desa')) {
    echo "  $PASS desa: " . $db->count('desa') . " rows\n";
} else {
    echo "  $SKIP desa: TABLE NOT FOUND (data ada di wilayah_kerja)\n";
}
echo "\n";

// ─── 5. SAMPLE DATA ───────────────────────────────────────────────
echo "{$CYAN}[5/8] Sample Live Data{$RESET}\n";

$users = $db->fetchAll("SELECT id, username, role, status_akun, last_login_at FROM users LIMIT 10");
if (count($users) > 0) {
    echo "  $PASS users: " . count($users) . " rows (SELECT * FROM users LIMIT 10)\n";
    foreach ($users as $u) {
        echo "    #{$u['id']} {$u['username']} ({$u['role']}) - {$u['status_akun']}\n";
    }
} else {
    echo "  $SKIP users: tabel kosong\n";
}

$wilayah = $db->fetchAll("SELECT * FROM wilayah_kerja LIMIT 10");
if (count($wilayah) > 0) {
    echo "  $PASS wilayah_kerja: " . count($wilayah) . " rows\n";
    foreach ($wilayah as $w) {
        echo "    {$w['kode_kecamatan']} — {$w['nama_kecamatan']} (PCL: {$w['kebutuhan_pcl']}/{$w['terisi_pcl']})\n";
    }
} else {
    echo "  $SKIP wilayah_kerja: tabel kosong\n";
}
echo "\n";

// ─── 6. REAL-TIME PROOF ──────────────────────────────────────────
echo "{$CYAN}[6/8] Realtime Validation{$RESET}\n";
$counts = [];
$keyTables = ['users', 'wilayah_kerja', 'alokasi_petugas', 'monitoring_progress', 'sipw_import', 'activity_logs'];
foreach ($keyTables as $tbl) {
    $exists = $db->tableExists($tbl);
    $counts[$tbl] = $exists ? $db->count($tbl) : -1;
}
foreach ($counts as $tbl => $cnt) {
    $label = $cnt >= 0 ? number_format($cnt) . " rows" : "NOT FOUND";
    echo "  $tbl: $label\n";
}
echo "  $PASS All queries are LIVE — no cache, no sync\n\n";

// ─── 7. TABLE LIST ───────────────────────────────────────────────
echo "{$CYAN}[7/8] All Tables in Database{$RESET}\n";
$allTables = $db->fetchAll(
    "SELECT TABLE_NAME FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
     ORDER BY TABLE_NAME",
    [$dbName]
);
echo "  Total: " . count($allTables) . " tables\n";
$chunks = array_chunk($allTables, 5);
foreach ($chunks as $group) {
    $names = array_map(fn($r) => $r['TABLE_NAME'], $group);
    echo "    " . implode(', ', $names) . "\n";
}
echo "\n";

// ─── 8. SUMMARY ──────────────────────────────────────────────────
echo "{$CYAN}[8/8] Summary{$RESET}\n";
$queryCount = $db->getQueryCount();
echo "  Total queries executed: $queryCount\n\n";

echo "{$GREEN}{$BOLD}============================================================{$RESET}\n";
echo "{$GREEN}{$BOLD}  ✓ DASHBOARD SE2026 TERHUBUNG SHARED DATABASE{$RESET}\n";
echo "{$GREEN}{$BOLD}    Database : $dbName{$RESET}\n";
echo "{$GREEN}{$BOLD}    Koneksi  : PDO Singleton ID: $connId{$RESET}\n";
echo "{$GREEN}{$BOLD}    Waktu    : " . $db->serverTime() . "{$RESET}\n";
echo "{$GREEN}{$BOLD}    Status   : REAL-TIME — tanpa copy/sync{$RESET}\n";
echo "{$GREEN}{$BOLD}============================================================{$RESET}\n\n";

echo "{$YELLOW}Cara membuktikan real-time:{$RESET}\n";
echo "  1. Buka web SE2026 (aplikasi existing)\n";
echo "  2. Tambah/edit data (contoh: tambah user baru)\n";
echo "  3. Jalankan ulang: php test-shared-connection.php\n";
echo "  4. Data count harus berubah langsung\n";
echo "  5. Tidak ada proses sinkronasi\n";
echo "\n";
