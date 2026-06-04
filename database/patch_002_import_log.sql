-- =============================================================================
-- PATCH 002: IMPORT LOG & UNIQUE CONSTRAINT
-- =============================================================================
-- Database : bps_jember_se2026
-- Target   : Production (idempotent)
--
-- Isi patch:
--   1. Tabel dash_import_log — riwayat import file
--   2. UNIQUE KEY sipw_import.idfrs — untuk UPSERT (ON DUPLICATE KEY UPDATE)
-- =============================================================================

USE bps_jember_se2026;

-- =============================================================================
-- 1. CREATE TABLE dash_import_log
-- =============================================================================
-- Fungsi: Mencatat setiap aktivitas import SIPW untuk audit trail.
-- =============================================================================

CREATE TABLE IF NOT EXISTS dash_import_log (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    batch_id        VARCHAR(40) NOT NULL         COMMENT 'UUID unik per batch import',
    nama_file       VARCHAR(255) NOT NULL        COMMENT 'Nama file asli',
    ukuran_file     INT DEFAULT 0                COMMENT 'Ukuran file dalam bytes',
    total_baris     INT DEFAULT 0                COMMENT 'Jumlah baris data di file',
    baris_berhasil  INT DEFAULT 0                COMMENT 'Baris berhasil di-insert',
    baris_diupdate  INT DEFAULT 0                COMMENT 'Baris yang di-update (duplikat)',
    baris_gagal     INT DEFAULT 0                COMMENT 'Baris gagal di-import',
    status          ENUM('processing','success','partial','failed')
                    NOT NULL DEFAULT 'processing' COMMENT 'Status import',
    user_id         INT NULL                     COMMENT 'FK users.id pelaku import',
    waktu_mulai     DATETIME NULL                COMMENT 'Waktu mulai proses',
    waktu_selesai   DATETIME NULL                COMMENT 'Waktu selesai proses',
    error_message   TEXT NULL                    COMMENT 'Pesan error jika gagal/partial',
    ip_address      VARCHAR(45) NULL             COMMENT 'Alamat IP pelaku',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_log_batch(batch_id),
    INDEX idx_log_status(status),
    INDEX idx_log_user(user_id),
    INDEX idx_log_waktu(waktu_mulai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  COMMENT='Log import file SIPW ke tabel sipw_import';

-- =============================================================================
-- 2. UNIQUE KEY ON sipw_import.idfrs
-- =============================================================================
-- Fungsi: Memungkinkan INSERT ... ON DUPLICATE KEY UPDATE (UPSERT).
-- idfrs adalah identitas unik SLS dari sistem SIPW.
-- Untuk baris tanpa idfrs, dibuatkan unique key komposit.
-- =============================================================================

SET @has_unique_idfrs = (
    SELECT COUNT(*) > 0
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sipw_import'
      AND INDEX_NAME = 'uq_sipw_idfrs'
      AND NON_UNIQUE = 0
);

SET @sql_uq_idfrs = IF(@has_unique_idfrs = 0,
    'ALTER TABLE sipw_import
     ADD UNIQUE INDEX uq_sipw_idfrs (idfrs)',
    "SELECT 'SKIP — uq_sipw_idfrs sudah ada' AS info"
);

PREPARE stmt_uq_idfrs FROM @sql_uq_idfrs;
EXECUTE stmt_uq_idfrs;
DEALLOCATE PREPARE stmt_uq_idfrs;

-- =============================================================================
-- 3. UNIQUE KEY KOMPOSIT (untuk baris tanpa idfrs)
-- =============================================================================

SET @has_uq_sls = (
    SELECT COUNT(*) > 0
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sipw_import'
      AND INDEX_NAME = 'uq_sipw_sls'
      AND NON_UNIQUE = 0
);

SET @sql_uq_sls = IF(@has_uq_sls = 0,
    'ALTER TABLE sipw_import
     ADD UNIQUE INDEX uq_sipw_sls (kdkec, kddesa, kdsls, idsubsls)',
    "SELECT 'SKIP — uq_sipw_sls sudah ada' AS info"
);

PREPARE stmt_uq_sls FROM @sql_uq_sls;
EXECUTE stmt_uq_sls;
DEALLOCATE PREPARE stmt_uq_sls;

-- =============================================================================
-- VERIFIKASI
-- =============================================================================

SELECT 'PATCH 002 SELESAI' AS info;

SELECT '--- TABEL BARU ---' AS info;
SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'dash_import_log';

SELECT '--- UNIQUE INDEX ---' AS info;
SELECT INDEX_NAME, COLUMN_NAME, INDEX_TYPE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'sipw_import'
  AND INDEX_NAME IN ('uq_sipw_idfrs', 'uq_sipw_sls')
GROUP BY INDEX_NAME, COLUMN_NAME, INDEX_TYPE;
