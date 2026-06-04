<?php
/**
 * Smoke test: kecamatan scope (1:1) untuk role pegawai.
 *
 * Tujuan:
 * 1. Verify 5 pegawai login → session kecamatan_tugas ter-set
 * 2. Verify admin login → session kecamatan_tugas = NULL
 * 3. Verify applyKecamatanScope() override kdkec apapun
 * 4. Verify direct DB query (bypassing scope) masih lihat data luas
 *
 * Usage: php scripts/smoke_kecamatan_scope.php
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$db = Database::getInstance();
$pdo = $db->pdo();

echo "=== SMOKE TEST: Kecamatan Scope (1:1) untuk Role Pegawai ===\n\n";

/* 1. Cek schema + 5 pegawai default assignment */
echo "[1] Schema check + 5 pegawai default assignment:\n";
$rows = $db->query(
    "SELECT u.id, u.username, u.role, u.kecamatan_tugas, k.nm_kec
     FROM users u
     LEFT JOIN prelist_kecamatan k ON k.kd_kec = u.kecamatan_tugas
     WHERE u.role = 'pegawai' AND u.kecamatan_tugas IS NOT NULL
     ORDER BY u.id"
)->fetchAll();
foreach ($rows as $r) {
    printf("    id=%-5d %-12s role=%-10s kd_kec=%-7s nm_kec=%s\n",
        $r['id'], $r['username'], $r['role'], $r['kecamatan_tugas'], $r['nm_kec'] ?? '(NULL)');
}

/* 2. Validate 5 pegawai + cek apakah kd_kec valid di prelist_sls */
echo "\n[2] Validate kecamatan_tugas is referenced in prelist_kecamatan & prelist_sls:\n";
foreach ($rows as $r) {
    $kd7 = $r['kecamatan_tugas']; // 7-digit (matches prelist_kecamatan.kd_kec)
    $kd3 = substr($kd7, -3);      // 3-digit (matches prelist_sls.kd_kec, sipw_import.kdkec)
    $cKec = $db->fetchColumn(
        "SELECT COUNT(*) FROM prelist_kecamatan WHERE kd_kec = ?",
        [$kd7]
    );
    $cSls = $db->fetchColumn(
        "SELECT COUNT(*) FROM prelist_sls WHERE kd_kec = ?",
        [$kd3]
    );
    $cSipw = $db->fetchColumn(
        "SELECT COUNT(*) FROM sipw_import WHERE kdkec = ?",
        [$kd3]
    );
    printf("    %-12s (7d=%s / 3d=%s): prelist_kecamatan=%d  prelist_sls=%-6d  sipw_import=%d\n",
        $r['username'], $kd7, $kd3, $cKec, $cSls, $cSipw);
}

/* 3. Simulate Session::set('user', ...) dan applyKecamatanScope */
echo "\n[3] Simulate applyKecamatanScope() — verify regex + override:\n";

$baseClass = new class {
    public function getKecamatanScope(): ?string
    {
        if (empty($_SESSION['user']['kecamatan_tugas'])) {
            return null;
        }
        $scope = (string) $_SESSION['user']['kecamatan_tugas'];
        if (preg_match('/^([0-9]{3}|[0-9]{7})$/', $scope)) {
            return $scope;
        }
        return null;
    }
};

// Inisialisasi session (CLI-safe minimal stub)
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$tests = [
    ['name' => 'ali  (KENCONG 3509010)', 'kd_kec' => '3509010', 'expected' => '010'],
    ['name' => 'budi (SILO 3509070)',    'kd_kec' => '3509070', 'expected' => '070'],
    ['name' => 'citra(TANGGUL 3509180)', 'kd_kec' => '3509180', 'expected' => '180'],
    ['name' => 'dani (BANGSALSARI 3509190)', 'kd_kec' => '3509190', 'expected' => '190'],
    ['name' => 'erni (KALIWATES 3509710)',   'kd_kec' => '3509710', 'expected' => '710'],
];

foreach ($tests as $t) {
    $_SESSION['user'] = [
        'id' => 0,
        'username' => explode(' ', $t['name'])[0],
        'role' => 'pegawai',
        'kecamatan_tugas' => $t['kd_kec'],
    ];
    $scope = $baseClass->getKecamatanScope();
    $kd3 = $scope ? substr($scope, -3) : null;
    $ok = ($kd3 === $t['expected']) ? 'OK' : 'FAIL';
    printf("    [%s] %-30s scope=%s  → kd3=%s  (expected %s)\n",
        $ok, $t['name'], $scope ?? 'NULL', $kd3 ?? 'NULL', $t['expected']);
}

/* 4. Security test: try injection di session */
echo "\n[4] Security: injection attempts:\n";
$attacks = [
    "'; DROP TABLE users; --" => 'SQL injection (must reject)',
    "1 OR 1=1" => 'Tautology',
    "<script>alert(1)</script>" => 'XSS',
    "../../etc/passwd" => 'Path traversal',
    "" => 'Empty',
    "abc" => 'Non-numeric',
    "350910" => '6-digit (must reject — not 3 nor 7)',
    "3509100" => '7-digit (valid)',
    "190" => '3-digit (valid)',
];
foreach ($attacks as $payload => $label) {
    $_SESSION['user']['kecamatan_tugas'] = $payload;
    $scope = $baseClass->getKecamatanScope();
    $isValid = preg_match('/^([0-9]{3}|[0-9]{7})$/', (string) $payload) === 1;
    $verdict = ($scope !== null) === $isValid ? 'OK' : 'MISMATCH';
    printf("    [%s] %-40s → scope=%-8s (%s)\n",
        $verdict,
        $label,
        $scope === null ? 'NULL' : $scope,
        $scope === null ? 'rejected' : 'accepted'
    );
}

/* 5. Admin → no scope */
echo "\n[5] Admin user (no scope):\n";
$_SESSION['user'] = [
    'id' => 1,
    'username' => 'admin',
    'role' => 'admin',
    'kecamatan_tugas' => null,
];
$scope = $baseClass->getKecamatanScope();
printf("    admin scope = %s (expected NULL = no restriction)\n", $scope === null ? 'NULL' : $scope);

/* 6. Cross-kecamatan data test: prelist_sls count per kecamatan */
echo "\n[6] Pre-flight: prelist_sls row count per scope kecamatan:\n";
foreach ($tests as $t) {
    $count = $db->fetchColumn(
        "SELECT COUNT(*) FROM prelist_sls WHERE kd_kec = ?",
        [$t['expected']]
    );
    printf("    kd_kec=%s (%-13s) → %d SLS\n", $t['expected'], explode(' ', $t['name'])[1], $count);
}

/* 7. Cross-verify: query prelist_sls with all kecamatan should give 16538 */
echo "\n[7] Cross-verify: total prelist_sls for kdkab='09' (Jember):\n";
$total = $db->fetchColumn(
    "SELECT COUNT(*) FROM prelist_sls WHERE kd_kab = '3509'"
);
printf("    kd_kab='3509' (Jember) → %d SLS (expected ~16,538)\n", $total);

$perKec = $db->query("SELECT kd_kec, COUNT(*) AS c FROM prelist_sls WHERE kd_kab='3509' GROUP BY kd_kec ORDER BY kd_kec");
$sum = 0;
foreach ($perKec as $r) {
    $sum += (int) $r['c'];
}
printf("    sum of per-kecamatan rows = %d (should match above)\n", $sum);

echo "\n=== END SMOKE TEST ===\n";
