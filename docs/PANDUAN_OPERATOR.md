# Panduan Penggunaan Dashboard SE2026

## Untuk Operator BPS Kabupaten Jember

---

## Daftar Isi

1. [Apa itu Dashboard SE2026?](#1-apa-itu-dashboard-se2026)
2. [Login](#2-login)
3. [Tampilan Utama (Dashboard)](#3-tampilan-utama-dashboard)
4. [Import Data SIPW](#4-import-data-sipw)
5. [Assign Petugas](#5-assign-petugas)
6. [Monitoring Wilayah](#6-monitoring-wilayah)
7. [Laporan & Ekspor](#7-laporan--ekspor)
8. [Beban Petugas (Workload)](#8-beban-petugas-workload)
9. [Riwayat Aktivitas (Audit Log)](#9-riwayat-aktivitas-audit-log)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Apa itu Dashboard SE2026?

Dashboard SE2026 adalah aplikasi untuk memantau pelaksanaan **Sensus Ekonomi 2026** di Kabupaten Jember. Fungsinya:

- **Import data** SLS dari file Excel SIPW
- **Assign petugas** (PCL, PML, Task Force) ke SLS yang menjadi tanggung jawabnya
- **Monitor progress** pendataan secara real-time
- **Export laporan** Excel/PDF untuk pimpinan

---

## 2. Login

### 2.1. Membuka Aplikasi

Buka browser (Chrome, Firefox, Edge) dan akses alamat:

```
https://dashboard-se2026.bpsjember.id
```

### 2.2. Halaman Login

Anda akan melihat halaman login seperti berikut:

```
  ┌─────────────────────────────────────┐
  │            Dashboard SE2026          │
  │         BPS Kabupaten Jember         │
  │                                     │
  │  ┌─────────────────────────────────┐│
  │  │ 👤 Masukkan username            ││
  │  └─────────────────────────────────┘│
  │  ┌─────────────────────────────────┐│
  │  │ 🔒 Masukkan password            ││
  │  └─────────────────────────────────┘│
  │                                     │
  │  ┌─────────────────────────────────┐│
  │  │          Login                  ││
  │  └─────────────────────────────────┘│
  │                                     │
  │       Sistem Internal BPS Jember    │
  └─────────────────────────────────────┘
```

### 2.3. Cara Login

1. **Username**: Isi dengan username yang diberikan (contoh: `admin_jember`)
2. **Password**: Isi dengan password yang diberikan
3. Klik tombol **Login**

### 2.4. Jika Lupa Password

Hubungi administrator BPS Jember untuk mereset password.

### 2.5. Logout

Klik nama pengguna di pojok kanan atas, lalu pilih **Logout**.

### 2.6. Session Timeout

Demi keamanan, Anda akan otomatis logout jika tidak melakukan aktivitas selama **30 menit**. Simpan pekerjaan Anda secara berkala.

---

## 3. Tampilan Utama (Dashboard)

Setelah login, Anda akan melihat halaman utama:

### 3.1. Bagian-bagian Halaman

```
  ┌──────────────────────────────────────────────────────────┐
  │ BPS Jember    Dashboard SE2026            👤 Admin       │ ← Navbar
  │ ┌─────────┐  ┌──────────────────────────────────────────┐│
  │ │ 📊      │  │                                          ││
  │ │ Dashboard│  │  🗺️ Kecamatan: 31     📍 Desa: 248      ││
  │ │         │  │  💾 Total SLS: 5215    👨‍👩‍👧 Total KK: ...  ││ ← Kartu Statistik
  │ │ 📥      │  │  🏪 Usaha: ...         ⚖️ Muatan: ...    ││
  │ │ Import  │  │                                          ││
  │ │         │  │  PCL: 120     PML: 45     TF: 15        ││
  │ │ 📋      │  │  ┌──────────────────┐ ┌───────────────┐ ││
  │ │ Assign  │  │  │  📊 Muatan per   │ │  📊 Beban     │ ││
  │ │         │  │  │  Kecamatan       │ │  Pencacah     │ ││ ← Grafik
  │ │ 📈      │  │  │  (Grafik Batang) │ │  (Diagram     │ ││
  │ │Monitor  │  │  │                  │ │   Lingkaran)  │ ││
  │ │         │  │  └──────────────────┘ └───────────────┘ ││
  │ │ 📄      │  │  ┌──────────────────────────────────────┐││
  │ │ Laporan │  │  │  Progress per Kecamatan (Grafik)     │││
  │ │         │  │  └──────────────────────────────────────┘││
  │ │ 🔄      │  │  ┌──────────────────────────────────────┐││
  │ │Workload │  │  │  Tabel Progress per Kecamatan        │││
  │ │         │  │  │  ┌────────┬──────┬──────┬──────┬───┐ │││
  │ │ 👥      │  │  │  │Kecamatan│Total │Assign│Selesai│% │ │││ ← Tabel
  │ │ Petugas │  │  │  ├────────┼──────┼──────┼──────┼───┤ │││
  │ │         │  │  │  │Patrang │  215 │  200 │  150 │70%│ │││
  │ │ 📋      │  │  │  │...     │  ... │  ... │  ... │...│ │││
  │ │ Audit   │  │  │  └────────┴──────┴──────┴──────┴───┘ │││
  │ └─────────┘  │  └──────────────────────────────────────┘││
  │  Sidebar     │                   Konten Utama           ││
  └──────────────┴──────────────────────────────────────────┘│
  │                © BPS Kabupaten Jember                    │ ← Footer
  └──────────────────────────────────────────────────────────┘
```

### 3.2. Menu di Samping Kiri (Sidebar)

| Ikon | Menu | Fungsi |
|------|------|--------|
| 📊 | Dashboard | Halaman utama (anda di sini) |
| 📥 | Import SIPW | Upload data SLS dari Excel |
| 📋 | Assign Petugas | Menugaskan PCL/PML/TF ke SLS |
| 📈 | Monitoring | Pantau progress pendataan |
| 📄 | Laporan | Cetak/export laporan |
| 🔄 | Workload | Lihat beban kerja petugas |
| 👥 | Petugas | Kelola data petugas |
| 📋 | Audit Log | Riwayat aktivitas sistem |

### 3.3. Kartu Statistik (Angka Besar)

Deretan kartu di bagian atas menampilkan angka penting:
- **Kecamatan**: Jumlah kecamatan di Jember (31)
- **Desa**: Jumlah desa/kelurahan
- **Total SLS**: Jumlah SLS yang sudah diimport
- **Total KK**: Jumlah Kepala Keluarga
- **Total Usaha**: Jumlah unit usaha
- **Total Muatan**: Total beban pendataan
- **PCL/PML/TF**: Jumlah petugas aktif

> **Tips**: Angka-angka ini langsung memberi gambaran seberapa besar cakupan SE2026 di Jember.

---

## 4. Import Data SIPW

Menu ini digunakan untuk memasukkan data SLS dari file Excel SIPW ke dalam sistem.

### 4.1. Alur Import

```
  Upload File → Preview Data → Proses Import → Selesai
```

### 4.2. Cara Import

#### Langkah 1: Buka Menu Import

- Klik **Import SIPW** di menu samping kiri
- Anda akan melihat halaman upload:

```
  ┌──────────────────────────────────────────────┐
  │  📥 Import SIPW                              │
  │  Data existing: 5.215 SLS   31 Kec   248 Desa│
  │                                              │
  │  ┌──────────────────────────────────────────┐│
  │  │  Pilih File Excel SIPW                   ││
  │  │  ┌─────────────────────────────────┐     ││
  │  │  │ [Pilih File] Tidak ada file... │     ││
  │  │  └─────────────────────────────────┘     ││
  │  │  Format: XLSX, XLS, atau CSV.            ││
  │  │  Maksimal 20 MB.                         ││
  │  │                              [Upload]    ││
  │  └──────────────────────────────────────────┘│
  │                                              │
  │  Format Kolom yang Didukung:                 │
  │  ✅ Wajib: kode_kecamatan, kode_desa         │
  │  👍 Rekomendasi: nama_sls, total_muatan      │
  │  ℹ️ Lainnya: id_frs, kk, btt, bku, usaha    │
  │                                              │
  │  ┌── Riwayat Import ────────────────────────┐│
  │  │ Batch ID │ File     │ Status  │ Waktu   ││
  │  │ abc123.. │ data.xlsx│ ✅      │ 10:30   ││
  │  └──────────┴──────────┴─────────┴─────────┘│
  └──────────────────────────────────────────────┘
```

#### Langkah 2: Pilih File

1. Klik tombol **Pilih File**
2. Cari file Excel SIPW (format `.xlsx`, `.xls`, atau `.csv`)
3. Pastikan file berisi kolom **kode_kecamatan** dan **kode_desa**

> **Catatan**: Nama kolom bisa fleksibel. Contoh: `kdkec`, `kode_kec`, `kode_kecamatan`, `kec` — semua dikenali.

#### Langkah 3: Upload & Preview

1. Klik tombol **Upload & Preview**
2. Sistem akan membaca file dan menampilkan **pratinjau data**:

```
  ┌── Preview Data ──────────────────────────────────┐
  │ ⚠️ File: data_se2026.xlsx     5.215 baris  256KB│
  │                                                  │
  │  Mapping kolom: ✅ 10/10 kolom terpetakan       │
  │                                                  │
  │  ┌────┬────────┬──────┬────────┬──────┬───┐     │
  │  │  # │ Kec    │ Desa │ SLS    │ Muatan│ ✅│     │
  │  ├────┼────────┼──────┼────────┼──────┼───┤     │
  │  │  1 │ Patrang│ Slawu│ SLS001 │   125 │ ✅│     │
  │  │  2 │ Patrang│ Jember│SLS002 │    98 │ ✅│     │
  │  │ ...│ ...    │ ...  │ ...    │   ... │...│     │
  │  └────┴────────┴──────┴────────┴──────┴───┘     │
  │                                                  │
  │  Menampilkan 50 dari 5.215 baris.                │
  │            [Proses Import]    [Batal]            │
  └──────────────────────────────────────────────────┘
```

**Cek pratinjau:**
- ✅ **Centang hijau** = data valid
- ❌ **Silang merah** = data bermasalah (arahkan kursor untuk detail error)
- Periksa apakah jumlah baris sesuai dengan file Anda

#### Langkah 4: Proses Import

1. Klik tombol **Proses Import**
2. Konfirmasi dengan klik **OK** pada kotak dialog
3. Tunggu hingga proses selesai (tergantung jumlah baris)

#### Langkah 5: Hasil Import

Setelah selesai, akan muncul notifikasi:

```
  ✅ Import berhasil! 5.200 baru, 15 diupdate (Batch: abc123xy)
```

Cek di bagian **Riwayat Import** untuk melihat status:
- ✅ **success** = Semua baris berhasil
- ⚠️ **partial** = Sebagian berhasil, sebagian gagal
- ❌ **failed** = Gagal semua

### 4.3. Jika Import Gagal

Penyebab umum dan solusinya:

| Masalah | Solusi |
|---------|--------|
| "Kolom wajib tidak ditemukan" | Pastikan file ada kolom `kode_kecamatan` dan `kode_desa` |
| "File tidak mengandung data" | Cek apakah file Excel kosong atau hanya header |
| "Ukuran file melebihi batas" | File >20 MB, gunakan file yang lebih kecil |
| Format file tidak didukung | Simpan sebagai `.xlsx` atau `.csv` |

---

## 5. Assign Petugas

Menu ini digunakan untuk menugaskan **PCL** (Pencacah), **PML** (Pengawas), dan **Task Force** ke setiap SLS.

### 5.1. Buka Halaman Assign

Klik **Assign Petugas** di menu samping kiri.

### 5.2. Tampilan Halaman

```
  ┌──────────────────────────────────────────────────┐
  │  📋 Assignment Petugas                           │
  │  [Bulk Assign] [Beban Petugas]                   │
  │                                                  │
  │  Total SLS: 5215  Assign: 4000  Belum: 1215     │
  │  Proses: 2000     Selesai: 1500  Petugas: 180   │
  │                                                  │
  │  ┌── Filter ───────────────────────────────────┐ │
  │  │ Kecamatan: [▼ Semua]  Desa: [▼ Semua]      │ │
  │  │ Status: [▼ Semua]     Cari: [........]      │ │
  │  │                     [🔍 Cari] [Reset]      │ │
  │  └──────────────────────────────────────────────┘ │
  │                                                  │
  │  ┌─ ✅ Sudah Assign (4000) ─── ⏳ Belum ──────┐ │
  │  │ SLS │ Desa │ Kec │ Muatan │ PCL │ PML │ ...│ │
  │  ├─────┼──────┼─────┼────────┼─────┼─────┼────┤ │
  │  │S001 │Slawu │Ptrng│  125   │Amir │Budi │... │ │
  │  │S002 │Jbr   │Ptrng│   98   │Citra│Budi │... │ │
  │  └─────┴──────┴─────┴────────┴─────┴─────┴────┘ │
  └──────────────────────────────────────────────────┘
```

### 5.3. Assign Perorangan

1. Pilih tab **Belum Assign** (untuk melihat SLS yang belum punya petugas)
2. Gunakan filter **Kecamatan** dan **Desa** untuk mempersempit pencarian
3. Klik tombol **Assign** pada baris SLS yang ingin ditugaskan
4. Akan muncul form:

```
  ┌─────── Assign Petugas ──────────────────────┐
  │                                             │
  │  SLS: SLS001 — Desa Slawu, Kec Patrang     │
  │                                             │
  │  Pencacah (PCL):    [▼ Pilih PCL ▼]        │
  │  Pengawas (PML):    [▼ Pilih PML ▼]        │
  │  Task Force:         [▼ Pilih TF ▼]         │
  │                                             │
  │           [Simpan]     [Batal]              │
  └─────────────────────────────────────────────┘
```

5. Pilih petugas dari dropdown
6. Klik **Simpan**

### 5.4. Bulk Assign (Assign Massal)

Untuk mengassign banyak SLS sekaligus:

1. Klik tombol **Bulk Assign**
2. Pilih **Kecamatan** dan **Desa** tujuan
3. Pilih **PCL** dan **PML** yang akan ditugaskan
4. Klik **Assign Semua**

> **Tips**: Bulk assign sangat berguna saat pertama kali mengatur petugas untuk satu desa penuh.

### 5.5. Melihat Beban Petugas

Klik tombol **Beban Petugas** untuk melihat:
- Berapa banyak SLS yang ditugaskan ke setiap PCL
- Progress penyelesaian masing-masing petugas
- Rata-rata beban per petugas

### 5.6. Filter yang Tersedia

| Filter | Fungsi |
|--------|--------|
| Kecamatan | Pilih satu kecamatan |
| Desa | Pilih satu desa (muncul setelah pilih kecamatan) |
| Status | Belum / Proses / Selesai |
| Cari | Ketik nama SLS atau petugas |

---

## 6. Monitoring Wilayah

Menu ini untuk memantau progress pendataan secara detail sampai level SLS.

### 6.1. Buka Halaman Monitoring

Klik **Monitoring** di menu samping kiri.

### 6.2. Tampilan Halaman

```
  ┌──────────────────────────────────────────────────┐
  │  📈 Monitoring Wilayah                  10:30   │
  │                                                  │
  │  Total SLS: 5215   Assign: 4000                 │
  │  Proses: 2000       Selesai: 1500               │
  │                                                  │
  │  ┌── Filter ───────────────────────────────────┐ │
  │  │ Kec: [▼ Semua]│Desa:[▼ Semua]│PCL:[▼ Smua]│ │
  │  │ PML: [▼ Semua]│TF: [▼ Semua]│[Excel][↺]  │ │
  │  └──────────────────────────────────────────────┘ │
  │                                                  │
  │  ┌─── Tabel Data ───────────────────────────────┐│
  │  │ Kec │ Desa │ SLS │ KK│Usaha│Muatan│PCL│...✅││
  │  ├─────┼──────┼─────┼───┼─────┼──────┼───┼───┼─┤│
  │  │Kaliw│Kali..│S001 │125│  50 │ 200 │Ami│Bud│⚪││
  │  │Kaliw│Kali..│S002 │ 98│  30 │ 150 │Cit│Bud│🟡││
  │  │ ... │  ... │ ... │...│  ...│  ...│...│...│.││
  │  └─────┴──────┴─────┴───┴─────┴──────┴───┴───┴─┘│
  │                    Halaman 1 dari 209             │
  └──────────────────────────────────────────────────┘
```

### 6.3. Membaca Status

Status pendataan ditampilkan dengan badge berwarna:

| Badge | Status | Arti |
|-------|--------|------|
| ⚪ **Belum** (abu-abu) | Belum | SLS belum didata |
| 🟡 **Proses** (kuning) | Proses | Sedang didata |
| 🟢 **Selesai** (hijau) | Selesai | Data sudah masuk |

### 6.4. Menggunakan Filter

1. **Kecamatan**: Pilih kecamatan untuk melihat SLS di wilayah tersebut
2. **Desa**: Setelah pilih kecamatan, pilih desa (filter berantai/cascade)
3. **PCL/PML/TF**: Filter berdasarkan petugas tertentu
4. **Search**: Ketik kata kunci untuk mencari real-time

> **Tips**: Gunakan filter kecamatan + desa untuk melihat progress satu wilayah secara detail.

### 6.5. Export ke Excel

Klik tombol **Excel** untuk mendownload data monitoring dalam format Excel.

Hasil export akan menyertakan filter yang sedang aktif. Jika ingin semua data, pastikan filter dalam posisi "Semua".

### 6.6. Mengurutkan Data

Klik header kolom untuk mengurutkan:
- Klik sekali: urutan A-Z (kecil ke besar)
- Klik dua kali: urutan Z-A (besar ke kecil)

### 6.7. Navigasi Halaman

- Gunakan nomor halaman di pojok kanan bawah tabel
- Atur jumlah baris per halaman: 10, 25, 50, 100, atau Semua
- **"Semua"** hanya direkomendasikan untuk jumlah data sedikit (<1000 baris)

---

## 7. Laporan & Ekspor

Menu untuk membuat laporan siap saji untuk pimpinan dalam berbagai format.

### 7.1. Buka Halaman Laporan

Klik **Laporan** di menu samping kiri.

### 7.2. Jenis Laporan

```
  ┌──────────────────────────────────────────────────┐
  │  📄 Laporan & Ekspor                             │
  │                                                  │
  │  ┌─────────────────┐  ┌──────────────────────┐  │
  │  │ 🗺️ Rekap per    │  │ 👤 Rekap per        │  │
  │  │   Kecamatan     │  │   Pencacah (PCL)     │  │
  │  │                 │  │                      │  │
  │  │ Total SLS,      │  │ Beban kerja, status  │  │
  │  │ muatan, progress│  │ penyelesaian per PCL │  │
  │  │ per kecamatan   │  │                      │  │
  │  │                 │  │                      │  │
  │  │ [Cetak][PDF]    │  │ [Cetak][PDF]         │  │
  │  │ [Excel][CSV]    │  │ [Excel][CSV]         │  │
  │  └─────────────────┘  └──────────────────────┘  │
  │                                                  │
  │  ┌─────────────────┐  ┌──────────────────────┐  │
  │  │ 🛡️ Rekap per    │  │ 📊 Progress per     │  │
  │  │   Pengawas(PML) │  │   Kecamatan (Grafik) │  │
  │  │ ...             │  │ ...                  │  │
  │  └─────────────────┘  └──────────────────────┘  │
  └──────────────────────────────────────────────────┘
```

### 7.3. Cara Export

1. Pilih jenis laporan yang diinginkan
2. Pilih format:

| Tombol | Format | Kegunaan |
|--------|--------|----------|
| 🖨️ **Cetak** | Print (Browser) | Cetak langsung ke printer |
| 📕 **PDF** | PDF | Laporan formal, kirim via WA/Email |
| 📗 **Excel** | XLSX | Olah data lanjutan |
| 📄 **CSV** | CSV | Import ke aplikasi lain |

3. File akan otomatis terdownload atau terbuka di tab baru

### 7.4. Cetak Rekap per Kecamatan

Paling sering digunakan untuk laporan harian:
- Menampilkan **31 kecamatan** dengan total SLS, muatan, assign, proses, selesai, dan persentase progress
- Cocok untuk rapat koordinasi pagi

---

## 8. Beban Petugas (Workload)

Menu untuk melihat distribusi beban kerja petugas.

### 8.1. Buka Halaman Workload

Klik **Workload** di menu samping kiri.

### 8.2. Yang Ditampilkan

Halaman ini menampilkan tabel beban kerja setiap petugas:

```
  ┌──────────────────────────────────────────────────┐
  │  🔄 Beban Petugas                                │
  │                                                  │
  │  ┌── Filter ─────────────────────┐               │
  │  │ Role: [▼ Semua]               │               │
  │  │ Kecamatan: [▼ Semua]          │               │
  │  │                     [ Cari ]  │               │
  │  └───────────────────────────────┘               │
  │                                                  │
  │  ┌────┬─────────┬─────────┬──────┬──────┬──────┐│
  │  │  # │ Nama    │ Role    │ Tugas│Proses│Slsai ││
  │  ├────┼─────────┼─────────┼──────┼──────┼──────┤│
  │  │  1 │ Amir    │ PCL     │  30  │  20  │  10  ││
  │  │  2 │ Budi    │ PCL     │  28  │  15  │  13  ││
  │  │  3 │ Citra   │ PCL     │  32  │  25  │   7  ││
  │  └────┴─────────┴─────────┴──────┴──────┴──────┘│
  └──────────────────────────────────────────────────┘
```

Kegunaan:
- Melihat apakah beban petugas sudah merata
- Mengetahui petugas mana yang perlu dibantu
- Bahan evaluasi untuk penugasan ulang

---

## 9. Riwayat Aktivitas (Audit Log)

Mencatat semua aktivitas penting di sistem.

### 9.1. Buka Halaman Audit

Klik **Audit Log** di menu samping kiri.

### 9.2. Yang Tercatat

| Aktivitas | Tercatat |
|-----------|----------|
| Login/logout | ✅ |
| Import file | ✅ (siapa, file, berapa baris) |
| Assign petugas | ✅ (siapa, perubahan) |
| Export data | ✅ |

### 9.3. Filter Log

Gunakan filter **tanggal** dan **aksi** untuk mencari aktivitas tertentu.

---

## 10. Troubleshooting

### 10.1. Error: "Sesi tidak valid"

**Penyebab**: Session habis karena tidak ada aktivitas >30 menit

**Solusi**:
1. Login ulang dengan username dan password
2. Data yang belum disimpan akan hilang, simpan pekerjaan secara berkala

### 10.2. Error: "CSRF token invalid"

**Penyebab**: Halaman terlalu lama terbuka sebelum submit

**Solusi**:
1. Refresh halaman
2. Isi ulang data
3. Submit kembali

### 10.3. Error: "Database connection failed"

**Penyebab**: Masalah koneksi ke server database

**Solusi**:
1. Tunggu 1-2 menit
2. Refresh halaman
3. Jika masih error, hubungi administrator

### 10.4. Import: "Kolom wajib tidak ditemukan"

**Penyebab**: File Excel tidak memiliki kolom yang dikenali

**Solusi**:
1. Pastikan file memiliki kolom **kode_kecamatan** atau **kdkec**
2. Pastikan file memiliki kolom **kode_desa** atau **kddesa**
3. Nama kolom bisa: `kdkec`, `kode_kec`, `kode_kecamatan`, `kec`
4. Cek baris pertama file: harus berisi nama kolom (header)

### 10.5. Import: "File tidak mengandung data"

**Penyebab**: File Excel kosong atau baris pertama tidak terbaca

**Solusi**:
1. Buka file Excel, pastikan ada data (tidak hanya header)
2. Hapus baris kosong di awal file
3. Simpan ulang sebagai `.xlsx`

### 10.6. Export: File tidak terdownload

**Penyebab**: Pop-up blocker browser, atau file terlalu besar

**Solusi**:
1. Izinkan pop-up untuk situs ini
2. Gunakan filter untuk memperkecil data sebelum export
3. Export per kecamatan, bukan semua data sekaligus

### 10.7. Halaman Loading Lama

**Penyebab**: Data terlalu banyak, koneksi lambat, atau cache perlu dibersihkan

**Solusi**:
1. Gunakan **filter** untuk mempersempit data
2. Gunakan **pagination**: set 25 atau 50 baris per halaman
3. Jangan pilih **"Semua"** baris jika data >500 baris
4. Coba refresh halaman

### 10.8. Tabel Tidak Muncul

**Penyebab**: Filter terlalu ketat atau data kosong

**Solusi**:
1. Klik **Reset** untuk menghapus semua filter
2. Pastikan Anda sudah melakukan import data
3. Pastikan Anda sudah melakukan assign petugas

### 10.9. Lupa Harus Ngapain? (Alur Kerja Normal)

```
  1. IMPORT data SIPW
     ↓
  2. ASSIGN petugas ke SLS
     ↓
  3. MONITOR progress pendataan
     ↓
  4. EXPORT laporan untuk pimpinan
```

### 10.10. Kontak Bantuan

Jika masalah tidak terselesaikan:

| Keperluan | Hubungi |
|-----------|---------|
| Lupa password | Administrator BPS Jember |
| Error sistem | Tim IT BPS Jember |
| Data tidak sesuai | Koordinator SE2026 |

---

## Lampiran: Istilah Penting

| Istilah | Kepanjangan | Arti |
|---------|-------------|------|
| SE2026 | Sensus Ekonomi 2026 | Sensus nasional untuk data ekonomi |
| BPS | Badan Pusat Statistik | Lembaga penyelenggara sensus |
| SLS | Satuan Lingkungan Setempat | Wilayah pendataan terkecil (RT/RW/dusun) |
| PCL | Pencacah | Petugas yang mendata langsung ke lapangan |
| PML | Pengawas | Petugas yang mengawasi PCL |
| TF | Task Force | Petugas khusus untuk wilayah tertentu |
| SIPW | Sistem Informasi | Sumber data SLS dari BPS RI |
| KK | Kepala Keluarga | Satuan rumah tangga |
| BTT | Bangunan Tempat Tinggal | Jumlah rumah di SLS |
| BKU | Bangunan Kegiatan Usaha | Jumlah tempat usaha di SLS |
| Muatan | Total Beban | Jumlah total (KK + BTT + BKU + Usaha) |
| Batch ID | Kode Import | ID unik setiap kali import file |

---

*Dokumen ini diperuntukkan bagi operator BPS Kabupaten Jember.*
*Versi 1.0 — Mei 2026*
