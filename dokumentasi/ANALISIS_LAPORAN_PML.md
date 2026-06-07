# Analisis Kelayakan & Rancangan Fitur — Laporan Statistik PML SLS

> **Proyek**: Dashboard SE2026 BPS Kabupaten Jember  
> **Tanggal**: 7 Juni 2026  
> **Penulis**: Tim Pengembang  
> **Status**: **DRAFT — Menunggu Persetujuan**

---

## Daftar Isi

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Audit Struktur Data](#2-audit-struktur-data)
3. [Identifikasi Dependensi Sistem](#3-identifikasi-dependensi-sistem)
4. [Analisis Kelayakan](#4-analisis-kelayakan)
5. [Rancangan Spesifikasi Teknis](#5-rancangan-spesifikasi-teknis)
6. [Rencana Pengujian](#6-rencana-pengujian)
7. [Rekomendasi Akhir](#7-rekomendasi-akhir)

---

## 1. Ringkasan Eksekutif

Fitur **Laporan Statistik PML SLS** bertujuan menyediakan dashboard bagi admin/operator untuk memantau progres pekerjaan PML (Petugas Pemeriksa Lapangan) secara agregat, serta menyediakan mekanisme bagi PML untuk mengirimkan laporan penyelesaian pekerjaan — dengan aturan bahwa PML wajib memiliki alokasi SLS resmi sebelum dapat mengirim laporan.

Berdasarkan audit sistem, fitur ini **dinyatakan layak** untuk diimplementasikan dengan catatan: (a) data assignment PML harus tersedia sebelum laporan dapat bermakna, (b) tidak diperlukan perubahan struktur tabel besar — cukup satu tabel baru untuk riwayat laporan PML, (c) estimasi pengembangan **5-7 hari kerja**.

---

## 2. Audit Struktur Data

### 2.1 Tabel Eksisting yang Relevan

#### `sipw_assignment` — Alokasi SLS ke Petugas

| Field | Tipe | Keterangan |
|-------|------|-----------|
| `id` | int (PK) | ID unik assignment |
| `sipw_id` | int (FK → `sipw_import.id`) | SLS yang dialokasikan |
| `pencacah_id` | int (FK → `users.id`) | PCL yang ditugaskan |
| `pengawas_id` | int (FK → `users.id`) | **PML yang ditugaskan** |
| `task_force_id` | int (FK → `users.id`) | Task Force yang ditugaskan |
| `status` | enum('belum','proses','selesai') | Status per-SLS |
| `created_by` | int | User pembuat assignment |
| `created_via` | enum('web','cli','import','restore') | Metode pembuatan |
| `progress_pct` | tinyint | Persentase progres (0-100) |
| `tanggal_mulai` | date | Tanggal mulai pengerjaan |
| `tanggal_selesai` | date | Tanggal selesai |
| `catatan` | text | Catatan tambahan |

**Temuan**: Semua kolom yang dibutuhkan untuk tracking status per-SLS sudah tersedia. Status `belum`/`proses`/`selesai` di level SLS bisa diagregasi untuk laporan PML.

#### `users` — Data PML

| Field | Tipe | Keterangan |
|-------|------|-----------|
| `id` | int (PK) | ID unik |
| `username` | varchar(50) | Username login |
| `nama_lengkap` | varchar(100) | Nama lengkap |
| `email` | varchar(100) | Email |
| `role` | enum('pml',...) | Role = 'pml' |
| `status_akun` | enum('active','inactive','blocked') | Status akun |
| `last_login_at` | datetime | Terakhir login |

#### `activity_logs` — Riwayat Aktivitas

| Field | Tipe | Keterangan |
|-------|------|-----------|
| `id` | int (PK) | ID unik |
| `user_id` | int (FK) | User pelaku |
| `action` | varchar(50) | Aksi (misal: `pml_report_submit`) |
| `module` | varchar(50) | Modul (misal: `pml_report`) |
| `detail` | text | Detail JSON |
| `ip_address` | varchar(45) | Alamat IP |
| `created_at` | timestamp | Waktu aksi |

### 2.2 Data Saat Ini

| Metrik | Nilai |
|--------|-------|
| Total PML (active) | **275** |
| PML dengan assignment | **0** (0%) |
| PML tanpa assignment | **275** (100%) |
| Total SLS (`sipw_import`) | **16.772** |
| Total assignment (`sipw_assignment`) | **0** |

> **Implikasi**: Fitur laporan akan berfungsi penuh hanya setelah data assignment diisi. Saat ini (0 assignment), laporan akan menunjukkan seluruh 275 PML dalam kategori "Belum Mendapat Alokasi" dan 0 untuk kategori lainnya.

### 2.3 Kesenjangan (Gaps)

| Kebutuhan | Status | Catatan |
|-----------|--------|---------|
| Tracking status per-SLS (PML) | ✅ Ada | `sipw_assignment.pengawas_id` + `status` |
| Tracking siapa yang lapor | ❌ Belum ada | Butuh tabel baru `pml_reports` |
| Waktu pengiriman laporan | ❌ Belum ada | Butuh kolom `submitted_at` |
| Pembatasan PML tanpa alokasi | ❌ Belum ada | Butuh validasi server-side |
| Agregasi per-PML | ✅ Bisa | Via `COUNT/GROUP BY` dari `sipw_assignment` |
| Audit trail pengiriman | ✅ Ada | `activity_logs` siap digunakan |

---

## 3. Identifikasi Dependensi Sistem

### 3.1 Dependensi Langsung

| Dependensi | Status | Risiko jika Tidak Terpenuhi |
|-----------|--------|---------------------------|
| **Data assignment PML** | ❌ **0 baris** | Laporan kosong, semua PML masuk kategori "tanpa alokasi" |
| **Auth/session PML** | ✅ Berfungsi | — |
| **Role middleware** | ✅ Ada (base controller) | — |
| **PML page access** | ✅ Terdefinisi di `config/constants.php` | — |
| **CSRF protection** | ✅ Ada | — |
| **Database transactional** | ✅ PDO | — |

### 3.2 Dependensi Tidak Langsung

| Dependensi | Status | Catatan |
|-----------|--------|---------|
| OpenSpout (export Excel) | ✅ Tersedia | Untuk fitur export laporan |
| Activity logs | ✅ Tersedia | Untuk audit trail pengiriman |
| Session fingerprint | ✅ Aktif | Aman untuk AJAX |

### 3.3 Potensi Konflik

| Konflik | Analisis | Mitigasi |
|---------|----------|----------|
| **Dual update status** | PML bisa mengirim laporan via form, sementara admin/operator bisa ubah status via dropdown di halaman assignment | Prioritaskan laporan PML sebagai sumber utama; dropdown admin tetap berfungsi sebagai override |
| **Race condition** | Dua pengiriman simultan dari PML yang sama | Gunakan transaksi database + validasi `updated_at` |
| **Perubahan status mendadak** | Admin mengubah status assignment saat PML sedang mengisi laporan | Validasi di backend sebelum update — tolak jika status sudah berubah sejak form dibuka |

---

## 4. Analisis Kelayakan

### 4.1 Aspek Teknis

| Aspek | Analisis |
|-------|----------|
| **Arsitektur** | Fitur dapat ditambahkan sebagai sub-halaman baru di bawah `?page=dashboard&sub=pml-report` — pola seperti existing sub-halaman (monitoring, assignment, report) |
| **Skema database** | Cukup 1 tabel baru (`pml_reports`) + 1 migration patch. Tidak perlu alter tabel besar |
| **Backend** | Controller baru `PmlReportController`, model baru `PmlReportModel`. Pola MVC sudah teruji |
| **Frontend** | DataTable server-side + 4 KPI cards + form modal laporan. Menggunakan stack yang sudah ada (Bootstrap 5, DataTables 1.13) |
| **API endpoint** | JSON endpoint untuk DataTable + form submit. Pola sama seperti `MonitoringController` |
| **Export** | Excel via OpenSpout (ReaderOptions::setTempFolder ke `storage/import`) |

**Skor Kelayakan Teknis**: 9/10 — Risiko rendah, semua komponen sudah tersedia.

### 4.2 Potensi Risiko

| Risiko | Dampak | Probabilitas | Mitigasi |
|--------|--------|-------------|----------|
| **0 assignment data** | Laporan tidak bermakna | **Sangat tinggi** (100% saat ini) | Dokumentasi di halaman bahwa data assignment perlu diisi terlebih dahulu |
| **PML mengirim duplikat** | Inflasi data | Rendah | Validasi server: 1 laporan per-PML per-periode |
| **Performa query** | Lambat jika 275 PML × 16.772 SLS | Rendah | Index sudah ada, query agregasi ringan |
| **Beban server** | Berat jika polling 275 PML simultan | Rendah | Pagination + cache 60s |

### 4.3 Estimasi Waktu Pengembangan

| Tahap | Durasi | Aktivitas |
|-------|--------|-----------|
| **1. Skema database** | 0,5 hari | Patch SQL `pml_reports`, index, FK |
| **2. Model** | 1 hari | `PmlReportModel` — query agregasi + CRUD laporan |
| **3. Controller** | 1 hari | `PmlReportController` — endpoint JSON + halaman + export |
| **4. View** | 1,5 hari | Halaman laporan (KPI, DataTable, form modal) |
| **5. JS** | 0,5 hari | `pml-report.js` — DataTable + form submit + validasi |
| **6. Validasi bisnis** | 0,5 hari | Blokir PML tanpa alokasi, cek duplikasi |
| **7. Export Excel** | 0,5 hari | Download laporan via OpenSpout |
| **8. Pengujian** | 1 hari | Unit + integrasi + smoke test |
| **Total** | **5-7 hari kerja** | |

### 4.4 Biaya Sumber Daya

| Sumber Daya | Kebutuhan |
|------------|-----------|
| **Storage** | ~1 MB tambahan (tabel `pml_reports`) |
| **Memory** | Tidak signifikan (< 10 MB tambahan) |
| **Developer** | 1-2 orang |
| **Dependency** | Tidak ada dependency baru |

---

## 5. Rancangan Spesifikasi Teknis

### 5.1 Skema Database

**Tabel baru: `pml_reports`**

```sql
CREATE TABLE IF NOT EXISTS `pml_reports` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pml_id`        INT NOT NULL COMMENT 'FK → users.id (role=pml)',
    `periode`       VARCHAR(7) NOT NULL COMMENT 'Periode laporan YYYY-MM',
    `total_assigned` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total SLS yang dialokasikan saat laporan',
    `total_selesai`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Jumlah SLS selesai',
    `total_proses`   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Jumlah SLS dalam proses',
    `total_belum`    INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Jumlah SLS belum dikerjakan',
    `catatan`        TEXT NULL COMMENT 'Catatan dari PML',
    `submitted_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pengiriman laporan',
    `ip_address`     VARCHAR(45) NULL,
    INDEX `idx_pml_report_pml` (`pml_id`, `periode`),
    INDEX `idx_pml_report_periode` (`periode`),
    CONSTRAINT `fk_pml_report_user` FOREIGN KEY (`pml_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Patch SQL**: `database/patch_010_pml_reports.sql` (idempotent via `information_schema`)

### 5.2 Endpoint API

Semua endpoint berada di bawah `?page=dashboard&sub=pml-report`:

| Method | Action | Deskripsi |
|--------|--------|-----------|
| `GET` | *(default)* | Halaman utama laporan PML |
| `GET` | `?action=data` | JSON DataTable laporan (server-side) |
| `GET` | `?action=stats` | JSON KPI statistik agregat |
| `GET` | `?action=detail&pml_id=N` | JSON detail per-PML (daftar SLS + status) |
| `POST` | `?action=submit` | Kirim laporan (PML only) |
| `GET` | `?action=export` | Download Excel laporan agregat |

### 5.3 Struktur Halaman

```
┌─────────────────────────────────────────────────┐
│  Laporan Statistik PML SLS                      │
├──────────┬──────────┬──────────┬──────────┐     │
│ 🟢 PML   │ 🔵 SLS   │ 🟡 Selesai│ ⚪ Tanpa │     │
│ Aktif    │ Diassign │ / Proses │ Alokasi  │     │
│   275    │    0     │  0 / 0   │   275    │     │
├──────────┴──────────┴──────────┴──────────┤     │
│  Filters: [Periode] [Kecamatan] [Status]       │
├───────────────────────────────────────────────┤
│  DataTable: Laporan PML                        │
│  ┌────┬──────────┬────────┬──────┬──────┬──┐  │
│  │ No │ Nama PML │ Alokasi│ Selesai│ Proses│  │
│  ├────┼──────────┼────────┼──────┼──────┼──┤  │
│  │  1 │ INDAH... │   0    │   0   │   0  │  │  │
│  │  2 │ BETY...  │   0    │   0   │   0  │  │  │
│  └────┴──────────┴────────┴──────┴──────┴──┘  │
└───────────────────────────────────────────────┘
```

### 5.4 Logika Validasi Pembatasan Laporan

Aturan bisnis diterapkan di **backend (controller)**, bukan hanya frontend:

```php
// Di PmlReportController::handleSubmit()
$pmlId = Session::get('user')['id'];

// 1. Cek apakah PML memiliki alokasi SLS
$hasAssignment = $this->model->countPmlAssignments($pmlId);
if ($hasAssignment === 0) {
    $this->json(['error' => true, 'message' => 'Anda belum memiliki alokasi SLS. Hubungi admin untuk penugasan.']);
    return;
}

// 2. Cek duplikasi periode
$existing = $this->model->getReportByPmlPeriode($pmlId, $periode);
if ($existing) {
    $this->json(['error' => true, 'message' => 'Laporan untuk periode ini sudah dikirim.']);
    return;
}

// 3. Hitung status dari sipw_assignment (bukan dari input PML)
$stats = $this->model->aggregatePmlStatus($pmlId);
// stats = ['total' => N, 'selesai' => M, 'proses' => P, 'belum' => Q]

// 4. Simpan laporan
$this->model->createReport($pmlId, $periode, $stats, $catatan);

// 5. Log aktivitas
$this->auditLog->log($pmlId, 'pml_report_submit', 'pml_report', json_encode($stats));
```

**Blokade akses form laporan**: Form submit laporan hanya muncul jika `countPmlAssignments($pmlId) > 0`.

### 5.5 Komponen Model

#### `PmlReportModel`

| Method | Deskripsi |
|--------|-----------|
| `getStats(?string $periode, ?string $kdkec): array` | KPI agregat (total PML, alokasi, selesai, tanpa alokasi) |
| `getReports(array $filters, int $page, int $perPage): array` | DataTable laporan (join users + hitung dari sipw_assignment) |
| `countReports(array $filters): int` | Total baris DataTable |
| `getDetail(int $pmlId, ?string $kdkec): array` | Per-SLS breakdown untuk PML tertentu |
| `countPmlAssignments(int $pmlId): int` | Cek apakah PML punya alokasi |
| `aggregatePmlStatus(int $pmlId): array` | Hitung agregat dari sipw_assignment |
| `getReportByPmlPeriode(int $pmlId, string $periode): ?array` | Cek duplikasi laporan |
| `createReport(int $pmlId, string $periode, array $stats, string $catatan): int` | Simpan laporan |
| `getExportData(?string $periode): array` | Data untuk export Excel |

### 5.6 Struktur File Baru

```
src/
├── Controllers/
│   └── PmlReportController.php       # Controller baru
├── Models/
│   └── PmlReportModel.php            # Model baru
database/
│   └── patch_010_pml_reports.sql     # Patch idempotent
views/
│   └── pml-report/
│       └── index.php                 # Halaman laporan PML
assets/
│   └── js/
│       └── pml-report.js             # DataTable + form
config/
│   └── constants.php                 # Update PAGE_ACCESS
```

---

## 6. Rencana Pengujian

### 6.1 Unit Testing

| Test | Skenario | Hasil Harapan |
|------|----------|---------------|
| `PmlReportModel::countPmlAssignments()` | PML dengan 5 SLS | return 5 |
| `PmlReportModel::countPmlAssignments()` | PML tanpa SLS | return 0 |
| `PmlReportModel::aggregatePmlStatus()` | 5 SLS: 2 selesai, 1 proses, 2 belum | return ['total'=>5, 'selesai'=>2, 'proses'=>1, 'belum'=>2] |
| `PmlReportModel::createReport()` | Data valid | return lastInsertId > 0 |
| `PmlReportModel::getReportByPmlPeriode()` | Laporan sudah ada | return array |
| `PmlReportModel::getReportByPmlPeriode()` | Belum ada | return null |

### 6.2 Validasi Aturan Bisnis

| Test | Skenario | Hasil Harapan |
|------|----------|---------------|
| Submit laporan (PML dengan alokasi) | PML memiliki 3 SLS | ✅ Laporan tersimpan |
| Submit laporan (PML tanpa alokasi) | PML memiliki 0 SLS | ❌ Ditolak, pesan error |
| Submit duplikat periode | Laporan sudah ada untuk bulan ini | ❌ Ditolak, pesan error |
| Submit bukan PML | Login sebagai admin | ❌ Ditolak (role check) |
| Akses form laporan (tanpa alokasi) | PML tanpa SLS | Form tidak ditampilkan |

### 6.3 Integrasi Testing

| Test | Skenario |
|------|----------|
| Assignment dibuat → jumlah alokasi PML bertambah | Create assignment via controller, cek `countPmlAssignments()` |
| Status SLS diubah → agregat laporan berubah | Ubah status via assignment page, cek `aggregatePmlStatus()` |
| Export Excel | Download via `?action=export`, verifikasi file XLSX |
| DataTable server-side | Filter + search + pagination di halaman laporan |

### 6.4 Smoke Test

```bash
# 1. Halaman index
curl -s 'http://localhost/dashboard-se2026/?page=dashboard&sub=pml-report'
# Harap: 200 OK, HTML mengandung struktur halaman

# 2. JSON DataTable
curl -s 'http://localhost/dashboard-se2026/?page=dashboard&sub=pml-report&action=data'
# Harap: JSON dengan draw, recordsTotal, recordsFiltered, data

# 3. JSON Stats
curl -s 'http://localhost/dashboard-se2026/?page=dashboard&sub=pml-report&action=stats'
# Harap: JSON dengan total_pml, total_assigned, completed, tanpa_alokasi

# 4. Export Excel
curl -s -o /tmp/pml-report.xlsx 'http://localhost/dashboard-se2026/?page=dashboard&sub=pml-report&action=export'
# Harap: file XLSX valid, bisa dibuka

# 5. Syntax check
php -l src/Controllers/PmlReportController.php
php -l src/Models/PmlReportModel.php
php -l views/pml-report/index.php
php -l database/patch_010_pml_reports.sql
```

---

## 7. Rekomendasi Akhir

### Keputusan: ✅ Layak Diterapkan — Dengan Catatan

**Dasar pertimbangan:**

1. **Kelayakan teknis tinggi (9/10)** — Semua komponen inti sudah tersedia: skema assignment, role system, DataTables, OpenSpout. Hanya perlu 1 tabel baru dan 4-5 file baru.

2. **Risiko rendah** — Tidak ada perubahan pada tabel/proses yang sudah berjalan. Fitur baru sepenuhnya independen dari modul assignment yang ada (cuma read dari `sipw_assignment`).

3. **Estimasi wajar (5-7 hari)** — Timeline realistis untuk 1 developer.

4. **Nilai bisnis tinggi** — PML adalah role kunci dalam SE2026. Memonitor progres mereka adalah kebutuhan operasional.

### Catatan Penting untuk Implementasi

| No | Catatan | Prioritas |
|----|---------|-----------|
| 1 | **Data assignment harus diisi terlebih dahulu** — tanpa data assignment, laporan hanya akan menampilkan 275 PML tanpa alokasi. Integrasi dengan SOP Penugasan SLS (lihat `SOP_PENUGASAN_SLS.md`) | 🔴 Wajib |
| 2 | **Validasi di backend, bukan hanya frontend** — Blokade PML tanpa alokasi harus diverifikasi di controller (server-side), tidak cukup hanya menyembunyikan tombol di UI | 🔴 Wajib |
| 3 | **Status dihitung dari `sipw_assignment`, bukan dari input PML** — PML tidak boleh mengirim angka sembarangan. Sistem membaca langsung dari database assignment | 🟡 Penting |
| 4 | **Periode laporan = YYYY-MM** — Gunakan format ISO untuk sorting dan filtering | 🟡 Penting |
| 5 | **Cache KPI 60 detik** — Statistik agregat bisa di-cache untuk mengurangi beban query | 🟢 Optional |
| 6 | **Export Excel via OpenSpout** — WAJIB panggil `setTempFolder()` ke `storage/import/` | 🔴 Wajib |

### Timeline Implementasi

```
Minggu 1:  Skema DB (0,5 hr) → Model (1 hr) → Controller (1 hr)
Minggu 2:  View + JS (2 hr) → Export (0,5 hr) → Testing (1 hr) → Deployment (0,5 hr)
```

### Status Akhir

| Aspek | Kesimpulan |
|-------|-----------|
| **Layak teknis** | ✅ Ya |
| **Layak bisnis** | ✅ Ya (value tinggi) |
| **Risiko** | 🟢 Rendah |
| **Estimasi** | 5-7 hari kerja |
| **Rekomendasi** | **Terapkan** segera setelah data assignment PML tersedia |
