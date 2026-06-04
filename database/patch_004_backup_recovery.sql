-- =============================================================================
-- PATCH 004: BACKUP & RECOVERY INFRASTRUCTURE
-- =============================================================================
-- Database : bps_jember_se2026
-- Target   : Production (idempotent)
--
-- Isi patch:
--   1. Tabel dash_assignment_log — audit trail perubahan assignment
--   2. Tabel dash_rollback_points — snapshot data untuk rollback import
--   3. Index pendukung untuk query backup & audit
-- =============================================================================

USE bps_jember_se2026;

-- =============================================================================
-- 1. CREATE TABLE dash_assignment_log
-- =============================================================================
-- Fungsi: Mencatat setiap perubahan pada sipw_assignment untuk audit trail.
-- Digunakan untuk:
--   - Melacak siapa mengubah assignment dan kapan
--   - Rollback perubahan assignment jika diperlukan
--   - Rekonsiliasi data setelah restore
-- =============================================================================

CREATE TABLE IF NOT EXISTS dash_assignment_log (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    assignment_id   INT NULL                     COMMENT 'ID sipw_assignment (NULL jika dihapus)',
    sipw_id         INT NOT NULL                 COMMENT 'ID sipw_import terkait',
    action          ENUM('INSERT','UPDATE','DELETE','STATUS_CHANGE')
                    NOT NULL                     COMMENT 'Jenis perubahan',
    old_data        JSON NULL                    COMMENT 'Snapshot data sebelum perubahan',
    new_data        JSON NULL                    COMMENT 'Snapshot data setelah perubahan',
    changed_by      INT NOT NULL                 COMMENT 'FK users.id pelaku perubahan',
    ip_address      VARCHAR(45) NULL             COMMENT 'Alamat IP pelaku',
    change_note     VARCHAR(500) NULL            COMMENT 'Catatan tambahan (opsional)',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_asglog_created(created_at),
    INDEX idx_asglog_sipw(sipw_id),
    INDEX idx_asglog_changedby(changed_by),
    INDEX idx_asglog_sipw_created(sipw_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  COMMENT='Audit trail perubahan assignment petugas';

-- =============================================================================
-- 2. CREATE TABLE dash_rollback_points
-- =============================================================================
-- Fungsi: Menyimpan titik rollback untuk operasi import SIPW.
-- Sebelum import batch dijalankan, data sipw_import yang akan terpengaruh
-- disimpan sebagai snapshot JSON. Jika rollback diperlukan, data dapat
-- dikembalikan ke kondisi sebelum import.
--
-- Strategi:
--   - Pre-import: simpan semua baris sipw_import yang akan di-UPSERT (by idfrs/kode wilayah)
--   - Post-rollback: restore data dari old_data, hapus baris baru
--   - Hanya menyimpan 30 hari terakhir (cleanup manual atau cron)
-- =============================================================================

CREATE TABLE IF NOT EXISTS dash_rollback_points (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    batch_id        VARCHAR(40) NOT NULL         COMMENT 'Batch ID dari dash_import_log',
    operation       ENUM('IMPORT_SIPW','BULK_DELETE','BULK_UPDATE')
                    NOT NULL DEFAULT 'IMPORT_SIPW' COMMENT 'Jenis operasi',
    table_name      VARCHAR(100) NOT NULL        COMMENT 'Nama tabel yang di-snapshot',
    row_ids         JSON NOT NULL                COMMENT 'Array ID baris yang terpengaruh',
    old_data        JSON NOT NULL                COMMENT 'Snapshot data sebelum operasi {id: {col: val, ...}}',
    new_data        JSON NULL                    COMMENT 'Snapshot data setelah operasi (untuk referensi)',
    row_count       INT NOT NULL DEFAULT 0       COMMENT 'Jumlah baris yang terpengaruh',
    created_by      INT NOT NULL                 COMMENT 'FK users.id pembuat rollback point',
    ip_address      VARCHAR(45) NULL             COMMENT 'Alamat IP',
    note            VARCHAR(500) NULL            COMMENT 'Catatan',
    is_used         TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Apakah rollback point sudah dipakai?',
    used_at         DATETIME NULL                COMMENT 'Waktu rollback point dipakai',
    used_by         INT NULL                     COMMENT 'User yang melakukan rollback',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE INDEX idx_rbp_batch(batch_id),
    INDEX idx_rbp_created(created_at),
    INDEX idx_rbp_used(is_used, created_at),
    INDEX idx_rbp_table(table_name, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  COMMENT='Titik rollback untuk operasi import dan bulk update';

-- =============================================================================
-- 3. INDEX UNTUK BACKUP QUERY
-- =============================================================================
-- Index berikut mempercepat query identifikasi data yang berubah
-- untuk incremental backup berdasarkan updated_at.
-- =============================================================================

-- 3a. Index untuk sipw_assignment.updated_at (incremental backup)
CALL add_index_if_missing(
    'sipw_assignment',
    'idx_asg_updated',
    'ALTER TABLE sipw_assignment ADD INDEX idx_asg_updated(updated_at)'
);

-- 3b. Index untuk sipw_import.updated_at (incremental backup)
CALL add_index_if_missing(
    'sipw_import',
    'idx_import_updated',
    'ALTER TABLE sipw_import ADD INDEX idx_import_updated(updated_at)'
);

-- =============================================================================
-- VERIFIKASI
-- =============================================================================

SELECT 'PATCH 004 SELESAI' AS info;

SELECT '--- TABEL BARU ---' AS info;
SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('dash_assignment_log', 'dash_rollback_points');

SELECT '--- INDEX DITAMBAHKAN ---' AS info;
SELECT INDEX_NAME, TABLE_NAME, COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('sipw_assignment', 'sipw_import')
  AND INDEX_NAME IN ('idx_asg_updated', 'idx_import_updated');
