-- patch_006_collation_harmonization.sql
-- Harmonize collation across prelist_* and sipw_* tables
-- Original: prelist_* uses utf8mb4_0900_ai_ci (MySQL 8 default)
--           sipw_* uses utf8mb4_unicode_ci (legacy)
-- Both are valid MySQL collations, but mixing them causes:
--   "Illegal mix of collations (utf8mb4_unicode_ci,IMPLICIT) and (utf8mb4_0900_ai_ci,IMPLICIT)"
-- on any JOIN/UPDATE/DELETE between the two table families.
--
-- Target: align prelist_* to utf8mb4_unicode_ci (matches existing sipw_* + users + wilayah_kerja)

ALTER TABLE prelist_kabkota    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE prelist_kecamatan  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE prelist_sls        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE prelist_subsektor  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
