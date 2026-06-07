-- patch_010_pml_reports.sql
-- Tambah tabel pml_reports untuk menyimpan riwayat laporan PML.
-- Idempotent: gunakan information_schema check via PHP script.
-- 
-- Apply via: php scripts/apply_patch_010.php [--execute]
-- Atau manual: mysql -u user -p db_name < patch_010_pml_reports.sql
--   (perintah di bawah akan error jika tabel sudah ada — aman di-ignore)

CREATE TABLE IF NOT EXISTS pml_reports (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pml_id          INT NOT NULL COMMENT 'FK → users.id (role=pml)',
    periode         VARCHAR(7) NOT NULL COMMENT 'Periode laporan YYYY-MM',
    total_assigned  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total SLS dialokasikan saat laporan',
    total_selesai   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'SLS selesai dikerjakan',
    total_proses    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'SLS dalam proses',
    total_belum     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'SLS belum dikerjakan',
    catatan         TEXT NULL COMMENT 'Catatan dari PML',
    submitted_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pengiriman',
    ip_address      VARCHAR(45) NULL,
    INDEX idx_pml_report_pml (pml_id, periode),
    INDEX idx_pml_report_periode (periode),
    CONSTRAINT fk_pml_report_user FOREIGN KEY (pml_id)
        REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
