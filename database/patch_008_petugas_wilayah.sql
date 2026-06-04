-- patch_008_petugas_wilayah.sql
-- Tambah tabel petugas_wilayah: alokasi formal per-kecamatan untuk setiap user.
-- Memisahkan dari users.kecamatan_bertugas (CSV string) agar lebih terstruktur.
--
-- Temuan (Jun 2026):
--   - 1 user `pegawai` (pegawai3509) tanpa kecamatan_bertugas
--   - 99,95% mitra tanpa kecamatan_bertugas
--   - 5 pegawai baru (R3.1) akan punya 6-7 kecamatan each
--   - Perlu relasi many-to-many formal users ↔ kecamatan
--
-- Idempotent: IF NOT EXISTS untuk CREATE TABLE (MySQL 8.0+).

CREATE TABLE IF NOT EXISTS petugas_wilayah (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL                                COMMENT 'FK users.id',
    kd_kab          CHAR(4) NOT NULL                                      COMMENT 'FK prelist_kabkota.kd_kab',
    kd_kec          CHAR(7) NOT NULL                                      COMMENT 'FK prelist_kecamatan.kd_kec',
    role_snapshot   VARCHAR(20) NOT NULL                                  COMMENT 'role user saat alokasi (untuk audit)',
    aktif           TINYINT(1) NOT NULL DEFAULT 1                         COMMENT '1 = aktif, 0 = sudah dilepas',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_petugas_wilayah (user_id, kd_kab, kd_kec),
    KEY idx_petugas_wilayah_kec (kd_kab, kd_kec),
    KEY idx_petugas_wilayah_role (role_snapshot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Alokasi formal per-kecamatan untuk user (pegawai/operator)';

-- Foreign keys (optional — tambahkan jika tabel users & prelist_kecamatan reliable)
-- ALTER TABLE petugas_wilayah
--     ADD CONSTRAINT fk_petugas_wilayah_user
--         FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
-- ALTER TABLE petugas_wilayah
--     ADD CONSTRAINT fk_petugas_wilayah_kec
--         FOREIGN KEY (kd_kab, kd_kec) REFERENCES prelist_kecamatan(kd_kab, kd_kec) ON DELETE RESTRICT;
