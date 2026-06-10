-- patch_015_lk_pairing.sql
-- Tabel lk_petugas (master PPL/PML dari LK Pairing)
-- Tabel lk_pairing (hasil pairing PPL↔SLS↔PML)
-- Kolom baru wilayah_kerja.aktual_ppl_lk + aktual_pml_lk
-- Idempotent via information_schema

DELIMITER //

CREATE PROCEDURE _patch_015_apply()
BEGIN
  DECLARE _tbl_count INT;

  -- Tabel lk_petugas
  SELECT COUNT(*) INTO _tbl_count FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='lk_petugas';
  IF _tbl_count = 0 THEN
    CREATE TABLE lk_petugas (
      id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      kode_lk     VARCHAR(10)  NOT NULL COMMENT 'PPL1..PPL2148 / PML1..PML280',
      tipe        ENUM('PPL','PML') NOT NULL,
      nm_kec      VARCHAR(100) NOT NULL,
      kd_kec      CHAR(7)      NULL COMMENT 'Resolved 7-digit dari nm_kec',
      nama        VARCHAR(200) NOT NULL,
      email       VARCHAR(200) NOT NULL,
      user_id     INT          NULL COMMENT 'FK ke users.id (join via email)',
      INDEX idx_kode  (kode_lk),
      INDEX idx_email (email),
      INDEX idx_kec   (kd_kec),
      INDEX idx_uid   (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  END IF;

  -- Tabel lk_pairing
  SELECT COUNT(*) INTO _tbl_count FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='lk_pairing';
  IF _tbl_count = 0 THEN
    CREATE TABLE lk_pairing (
      id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      idsubsls      VARCHAR(16)  NOT NULL COMMENT 'FK ke sipw_import.idsubsls',
      sipw_id       INT          NULL     COMMENT 'FK ke sipw_import.id',
      kode_ppl      VARCHAR(10)  NULL,
      ppl_id        INT          NULL     COMMENT 'FK ke users.id via lk_petugas',
      kode_pml      VARCHAR(10)  NULL,
      pml_id        INT          NULL     COMMENT 'FK ke users.id via lk_petugas',
      muatan        INT          NOT NULL DEFAULT 0,
      muatan_kel    INT          NOT NULL DEFAULT 0,
      muatan_st2023 INT          NOT NULL DEFAULT 0,
      muatan_bang   INT          NOT NULL DEFAULT 0,
      paired_at     TIMESTAMP    NULL,
      imported_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_idsubsls (idsubsls),
      INDEX idx_sipw   (sipw_id),
      INDEX idx_ppl    (kode_ppl),
      INDEX idx_pml    (kode_pml),
      INDEX idx_ppl_id (ppl_id),
      INDEX idx_pml_id (pml_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  END IF;

  -- Kolom wilayah_kerja.aktual_ppl_lk
  SELECT COUNT(*) INTO _tbl_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='wilayah_kerja'
    AND COLUMN_NAME='aktual_ppl_lk';
  IF _tbl_count = 0 THEN
    ALTER TABLE wilayah_kerja
      ADD COLUMN aktual_ppl_lk  SMALLINT DEFAULT 0 COMMENT 'Jumlah PPL aktual dari file LK Pairing';
  END IF;

  SELECT COUNT(*) INTO _tbl_count FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='wilayah_kerja'
    AND COLUMN_NAME='aktual_pml_lk';
  IF _tbl_count = 0 THEN
    ALTER TABLE wilayah_kerja
      ADD COLUMN aktual_pml_lk  SMALLINT DEFAULT 0 COMMENT 'Jumlah PML aktual dari file LK Pairing';
  END IF;

END //

DELIMITER ;

CALL _patch_015_apply();
DROP PROCEDURE IF EXISTS _patch_015_apply;
