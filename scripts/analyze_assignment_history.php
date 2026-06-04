<?php
/**
 * scripts/analyze_assignment_history.php
 *
 * Analisis pola historical assignment dari dash_assignment_log.
 *
 * Output:
 *   - Total log entries
 *   - Breakdown per action (INSERT/UPDATE/DELETE)
 *   - Per-user activity (changed_by)
 *   - Per-SLS final state (rekonstruksi)
 *   - Test vs production indicators
 *
 * Rekomendasi: R1.5 dari Laporan Analisis Pegawai Organik.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;

$db = Database::getInstance();

echo "=== Historical Assignment Analysis ===\n\n";

$logs = $db->query("SELECT * FROM dash_assignment_log ORDER BY created_at ASC, id ASC")->fetchAll();
echo "Total log entries: " . count($logs) . "\n\n";

echo "-- Breakdown by action --\n";
$byAction = [];
foreach ($logs as $l) {
    $a = $l['action'];
    $byAction[$a] = ($byAction[$a] ?? 0) + 1;
}
foreach ($byAction as $a => $cnt) {
    echo "  $a: $cnt\n";
}

echo "\n-- Breakdown by user --\n";
$byUser = [];
foreach ($logs as $l) {
    $u = $l['changed_by'];
    $byUser[$u] = ($byUser[$u] ?? 0) + 1;
}
foreach ($byUser as $u => $cnt) {
    $role = 'unknown';
    if ($u > 0) {
        $urow = $db->query("SELECT username, role FROM users WHERE id = " . (int)$u)->fetch();
        if ($urow) {
            $role = "{$urow['username']} ({$urow['role']})";
        }
    } else {
        $role = 'system/test';
    }
    echo "  user_id=$u ($role): $cnt aksi\n";
}

echo "\n-- Reconstructed final state per sipw_id --\n";
$state = [];
foreach ($logs as $l) {
    $sid = $l['sipw_id'];
    if ($l['action'] === 'INSERT') {
        $data = json_decode($l['new_data'], true);
        $state[$sid] = [
            'exists'      => true,
            'created_by'  => $l['changed_by'],
            'created_at'  => $l['created_at'],
            'pencacah_id' => $data['pencacah_id'] ?? null,
            'pengawas_id' => $data['pengawas_id'] ?? null,
            'tf_id'       => $data['task_force_id'] ?? null,
        ];
    } elseif ($l['action'] === 'DELETE') {
        $state[$sid] = ['exists' => false, 'deleted_by' => $l['changed_by'], 'deleted_at' => $l['created_at']];
    } elseif ($l['action'] === 'UPDATE') {
        if (isset($state[$sid]) && $state[$sid]['exists']) {
            $data = json_decode($l['new_data'], true);
            $state[$sid]['pencacah_id'] = $data['pencacah_id'] ?? ($state[$sid]['pencacah_id'] ?? null);
            $state[$sid]['pengawas_id'] = $data['pengawas_id'] ?? ($state[$sid]['pengawas_id'] ?? null);
            $state[$sid]['tf_id']       = $data['task_force_id'] ?? ($state[$sid]['tf_id'] ?? null);
        }
    }
}

$exists = 0;
$deleted = 0;
foreach ($state as $sid => $s) {
    if ($s['exists']) {
        $exists++;
        $row = $db->query("SELECT idsubsls, nmkec, nmdesa, nmsls FROM sipw_import WHERE id = " . (int)$sid)->fetch();
        if ($row) {
            echo "  [EXISTS] sipw_id={$sid} → {$row['idsubsls']} | {$row['nmkec']}/{$row['nmdesa']} | {$row['nmsls']}\n";
        } else {
            echo "  [EXISTS] sipw_id={$sid} → SLS not found in sipw_import\n";
        }
    } else {
        $deleted++;
    }
}
echo "\nFinal state: $exists still 'active' in log, $deleted deleted.\n\n";

echo "-- Test/Production indicator --\n";
$testIndicators = 0;
$prodIndicators = 0;
foreach ($logs as $l) {
    $isTest = false;
    if ($l['changed_by'] === 0) $isTest = true;
    if ($l['ip_address'] === '0.0.0.0') $isTest = true;
    if ($l['change_note'] && stripos($l['change_note'], 'test') !== false) $isTest = true;
    if ($isTest) $testIndicators++; else $prodIndicators++;
}
echo "  Test/manual entries: $testIndicators\n";
echo "  Production entries:  $prodIndicators\n\n";

echo "-- Current sipw_assignment row count --\n";
$cur = (int) $db->fetchColumn("SELECT COUNT(*) FROM sipw_assignment");
echo "  Current: $cur\n";

echo "\n-- Recommendation --\n";
if ($testIndicators === count($logs)) {
    echo "  ALL log entries are test/manual — no production data to restore.\n";
    echo "  Current 0-row state is correct for production. DO NOT RESTORE.\n";
    echo "  Use log only for audit/replay during dev, not for production rebuild.\n";
} elseif ($exists > 0) {
    echo "  Some entries indicate 'EXISTS' state but main table is empty (already cleaned up).\n";
    echo "  Manual review needed: should we replay? See R1.5 in laporan.\n";
} else {
    echo "  No active state to restore. Clean slate confirmed.\n";
}
