-- =============================================================================
-- PATCH 001: DASHBOARD SE2026 — BASE TABLES & ROLE
-- =============================================================================
-- Database : bps_jember_se2026
-- Target   : Production (aman dijalankan berulang / idempotent)
-- 
-- Isi patch:
--   1. Tambah role 'task_force' ke ENUM users.role
--   2. CREATE TABLE sipw_import — master data SLS dari SIPW
--   3. CREATE TABLE sipw_assignment — assignment petugas ke SLS
--   4. CREATE TABLE dash_monitoring_summary — agregat progress per kecamatan
--   5. Index pendukung via add_index_if_missing
-- =============================================================================

USE bps_jember_se2026;

-- =============================================================================
-- 1. TAMBAH ROLE task_force PADA users (AMAN & IDEMPOTENT)
-- =============================================================================
-- Strategi: cek dulu apakah 'task_force' sudah ada di ENUM via LOCATE.
-- Jika belum, jalankan ALTER TABLE MODIFY.
-- Tidak ada data yang dihapus/diubah.
-- =============================================================================

SET @has_task_force = (
    SELECT LOCATE("'task_force'", COLUMN_TYPE) > 0
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'role'
);

SET @sql_add_role = IF(@has_task_force = 0,
    "ALTER TABLE users
     MODIFY role ENUM(
         'admin','operator','pegawai','mitra','pml','pcl','task_force'
     ) NOT NULL DEFAULT 'mitra'",
    "SELECT 'SKIP — task_force sudah ada di ENUM users.role' AS info"
);

PREPARE stmt_add_role FROM @sql_add_role;
EXECUTE stmt_add_role;
DEALLOCATE PREPARE stmt_add_role;

-- =============================================================================
-- 2. CREATE TABLE sipw_import — MASTER DATA SLS DARI SIPW
-- =============================================================================
-- Fungsi: Menyimpan hasil import Excel SIPW (daftar SLS/blok sensus).
-- Data mencakup kode wilayah BPS, nama wilayah, dan muatan per SLS.
-- 
-- Catatan:
--   - Tidak ada FK ke wilayah_kerja karena data SIPW berasal dari
--     sistem eksternal dengan format kode yang mungkin berbeda.
--   - Kolom muatan (kk, btt, dll) dipakai untuk agregasi dashboard.
-- =============================================================================

CREATE TABLE IF NOT EXISTS sipw_import (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    idfrs           BIGINT NULL          COMMENT 'ID FRS dari sistem SIPW',
    semester        VARCHAR(20) NULL      COMMENT 'Semester pendataan',
    idsubsls        VARCHAR(50) NULL      COMMENT 'ID Sub-SLS',

    -- Kode wilayah BPS
    kdprov          VARCHAR(5) NULL       COMMENT 'Kode provinsi BPS',
    kdkab           VARCHAR(5) NULL       COMMENT 'Kode kabupaten BPS',
    kdkec           VARCHAR(10) NULL      COMMENT 'Kode kecamatan BPS',
    kddesa          VARCHAR(15) NULL      COMMENT 'Kode desa BPS',
    kdsls           VARCHAR(20) NULL      COMMENT 'Kode SLS',

    -- Nama wilayah
    nmprov          VARCHAR(100) NULL     COMMENT 'Nama provinsi',
    nmkab           VARCHAR(100) NULL     COMMENT 'Nama kabupaten',
    nmkec           VARCHAR(100) NULL     COMMENT 'Nama kecamatan',
    nmdesa          VARCHAR(100) NULL     COMMENT 'Nama desa',

    -- Detail SLS
    nmsls           VARCHAR(255) NULL     COMMENT 'Nama SLS',
    nama_ketua      VARCHAR(255) NULL     COMMENT 'Nama ketua SLS/RW',

    -- Muatan (jumlah)
    kk              INT DEFAULT 0         COMMENT 'Jumlah Kepala Keluarga',
    btt             INT DEFAULT 0         COMMENT 'Bangunan Tempat Tinggal',
    bttk            INT DEFAULT 0         COMMENT 'BTT Khusus',
    bku             INT DEFAULT 0         COMMENT 'Bangunan & tempat kegiatan',
    bbtt_nonusaha   INT DEFAULT 0         COMMENT 'BTT Non Usaha',
    usaha           INT DEFAULT 0         COMMENT 'Jumlah unit usaha',
    muatan          INT DEFAULT 0         COMMENT 'Total muatan SLS',

    -- Metadata
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Index untuk query per wilayah & lookup
    INDEX idx_sipw_kdkec(kdkec),
    INDEX idx_sipw_kddesa(kddesa),
    INDEX idx_sipw_idsubsls(idsubsls)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  COMMENT='Master data SLS hasil import SIPW untuk dashboard';

-- =============================================================================
-- 3. CREATE TABLE sipw_assignment — ASSIGNMENT PETUGAS KE SLS
-- =============================================================================
-- Fungsi: Menugaskan petugas PCL (pencacah), PML (pengawas), dan Task Force
-- ke masing-masing SLS dari hasil import SIPW.
--
-- Relasi:
--   sipw_assignment.sipw_id       ──FK──> sipw_import.id (CASCADE)
--   sipw_assignment.pencacah_id    ──FK──> users.id (SET NULL)
--   sipw_assignment.pengawas_id    ──FK──> users.id (SET NULL)
--   sipw_assignment.task_force_id  ──FK──> users.id (SET NULL)
--
-- Catatan:
--   - ON DELETE SET NULL: jika user dihapus, data assignment tetap ada
--   - Status: belum → proses → selesai
-- =============================================================================

CREATE TABLE IF NOT EXISTS sipw_assignment (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    sipw_id         INT NOT NULL          COMMENT 'FK ke sipw_import.id',

    pencacah_id     INT NULL              COMMENT 'FK ke users.id (role PCL)',
    pengawas_id     INT NULL              COMMENT 'FK ke users.id (role PML)',
    task_force_id   INT NULL              COMMENT 'FK ke users.id (role task_force)',

    status          ENUM('belum','proses','selesai')
                    NOT NULL DEFAULT 'belum' COMMENT 'Status pendataan SLS',

    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign Keys
    CONSTRAINT fk_sipw_assignment_sipw
        FOREIGN KEY (sipw_id) REFERENCES sipw_import(id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_sipw_assignment_pencacah
        FOREIGN KEY (pencacah_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_sipw_assignment_pengawas
        FOREIGN KEY (pengawas_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_sipw_assignment_taskforce
        FOREIGN KEY (task_force_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,

    -- Index untuk performa query per petugas & status
    INDEX idx_sipw_asg_pencacah(pencacah_id),
    INDEX idx_sipw_asg_pengawas(pengawas_id),
    INDEX idx_sipw_asg_taskforce(task_force_id),
    INDEX idx_sipw_asg_status(status),
    INDEX idx_sipw_asg_sipw_status(sipw_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  COMMENT='Assignment petugas PCL/PML/Task Force ke SLS';

-- =============================================================================
-- 4. CREATE TABLE dash_monitoring_summary — AGREGAT PROGRESS
-- =============================================================================
-- Fungsi: Menyimpan pre-kalkulasi progress per kecamatan per periode.
-- Mempercepat load dashboard tanpa query agregasi besar setiap kali.
--
-- Update tabel ini via cron/job scheduler (tidak realtime).
-- Data di-update periodik (setiap 1-6 jam) atau via trigger.
--
-- Relasi:
--   dash_monitoring_summary.wilayah_id ──FK──> wilayah_kerja.id
-- =============================================================================

CREATE TABLE IF NOT EXISTS dash_monitoring_summary (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    wilayah_id        INT NOT NULL        COMMENT 'FK ke wilayah_kerja.id',
    periode           VARCHAR(10) NOT NULL COMMENT 'Periode dalam format YYYY-MM',

    -- Sumber: sipw_import + sipw_assignment
    total_sls         INT DEFAULT 0       COMMENT 'Jumlah SLS dari sipw_import',
    assigned_sls      INT DEFAULT 0       COMMENT 'SLS yang sudah di-assign',
    progress_sls      INT DEFAULT 0       COMMENT 'SLS status proses',
    completed_sls     INT DEFAULT 0       COMMENT 'SLS status selesai',

    -- Sumber: monitoring_progress
    total_muatan      INT DEFAULT 0       COMMENT 'Total muatan dari sipw_import',
    realisasi_muatan  INT DEFAULT 0       COMMENT 'Realisasi dari monitoring_progress',
    persentase        DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Persentase realisasi',

    -- Metadata
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Unik per wilayah + periode
    UNIQUE KEY uq_summary_wil_periode(wilayah_id, periode),

    CONSTRAINT fk_summary_wilayah
        FOREIGN KEY (wilayah_id) REFERENCES wilayah_kerja(id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX idx_summary_periode(periode),
    INDEX idx_summary_persentase(persentase)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  COMMENT='Agregat progress per kecamatan untuk dashboard';

-- =============================================================================
-- 5. INDEX TAMBAHAN UNTUK PERFORMA DASHBOARD
-- =============================================================================
-- Index berikut memanfaatkan stored procedure add_index_if_missing yang
-- sudah ada di database. Hanya membuat index jika belum ada.
-- =============================================================================

-- 5a. Index komposit untuk query monitoring harian (filter wilayah + tanggal)
CALL add_index_if_missing(
    'monitoring_progress',
    'idx_monitoring_wil_tgl',
    'ALTER TABLE monitoring_progress ADD INDEX idx_monitoring_wil_tgl(wilayah_id, tanggal)'
);

-- 5b. Index untuk query user berdasarkan role + status (filter petugas aktif)
CALL add_index_if_missing(
    'users',
    'idx_users_role_status',
    'ALTER TABLE users ADD INDEX idx_users_role_status(role, status_akun)'
);

-- 5c. Index untuk query alokasi per user
CALL add_index_if_missing(
    'alokasi_petugas',
    'idx_alokasi_wilayah_id',
    'ALTER TABLE alokasi_petugas ADD INDEX idx_alokasi_wilayah_id(wilayah_id)'
);

-- 5d. Index untuk query surat tugas aktif per petugas
CALL add_index_if_missing(
    'surat_tugas',
    'idx_st_petugas_status',
    'ALTER TABLE surat_tugas ADD INDEX idx_st_petugas_status(petugas_id, status)'
);

-- =============================================================================
-- VERIFIKASI
-- =============================================================================
-- Tampilkan ringkasan setelah patch dijalankan

SELECT 'PATCH 001 SELESAI' AS info;

SELECT '--- TABEL BARU ---' AS info;
SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('sipw_import','sipw_assignment','dash_monitoring_summary');

SELECT '--- ENUM users.role ---' AS info;
SELECT COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'users'
  AND COLUMN_NAME = 'role';

SELECT '--- INDEX BARU ---' AS info;
SELECT INDEX_NAME, TABLE_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND INDEX_NAME IN (
      'idx_monitoring_wil_tgl',
      'idx_users_role_status',
      'idx_alokasi_wilayah_id',
      'idx_st_petugas_status'
  )
GROUP BY INDEX_NAME, TABLE_NAME;
