-- =============================================================================
-- Patch 013: Tambahkan kolom posisi_tugas ke tabel users
-- =============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS _patch_013_apply//

CREATE PROCEDURE _patch_013_apply()
BEGIN
    -- Tambah kolom `posisi_tugas` (Posisi yang ditetapkan/ditugaskan)
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'posisi_tugas') THEN
        ALTER TABLE `users` ADD COLUMN `posisi_tugas` VARCHAR(100) NULL AFTER `posisi_daftar`;
    END IF;
END//

DELIMITER ;

CALL _patch_013_apply();
DROP PROCEDURE _patch_013_apply;
