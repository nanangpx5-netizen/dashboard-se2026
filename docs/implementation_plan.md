# Rencana Implementasi Integrasi Data Master Resmi Semester 1 2025 untuk Kabupaten Jember

Rencana ini bertujuan untuk memutakhirkan dashboard ES2026 Kabupaten Jember agar sepenuhnya selaras dengan data master desa resmi versi Semester 1 Tahun 2025 (`mfd_25_2_3509.xlsx` dan `msubsls_25_2_3509.xlsx`).

Integrasi ini akan menggantikan data lama (`16.538` SLS unik dengan duplikat yang terabaikan) menjadi data baru resmi (`16.772` SLS yang 100% unik tanpa duplikat).

---

## Analisis Mendalam & Verifikasi Kelengkapan Data

Berdasarkan analisis pra-implementasi terhadap dataset resmi Semester 1 2025 di folder `data/mfd/sm22025/`:

### 1. Master File Desa (MFD) — `mfd_25_2_3509.xlsx`
* **Total Baris Data**: 248 desa/kelurahan definitif.
* **Kecamatan**: 31 kecamatan se-Kabupaten Jember.
* **Klasifikasi**: 157 desa Perkotaan (Urban), 91 desa Pedesaan (Rural).
* **Status Pemerintahan**: 226 Desa, 22 Kelurahan.
* **Kondisi**: 100% valid, tidak ada koordinat geografis (latitude/longitude) yang bernilai null (kecuali 0.0 pada beberapa entri).
* **Tabel Mapping**: Data MFD akan diimpor ke tabel **`mfd_kec`** yang sudah ada (patch_005), digunakan untuk JOIN nama kecamatan di dashboard.

### 2. Master Sub-SLS (MSUBSLS) — `msubsls_25_2_3509.xlsx`
* **Total Baris Data**: 16,772 sub-SLS/SLS.
* **Keunikan**: **100% unik pada kolom `idsubsls` (16,772 kode SLS unik, 0 duplikat)**. Ini adalah perbaikan besar dibandingkan data referensi lama (`rekap-sls.xlsx`) yang hanya menghasilkan 16,538 SLS setelah pembersihan 234 baris duplikat.
* **Muatan Kumulatif Kabupaten Jember**:
  - **Kepala Keluarga (KK)**: 808,696 KK
  - **Bangunan Tempat Tinggal (BSTT)**: 715,538 unit
  - **Bangunan Bukan Tempat Tinggal (BSBTT)**: 42,430 unit
  - **Bangunan Tempat Tinggal Khusus (BSTTK)**: 45,758 unit
  - **Bangunan Khusus Usaha (BKU)**: 88,129 unit
  - **Unit Usaha**: 223,381 unit usaha
  - **Total Muatan Pendataan**: 1,135,539 objek muatan
* **Tabel Target**: **`sipw_import`** — akan dikosongkan lalu diisi ulang (truncate + insert batch).

### 3. Hasil Verifikasi Silang (Cross-Validation) MFD vs MSUBSLS
* **Kecamatan**: 31 dari 31 kecamatan cocok sempurna.
* **Desa/Kelurahan**: 248 dari 248 desa cocok sempurna.
* **Kesimpulan**: Data referensi MFD dan MSUBSLS **100% konsisten**, tidak ada desa atau kecamatan yang berselisih. Data ini sangat valid untuk diimplementasikan.

---

## User Review Required

> [!IMPORTANT]
> **Penyelarasan Format Kode SLS (16 Digit)**:
> Pada implementasi sebelumnya, tabel `master_sls` menggunakan format kode 14 digit, sedangkan `sipw_import` menggunakan format 16 digit (`idsubsls`). Hal ini menyebabkan perbandingan SLS tidak dapat dilakukan secara langsung (JOIN langsung).
>
> **Keputusan**: Setelah integrasi ini, **`master_sls` akan diisi ulang** dengan data MSUBSLS format 16 digit (`idsubsls`). JOIN langsung antara `master_sls` dan `sipw_import` via kolom `idsubsls` akan menjadi mungkin, menampilkan daftar SLS yang hilang/berselisih secara presisi di dashboard.

> [!WARNING]
> **Dampak pada data assignment**: Tabel `sipw_assignment` memiliki foreign key ke `sipw_import.id`. Karena `sipw_import` akan di-truncate dan diisi ulang, **semua data assignment akan hilang**. Pastikan untuk:
> 1. Backup tabel `sipw_assignment` sebelum eksekusi.
> 2. Re-assign petugas setelah data baru termuat.
> 3. Atau pertimbangkan pendekatan mapping `id` lama ke `id` baru jika diperlukan.

---

## Proposed Changes

### 1. Script Import (PHP CLI) — *Implementasi Utama*

#### [NEW] [scripts/import_official_data.php](file:///c:/laragon/www/dashboard-se2026/scripts/import_official_data.php)
> **Catatan**: Plan awal menyebut migration SQL, namun diimplementasikan via PHP CLI script karena Excel parsing membutuhkan **OpenSpout** (streaming reader, tidak bisa via SQL murni).

Script ini menjalankan seluruh proses data migration:
1. **Backup otomatis** tabel `sipw_import`, `master_sls`, dan `sipw_assignment` (dengan mapping `idsubsls`) ke tabel backup.
2. **TRUNCATE** `sipw_import` dan `master_sls`.
3. **INSERT** 16,772 baris dari `msubsls_25_2_3509.xlsx` ke `sipw_import` (batch 500 baris, transaction) — mencakup kolom: `idfrs`, `semester`, `idsubsls`, `kdprov`, `nmprov`, `kdkab`, `nmkab`, `kdkec`, `nmkec`, `kddesa`, `nmdesa`, `kdsls`, `klas`, `nmsls`, `nama_ketua`, `kk`, `btt`, `bbtt_nonusaha`, `bttk`, `bku`, `usaha`, `muatan`, `dominan`.
4. **INSERT** 16,772 baris ke `master_sls` dengan format 16 digit (`idsubsls` sebagai `kode`).
5. **UPDATE** `mfd_kec` dengan data MFD terbaru (31 kecamatan, urutan, nama).
6. **Restore** `sipw_assignment` via mapping `idsubsls` (old ID → new ID).
7. **Hapus cache** dashboard agar data termuat real-time.
8. Output log + progress bar real-time ke console.

### 2. Database Schema Changes

#### Kolom baru di `sipw_import`
- `klas` TINYINT — Klasifikasi wilayah (1=Urban, 2=Rural), dari kolom `klas` di MSUBSLS.
- `dominan` TINYINT — Dominan kegiatan SLS, dari kolom `dominan` di MSUBSLS.

#### Kolom baru di `sipw_assignment`
- `idsubsls` VARCHAR(50) — Foreign key alternatif via `idsubsls` (16 digit), untuk remapping assignment saat re-import.

---

### 3. Controllers Update

#### [MODIFY] [DashboardController.php](file:///c:/laragon/www/dashboard-se2026/src/Controllers/DashboardController.php)
* **`getStats`**: Tambah agregat bangunan (`total_bstt`, `total_bsbtt`, `total_bsttk`, `total_bku`) + klasifikasi (`total_sls_urban`, `total_sls_rural`).
* **`getWilayahData`**: Tambah `sls_urban`, `sls_rural` per kecamatan (dari `sipw_import.klas`), `total_bsbtt`, `total_bsttk`.
* **`getPerbandingan`**: JOIN langsung via `idsubsls` (16 digit) — `master_sls.kode` = `sipw_import.idsubsls`. Detail SLS hilang ditampilkan (tidak hanya count).

> **Catatan**: Method `getBuildingStats` tidak dibuat terpisah — data bangunan sudah tersedia di `getStats` dan di-pass ke view langsung.

---

### 4. UI/UX Premium & Visualisasi Baru

#### [MODIFY] [views/dashboard/index.php](file:///c:/laragon/www/dashboard-se2026/views/dashboard/index.php)
1. **Glassmorphism Cards**: CSS gradients, backdrop-filter blur, drop shadows, hover animations.
2. **Chart Baru**:
   - **Donut Klasifikasi Wilayah**: Urban (11.690 SLS) vs Rural (5.082 SLS).
   - **Bar Chart Bangunan**: BSTT, BSBTT, BSTTK, BKU.
3. **Perbandingan Detail**: JOIN 16 digit — daftar SLS hilang muncul dalam tabel.
4. **Info footer**: Update sumber data ke `msubsls_25_2_3509.xlsx` (hapus referensi `rekap-sls.xlsx` lama).

#### [MODIFY] [assets/js/dashboard.js](file:///c:/laragon/www/dashboard-se2026/assets/js/dashboard.js)
* Inisialisasi chart klasifikasi (doughnut) dan bangunan (bar).
* Hapus dataset `assigned` dari progress chart.

---

## Verification & Test Plan

### 1. Automated Script Verification
* Menjalankan script PHP import dan memverifikasi output log:
  - Jumlah data `sipw_import` harus tepat **16.772** baris.
  - Jumlah data `master_sls` harus tepat **16.772** baris.
  - Jumlah baris gagal/error harus **0**.
  - Cache dashboard berhasil dihapus.

### 2. Database Verification
```sql
SELECT COUNT(*) FROM sipw_import;                              -- 16772
SELECT COUNT(*) FROM master_sls;                               -- 16772
SELECT COUNT(DISTINCT idsubsls) FROM sipw_import;               -- 16772
SELECT COUNT(*) FROM sipw_import si LEFT JOIN master_sls ms ON ms.kode = si.idsubsls WHERE ms.id IS NULL;  -- 0
```

### 3. Manual Verification (via Browser)
* Login sebagai Administrator/Operator di dashboard.
* Membuka halaman utama dashboard dan memverifikasi indikator KPI:
  - Jumlah SLS: **16,772**
  - Jumlah KK: **808,696**
  - Jumlah Usaha: **223,381**
  - Jumlah Muatan: **1,135,539**
* Memverifikasi grafik baru (klasifikasi urban/rural, distribusi bangunan) termuat dengan sempurna.
* Filter kecamatan → data perbandingan muncul dengan daftar detail jika ada selisih.

### 4. Rollback Plan

> **Catatan**: Script `rollback-import.php` menggunakan sistem batch ID, bukan flag `--restore`. Lihat help untuk detail.

Jika terjadi error, tersedia **dua metode rollback**:

**Metode A — Manual (via backup tables)**:
```sql
-- 1. Hapus data baru
TRUNCATE sipw_import;
TRUNCATE master_sls;
DELETE FROM sipw_assignment;

-- 2. Restore dari backup (jika ada)
INSERT INTO sipw_import SELECT * FROM sipw_import_bkp;
INSERT INTO master_sls SELECT * FROM master_sls_bkp;
INSERT INTO sipw_assignment SELECT * FROM sipw_assignment_bkp;
```

**Metode B — Re-import ulang dengan data MSUBSLS**:
```bash
php scripts/import_official_data.php
```
Script import idempoten — bisa dijalankan ulang kapan saja tanpa efek ganda (menggunakan `INSERT IGNORE`).
