<?php

namespace App\Helpers;

use App\Core\Database;

final class Backup
{
    /**
     * Catat perubahan assignment ke dash_assignment_log.
     * Panggil dari controller setiap kali assignment berubah.
     */
    public static function logAssignment(
        string $action,
        int $sipwId,
        ?int $assignmentId,
        ?array $oldData,
        ?array $newData,
        ?string $note = null
    ): void {
        $pdo = Database::instance()->pdo();
        $user = Session::get('user');
        $userId = (int) ($user['id'] ?? 0);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $stmt = $pdo->prepare("
            INSERT INTO dash_assignment_log
                (assignment_id, sipw_id, action, old_data, new_data,
                 changed_by, ip_address, change_note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $assignmentId,
            $sipwId,
            $action,
            $oldData ? json_encode($oldData) : null,
            $newData ? json_encode($newData) : null,
            $userId,
            $ip,
            $note,
        ]);
    }

    /**
     * Buat rollback point sebelum import SIPW batch.
     * Menyimpan snapshot data sipw_import yang akan terpengaruh.
     *
     * @param string $batchId    Batch ID dari dash_import_log
     * @param array  $affectedIds  Array ID dari sipw_import yang akan di-UPSERT
     * @return int ID rollback point
     */
    public static function createRollbackPoint(
        string $batchId,
        array $affectedIds,
        string $note = null
    ): int {
        $pdo = Database::instance()->pdo();
        $user = Session::get('user');
        $userId = (int) ($user['id'] ?? 0);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $affectedIds = array_map('intval', $affectedIds);
        $affectedIds = array_unique($affectedIds);
        $rowCount = count($affectedIds);

        if ($rowCount === 0) {
            return 0;
        }

        // Ambil snapshot data lama
        $placeholders = implode(',', array_fill(0, $rowCount, '?'));
        $stmt = $pdo->prepare("
            SELECT * FROM sipw_import WHERE id IN ({$placeholders})
        ");
        $stmt->execute(array_values($affectedIds));
        $rows = $stmt->fetchAll();

        $oldData = [];
        foreach ($rows as $r) {
            $id = (int) $r['id'];
            unset($r['id']); // id tidak perlu di-restore
            $oldData[$id] = $r;
        }

        $stmt = $pdo->prepare("
            INSERT INTO dash_rollback_points
                (batch_id, operation, table_name, row_ids, old_data,
                 row_count, created_by, ip_address, note)
            VALUES (?, 'IMPORT_SIPW', 'sipw_import', ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $batchId,
            json_encode($affectedIds),
            json_encode($oldData),
            $rowCount,
            $userId,
            $ip,
            $note,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Rollback import batch berdasarkan batch_id.
     * - Baris baru (ada di new_data tapi tidak di old_data) → DELETE
     * - Baris lama (ada di old_data) → UPDATE ke data sebelum import
     */
    public static function rollbackImport(string $batchId): array
    {
        $pdo = Database::instance()->pdo();
        $user = Session::get('user');
        $userId = (int) ($user['id'] ?? 0);

        // Cari rollback point
        $stmt = $pdo->prepare("
            SELECT * FROM dash_rollback_points
            WHERE batch_id = ? AND is_used = 0
            LIMIT 1
        ");
        $stmt->execute([$batchId]);
        $point = $stmt->fetch();

        if (!$point) {
            return [
                'success' => false,
                'message' => 'Rollback point tidak ditemukan atau sudah pernah dipakai.',
            ];
        }

        $oldData = json_decode($point['old_data'], true);
        $rowIds = json_decode($point['row_ids'], true);
        $restored = 0;
        $deleted = 0;

        $pdo->beginTransaction();
        try {
            foreach ($oldData as $id => $data) {
                $sets = [];
                $params = [];
                foreach ($data as $col => $val) {
                    $sets[] = "{$col} = ?";
                    $params[] = $val;
                }
                $params[] = (int) $id;
                $sql = "UPDATE sipw_import SET " . implode(', ', $sets) . " WHERE id = ?";
                $pdo->prepare($sql)->execute($params);
                $restored++;
            }

            // Hapus baris baru yang tidak ada di old_data (baris murni baru dari import ini)
            if (!empty($rowIds)) {
                $existingIds = array_keys($oldData);
                $newIds = array_diff($rowIds, $existingIds);
                if (!empty($newIds)) {
                    $placeholders = implode(',', array_fill(0, count($newIds), '?'));
                    $pdo->prepare("DELETE FROM sipw_import WHERE id IN ({$placeholders})")
                        ->execute(array_values($newIds));
                    $deleted = count($newIds);
                }
            }

            // Hapus assignment terkait baris yang baru (tidak ada di old_data)
            if (!empty($newIds)) {
                $placeholders = implode(',', array_fill(0, count($newIds), '?'));
                $pdo->prepare("DELETE FROM sipw_assignment WHERE sipw_id IN ({$placeholders})")
                    ->execute(array_values($newIds));
            }

            // Tandai rollback point sebagai sudah dipakai
            $stmt = $pdo->prepare("
                UPDATE dash_rollback_points
                SET is_used = 1, used_at = NOW(), used_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$userId, (int) $point['id']]);

            // Update status import log
            $stmt = $pdo->prepare("
                UPDATE dash_import_log
                SET status = 'rolled_back',
                    error_message = CONCAT(
                        COALESCE(error_message, ''),
                        ' | Rollback oleh user #', ?, ' pada ', NOW()
                    )
                WHERE batch_id = ?
            ");
            $stmt->execute([$userId, $batchId]);

            $pdo->commit();

            Cache::forget('dashboard_stats');
            Cache::forget('dashboard_wilayah');
            Cache::forget('dashboard_beban');
            Cache::forget('kecamatan_list');

            return [
                'success' => true,
                'message' => "Rollback berhasil: {$restored} baris di-restore, {$deleted} baris baru dihapus.",
                'stats' => [
                    'restored' => $restored,
                    'deleted'  => $deleted,
                ],
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Rollback gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Daftar riwayat backup dari file system
     */
    public static function getBackupHistory(string $backupDir = null, int $limit = 20): array
    {
        $backupDir ??= dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($backupDir)) {
            return [];
        }

        $files = glob($backupDir . '/*.sql.gz');
        $files = array_merge($files, glob($backupDir . '/*.sql'));

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $result = [];
        foreach (array_slice($files, 0, $limit) as $f) {
            $size = filesize($f);
            $result[] = [
                'filename'  => basename($f),
                'path'      => $f,
                'size'      => $size,
                'size_fmt'  => Format::number($size / 1024, 1) . ' KB',
                'modified'  => date('Y-m-d H:i:s', filemtime($f)),
                'type'      => str_contains(basename($f), 'full') ? 'full' : 'incremental',
            ];
        }

        return $result;
    }

    /**
     * Daftar tabel dashboard yang perlu di-backup
     */
    public static function dashboardTables(): array
    {
        return [
            'sipw_import',
            'sipw_assignment',
            'dash_import_log',
            'dash_monitoring_summary',
            'dash_assignment_log',
            'dash_rollback_points',
        ];
    }
}
