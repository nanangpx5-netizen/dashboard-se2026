<?php

namespace App\Helpers;

use App\Core\Database;

final class AuditLog
{
    /**
     * Catat event ke activity_logs.
     */
    public static function log(
        string $action,
        string $module,
        string $detail = '',
        ?int $userId = null
    ): void {
        $pdo = Database::instance()->pdo();

        if ($userId === null) {
            $user = Session::get('user');
            $userId = $user['id'] ?? null;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs
                    (user_id, action, module, detail, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $action,
                $module,
                $detail,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]);
        } catch (\Throwable $e) {
            error_log('AuditLog gagal: ' . $e->getMessage());
        }
    }

    /**
     * Catat perubahan petugas dengan before/after JSON.
     */
    public static function petugasChange(
        string $action,
        int $petugasId,
        ?array $before,
        ?array $after
    ): void {
        $detail = json_encode([
            'petugas_id' => $petugasId,
            'before' => $before,
            'after'  => $after,
        ], JSON_UNESCAPED_UNICODE);

        self::log('petugas_' . $action, 'petugas', $detail);
    }

    /**
     * Catat aktivitas import SIPW.
     */
    public static function importEvent(
        string $status,
        string $filename,
        array $extra = []
    ): void {
        $detail = json_encode(array_merge(
            ['file' => $filename],
            $extra
        ), JSON_UNESCAPED_UNICODE);

        self::log('import_' . $status, 'import', $detail);
    }
}
