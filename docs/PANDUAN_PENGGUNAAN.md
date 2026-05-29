# 📘 Buku Panduan Penggunaan
## Dashboard SE2026 — BPS Kabupaten Jember

```
  ╔══════════════════════════════════════════════════════════════╗
  ║           SENSUS EKONOMI 2026 - BPS KABUPATEN JEMBER         ║
  ║              SISTEM DASHBOARD MONITORING                      ║
  ╚══════════════════════════════════════════════════════════════╝
```

---

## 📋 Daftar Isi

1. [Akses & Login](#1-akses--login)
2. [Dashboard Utama](#2-dashboard-utama)
3. [Import Data SIPW](#3-import-data-sipw)
4. [Assignment Petugas](#4-assignment-petugas)
5. [Monitoring Wilayah](#5-monitoring-wilayah)
6. [Beban Kerja](#6-beban-kerja)
7. [Wilayah Kerja](#7-wilayah-kerja)
8. [Manajemen Petugas](#8-manajemen-petugas)
9. [Audit Log](#9-audit-log)
10. [Laporan & Ekspor](#10-laporan--ekspor)
11. [Tabel & Filter DataTables](#11-tabel--filter-datatables)
12. [Pemecahan Masalah Umum](#12-pemecahan-masalah-umum)

---

## 1. Akses & Login

### ⚙️ Persyaratan Sistem

| Kebutuhan | Spesifikasi |
|---|---|
| **Browser** | Google Chrome 90+, Firefox 88+, Edge 90+ |
| **Resolusi** | Minimal 1280×720 (1366×768 dianjurkan) |
| **Jaringan** | Koneksi internet stabil (untuk CDN) |
| **PDF** | Adobe Reader atau browser bawaan |

### 🔐 Halaman Login

Buka URL berikut di browser:

```
http://localhost/dashboard-se2026/?page=login
```

```
  ┌──────────────────────────────────────────────┐
  │                                              │
  │   ┌──────────────────────────────────────┐   │
  │   │       DASHBOARD SE2026 JEMBER         │   │
  │   │    BPS Kabupaten Jember               │   │
  │   │                                      │   │
  │   │   ┌────────────────────────────────┐  │   │
  │   │   │  Username                      │  │   │
  │   │   └────────────────────────────────┘  │   │
  │   │   ┌────────────────────────────────┐  │   │
  │   │   │  Password                      │  │   │
  │   │   └────────────────────────────────┘  │   │
  │   │                                      │   │
  │   │   ┌────────────────────────────────┐  │   │
  │   │   │        ┌──────────┐            │  │   │
  │   │   │        │  MASUK   │            │  │   │
  │   │   │        └──────────┘            │  │   │
  │   │   └────────────────────────────────┘  │   │
  │   └──────────────────────────────────────┘   │
  └──────────────────────────────────────────────┘
```

**Langkah:**
1. Masukkan **Username** (nama pengguna yang didaftarkan)
2. Masukkan **Password**
3. Klik tombol **"Masuk"**

### ❌ Gagal Login

Jika muncul pesan **"Username atau password salah"**:
- Periksa kembali penulisan username dan password (huruf besar/kecil berpengaruh)
- Jika 5 kali gagal, akun akan **terkunci sementara 15 menit**
- Hubungi admin jika lupa password

### 🚪 Logout

Klik nama pengguna di pojok kanan atas → pilih **"Keluar"**

```
  ┌──────────────────────────────────────────────┐
  │  👤 admin  ▼                                 │
  │  ┌──────────────────────────────────────┐    │
  │  │  ⚙️ Pengaturan Akun                  │    │
  │  │  🚪 Keluar                           │    │
  │  └──────────────────────────────────────┘    │
  └──────────────────────────────────────────────┘
```

### 👑 Hak Akses per Halaman

| Halaman | admin | operator | pegawai | mitra | task_force | pml | pcl |
|---|---|---|---|---|---|---|---|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Import SIPW | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Assignment | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Monitoring | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Beban Kerja | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Wilayah | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| Petugas | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Audit Log | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Laporan | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## 2. Dashboard Utama

**URL:** `?page=dashboard`

Dashboard adalah halaman utama yang menampilkan ringkasan data secara real-time.

```
  ╔══════════════════════════════════════════════════════════════╗
  ║  📊 DASHBOARD SE2026                    👤 admin  🔔  🚪   ║
  ╚══════════════════════════════════════════════════════════════╝

  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐
  │   31   │ │  248   │ │ 16.538 │ │1.234.567│ │ 89.012 │ │345.678 │
  │Kecamatan│ │  Desa  │ │Total SLS│ │ Total KK│ │ Usaha  │ │ Muatan │
  └────────┘ └────────┘ └────────┘ └────────┘ └────────┘ └────────┘

  ┌──────────┐ ┌──────────┐ ┌──────────┐
  │   1.234   │ │   567    │ │    89    │
  │ Pencacah  │ │ Pengawas │ │Task Force│
  └──────────┘ └──────────┘ └──────────┘

  ┌──────────────────────────────────────────────────┐
  │  📊 Muatan per Kecamatan                         │
  │  ██▁▁▃▃▅▅▇▇██▁▁▃▃▅▅▇▇██▁▁▃▃▅▅▇▇██▁▁▃▃             │
  │  └──────────────────────────────────────────┘     │
  │  KENCONG  GUMUKMAS  PUGER  WULUHAN  ...           │
  └──────────────────────────────────────────────────┘

  ┌──────────────────────────────────────────────────┐
  │  🥧 Beban Pencacah                               │
  │       ╭─────────╮                                │
  │      ╱  ╲       ╲                               │
  │     │ 🟦 🟧 🟩  │                               │
  │      ╲  ╱       ╱                               │
  │       ╰─────────╯                                │
  └──────────────────────────────────────────────────┘
```

### 📊 Kartu Statistik (6 kartu)

| Kartu | Sumber Data | Keterangan |
|---|---|---|
| **Kecamatan** | `sipw_import` | Jumlah kecamatan yang terdata |
| **Desa** | `sipw_import` | Jumlah desa/kelurahan |
| **Total SLS** | `sipw_import` | Total Satuan Lingkungan Setempat |
| **Total KK** | `sipw_import` | Jumlah Kepala Keluarga |
| **Usaha** | `sipw_import` | Jumlah usaha terdata |
| **Muatan** | `sipw_import` | Total beban kerja |

### 👥 Kartu Petugas (3 kartu)

| Kartu | Keterangan |
|---|---|
| **Pencacah (PCL)** | Jumlah petugas pencacah aktif |
| **Pengawas (PML)** | Jumlah petugas pengawas aktif |
| **Task Force** | Jumlah petugas tugas khusus aktif |

### 📈 Grafik

1. **Muatan per Kecamatan** — Grafik batang vertikal
2. **Beban Pencacah** — Grafik donat 15 petugas dengan beban tertinggi
3. **Progress Wilayah** — Grafik batang progress assignment per kecamatan

### 📋 Tabel Progress per Kecamatan

| Kecamatan | Total SLS | Muatan | Assign | Proses | Selesai | Progress |
|---|---|---|---|---|---|---|
| KENCONG | 625 | 1.250 | 500 | 300 | 200 | ██████░░░░ 60% |
| GUMUKMAS | 540 | 1.080 | 400 | 250 | 150 | ████░░░░░░ 40% |
| ... | ... | ... | ... | ... | ... | ... |

---

## 3. Import Data SIPW

**URL:** `?page=dashboard&sub=import`
**Hak Akses:** admin, operator

Halaman ini digunakan untuk mengimpor data SLS (Satuan Lingkungan Setempat) dari file Excel SIPW.

```
  ┌─────────────────────────────────────────────────────────────┐
  │  📥 IMPORT DATA SIPW                     👤 admin           │
  ├─────────────────────────────────────────────────────────────┤
  │                                                             │
  │  ┌──────────────────────────────────────────────────────┐   │
  │  │  📁 Pilih File Excel SIPW                    📤 Upload │   │
  │  │  [Choose File]  data_sipw_kencong.xlsx               │   │
  │  │  Format: .xlsx, .xls, .csv | Maks: 20 MB            │   │
  │  └──────────────────────────────────────────────────────┘   │
  │                                                             │
  │  ┌─ Preview Data ───────────────────────────────────────┐   │
  │  │  ✅ Header valid (12 kolom terdeteksi)               │   │
  │  │                                                      │   │
  │  │  ┌────────┬────────┬────────┬────────┬──────┬──────┐ │   │
  │  │  │ kdkec  │ kddesa │ nmsls  │ nmdesa │ kk   │ ...  │ │   │
   ...  ...  ...
  │  │  └────────┴────────┴────────┴────────┴──────┴──────┘ │   │
  │  │                                    📥 Mulai Import    │   │
  │  └──────────────────────────────────────────────────────┘   │
  │                                                             │
  │  ┌─ Riwayat Import ───────────────────────────────────────┐ │
  │  │  Batch ID    File      Baris  Baru  Update  Status     │ │
  │  │  IMP-001   file1.xlsx   500    500    0     ✅ Sukses  │ │
  │  │  IMP-002   file2.xlsx   467    467    0     ✅ Sukses  │ │
  │  └────────────────────────────────────────────────────────┘ │
  └─────────────────────────────────────────────────────────────┘
```

### 📤 Cara Import

1. **Siapkan file Excel** dengan format yang sesuai (unduh template jika perlu)
2. Klik **"Pilih File"** dan pilih file `.xlsx` / `.xls` / `.csv`
3. Klik **"Upload"** untuk melihat pratinjau data
4. Periksa **header kolom** — pastikan semua kolom wajib terdeteksi:

| Kolom Wajib | Keterangan |
|---|---|
| `kdkec` | Kode kecamatan (3 digit) |
| `kddesa` | Kode desa (3 digit) |
| `nmsls` | Nama SLS |

| Kolom Dianjurkan | Keterangan |
|---|---|
| `nmdesa` | Nama desa |
| `nmkec` | Nama kecamatan |
| `kk` | Jumlah Kepala Keluarga |
| `btt` | Jumlah Bangunan Tempat Tinggal |
| `bku` | Jumlah Bangunan dan Tempat Usaha |
| `usaha` | Jumlah usaha |
| `muatan` | Total muatan (KK + usaha) |

5. Jika pratinjau sudah benar, klik **"Mulai Import"**
6. Tunggu proses import selesai (progres akan muncul)

### 📋 Riwayat Import

Tabel riwayat menampilkan semua aktivitas import:

| Kolom | Keterangan |
|---|---|
| **Batch ID** | ID unik untuk setiap import |
| **File** | Nama file yang diimpor |
| **Baris** | Total baris data dalam file |
| **Baru** | Data baru yang ditambahkan |
| **Update** | Data yang diperbarui |
| **Gagal** | Data yang gagal (format salah) |
| **Status** | ✅ Sukses / ⚠️ Sebagian / ❌ Gagal |
| **User** | Nama pengguna yang melakukan import |
| **Waktu** | Tanggal dan jam import |

### ↩️ Batalkan Import (Rollback)

Jika terjadi kesalahan, admin dapat membatalkan import dari CLI:

```bash
# Di folder scripts/
php rollback-import.php list          # Lihat daftar import
php rollback-import.php info IMP-001  # Detail import
php rollback-import.php rollback IMP-001  # Batalkan import
```

---

## 4. Assignment Petugas

**URL:** `?page=dashboard&sub=assignment`
**Hak Akses:** admin, operator

Halaman ini digunakan untuk menugaskan petugas (PCL/PML/Task Force) ke setiap SLS.

```
  ┌─────────────────────────────────────────────────────────────┐
  │  📋 ASSIGNMENT PETUGAS                   👤 admin           │
  ├─────────────────────────────────────────────────────────────┤
  │  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐             │
  │  │16.538│ │ 5.442│ │10.541│ │ 2.500│ │ 1.200│             │
  │  │Total │ │Assign│ │Belum │ │Proses│ │Selesai│             │
  │  │ SLS  │ │      │ │      │ │      │ │      │             │
  │  └──────┘ └──────┘ └──────┘ └──────┘ └──────┘             │
  │                                                             │
  │  ┌─ Filter ─────────────────────────────────────────────┐   │
  │  │  Kecamatan: [▼ KENCONG  ]  Desa: [▼ Semua           ] │   │
  │  │  Status:    [▼ Semua     ]  Cari: [🔍          ] 🔄  │   │
  │  └──────────────────────────────────────────────────────┘   │
  │                                                             │
  │  [📄 Template]  [📥 Import Excel]  [📊 Beban Petugas]      │
  │                                                             │
  │  ┌─ Tab: [ ] Belum Assign (10.541) ───────────────────────┐ │
  │  │  No  SLS         Desa      Kecamatan  KK   ...  Aksi   │ │
  │  │  1   SAWAH      KRATON    KENCONG    125  ...  [Assign]│ │
  │  │  2   TEGAL      KRATON    KENCONG     89  ...  [Assign]│ │
  │  │  3   SAWAH      KENCONG   KENCONG    200  ...  [Assign]│ │
  │  │  ...                                                    │ │
  │  │  Halaman: ◀ 1 2 3 ... 422 ▶  [25 baris ▼]             │ │
  │  └────────────────────────────────────────────────────────┘ │
  └─────────────────────────────────────────────────────────────┘
```

### 📑 Dua Tab Data

| Tab | Keterangan |
|---|---|
| **Belum Assign** (default) | SLS yang belum ditugaskan ke petugas manapun |
| **Sudah Assign** | SLS yang sudah memiliki petugas (PCL/PML/TF) |

### 🔍 Filter Data

| Filter | Fungsi |
|---|---|
| **Kecamatan** | Pilih kecamatan untuk mempersempit data |
| **Desa** | Muncul setelah kecamatan dipilih |
| **Status** | Filter berdasarkan status pengerjaan (Belum/Proses/Selesai) |
| **Cari** | Cari berdasarkan nama SLS, desa, atau petugas |

### ✍️ Assignment Tunggal

1. Pada tab **"Belum Assign"**, klik tombol **"Assign"** pada SLS yang ingin ditugaskan
2. Akan muncul modal:

```
  ┌──────────────────────────────────────────┐
  │  📝 Assign Petugas                       │
  ├──────────────────────────────────────────┤
  │  SLS: SAWAH — KRATON                     │
  │                                          │
  │  Pencacah (PCL):  [▼ Pilih petugas  ▼]  │
  │  Pengawas (PML):  [▼ Pilih petugas  ▼]  │
  │  Task Force:      [▼ Pilih petugas  ▼]  │
  │                                          │
  │           [Batal]  [💾 Simpan]           │
  └──────────────────────────────────────────┘
```

3. Pilih petugas dari dropdown (paling tidak satu)
4. Klik **"Simpan"**

### ✏️ Edit Assignment

1. Pada tab **"Sudah Assign"**, klik ikon ✏️ **Edit**
2. Ubah petugas pada modal yang muncul
3. Klik **"Simpan"**

### 🗑️ Hapus Assignment

1. Pada tab **"Sudah Assign"**, klik ikon 🗑️ **Hapus**
2. Konfirmasi penghapusan

### 🔄 Ubah Status

Pada tab **"Sudah Assign"**, ubah status melalui dropdown inline:

```
  Status: [▼ Belum  ▼]
           Belum
           Proses
           Selesai
```

### 📥 Import Assignment dari Excel

1. Klik tombol **"Template"** untuk mengunduh file template Excel
2. Isi template dengan data assignment:

| Kolom | Wajib? | Keterangan |
|---|---|---|
| `nmsls` | ✅ Wajib | Nama SLS (harus cocok dengan database) |
| `nmdesa` | ✅ Wajib | Nama desa |
| `nmkec` | ✅ Wajib | Nama kecamatan |
| `pcl` | Opsional | Username petugas PCL |
| `pml` | Opsional | Username petugas PML |
| `task_force` | Opsional | Username petugas Task Force |

3. Klik **"Import Excel"**, pilih file, klik **"Upload & Proses"**
4. Periksa pratinjau data
5. Klik **"Proses Import"** untuk menyimpan

### 📊 Beban Petugas

Klik tombol **"Beban Petugas"** untuk melihat ringkasan beban kerja setiap petugas:

| Petugas | Role | PCL | PML | TF | Total | Selesai PCL | Selesai PML |
|---|---|---|---|---|---|---|---|
| operator | admin | 150 | 20 | 5 | 175 | 80 | 10 |
| pegawai01 | pegawai | 0 | 0 | 0 | 0 | 0 | 0 |

---

## 5. Monitoring Wilayah

**URL:** `?page=dashboard&sub=monitoring`
**Hak Akses:** Semua peran (admin, operator, pegawai, mitra, task_force, pml, pcl)

Halaman ini digunakan untuk memantau progress pendataan per wilayah secara detail.

```
  ┌─────────────────────────────────────────────────────────────┐
  │  📊 MONITORING WILAYAH                   👤 admin           │
  ├─────────────────────────────────────────────────────────────┤
  │  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐                      │
  │  │16.538│ │ 5.442│ │ 2.500│ │ 1.200│                      │
  │  │Total │ │Sudah │ │Proses│ │Selesai│                      │
  │  │ SLS  │ │Assign│ │      │ │      │                      │
  │  └──────┘ └──────┘ └──────┘ └──────┘                      │
  │                                                             │
  │  ┌─ Filter ─────────────────────────────────────────────┐   │
  │  │  Kec: [▼ KENCONG  ▼]  Desa: [▼ Semua           ▼]   │   │
  │  │  PCL: [▼ Semua     ▼]  PML:  [▼ Semua           ▼]   │   │
  │  │  TF:  [▼ Semua     ▼]  Cari: [🔍               ]    │   │
  │  └──────────────────────────────────────────────────────┘   │
  │                                                             │
  │  ┌─ Data Monitoring ──────────────────────────────────────┐ │
  │  │  No  Kec   Desa   SLS      PCL     PML    Status  Aksi│ │
  │  │  1  KENCONG KRATON SAWAH   budi    sari   ✅      📄  │ │
  │  │  2  KENCONG KRATON TEGAL   budi    sari   🔄      📄  │ │
  │  │  3  KENCONG KENCONG ...    ...     ...    ⏳      📄  │ │
  │  │  ...                                                  │ │
  │  └────────────────────────────────────────────────────────┘ │
  │                                               [📥 Export]  │
  └─────────────────────────────────────────────────────────────┘
```

### 📥 Ekspor Data

Klik tombol **"Export"** untuk mengunduh data yang sedang difilter ke file Excel.

---

## 6. Beban Kerja

**URL:** `?page=dashboard&sub=workload`
**Hak Akses:** admin, operator, pegawai

Halaman ini menampilkan peringkat beban kerja petugas.

```
  ┌─────────────────────────────────────────────────────────────┐
  │  📊 BEBAN KERJA PETUGAS                  👤 admin           │
  ├─────────────────────────────────────────────────────────────┤
  │  Role: [▼ PCL ▼]  Kecamatan: [▼ Semua ▼]                   │
  │                                                             │
  │  ┌─ Grafik ──────────────────────────────────────────────┐  │
  │  │  operator ██████████████████████ 1.250 muatan         │  │
  │  │  budi     ██████████████████░░░░ 950 muatan           │  │
  │  │  sari     ██████████████░░░░░░░░ 720 muatan           │  │
  │  │  ...                                                  │  │
  │  └───────────────────────────────────────────────────────┘  │
  │                                                             │
  │  ┌─ Peringkat ───────────────────────────────────────────┐ │
  │  │  #  Petugas    Role   SLS   KK    Usaha  Muatan Detail│ │
  │  │  1  operator   admin   200   500    50    1.250  [👁]  │ │
  │  │  2  budi       pcl     150   400    30     950  [👁]  │ │
  │  │  3  sari       pcl     120   300    25     720  [👁]  │ │
  │  └────────────────────────────────────────────────────────┘ │
  └─────────────────────────────────────────────────────────────┘
```

Klik tombol **👁 (Detail)** untuk melihat rincian beban kerja per petugas.

---

## 7. Wilayah Kerja

**URL:** `?page=dashboard&sub=wilayah`
**Hak Akses:** admin, operator, pegawai, task_force

Halaman ini menampilkan data kebutuhan dan keterisian petugas per kecamatan.

```
  ┌─────────────────────────────────────────────────────────────┐
  │  📋 WILAYAH KERJA                        👤 admin           │
  ├─────────────────────────────────────────────────────────────┤
  │  ┌─ Data Wilayah ─────────────────────────────────────────┐ │
  │  │  Kecamatan   Kebutuhan  Terisi  SLS  Assign  Selesai  ✏️ │
  │  │  KENCONG     PCL: 30   PCL: 25  625   500      300   ✏️ │
  │  │              PML: 10   PML: 8                         │ │
  │  │  GUMUKMAS    PCL: 25   PCL: 20  540   400      250   ✏️ │ │
  │  │              PML: 8    PML: 6                         │ │
  │  │  ...                                                 │ │
  │  └────────────────────────────────────────────────────────┘ │
  └─────────────────────────────────────────────────────────────┘
```

Klik tombol ✏️ untuk mengedit kebutuhan PCL/PML per kecamatan.

---

## 8. Manajemen Petugas

**URL:** `?page=dashboard&sub=petugas`
**Hak Akses:** admin, operator, pegawai

Halaman ini digunakan untuk mengelola data petugas/pengguna sistem.

```
  ┌─────────────────────────────────────────────────────────────┐
  │  👥 MANAJEMEN PETUGAS                    👤 admin           │
  ├─────────────────────────────────────────────────────────────┤
  │  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐   │
  │  │  4   │ │  2   │ │  1   │ │  5   │ │ 3.088│ │  3   │   │
  │  │Admin │ │Oper. │ │Pegawai│ │PCL   │ │Mitra │ │PML   │   │
  │  └──────┘ └──────┘ └──────┘ └──────┘ └──────┘ └──────┘   │
  │                                                             │
  │  [+ Tambah Petugas]  Role: [▼ Semua Role ▼]                │
  │                                                             │
  │  ┌─ Daftar Petugas ───────────────────────────────────────┐ │
  │  │  Username   Role     Status       Terakhir Login   Aksi│ │
  │  │  admin      Admin    🟢 Aktif     2026-05-28 17:00  ✏️🔒│ │
  │  │  operator   Operator 🟢 Aktif     2026-05-27 10:00  ✏️🔒│ │
  │  │  pegawai01  Pegawai  🟢 Aktif     2026-05-26 08:00  ✏️🔒│ │
  │  │  ...                                                    │ │
  │  └────────────────────────────────────────────────────────┘ │
  └─────────────────────────────────────────────────────────────┘
```

### ➕ Tambah Petugas Baru

1. Klik **"Tambah Petugas"**
2. Isi form:

| Field | Keterangan |
|---|---|
| **Username** | Nama pengguna (min 3 karakter, unik) |
| **Email** | Alamat email |
| **Password** | Kata sandi (min 6 karakter) |
| **Role** | PCL / PML / Task Force / Operator / Pegawai |

3. Klik **"Simpan"**

### ✏️ Edit Petugas

1. Klik ikon ✏️ pada petugas yang akan diedit
2. Ubah **Email** dan/atau **Role**
3. Klik **"Simpan"**

### 🔒 Reset Password

1. Klik ikon 🔒 pada petugas
2. Masukkan password baru (min 6 karakter)
3. Klik **"Simpan"**

### 🔴 Nonaktifkan / 🟢 Aktifkan

Klik tombol toggle status untuk mengaktifkan atau menonaktifkan akun petugas.

---

## 9. Audit Log

**URL:** `?page=dashboard&sub=audit`
**Hak Akses:** admin, operator

Halaman ini menampilkan seluruh aktivitas yang tercatat dalam sistem.

```
  ┌─────────────────────────────────────────────────────────────┐
  │  📋 AUDIT LOG                           👤 admin           │
  ├─────────────────────────────────────────────────────────────┤
  │  Modul: [▼ Semua ▼]  User: [▼ Semua ▼]                    │
  │  Dari: [01/05/2026]  Sampai: [28/05/2026]  [🔍 Cari]     │
  │                                                             │
  │  ┌─ Log Aktivitas ────────────────────────────────────────┐ │
  │  │  Waktu              User     Aksi           Modul      │ │
  │  │  28/05 17:00        admin    ✅ Login       Auth       │ │
  │  │  28/05 16:30        admin    📥 Import      Import     │ │
  │  │  28/05 15:00        operator 📝 Assignment   Assignment│ │
  │  │  28/05 14:00        operator 👤 Tambah       Petugas   │ │
  │  │  28/05 13:00        admin    🔄 Edit         Petugas   │ │
  │  └────────────────────────────────────────────────────────┘ │
  └─────────────────────────────────────────────────────────────┘
```

Klik baris log untuk melihat detail perubahan (data sebelum/sesudah).

---

## 10. Laporan & Ekspor

**URL:** `?page=dashboard&sub=report`
**Hak Akses:** admin, operator, pegawai

Halaman ini menyediakan berbagai jenis laporan yang dapat diekspor.

```
  ┌─────────────────────────────────────────────────────────────┐
  │  📑 LAPORAN                            👤 admin           │
  ├─────────────────────────────────────────────────────────────┤
  │                                                             │
  │  ┌──────────────────────┐ ┌──────────────────────┐         │
  │  │  📊 Rekap per        │ │  👤 Rekap per        │         │
  │  │  Kecamatan           │ │  Pencacah (PCL)      │         │
  │  │  [🖨️] [📄] [📊] [📋]│ │  [🖨️] [📄] [📊] [📋]│         │
  │  └──────────────────────┘ └──────────────────────┘         │
  │                                                             │
  │  ┌──────────────────────┐ ┌──────────────────────┐         │
  │  │  👁️ Rekap per        │ │  📋 Detail Wilayah   │         │
  │  │  Pengawas (PML)      │ │  [🖨️] [📄] [📊] [📋]│         │
  │  │  [🖨️] [📄] [📊] [📋]│ │                      │         │
  │  └──────────────────────┘ └──────────────────────┘         │
  │                                                             │
  │  ┌──────────────────────┐                                  │
  │  │  📈 Dashboard        │                                  │
  │  │  Snapshot            │                                  │
  │  │  [🖨️] [📄] [📊] [📋]│                                  │
  │  └──────────────────────┘                                  │
  │                                                             │
  └─────────────────────────────────────────────────────────────┘
```

### 📋 Jenis Laporan

| Laporan | Keterangan |
|---|---|
| **Rekap per Kecamatan** | Total SLS, assign, proses, selesai per kecamatan |
| **Rekap per Pencacah (PCL)** | Beban kerja per petugas PCL |
| **Rekap per Pengawas (PML)** | Beban kerja per petugas PML |
| **Detail Wilayah** | Data per SLS lengkap |
| **Dashboard Snapshot** | Ringkasan eksekutif + rekap per kecamatan |

### 🎯 Format Ekspor

| Tombol | Format | Aksi |
|---|---|---|
| 🖨️ **Print** | HTML | Membuka tampilan cetak di browser |
| 📄 **PDF** | PDF | Mengunduh file PDF (ukuran A4 landscape) |
| 📊 **Excel** | XLSX | Mengunduh file Excel |
| 📋 **CSV** | CSV | Mengunduh file CSV (bisa dibuka di Excel) |

---

## 11. Tabel & Filter DataTables

Hampir semua tabel di aplikasi ini menggunakan **DataTables** yang memiliki fitur-fitur berikut:

```
  ┌─────────────────────────────────────────────────────────┐
  │  🔍 Search: [_________________________]                 │
  ├────┬──────────┬──────────┬──────────┬──────────┬───────┤
  │  # │ Kolom 1  │ Kolom 2  │ Kolom 3  │ ...      │ Aksi  │
  ├────┼──────────┼──────────┼──────────┼──────────┼───────┤
  │  1 │ Data ... │ ...      │ ...      │ ...      │ [btn] │
  │  2 │ ...      │ ...      │ ...      │ ...      │ [btn] │
  │  3 │ ...      │ ...      │ ...      │ ...      │ [btn] │
  ├────┴──────────┴──────────┴──────────┴──────────┴───────┤
  │  Show [25 ▼] entries           Halaman ◀ 1 2 3 ... ▶  │
  └─────────────────────────────────────────────────────────┘
```

### 🎯 Fitur DataTables

| Fitur | Cara Pakai |
|---|---|
| **🔍 Pencarian** | Ketik kata kunci di kotak "Search" untuk mencari ke semua kolom |
| **↕️ Urutkan** | Klik judul kolom untuk mengurutkan (↑ naik / ↓ turun) |
| **📄 Jumlah baris** | Pilih jumlah baris per halaman: 10, 25, 50, 100, atau Semua |
| **◀ ▶ Halaman** | Navigasi halaman dengan tombol prev/next atau angka halaman |

---

## 12. Pemecahan Masalah Umum

### 🚫 Halaman Tidak Ditemukan (404)

| Penyebab | Solusi |
|---|---|
| URL salah | Periksa kembali URL, gunakan menu navigasi sidebar |
| Tidak punya akses | Hubungi admin untuk diberikan hak akses |

### 🔒 Tidak Punya Izin (403)

| Penyebab | Solusi |
|---|---|
| Role tidak sesuai | Login dengan akun yang memiliki hak akses sesuai |

### ⚠️ DataTables Warning

| Pesan | Solusi |
|---|---|
| `Requested unknown parameter` | Refresh halaman (F5) |
| `Incorrect column count` | Refresh halaman, data masih kosong |
| Tabel tidak muncul | Pastikan koneksi internet stabil (CDN DataTables) |

### 🐌 Halaman Lambat

| Penyebab | Solusi |
|---|---|
| Data terlalu banyak | Gunakan filter untuk mempersempit data |
| Koneksi internet lambat | DataTables dan Chart.js dimuat dari CDN |

### ❌ Gagal Import Excel

| Penyebab | Solusi |
|---|---|
| Format header salah | Gunakan template, pastikan kolom `kdkec` dan `kddesa` ada |
| File terlalu besar | Maksimal 20 MB |
| Bukan file Excel | Gunakan format .xlsx, .xls, atau .csv |

### 🔄 Session Habis

Jika tiba-tiba kembali ke halaman login:
- Session otomatis habis setelah **2 jam** tidak ada aktivitas
- Login kembali untuk melanjutkan

---

## 📝 Catatan Penting

1. **Cache**: Data dashboard diperbarui setiap **60 detik**. Perubahan data mungkin perlu waktu untuk muncul.
2. **CSRF Protection**: Semua form POST dilindungi token CSRF. Jangan membuka halaman terlalu lama sebelum submit.
3. **Backup Otomatis**: Sistem melakukan backup database secara otomatis. Hubungi admin untuk informasi pemulihan data.
4. **Browser**: Dianjurkan menggunakan **Google Chrome** atau **Firefox** versi terbaru.

---

```
  ╔══════════════════════════════════════════════════════════════╗
  ║              BPS KABUPATEN JEMBER 2026                       ║
  ║                Sensus Ekonomi 2026                          ║
  ╚══════════════════════════════════════════════════════════════╝
```

_Dokumen ini dibuat otomatis pada: 28 Mei 2026_
