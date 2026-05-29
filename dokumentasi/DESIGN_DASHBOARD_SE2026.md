# DESAIN FINAL DASHBOARD SE2026 JEMBER

## A. Ringkasan Arsitektur

```
┌─────────────────────────────────────────────────────────────────────┐
│                        LARAGON (localhost)                          │
│                                                                     │
│  ┌─────────────────────────────┐   ┌──────────────────────────────┐ │
│  │  SE2026 EXISTING            │   │  DASHBOARD SE2026 (BARU)      │ │
│  │  /se2026-jember/            │   │  /dashboard-se2026/           │ │
│  │                             │   │                              │ │
│  │  - Operasional rekrutmen   │   │  - Import SIPW dari Excel    │ │
│  │  - Pelatihan PCL/PML       │   │  - Assignment PCL/PML/TF     │ │
│  │  - Monitoring lapangan     │   │  - Dashboard realtime        │ │
│  │  - Surat-menyurat          │   │  - Progress monitoring       │ │
│  │  - Manajemen pengumuman    │   │  - Export laporan            │ │
│  └──────────┬──────────────────┘   └──────────┬───────────────────┘ │
│             │                                  │                    │
│             └──────────┬───────────┬───────────┘                    │
│                        │           │                                │
│                        ▼           ▼                                │
│             ┌─────────────────────────────┐                        │
│             │   MySQL 8.0                 │                        │
│             │   Database: bps_jember_se2026│                        │
│             │                             │                        │
│             │   ┌─────────────────────┐   │                        │
│             │   │  Tabel Existing (36) │   │  ← Dashboard READ-ONLY │
│             │   │  Tabel Dashboard (5) │   │  ← Dashboard R/W      │
│             │   └─────────────────────┘   │                        │
│             └─────────────────────────────┘                        │
└─────────────────────────────────────────────────────────────────────┘
```

**Prinsip kunci:**
- Dashboard aplikasi **terpisah** (direktori sendiri, codebase sendiri)
- Database **shared** (`bps_jember_se2026`)
- Dashboard **READ-ONLY** ke 36 tabel existing
- Dashboard **R/W** hanya ke tabel milik sendiri (prefix `sipw_` / `dash_`)
- Semua perubahan existing via **database patch incremental**

---

## B. ERD Final Dashboard

```
                          ┌───────────────┐
                          │    users      │◄──── 25 FK dari tabel existing
                          │  (existing)   │      (dashboard: READ ONLY)
                          └───────┬───────┘
                                  │
                    ┌─────────────┼─────────────────────┐
                    │             │                     │
              ┌─────┴──────┐ ┌───┴───────┐     ┌───────┴──────┐
              │ wilayah_   │ │ existing  │     │  sipw_      │
              │ kerja      │ │ tables... │     │ assignment  │
              │ (existing) │ │           │     │  (BARU)     │
              └─────┬──────┘ └───────────┘     └──────┬───────┘
                    │                                 │
                    │                         ┌───────┴──────┐
                    │                         │  sipw_import │
                    │                         │   (BARU)     │
                    │                         └───────┬──────┘
                    │                                 │
              ┌─────┴─────────────────────────────────┴──────┐
              │          dash_monitoring_summary             │
              │               (BARU)                         │
              └──────────────────────────────────────────────┘

TABEL BARU:
  sipw_import ──────1:N──> sipw_assignment
  users      ──────1:N──> sipw_assignment (pencacah_id)
  users      ──────1:N──> sipw_assignment (pengawas_id)
  users      ──────1:N──> sipw_assignment (task_force_id)
  wilayah_kerja ────1:N──> dash_monitoring_summary
  sipw_assignment ──1:N──> dash_monitoring_summary (opsional, via view)
```

---

## C. Tabel Existing yang Dipakai Dashboard

### C.1 Dibaca Langsung (SELECT only)

| # | Tabel | Kegunaan Dashboard | Kolom Kunci |
|---|-------|-------------------|-------------|
| 1 | `users` | Data petugas (PCL, PML, Task Force) | id, username, role, status_akun |
| 2 | `wilayah_kerja` | Master kecamatan & kebutuhan | id, kode_kecamatan, nama_kecamatan, kebutuhan_pcl/pml, terisi_pcl/pml |
| 3 | `alokasi_petugas` | Alokasi existing PCL/PML ke wilayah | user_id, wilayah_id, posisi_tugas, status_alokasi |
| 4 | `monitoring_progress` | Progress harian petugas | petugas_id, wilayah_id, tanggal, target, realisasi |
| 5 | `pendaftaran` | Data demografi pendaftar terdaftar | id, nik, nama_lengkap, posisi_dilamar, kecamatan_domisili_id |
| 6 | `seleksi_mitra` | Status lolos/tidak mitra | pendaftaran_id, status_seleksi |
| 7 | `surat_tugas` | Surat tugas aktif petugas | petugas_id, wilayah_id, status, tanggal_mulai |
| 8 | `laporan_kegiatan` | Verifikasi laporan lapangan | wilayah_id, created_by, status, tanggal_kegiatan |
| 9 | `anomaly` | Jumlah laporan anomali | wilayah_id, status, pelapor_id |
| 10 | `jadwal_selesai` | Target vs realisasi penyelesaian | wilayah_id, status, target_tanggal, realisasi_tanggal |
| 11 | `pelatihan` | Data pelatihan terselenggara | id, status, mulai_at |
| 12 | `presensi_pelatihan` | Kehadiran training | pelatihan_id, user_id |
| 13 | `activity_logs` | Aktivitas sistem (audit trail) | user_id, action, module, created_at |

### C.2 Satu-satunya Modifikasi pada Tabel Existing

**Tabel: `users`** — tambah value ENUM `task_force` pada kolom `role`:

```sql
ALTER TABLE users
MODIFY role ENUM(
    'admin','operator','pegawai','mitra','pml','pcl','task_force'
) NOT NULL DEFAULT 'mitra';
```

Ini adalah **perubahan aman** karena:
- Tidak mengubah nama kolom
- Tidak menghapus constraint
- Tidak menghapus data
- Hanya memperluas ENUM (idempotent — bisa dijalankan berulang)

---

## D. Tabel Baru

### D.1 `sipw_import` — Master Data SLS dari SIPW

Fungsi: Menyimpan data hasil import Excel dari sistem SIPW (daftar SLS/blok sensus).

```sql
CREATE TABLE sipw_import (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    idfrs         BIGINT NULL,          -- ID FRS dari SIPW
    semester      VARCHAR(20) NULL,      -- Semester pendataan
    idsubsls      VARCHAR(50) NULL,      -- ID Sub-SLS

    -- Kode wilayah BPS
    kdprov        VARCHAR(5) NULL,
    kdkab         VARCHAR(5) NULL,
    kdkec         VARCHAR(10) NULL,
    kddesa        VARCHAR(15) NULL,
    kdsls         VARCHAR(20) NULL,

    -- Nama wilayah
    nmprov        VARCHAR(100) NULL,
    nmkab         VARCHAR(100) NULL,
    nmkec         VARCHAR(100) NULL,
    nmdesa        VARCHAR(100) NULL,

    -- Detail SLS
    nmsls         VARCHAR(255) NULL,
    nama_ketua    VARCHAR(255) NULL,     -- Nama ketua SLS

    -- Muatan (jumlah)
    kk            INT DEFAULT 0,          -- Kepala Keluarga
    btt           INT DEFAULT 0,          -- Bangunan Tempat Tinggal
    bttk          INT DEFAULT 0,          -- BTT Khusus
    bku           INT DEFAULT 0,          -- Bangunan dan tempat kegiatan
    bbtt_nonusaha INT DEFAULT 0,          -- BTT Non Usaha
    usaha         INT DEFAULT 0,          -- Jumlah usaha
    muatan        INT DEFAULT 0,          -- Total muatan

    -- Metadata
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_kdkec(kdkec),
    INDEX idx_kddesa(kddesa),
    INDEX idx_idsubsls(idsubsls)
);
```

**Catatan desain:**
- Tidak ada FK ke `wilayah_kerja` karena data SIPW berasal dari sistem eksternal (kode wilayah bisa berbeda format)
- Kolom muatan numerik memungkinkan agregasi dashboard (total SLS, total muatan per kecamatan)
- Index pada `kdkec` dan `kddesa` untuk performa query per wilayah

### D.2 `sipw_assignment` — Assignment Petugas ke SLS

Fungsi: Menugaskan petugas (PCL/PML/Task Force) ke SLS tertentu.

```sql
CREATE TABLE sipw_assignment (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    sipw_id       INT NOT NULL,          -- FK ke sipw_import
    pencacah_id   INT NULL,              -- FK ke users (role=pcl)
    pengawas_id   INT NULL,              -- FK ke users (role=pml)
    task_force_id INT NULL,              -- FK ke users (role=task_force)
    status        ENUM('belum','proses','selesai') DEFAULT 'belum',

    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

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
        ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_sipw_assignment_pencacah ON sipw_assignment(pencacah_id);
CREATE INDEX idx_sipw_assignment_pengawas ON sipw_assignment(pengawas_id);
CREATE INDEX idx_sipw_assignment_taskforce ON sipw_assignment(task_force_id);
```

**Catatan desain:**
- `ON DELETE SET NULL` untuk petugas — jika user dihapus, assignment tidak hilang, hanya data petugasnya menjadi null
- `ON DELETE CASCADE` untuk `sipw_id` — jika data SIPW dihapus, assignment ikut terhapus
- Status `belum → proses → selesai` untuk tracking progres per SLS
- Satu SLS bisa punya 1 PCL (pencacah), 1 PML (pengawas), dan 1+ Task Force

### D.3 `dash_monitoring_summary` — Agregat Progress (Patch 2)

Fungsi: Menyimpan pre-kalkulasi progress per kecamatan. Mempercepat load dashboard tanpa harus query agregasi besar setiap kali.

```sql
CREATE TABLE dash_monitoring_summary (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    wilayah_id       INT NOT NULL,           -- FK ke wilayah_kerja
    periode          VARCHAR(10) NOT NULL,    -- '2026-05', '2026-06', dll
    total_sls        INT DEFAULT 0,           -- Jumlah SLS dari sipw_import
    assigned_sls     INT DEFAULT 0,           -- SLS yang sudah di-assign
    progress_sls     INT DEFAULT 0,           -- SLS status 'proses'
    completed_sls    INT DEFAULT 0,           -- SLS status 'selesai'
    total_muatan     INT DEFAULT 0,           -- Total muatan usaha
    realisasi_muatan INT DEFAULT 0,           -- Realisasi dari monitoring_progress
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_summary_wil_periode (wilayah_id, periode),
    CONSTRAINT fk_summary_wilayah
        FOREIGN KEY (wilayah_id) REFERENCES wilayah_kerja(id)
        ON DELETE CASCADE ON UPDATE CASCADE
);
```

**Catatan desain:**
- Tabel agregat ini **diupdate oleh cron/job scheduler** (setiap jam atau setiap malam), bukan realtime
- Dashboard membaca dari tabel ini → query cepat meski data besar
- `UNIQUE(wilayah_id, periode)` mencegah duplikasi data per bulan

---

## E. Ringkasan Foreign Key Relationship Final

```
sipw_import             → (tabel master, tanpa FK ke existing)
sipw_assignment.sipw_id ──FK──> sipw_import.id
sipw_assignment.pencacah_id ──FK──> users.id
sipw_assignment.pengawas_id ──FK──> users.id
sipw_assignment.task_force_id ──FK──> users.id
dash_monitoring_summary.wilayah_id ──FK──> wilayah_kerja.id
```

**Tidak ada FK baru ke tabel existing lain** → zero risiko bagi sistem existing.

---

## F. Perubahan Aman pada Tabel Existing

| Tabel | Perubahan | Dampak |
|-------|-----------|--------|
| `users` | MODIFY ENUM role tambah `task_force` | Aman — hanya perluas nilai enum, data existing tidak berubah |
| `users` | (Opsional) Tambah INDEX `idx_users_role_status` on `(role, status_akun)` | Mempercepat query dashboard filter petugas |

Tidak ada perubahan lain. Tabel existing tetap utuh.

---

## G. Flow Data End-to-End

### G.1 Import SIPW

```
User upload Excel SIPW
        │
        ▼
Dashboard: validasi & parse file
        │
        ├── Jika error → tampilkan pesan
        │
        ▼ sukses
INSERT INTO sipw_import (batch insert)
        │
        ▼
Halaman Import: tampilkan ringkasan
  - Total baris diimport
  - Jumlah SLS per kecamatan
  - Total muatan
```

### G.2 Assignment Petugas

```
Dashboard: tampilkan list SLS belum di-assign
        │
        ▼
Pilih SLS → pilih PCL (dari users role=pcl)
        │
        ▼
INSERT INTO sipw_assignment (sipw_id, pencacah_id, ...)
        │
        ▼
Dashboard: tampilkan progress assignment
  - SLS assigned vs total
  - Coverage per kecamatan
```

### G.3 Monitoring Realtime

```
Dashboard load → query dari:
  1. dash_monitoring_summary (agregat per kecamatan)
  2. monitoring_progress (harian)
  3. alokasi_petugas (coverage)
  4. sipw_assignment (progress SLS)
        │
        ▼
Tampilkan:
  - Card: Total SLS, Assigned, Progress, Selesai
  - Chart: progress per kecamatan (bar chart)
  - Tabel: detail per kecamatan
  - Map: sebaran wilayah (opsional)
```

### G.4 Update Progress (dari Dashboard atau Existing)

```
Existing App: petugas update monitoring_progress
        │
        ▼
Cron/Trigger: UPDATE dash_monitoring_summary
  Hitung ulang progress per kecamatan
        │
        ▼
Dashboard: menampilkan data terbaru (delay 1-2 jam maksimal)
```

---

## H. Justifikasi Desain

| Keputusan | Alasan |
|-----------|--------|
| **Dashboard terpisah** | Zero risiko terhadap operasional existing. Bisa develop & deploy independen |
| **Shared database** | Data real-time tanpa perlu sinkronisasi API. Query langsung ke DB yang sama |
| **Dashboard READ-ONLY ke tabel existing** | Menjamin tidak ada INSERT/UPDATE/DELETE yang merusak data operasional |
| **sipw_import tanpa FK ke wilayah_kerja** | Data SIPW dari eksternal — format kode wilayah bisa berbeda. Query pakai JOIN via kode string |
| **dash_monitoring_summary (tabel agregat)** | Menghindari query agregasi mahal setiap load dashboard. Update periodik via cron |
| **sipw_assignment status ENUM** | Sederhana, cukup untuk tracking progress 3 state |
| **Patch SQL incremental** | Setiap perubahan adalah file SQL terpisah, bisa di-version control dan di-review |
| **FK dengan ON DELETE SET NULL** | Jika petugas dihapus dari `users`, data assignment tetap utuh (tidak hilang) |

---

## I. Kebutuhan File Patch

| Patch | Isi | Status |
|-------|-----|--------|
| `patch_001_dashboard_base.sql` | ALTER users + CREATE sipw_import + sipw_assignment | ✅ Siap (file: `final_database_dashboard_se2026.sql`) |
| `patch_002_dashboard_aggregate.sql` | CREATE dash_monitoring_summary + INDEX | 🔜 Akan dibuat |
| `patch_003_dashboard_index.sql` | Index tambahan di existing (rol/stts) | 🔜 Opsional |

---

## J. Ringkasan

| Aspek | Detail |
|-------|--------|
| **Aplikasi** | Dashboard terpisah di `/dashboard-se2026/` |
| **Tech stack** | PHP 8.2 Native + Tailwind CSS (sama dengan existing) |
| **Database** | `bps_jember_se2026` (shared) |
| **Tabel baru** | `sipw_import`, `sipw_assignment`, `dash_monitoring_summary` |
| **Modifikasi existing** | 1 tabel (`users` — tambah enum task_force) |
| **Mode akses dashboard ke existing** | READ ONLY (SELECT) |
| **Mode akses dashboard ke tabel baru** | FULL R/W (INSERT, UPDATE, SELECT) |
