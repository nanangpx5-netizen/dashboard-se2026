-- patch_009_pegawai_kecamatan.sql
-- Tambah kolom kecamatan_tugas (1:1) untuk filter akses dashboard per role pegawai
-- 
-- Tujuan:
--   Setiap akun pegawai dikaitkan dengan 1 kecamatan spesifik (primary assignment)
--   Dashboard monitoring/assignment/workload filter data sesuai kecamatan tsb
--
-- Idempotent via information_schema (MySQL 8.0)
-- Index dibuat oleh apply_patch_009.php (idempotent via information_schema)

DELIMITER //
DROP PROCEDURE IF EXISTS apply_patch_009 //
CREATE PROCEDURE apply_patch_009()
BEGIN
    -- 1) kecamatan_tugas: kd_kec yang menjadi primary assignment
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_schema = DATABASE() AND table_name = 'users' 
                   AND column_name = 'kecamatan_tugas') THEN
        ALTER TABLE users ADD COLUMN kecamatan_tugas VARCHAR(7) NULL 
            COMMENT 'kd_kec primary assignment — filter akses dashboard untuk role pegawai' 
            AFTER kecamatan_bertugas;
    END IF;
END //
DELIMITER ;

CALL apply_patch_009();
DROP PROCEDURE apply_patch_009;
