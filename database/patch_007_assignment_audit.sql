-- patch_007_assignment_audit.sql
-- Tambah kolom audit trail & progress tracking ke sipw_assignment.
-- Temuan Laporan Analisis Pegawai Organik:
--   - Tidak ada created_by/updated_by → tidak bisa lacak siapa yang assign
--   - Tidak ada progress_pct → monitoring tidak bisa ukur penyelesaian
--   - Tidak ada tanggal_mulai/tanggal_selesai → tidak bisa rekap durasi
--
-- Idempotent: cek information_schema.columns sebelum ALTER (MySQL 8.0).
-- Indexes dibuat oleh apply_patch_007.php (idempotent via information_schema).

DELIMITER //
DROP PROCEDURE IF EXISTS apply_patch_007 //
CREATE PROCEDURE apply_patch_007()
BEGIN
    -- 1) created_by
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_schema = DATABASE() AND table_name = 'sipw_assignment' AND column_name = 'created_by') THEN
        ALTER TABLE sipw_assignment ADD COLUMN created_by INT UNSIGNED NULL COMMENT 'FK users.id — yang membuat assignment';
    END IF;

    -- 2) updated_by
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_schema = DATABASE() AND table_name = 'sipw_assignment' AND column_name = 'updated_by') THEN
        ALTER TABLE sipw_assignment ADD COLUMN updated_by INT UNSIGNED NULL COMMENT 'FK users.id — yang update terakhir';
    END IF;

    -- 3) created_via
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_schema = DATABASE() AND table_name = 'sipw_assignment' AND column_name = 'created_via') THEN
        ALTER TABLE sipw_assignment ADD COLUMN created_via ENUM('web','cli','import','restore') NOT NULL DEFAULT 'web' COMMENT 'asal assignment';
    END IF;

    -- 4) progress_pct
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_schema = DATABASE() AND table_name = 'sipw_assignment' AND column_name = 'progress_pct') THEN
        ALTER TABLE sipw_assignment ADD COLUMN progress_pct TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-100 persen penyelesaian';
    END IF;

    -- 5) tanggal_mulai
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_schema = DATABASE() AND table_name = 'sipw_assignment' AND column_name = 'tanggal_mulai') THEN
        ALTER TABLE sipw_assignment ADD COLUMN tanggal_mulai DATE NULL COMMENT 'tanggal mulai penugasan';
    END IF;

    -- 6) tanggal_selesai
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_schema = DATABASE() AND table_name = 'sipw_assignment' AND column_name = 'tanggal_selesai') THEN
        ALTER TABLE sipw_assignment ADD COLUMN tanggal_selesai DATE NULL COMMENT 'tanggal selesai penugasan';
    END IF;

    -- 7) catatan
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_schema = DATABASE() AND table_name = 'sipw_assignment' AND column_name = 'catatan') THEN
        ALTER TABLE sipw_assignment ADD COLUMN catatan TEXT NULL COMMENT 'catatan petugas/operator';
    END IF;
END //
DELIMITER ;

CALL apply_patch_007();
DROP PROCEDURE apply_patch_007;
