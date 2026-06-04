-- =============================================================================
-- PATCH 005: MFD KECAMATAN (Master File Desa level Kecamatan)
-- =============================================================================
-- Source : data/mfd/mfd_kec.xlsx
-- Database : bps_jember_se2026
--
-- Isi:
--   1. Create mfd_kec table
--   2. Import data MFD (31 kecamatan se-Jember)
-- =============================================================================

USE bps_jember_se2026;

-- =============================================================================
-- 1. Create mfd_kec table
-- =============================================================================

CREATE TABLE IF NOT EXISTS mfd_kec (
    id INT AUTO_INCREMENT PRIMARY KEY,
    urutan INT NOT NULL COMMENT 'Urutan dari MFD',
    kode_kecamatan VARCHAR(20) NOT NULL COMMENT 'Kode BPS kecamatan (7 digit)',
    nama_kecamatan VARCHAR(100) NOT NULL COMMENT 'Nama kecamatan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_mfd_kode (kode_kecamatan),
    UNIQUE KEY uk_mfd_urutan (urutan),
    INDEX idx_mfd_nama (nama_kecamatan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. Import data MFD (31 kecamatan)
-- =============================================================================

INSERT INTO mfd_kec (urutan, kode_kecamatan, nama_kecamatan) VALUES
(1,  '3509010', 'Kencong'),
(2,  '3509020', 'Gumukmas'),
(3,  '3509030', 'Puger'),
(4,  '3509040', 'Wuluhan'),
(5,  '3509050', 'Ambulu'),
(6,  '3509060', 'Tempurejo'),
(7,  '3509070', 'Silo'),
(8,  '3509080', 'Mayang'),
(9,  '3509090', 'Mumbulsari'),
(10, '3509100', 'Jenggawah'),
(11, '3509110', 'Ajung'),
(12, '3509120', 'Rambipuji'),
(13, '3509130', 'Balung'),
(14, '3509140', 'Umbulsari'),
(15, '3509150', 'Semboro'),
(16, '3509160', 'Jombang'),
(17, '3509170', 'Sumberbaru'),
(18, '3509180', 'Tanggul'),
(19, '3509190', 'Bangsalsari'),
(20, '3509200', 'Panti'),
(21, '3509210', 'Sukorambi'),
(22, '3509220', 'Arjasa'),
(23, '3509230', 'Pakusari'),
(24, '3509240', 'Kalisat'),
(25, '3509250', 'Ledokombo'),
(26, '3509260', 'Sumberjambe'),
(27, '3509270', 'Sukowono'),
(28, '3509280', 'Jelbuk'),
(29, '3509710', 'Kaliwates'),
(30, '3509720', 'Sumbersari'),
(31, '3509730', 'Patrang')
ON DUPLICATE KEY UPDATE nama_kecamatan = VALUES(nama_kecamatan);

-- =============================================================================
-- VERIFIKASI
-- =============================================================================

SELECT 'PATCH 005 SELESAI' AS info;
SELECT * FROM mfd_kec ORDER BY urutan;
