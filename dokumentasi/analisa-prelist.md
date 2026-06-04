Saya akan menganalisis file Excel tersebut secara menyeluruh. Mari mulai dengan membaca struktur dan konten datanya.

Sekarang saya akan menggali lebih dalam untuk menganalisis data kuantitatif dari sheet-sheet kunci:

Sekarang saya punya gambaran lengkap. Mari bersihkan file sementara dan buat laporan analisis komprehensif:

Berikut adalah laporan analisis komprehensif berdasarkan eksplorasi mendalam terhadap file tersebut:

---

# Laporan Analisis: PRELIST SE2026.xlsx

## 1. Struktur Data — Gambaran Umum

File ini berisi **46 sheet** dengan total lebih dari **300.000+ baris data** yang mencakup seluruh wilayah Jawa Timur. Strukturnya terbagi menjadi beberapa lapisan hierarki:

| Sheet | Nama | Baris | Granularitas |
|---|---|---|---|
| 0 | Prelist SE2026 | 44 | Ringkasan per Kab/Kota (38 wilayah Jatim) |
| 1 | Prelist SE2026 kecamatan | 669 | Ringkasan per Kecamatan |
| 2 | Prelist SE2026 desa | 8.497 | Ringkasan per Desa/Kelurahan |
| 3 | SBR | 15.997 | Pivot data SBR per desa/kecamatan |
| 4 | subsektorA | 191.574 | Data subsektor per SLS (granular tertinggi) |
| 5 | plkumkm | 10.868 | Data PLKUMKM per SLS |
| 6–43 | Prelist SE2026_35XX | Variatif | Data SLS per Kabupaten/Kota (35 wilayah) |

---

## 2. Identifikasi Atribut Data

### Atribut Identifikasi Wilayah
| Kolom | Keterangan |
|---|---|
| `idsls` | ID unik SLS (14 digit: kode kab+kec+desa+sls) |
| `iddesa` | ID desa (10 digit) |
| `kec`, `nm_kec` | Kode dan nama kecamatan |
| `desa`, `nm_desakel` | Kode dan nama desa/kelurahan |
| `nama_sls` | Nama satuan lingkungan setempat (RT/RW/Dusun) |

### Atribut Beban Kerja / Muatan Sensus
| Kolom | Keterangan |
|---|---|
| `SBR` | Jumlah usaha dari Sumber Basis Referensi |
| `usaha SE2016` | Jumlah usaha hasil SE2016 (baseline) |
| `RTUP ST2023` | Rumah Tangga Usaha Pertanian (ST2023) |
| `ST2023 UTP` | Usaha Tani Perorangan |
| `ST2023 subsektor` | Jumlah subsektor pertanian |
| `jml kk` | Jumlah kepala keluarga |
| `usaha wilkerstat / plkumkm` | Usaha dari wilkerstat atau PLKUMKM |
| `Total muatan RS kolom 11` | Total beban tugas per SLS |
| `mall`, `pasar`, `pertokoan`, `sentra ekonomi`, `kdm` | Lokasi usaha khusus |

### Atribut Petugas
| Kolom | Keterangan |
|---|---|
| `ppl` | Jumlah Petugas Pencacah Lapangan |
| `pml` | Jumlah Pengawas Mitra Lapangan |
| `n_sls` | Jumlah SLS yang dicakup |
| `sls/ppl` | Rasio SLS per PPL |
| `rata2 beban per ppl` | Rata-rata beban kerja per PPL |

### Atribut Estimasi Waktu (hanya Sheet 0)
Kolom waktu sangat detail: `kue-P menit`, `L usaha nontani menit`, `L usaha tani menit`, `L sosek menit`, `antar sls menit`, hingga `total waktu jam` dan `total waktu per petugas`.

### Atribut Klasifikasi Usaha (Sheet 0)
`UB` (Usaha Besar), `UM` (Usaha Menengah), `UMK` (Usaha Mikro Kecil), `perubahan prelist`.

---

## 3. Temuan Masalah Kualitas Data

### 🔴 Masalah Kritis
1. **Formula Excel masih tersimpan sebagai string** — Kolom `subsektor` di sheet per-kabupaten menyimpan formula `=XLOOKUP(...)` sebagai teks, bukan nilai hasil. Ini berarti data subsektor per SLS **belum teresolusi** saat dibaca programatik.
2. **Nilai "tidak ketemu"** — Ditemukan di beberapa sheet (3517, 3523, 3524, 3576, 3577, 3578) pada kolom `RTUP ST2023` dan `UTP`. Ini menandakan SLS yang tidak cocok dengan data referensi ST2023.
3. **Referensi `#REF!`** — Sheet 3523 mengandung formula dengan referensi rusak (`#REF!`), menandakan data sumber yang dirujuk sudah berubah.

### 🟡 Masalah Sedang
4. **Header multi-baris** — Semua sheet menggunakan header 3 baris (judul, nama kolom, nomor kolom). Parsing harus mengambil baris ke-2 sebagai header aktual.
5. **Kolom tidak konsisten antar sheet** — Sheet 3501 punya 17 kolom, sheet 3505/3506 punya 19 kolom (ada `mall`, `pasar`, `kdm`). Perlu normalisasi.
6. **Kolom `PPL` dan `PML` kosong** di sheet kecamatan dan desa — Alokasi petugas belum diisi di level tersebut.
7. **Kolom formula turunan** (`=LEFT(A5,10)`, `=MID(C5,5,3)`) — Kolom `iddesa`, `kec`, `desa` diturunkan dari `idsls` via formula, bukan nilai statis.

### 🟢 Catatan Positif
8. **`idsls` 14 digit** adalah kunci unik yang sangat baik — mengandung hierarki wilayah lengkap (kab+kec+desa+sls) sehingga bisa di-parse tanpa join tambahan.
9. **Sheet 0 (ringkasan Kab/Kota)** sudah bersih dengan nilai numerik aktual — cocok langsung untuk KPI dashboard.

---

## 4. Nilai Manfaat untuk Dashboard

Data ini sangat bernilai tinggi. Berikut pemetaannya:

| Kebutuhan Dashboard | Data Tersedia | Kualitas |
|---|---|---|
| KPI total usaha per wilayah | UB, UM, UMK, SBR, SE2016 | ✅ Baik |
| Perbandingan SE2016 vs SE2026 | `usaha SE2016` vs `SBR`/`wilkerstat` | ✅ Baik |
| Distribusi beban kerja PPL | `rata2 beban per ppl`, `sls/ppl` | ✅ Baik |
| Estimasi waktu lapangan | Kolom waktu (menit/jam) | ✅ Baik (Sheet 0) |
| Peta sebaran usaha per kecamatan | Sheet kecamatan + kode wilayah | ✅ Baik |
| Drill-down sampai level SLS | Sheet per-kabupaten | ⚠️ Perlu cleaning |
| Monitoring alokasi petugas | `ppl`, `pml` | ⚠️ Sebagian kosong |
| Analisis subsektor pertanian | Sheet `subsektorA` | ⚠️ Perlu join |

---

## 5. Rekomendasi Implementasi

### A. Strategi Pembersihan Data

```
1. Parsing bertahap:
   - Baris 1: skip (judul)
   - Baris 2: ambil sebagai header (ganti newline dengan underscore)
   - Baris 3: skip (nomor kolom)
   - Baris 4+: data aktual

2. Normalisasi nilai:
   - Ganti "tidak ketemu" → NULL
   - Deteksi dan skip baris yang berisi formula string (starts with "=")
   - Ekstrak iddesa/kec/desa dari idsls secara programatik:
     iddesa = substr(idsls, 0, 10)
     kec    = substr(idsls, 4, 3)
     desa   = substr(idsls, 7, 3)

3. Resolusi subsektor:
   - Sheet subsektorA (191.574 baris) adalah lookup table
   - Join ke sheet per-kabupaten via idsls
   - Lakukan di sisi PHP/SQL, bukan via XLOOKUP Excel
```

### B. Skema Tabel Database yang Direkomendasikan

```sql
-- Tabel utama SLS (dari sheet per-kabupaten)
CREATE TABLE prelist_sls (
    idsls        CHAR(14) PRIMARY KEY,
    iddesa       CHAR(10),
    kd_kab       CHAR(4),
    kd_kec       CHAR(3),
    kd_desa      CHAR(3),
    nm_kec       VARCHAR(100),
    nm_desa      VARCHAR(100),
    nama_sls     VARCHAR(200),
    sbr          INT,
    rtup_st2023  INT,
    utp_st2023   INT,
    subsektor    INT,
    jml_kk       INT,
    usaha_wilkerstat INT,
    muatan_rs    INT,
    ada_mall     TINYINT DEFAULT 0,
    ada_pasar    TINYINT DEFAULT 0,
    ada_kdm      TINYINT DEFAULT 0,
    ppl          INT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel ringkasan kecamatan (dari Sheet 1)
CREATE TABLE prelist_kecamatan (
    kd_kab       CHAR(4),
    kd_kec       CHAR(7) PRIMARY KEY,
    nm_kec       VARCHAR(100),
    upb_utl      INT,
    sbr          INT,
    se2016       INT,
    rtup         INT,
    utp          INT,
    subsektor    INT,
    jml_kk       INT,
    wilkerstat   INT,
    muatan_rs    INT,
    ppl          INT,
    pml          INT
);

-- Tabel ringkasan kabupaten (dari Sheet 0)
CREATE TABLE prelist_kabkota (
    kd_kab       CHAR(4) PRIMARY KEY,
    nm_kabkota   VARCHAR(100),
    se2016       INT,
    jml_kk       INT,
    utp          INT,
    subsektor    INT,
    ub           INT,
    um           INT,
    umk          INT,
    n_sls        INT,
    ppl          INT,
    pml          INT,
    total_waktu_jam DECIMAL(10,2)
);
```

### C. Strategi Integrasi Backend

Gunakan library **openspout** yang sudah ada di project ini. Buat satu ImportController yang:
1. Membaca sheet per-kabupaten secara streaming (openspout sudah mendukung ini — efisien untuk 191K baris)
2. Proses batch insert 500 baris per transaksi
3. Simpan log import per sheet untuk tracking

```php
// Contoh pola import streaming dengan openspout
$reader = new \OpenSpout\Reader\XLSX\Reader();
$reader->open($filePath);
foreach ($reader->getSheetIterator() as $sheet) {
    if (!str_starts_with($sheet->getName(), 'Prelist SE2026_')) continue;
    $batch = [];
    foreach ($sheet->getRowIterator() as $rowIdx => $row) {
        if ($rowIdx <= 3) continue; // skip header 3 baris
        $cells = $row->getCells();
        $idsls = trim($cells[0]->getValue());
        if (!preg_match('/^\d{14}$/', $idsls)) continue; // skip non-data
        // ... build $batch, flush setiap 500 rows
    }
}
```

### D. Skema Visualisasi yang Direkomendasikan

**1. KPI Cards (dari Sheet 0 — data Jatim)**
- Total SLS: `n_sls` → ~200K+ SLS
- Total Usaha (SBR): sum `SBR`
- Pertumbuhan vs SE2016: `(SBR - SE2016) / SE2016 * 100%`
- Total PPL/PML yang dibutuhkan

**2. Bar Chart — Perbandingan Beban per Kabupaten**
- X: nama kabupaten, Y: `rata2 beban per ppl`
- Highlight kabupaten dengan beban tertinggi (Jember: 808K KK, Surabaya: 926K KK)

**3. Stacked Bar — Komposisi Usaha per Kab/Kota**
- UB + UM + UMK per kabupaten
- Terlihat jelas dominasi UMK di semua wilayah

**4. Scatter Plot — Efisiensi PPL**
- X: jumlah SLS, Y: jumlah PPL
- Outlier = wilayah yang under/over-staffed

**5. Choropleth Map (jika ada GeoJSON)**
- Warna berdasarkan `muatan_rs` atau `sls/ppl` per kecamatan
- Drill-down dari kab → kec → desa

**6. Tabel Drill-down Interaktif**
- Level 1: Kabupaten → Level 2: Kecamatan → Level 3: Desa → Level 4: SLS
- Kolom: SBR, RTUP, UTP, subsektor, jml_kk, muatan_RS, PPL

**7. Progress Bar — Alokasi vs Kebutuhan PPL**
- Setelah kolom PPL terisi, bandingkan `ppl_tersedia` vs `ppl_dibutuhkan` (dari estimasi waktu)

---

## 6. Prioritas Implementasi

| Prioritas | Aksi |
|---|---|
| 🔴 Segera | Import Sheet 0 (Kab/Kota) → langsung bisa tampil KPI |
| 🔴 Segera | Import Sheet 1 (Kecamatan) → chart distribusi |
| 🟡 Berikutnya | Import Sheet 2 (Desa) → drill-down |
| 🟡 Berikutnya | Import Sheet 4 (subsektorA) sebagai lookup table |
| 🟢 Opsional | Import sheet per-kabupaten (35 sheet) → granularitas SLS |

Sheet 0 dan 1 adalah yang paling siap pakai dan langsung memberikan nilai visualisasi tertinggi. Sheet per-kabupaten memerlukan cleaning lebih dulu sebelum bisa diimport.