-- patch_014_fasih_assignment.sql
-- Tambah 8 kolom FASIH assignment (prelist_sls) + 9 kolom assignment (sipw_import)
-- Idempotent, hanya menambah kolom yang belum ada

DELIMITER //

CREATE PROCEDURE add_fasih_columns_if_not_exists()
BEGIN
    -- Tambah ke prelist_sls jika belum ada
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'prelist_sls' AND column_name = 'total_fasih') THEN
        ALTER TABLE prelist_sls ADD COLUMN total_fasih INT(11) UNSIGNED DEFAULT 0;
        ALTER TABLE prelist_sls ADD COLUMN fasih_kk INT(11) UNSIGNED DEFAULT 0;
        ALTER TABLE prelist_sls ADD COLUMN fasih_umk INT(11) UNSIGNED DEFAULT 0;
        ALTER TABLE prelist_sls ADD COLUMN fasih_um INT(11) UNSIGNED DEFAULT 0;
        ALTER TABLE prelist_sls ADD COLUMN fasih_ub INT(11) UNSIGNED DEFAULT 0;
        ALTER TABLE prelist_sls ADD COLUMN fasih_bangunan INT(11) UNSIGNED DEFAULT 0;
        ALTER TABLE prelist_sls ADD COLUMN dominan VARCHAR(7) DEFAULT '';
        ALTER TABLE prelist_sls ADD COLUMN flag_open_pbi TINYINT(1) DEFAULT 0;
        ALTER TABLE prelist_sls ADD COLUMN kk_open_pbi INT(11) UNSIGNED DEFAULT 0;
    END IF;

    -- Tambah ke sipw_import jika belum ada
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'sipw_import' AND column_name = 'total_fasih') THEN
        ALTER TABLE sipw_import ADD COLUMN total_fasih INT(11) UNSIGNED DEFAULT 0;
        ALTER TABLE sipw_import ADD COLUMN fasih_kk INT(11) UNSIGNED DEFAULT 0;
        ALTER TABLE sipw_import ADD COLUMN fasih_umk INT(11) UNSIGNED DEFAULT 0;
        ALTER TABLE sipw_import ADD COLUMN fasih_um INT(11) UNSIGNED DEFAULT 0;
        ALTER TABLE sipw_import ADD COLUMN fasih_ub INT(11) UNSIGNED DEFAULT 0;
        ALTER TABLE sipw_import ADD COLUMN fasih_bangunan INT(11) UNSIGNED DEFAULT 0;
        ALTER TABLE sipw_import ADD COLUMN dominan VARCHAR(7) DEFAULT '';
        ALTER TABLE sipw_import ADD COLUMN flag_open_pbi TINYINT(1) DEFAULT 0;
        ALTER TABLE sipw_import ADD COLUMN kk_open_pbi INT(11) UNSIGNED DEFAULT 0;
    END IF;
END //

DELIMITER ;

-- Jalankan stored procedure
CALL add_fasih_columns_if_not_exists();
DROP PROCEDURE IF EXISTS add_fasih_columns_if_not_exists;
