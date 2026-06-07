-- =============================================================================
-- Patch 012: Tambahkan kolom detail mitra ke tabel users
-- =============================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS _patch_012_apply//

CREATE PROCEDURE _patch_012_apply()
BEGIN
    -- Tambah kolom `jenis_kelamin`
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'jenis_kelamin') THEN
        ALTER TABLE `users` ADD COLUMN `jenis_kelamin` ENUM('Lk', 'Pr') NULL AFTER `nama_lengkap`;
    END IF;

    -- Tambah kolom `nik`
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'nik') THEN
        ALTER TABLE `users` ADD COLUMN `nik` VARCHAR(16) NULL UNIQUE AFTER `email`;
    END IF;

    -- Tambah kolom `no_hp`
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'no_hp') THEN
        ALTER TABLE `users` ADD COLUMN `no_hp` VARCHAR(20) NULL AFTER `nik`;
    END IF;

    -- Tambah kolom `pendidikan`
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'pendidikan') THEN
        ALTER TABLE `users` ADD COLUMN `pendidikan` VARCHAR(100) NULL AFTER `no_hp`;
    END IF;

    -- Tambah kolom `pekerjaan`
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'pekerjaan') THEN
        ALTER TABLE `users` ADD COLUMN `pekerjaan` VARCHAR(100) NULL AFTER `pendidikan`;
    END IF;

    -- Tambah kolom `alamat_lengkap`
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'alamat_lengkap') THEN
        ALTER TABLE `users` ADD COLUMN `alamat_lengkap` TEXT NULL AFTER `pekerjaan`;
    END IF;

    -- Tambah kolom `posisi_daftar` (Role awal saat mendaftar)
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'posisi_daftar') THEN
        ALTER TABLE `users` ADD COLUMN `posisi_daftar` VARCHAR(100) NULL AFTER `role`;
    END IF;

    -- Tambah kolom `kecamatan_domisili`
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'kecamatan_domisili') THEN
        ALTER TABLE `users` ADD COLUMN `kecamatan_domisili` VARCHAR(100) NULL AFTER `alamat_lengkap`;
    END IF;

    -- Tambah kolom `desa_domisili`
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'desa_domisili') THEN
        ALTER TABLE `users` ADD COLUMN `desa_domisili` VARCHAR(100) NULL AFTER `kecamatan_domisili`;
    END IF;

END//

DELIMITER ;

CALL _patch_012_apply();
DROP PROCEDURE _patch_012_apply;
