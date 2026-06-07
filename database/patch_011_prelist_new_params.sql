-- =============================================================================
-- Patch 011: Tambahkan kolom parameter baru di sipw_import
-- =============================================================================
-- Konteks  : Penambahan parameter perhitungan: jumlah subsektor ST2023, 
--            jumlah kepala keluarga (jml kk), dan jumlah unit usaha 
--            di wilayah kerja statistik (usaha wilkerstat).
--
-- Apply    : mysql -u root bps_jember_se2026 < database/patch_011_prelist_new_params.sql
-- =============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS _patch_011_apply//

CREATE PROCEDURE _patch_011_apply()
BEGIN
    -- Tambah kolom `subsektor_st2023`
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'sipw_import'
          AND COLUMN_NAME  = 'subsektor_st2023'
    ) THEN
        ALTER TABLE `sipw_import`
        ADD COLUMN `subsektor_st2023` INT DEFAULT 0
        COMMENT 'Jumlah subsektor ST2023'
        AFTER `muatan`;
    END IF;

    -- Tambah kolom `jml_kk`
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'sipw_import'
          AND COLUMN_NAME  = 'jml_kk'
    ) THEN
        ALTER TABLE `sipw_import`
        ADD COLUMN `jml_kk` INT DEFAULT 0
        COMMENT 'Jumlah kepala keluarga (dari prelist baru)'
        AFTER `subsektor_st2023`;
    END IF;

    -- Tambah kolom `usaha_wilkerstat`
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'sipw_import'
          AND COLUMN_NAME  = 'usaha_wilkerstat'
    ) THEN
        ALTER TABLE `sipw_import`
        ADD COLUMN `usaha_wilkerstat` INT DEFAULT 0
        COMMENT 'Jumlah unit usaha di wilayah kerja statistik'
        AFTER `jml_kk`;
    END IF;

END//

DELIMITER ;

CALL _patch_011_apply();
DROP PROCEDURE _patch_011_apply;
