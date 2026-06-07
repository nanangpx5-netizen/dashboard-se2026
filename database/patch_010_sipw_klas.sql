-- =============================================================================
-- Patch 010: Tambahkan kolom `klas` di sipw_import
-- =============================================================================
-- Konteks  : `src/Controllers/DashboardController.php` (line 95-96, 139-140)
--            merefer kolom `sipw_import.klas` untuk klasifikasi urban/rural
--            tetapi kolom ini tidak pernah dibuat di patch 001-009 maupun
--            disertakan di dump hosting. Akibatnya dashboard error 500
--            (PDOException "Unknown column 'klas' in 'field list'") saat
--            cache stats expired & query ulang dijalankan.
--
-- Solusi   : Tambah kolom `klas TINYINT NULL` (1=urban, 2=rural, NULL=unknown).
--            Idempotent via INFORMATION_SCHEMA check + stored procedure
--            (pattern yg dipakai juga di patch_007). MySQL 8.0+ compatible.
--
-- Apply    : mysql -u root bps_jember_se2026 < database/patch_010_sipw_klas.sql
-- =============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS _patch_010_apply//

CREATE PROCEDURE _patch_010_apply()
BEGIN
    -- Tambah kolom `klas` bila belum ada
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'sipw_import'
          AND COLUMN_NAME  = 'klas'
    ) THEN
        ALTER TABLE `sipw_import`
        ADD COLUMN `klas` TINYINT NULL DEFAULT NULL
        COMMENT 'Klasifikasi SLS: 1=urban (perkotaan), 2=rural (perdesaan), NULL=tidak diketahui'
        AFTER `muatan`;
    END IF;

    -- Index untuk filter klas (sering dipakai di chart urban/rural)
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'sipw_import'
          AND INDEX_NAME   = 'idx_sipw_klas'
    ) THEN
        CREATE INDEX `idx_sipw_klas` ON `sipw_import` (`klas`);
    END IF;
END//

DELIMITER ;

CALL _patch_010_apply();
DROP PROCEDURE _patch_010_apply;

-- Verifikasi
SELECT
    'patch_010 applied' AS info,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='sipw_import' AND COLUMN_NAME='klas') AS klas_column_exists,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='sipw_import' AND INDEX_NAME='idx_sipw_klas') AS klas_index_exists;
