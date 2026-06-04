# SOP Penugasan SLS — Dashboard SE2026 Jember

> **Versi**: 1.0  
> **Tanggal**: Juni 2026  
> **Pemilik**: Subbagian Umum BPS Kabupaten Jember  
> **SOP ini merujuk pada**: Laporan Analisis Pegawai Organik (Jun 2026), rekomendasi R3.5/R3.6

---

## 1. Tujuan

Standar Operasional Prosedur ini mengatur alur penugasan SLS (Satuan Lingkungan Setempat) ke petugas PCL/PML/Task Force secara end-to-end, mulai dari pra-penugasan hingga rekap akhir, guna:

- **Menjamin kontinuitas** alokasi petugas (min. 2 pegawai aktif per siklus)
- **Mempercepat penugasan** melalui auto-suggest berdasarkan `kecamatan_bertugas`
- **Menjamin akuntabilitas** melalui audit trail (created_by, updated_by, created_via)
- **Memonitor progres** real-time via widget monitoring

## 2. Lingkup

| Komponen | Keterangan |
|----------|------------|
| Modul | Assignment (`?page=dashboard&sub=assignment`) |
| Aktor | Admin, Operator, Pegawai Organik (5 user), Mitra (PCL/PML/TF) |
| Siklus | Per gelombang SLS (lihat `prelist_sls`) |
| Data input | `prelist_kecamatan` (31 kec), `prelist_sls` (16.538 SLS), `users` (mitra) |
| Data output | `sipw_assignment` (1 row per SLS) |

## 3. Definisi

- **PCL** (*Pencacah*) — Petugas yang melakukan pencacahan lapangan
- **PML** (*Pengawas*) — Petugas yang mengawasi PCL di 1+ kecamatan
- **Task Force** — Petugas supervisor yang membantu PML menyelesaikan masalah
- **Pegawai Organik** — PNS BPS (5 user: ali, budi, citra, dani, erni)
- **Mitra** — Non-PNS yang direkrut musiman (3.089 user di Jember)
- **SLS** — *Satuan Lingkungan Setempat* (terendah dalam struktur sensus)

## 4. Prasyarat

Sebelum melakukan penugasan, pastikan:

1. **Data prelist sudah di-import** ke `prelist_kabkota`, `prelist_kecamatan`, `prelist_sls`
2. **Data mitra sudah punya `kecamatan_bertugas`** — jalankan `scripts/backfill_mitra_kecamatan.php --execute` (R1.1) untuk backfill otomatis via `id_sobat`
3. **Pegawai organik tersedia** — 5 user dengan role `pegawai` dan `kecamatan_bertugas` terisi (lihat `scripts/seed_pegawai_organik.php --execute`, R3.1)
4. **Patch schema `sipw_assignment`** — kolom audit (created_by, updated_by, progress_pct, dll) sudah ada (R2.1, `scripts/apply_patch_007.php`)
5. **Role-based access** — Admin/Operator untuk assign, Pegawai untuk monitoring

## 5. Alur Penugasan (5 Tahap)

### Tahap 1 — Pra-Penugasan (H-7 s.d. H-1)

**Tujuan**: Memetakan beban, menentukan kuota, dan memilih calon petugas.

| No | Aktivitas | PIC | Output |
|----|-----------|-----|--------|
| 1.1 | Review **beban kerja** per kecamatan di `?page=dashboard&sub=workload` | Admin | Daftar 31 kecamatan + total SLS |
| 1.2 | Cek **distribusi PPL/PML** di `prelist_kecamatan.ppl/pml` (R1.3) | Admin | Kebutuhan PCL/PML per kec |
| 1.3 | Buka `?page=dashboard&sub=assignment` filter per kecamatan | Admin | Tab "Belum Assign" |
| 1.4 | Lihat **Saran Petugas** (panel oranye di atas tabel) — hasil R2.2 | Admin | Top-of-mind: pegawai_ali untuk KENCONG, dll |
| 1.5 | Review **beban existing** (panel "Beban Kerja Petugas" di modal) | Admin | Siapa yang sudah overload |

**Decision**: Tetapkan 1-3 PCL per kecamatan, 1 PML per 5-10 PCL, 1 TF per kabupaten.

**Waktu**: 1-2 jam.

### Tahap 2 — Penugasan (Hari H)

**Tujuan**: Assign PCL/PML/TF ke setiap SLS.

#### Cara A — Bulk per Kecamatan (Direkomendasikan)

1. Filter kecamatan: `<select name="kdkec">` → pilih kecamatan
2. Klik **"Bulk Assign"** (tombol hijau di header tabel)
3. Pilih PCL, PML, Task Force dari dropdown Select2
4. Klik **Proses** — semua SLS di kecamatan tsb terisi
5. **Hasil**: assignment baru di `sipw_assignment` dengan `created_via='web'`, `created_by=admin_id`

#### Cara B — Single per SLS (Untuk Koreksi)

1. Klik **"Assign"** di baris SLS
2. Modal terbuka dengan 3 dropdown: Pencacah, Pengawas, Task Force
3. Pilih petugas (auto-populated dengan Select2)
4. Klik **Simpan**
5. **Hasil**: 1 row `sipw_assignment`

#### Cara C — Import Excel (Untuk >100 SLS Sekaligus)

1. Download template: `?action=template`
2. Isi kolom: `nmsls, nmdesa, nmkec, pcl, pml, task_force`
   - `pcl/pml/task_force` = `username` user (case-sensitive)
3. Upload via **"Import Assignment"**
4. Preview → klik **Proses Import**
5. **Hasil**: assignment dibuat via `AssignmentImporter::import()` dengan `created_via='import'`

**Validasi pasca-assign**:
- Total `countAll` ≈ total SLS di `prelist_sls` (Jember: 16.538)
- Tidak ada SLS tanpa PCL (cek tab "Belum Assign" = 0)
- Setiap PML mengawasi max 10 PCL

**Waktu**: 2-4 jam (tergantung jumlah SLS).

### Tahap 3 — Monitoring (H+1 s.d. H+30)

**Tujuan**: Memantau progres, identifikasi SLS yang macet.

1. Buka `?page=dashboard&sub=monitoring`:
   - Widget **Kecamatan Summary** (kartu per kecamatan, auto-refresh 30s)
   - Widget **Desa Rincian** (filter by kecamatan)
   - Widget **SLS/Non-SLS Tabs** (DataTable paginasi, 10/hal, search)
2. Cek **progres assignment** di AssignmentController: `progress_pct` per SLS
3. **Aksi**: Jika ada SLS >14 hari `status='belum'`, follow-up ke PCL via WA/telpon

**Threshold alerts** (lihat R3.5):
- `progress_pct < 25%` setelah H+7 → Warning
- `progress_pct = 0` setelah H+14 → Critical, escalate ke PML

**Waktu**: 15-30 menit/hari.

### Tahap 4 — Koreksi & Update (H+1 s.d. H+30)

**Tujuan**: Handle SLS yang perlu reassign atau perubahan status.

1. **Edit assignment**: Klik tombol pensil di baris SLS → ubah PCL/PML/TF → Simpan
   - `updated_by` dan `updated_at` terupdate otomatis (lihat patch_007)
2. **Ubah status**: Klik status badge → pilih `belum/proses/selesai` → Simpan
3. **Hapus assignment**: Klik tombol trash → konfirmasi → Hapus
   - Tercatat di `dash_assignment_log` sebagai DELETE
4. **Bulk status update**: (coming soon) — via `?action=bulk_status`

**Audit**: Semua perubahan tercatat di `dash_assignment_log` + `sipw_assignment.created_by/updated_by`.

**Waktu**: sesuai kebutuhan.

### Tahap 5 — Rekap & Evaluasi (Pasca-Selesai)

**Tujuan**: Generate laporan, evaluasi beban, lessons learned.

1. Buka `?page=dashboard&sub=report`:
   - Pilih tipe: **"Rekap Assignment"** atau **"Per Petugas"**
   - Filter by siklus / kecamatan
   - Export: **Excel/CSV/PDF/Print**
2. Lihat `?page=dashboard&sub=insight`:
   - **Tabel Beban** — distribusi SLS per PCL
   - **User Pool** — rasio beban/kapasitas
3. **Evaluasi**:
   - PCL dengan `current_load > 80 SLS` → overload, rekrut tambahan
   - PCL dengan `current_load < 10 SLS` → idle, redistribusi
   - Petugas dengan `progress_pct=0` → evaluasi kinerja
4. **Lessons learned** → update SOP ini untuk siklus berikutnya

**Output**: Laporan PDF + update `prelist_kecamatan.ppl/pml` (R1.3) untuk siklus berikut.

**Waktu**: 1-2 hari setelah SLS selesai.

## 6. Aturan & Konstrain

### 6.1 Single Point of Failure (R3.5)

> **WAJIB**: Minimal 2 pegawai organik aktif per siklus penugasan.

Jika hanya 1 pegawai (saat ini `pegawai3509` placeholder), **WAJIB** jalankan `scripts/seed_pegawai_organik.php --execute` untuk menambah 5 pegawai (ali, budi, citra, dani, erni) sebelum penugasan.

### 6.2 Auto-Suggest Priority (R2.2)

Saat assign, panel **Saran Petugas** memprioritaskan:

1. **Pegawai organik** dengan `kecamatan_bertugas` match (warna hijau)
2. **Mitra PCL/PML/TF** dengan `kecamatan_bertugas` match (warna biru)
3. **Mitra lain** yang beban kerjanya `current_load < 50 SLS` (idle)

### 6.3 Beban Kerja Maksimum

| Role | Beban maks | Keterangan |
|------|------------|------------|
| PCL  | 80 SLS    | 1 SLS ≈ 30-60 menit pencacahan |
| PML  | 10 PCL    | 1 PML mengawasi 5-10 PCL |
| TF   | 1 kabupaten | 1 TF per kabupaten |

### 6.4 Audit Trail (R2.1)

Setiap INSERT/UPDATE/DELETE di `sipw_assignment` WAJIB mencatat:
- `created_by` / `updated_by` (FK ke `users.id`)
- `created_via` (enum: `web`/`cli`/`import`/`restore`)
- `progress_pct` (0-100)
- `tanggal_mulai` / `tanggal_selesai`
- `catatan` (free text, opsional)

Lihat `Backup::logAssignment()` di `src/Helpers/Backup.php`.

## 7. Troubleshooting

| Masalah | Penyebab | Solusi |
|---------|----------|--------|
| Saran Petugas kosong | Mitra belum punya `kecamatan_bertugas` | Jalankan `scripts/backfill_mitra_kecamatan.php --execute` |
| Total `n_sls` tidak match | `prelist_kabkota.n_sls` stale | Jalankan `scripts/fix_prelist_n_sls.php --execute` |
| Beban kerja semua 0 | `prelist_kecamatan.ppl/pml` belum diisi | Jalankan `scripts/populate_kecamatan_ppl_pml.php --execute` |
| Audit `created_by` NULL | Patch_007 belum diaplikasikan | Jalankan `scripts/apply_patch_007.php` |
| Auto-suggest error | `users` tanpa `kecamatan_bertugas` | Cek `SELECT * FROM users WHERE role IN ('pcl','pml','task_force','pegawai') AND kecamatan_bertugas IS NULL` |

## 8. Lampiran

### 8.1 Daftar Pegawai Organik (Hasil R3.1)

| Username | Nama | Kecamatan |
|----------|------|-----------|
| `pegawai_ali` | Ali Sodikin, S.ST | KENCONG, GUMUK MAS, PUGER, WULUHAN, AMBULU, TEMPUREJO |
| `pegawai_budi` | Budi Santoso, S.ST | SILO, MAYANG, MUMBULSARI, JENGGAWAH, AJUNG, RAMBIPUJI |
| `pegawai_citra` | Citra Larasati, S.Si | BALUNG, UMBULSARI, SEMBORO, JOMBANG, SUMBER BARU, TANGGUL |
| `pegawai_dani` | Dani Rahmadi, S.ST | BANGSALSARI, PANTI, SUKORAMBI, ARJASA, PAKUSARI, KALISAT |
| `pegawai_erni` | Erni Wulandari, S.ST | LEDOKOMBO, SUMBERJAMBE, SUKOWONO, JELBUK, KALIWATES, SUMBERSARI, PATRANG |

**Default password**: `Pegawai@2026` (wajib diubah setelah login pertama)

### 8.2 SQL Penting

```sql
-- Cek progres per kecamatan
SELECT
    pk.nm_kec,
    COUNT(sa.id) AS assigned,
    SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END) AS selesai
FROM prelist_kecamatan pk
LEFT JOIN sipw_import si ON si.kdkec = pk.kd_kec
LEFT JOIN sipw_assignment sa ON sa.sipw_id = si.id
WHERE pk.kd_kab = '3509'
GROUP BY pk.kd_kec, pk.nm_kec
ORDER BY pk.nm_kec;

-- Cek beban per PCL
SELECT
    u.nama_lengkap,
    u.role,
    COUNT(sa.id) AS current_load,
    SUM(CASE WHEN sa.status = 'selesai' THEN 1 ELSE 0 END) AS selesai
FROM users u
LEFT JOIN sipw_assignment sa ON sa.pencacah_id = u.id
WHERE u.role IN ('pcl','pml','task_force','pegawai')
  AND u.status_akun = 'active'
GROUP BY u.id, u.nama_lengkap, u.role
HAVING current_load > 0
ORDER BY current_load DESC;
```

### 8.3 Referensi Laporan

- `docs/ANALISIS_APLIKASI.md` — Analisis kondisi Juni 2026
- `docs/PANDUAN_OPERATOR.md` — Panduan operator harian
- `docs/SOP_BACKUP.md` — Backup & recovery
- `scripts/seed_pegawai_organik.php` — R3.1
- `scripts/backfill_mitra_kecamatan.php` — R1.1
- `scripts/fix_prelist_n_sls.php` — R1.4
- `scripts/populate_kecamatan_ppl_pml.php` — R1.3
- `scripts/apply_patch_007.php` — R2.1

---

**Disusun oleh**: Tim IT BPS Kabupaten Jember  
**Disetujui oleh**: Kepala Subbagian Umum  
**Tanggal efektif**: 1 Juli 2026  
**Review berkala**: Setiap akhir siklus SLS
