-- =============================================================================
-- PATCH 003: PERFORMANCE INDEXES
-- =============================================================================
-- Database : bps_jember_se2026
-- Target   : Production (idempotent)
--
-- Isi patch:
--   1. Index komposit activity_logs untuk rate limit query
--   2. Index komposit dash_import_log untuk history query
--   3. Index komposit sipw_import untuk grouping per wilayah
-- =============================================================================

USE bps_jember_se2026;

-- =============================================================================
-- 1. Index untuk rate limit login
-- =============================================================================
-- Query di AuthController.isLockedOut():
--   SELECT COUNT(*) FROM activity_logs
--   WHERE action = 'login_failed'
--     AND detail = ?
--     AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
-- =============================================================================

CALL add_index_if_missing(
    'activity_logs',
    'idx_act_rate_limit',
    'ALTER TABLE activity_logs ADD INDEX idx_act_rate_limit(action, detail(50), created_at)'
);

-- =============================================================================
-- 2. Index untuk riwayat import per user
-- =============================================================================
-- Query di ImportProcessor.getImportHistory():
--   SELECT ... FROM dash_import_log l
--   LEFT JOIN users u ON u.id = l.user_id
--   ORDER BY l.created_at DESC LIMIT ?
-- =============================================================================

CALL add_index_if_missing(
    'dash_import_log',
    'idx_importlog_created',
    'ALTER TABLE dash_import_log ADD INDEX idx_importlog_created(created_at)'
);

CALL add_index_if_missing(
    'dash_import_log',
    'idx_importlog_user_created',
    'ALTER TABLE dash_import_log ADD INDEX idx_importlog_user_created(user_id, created_at)'
);

-- =============================================================================
-- 3. Index komposit sipw_import untuk grouping per wilayah
-- =============================================================================
-- Query di DashboardController.muatanPerKecamatan() & progressWilayah():
--   SELECT ... FROM sipw_import si
--   LEFT JOIN ... GROUP BY si.kdkec, wk.nama_kecamatan
--
-- Juga dipakai untuk join kecamatan di MonitoringModel.getDesa():
--   SELECT DISTINCT ... FROM sipw_import WHERE kdkec = ?
-- =============================================================================

CALL add_index_if_missing(
    'sipw_import',
    'idx_sipw_kec_desa',
    'ALTER TABLE sipw_import ADD INDEX idx_sipw_kec_desa(kdkec, kddesa)'
);

-- =============================================================================
-- VERIFIKASI
-- =============================================================================

SELECT 'PATCH 003 SELESAI' AS info;

SELECT '--- INDEX DITAMBAHKAN ---' AS info;
SELECT INDEX_NAME, TABLE_NAME, COLUMN_NAME, SEQ_IN_INDEX, INDEX_TYPE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_NAME IN (
      'idx_act_rate_limit',
      'idx_importlog_created',
      'idx_importlog_user_created',
      'idx_sipw_kec_desa'
  )
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
