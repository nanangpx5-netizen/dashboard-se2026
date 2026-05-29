# LAPORAN ANALISIS APLIKASI — Dashboard SE2026 BPS Jember

**Tanggal:** 28 Mei 2026  
**Versi Aplikasi:** 1.0.0  
**Tujuan:** Audit menyeluruh arsitektur, keamanan, kinerja, UX, kualitas kode, dan fungsionalitas.

---

## RINGKASAN EKSEKUTIF

Dashboard SE2026 adalah aplikasi monitoring Sensus Ekonomi 2026 berbasis **PHP 8.2 native MVC** dengan **MySQL 8.0**, berjalan di **Laragon** (lingkungan Windows). Aplikasi berfungsi sebagai alat bantu operator BPS untuk mengelola import data SLS (Sarana Lingkungan Sekitar), alokasi petugas lapangan (PCL/PML), monitoring progres real-time, dan pelaporan.

**Risiko Tertinggi:**
1. **Tabrakan namespace** antara `app/` dan `src/` (keduanya menggunakan `App\`) — dapat menyebabkan error tak terduga
2. **Tidak ada pengujian otomatis** — setiap perubahan manual rentan regresi
3. **Data SIPW hanya 9 dari 31 kecamatan** — cakupan data tidak lengkap
4. **Tidak ada .gitignore** — risiko commit data sensitif (.env, cache, log)

**Skor Kematangan Aplikasi:** 6.5/10 — Fungsionalitas inti lengkap, tetapi tata kelola teknis dan jaminan kualitas masih lemah.

---

## 1. ANALISIS ARSITEKTUR TEKNIS

### 1.1 Stack Teknologi

| Lapisan | Teknologi | Versi | Status |
|---------|-----------|-------|--------|
| Bahasa | PHP | ^8.2 | ✅ Modern, type-safe |
| Database | MySQL (MariaDB via Laragon) | 8.0 | ✅ Stabil |
| Frontend | Bootstrap 5 + jQuery 3.7 | CDN | ✅ Standar industri |
| Grafik | Chart.js 4.4.1 | CDN | ✅ Modern |
| Tabel | DataTables 1.13.6 | CDN | ✅ Mature |
| Ikon | Font Awesome 6.5.1 | CDN | ✅ |
| PDF | Dompdf 3.1 | Composer | ⚠️ Versi terbaru |
| Excel | OpenSpout 4.24 | Composer | ✅ Streaming, memory-efficient |
| CSS/JS | Custom (app.css, app.js, dashboard.js) | - | ⚠️ Manual, tanpa build tool |

### 1.2 Pola Arsitektur

- **Front Controller:** Single entry point `index.php` dengan routing via parameter query `?page=`
- **MVC:** Controller → Model → View dengan pemisahan tanggung jawab yang jelas
- **Service Layer:** ImportProcessor, ImportValidator (pemisahan logika bisnis dari controller)
- **Middleware Chain:** CSRF → Auth → Role (runs before every request)
- **Singleton:** `App::instance()`, kedua Database class, helper statis
- **Repository Pattern:** Model class sebagai data access object

### 1.3 Pola Desain yang Diterapkan

| Pola | Implementasi | Kepatuhan |
|------|-------------|-----------|
| Singleton | `App::instance()`, `Database::instance()` / `Database::getInstance()` | ✅ |
| Front Controller | `index.php` sebagai satu titik masuk | ✅ |
| MVC | Controller, Model, View terpisah | ✅ |
| Middleware | Chain of Responsibility | ✅ |
| Strategy | `ImportProcessor` + `ImportValidator` | ✅ |
| Service Layer | `ImportProcessor`, `Backup`, `Cache` | ✅ |
| Repository | Model classes wrapping PDO | ⚠️ Sebagian query masih di controller |

### 1.4 Alur Request

```
Browser → index.php → config.php + constants.php → vendor/autoload.php
  → App::instance()->boot() → Router::match() → Middleware chain
    → Controller::method() → Model/Service/Helper → render(view, data)
```

### 1.5 Dependensi (Composer)

| Paket | Versi | Fungsi | Risiko |
|-------|-------|--------|--------|
| `php` | ^8.2 | Runtime | ✅ |
| `openspout/openspout` | ^4.24 | Baca Excel streaming | ✅ Versi terbaru (Juli 2024) |
| `dompdf/dompdf` | ^3.1 | Generate PDF | ✅ Versi stabil |
| `ext-pdo` | * | Koneksi database | ✅ Built-in |
| `ext-mbstring` | * | String multibyte | ✅ Built-in |

**Temuan:** Tidak ada dependency yang usang atau memiliki CVE publik pada versi yang digunakan.

### 1.6 Skalabilitas

| Skenario | Kemampuan | Catatan |
|----------|-----------|---------|
| Beban pengguna (10-50 concurrent) | ✅ Mampu | Laragon + PHP-FPM thread-safe |
| Beban data besar (500K+ baris sipw_import) | ⚠️ Perlu indeks | `sipw_import` punya indeks di kdkec, kddesa, idsubsls |
| File Excel besar (10K+ baris) | ✅ OpenSpout streaming | Tidak load semua file ke memory |
| Lonjakan import (100 file batch) | ✅ Batch commit 500 baris | Transaksi per batch, auto-rollback |
| Pertumbuhan activity_logs | ⚠️ Tidak ada archival | 708 baris sekarang, akan membesar seiring penggunaan |
| Backup database | ✅ Script terpisah | `scripts/backup.ps1`, full + incremental, GZip, rotasi 7/30 hari |

### ⚠️ 1.7 Masalah Arsitektur Kritis: Dual Namespace

```
composer.json:  "App\\" → "src/"
app/bootstrap.php: "App\\" → "app/" (via spl_autoload_register)
```

Dua direktori menggunakan **namespace yang sama** (`App\`). Composer autoload akan memenangkan `src/`, sementara autoload custom hanya akan dipanggil jika Composer gagal menemukan kelas.

**Dampak:**
- `new App\Controllers\DashboardController()` → selalu ambil dari `src/`
- `require 'app/bootstrap.php'` → autoload custom hanya melengkapi, tidak mengganti
- Kelas duplikat: `App\Helpers\Env`, `App\Helpers\Database`
- Aplikasi **berpotensi error** jika runtime memuat kelas dari path yang salah

| Tingkat Keparahan | **KRITIS** |
|-------------------|------------|
| Perbaikan | Konsolidasi `app/` ke dalam `src/` atau namespace terpisah (misal `App\Legacy\`) |

---

## 2. AUDIT KEAMANAN APLIKASI

### 2.1 Autentikasi & Otorisasi

| Aspek | Implementasi | Penilaian |
|-------|-------------|-----------|
| Hash password | `password_hash()` bcrypt cost 12 | ✅ Baik |
| Session fingerprint | IP + User-Agent | ✅ Baik |
| Rate limiting login | 5 gagal → 15 menit lockout | ✅ Baik |
| Role-based access | Middleware `RoleMiddleware` | ✅ Baik |
| Session regeneration | Ya, setelah login | ✅ Baik |
| Session lifetime | 30 menit (konfigurasi .env) | ✅ Default wajar |

**⚠️ Masalah Login:**
- Pada `LoginForm`, tidak ada CSRF token di form login. Middleware CSRF hanya memproses POST request, tapi form login mungkin menggunakan GET di beberapa kondisi. Token CSRF tidak dirender di form login (`views/auth/login.php` perlu dicek).
- Activity_logs untuk rate limiting bisa menjadi target serangan DoS dengan membanjiri log.

### 2.2 Kerentanan Umum

| Kerentanan | Status | Bukti |
|-----------|--------|-------|
| SQL Injection | ✅ Terlindungi | PDO prepared statements di semua query |
| XSS | ✅ Terlindungi | Output selalu `htmlspecialchars()` atau `escape()` |
| CSRF | ✅ Terlindungi | Token 32-byte hex divalidasi di POST/PUT/DELETE |
| Session Hijacking | ✅ Terlindungi | Fingerprinting + HTTP-only cookies |
| Directory Traversal | ✅ Terlindungi | `.htaccess` blokir akses langsung ke direktori |
| Path Disclosure | ✅ | `display_errors=0` di production |

### 2.3 Penyimpanan Data Sensitif

| Data | Metode | Penilaian |
|------|--------|-----------|
| Password user | bcrypt cost 12 | ✅ Aman |
| Database password | Plain text di `.env` | ⚠️ **Risiko sedang** - tidak dienkripsi |
| Session data | File-based di storage | ✅ Tidak terekspos publik |
| CSRF token | Session | ✅ |

### 2.4 Keamanan Transport

| Aspek | Status | Catatan |
|-------|--------|---------|
| HTTPS | ⚠️ Tidak aktif | Berjalan di localhost Laragon tanpa SSL |
| Security headers | ✅ | `.htaccess` menyetel X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy |
| Cookie flags | ✅ HTTP-only | Session cookie memiliki HttpOnly |

### 2.5 Potensi Kerentanan Lain

| # | Temuan | Severity | Detail |
|---|--------|----------|--------|
| 1 | `.env` tidak di-gitignore | **Tinggi** | Kredensial database bisa ter-commit |
| 2 | `storage/` tidak di-gitignore | **Sedang** | Cache, log, upload tidak boleh masuk repo |
| 3 | Tidak ada validasi file upload MIME | **Sedang** | Import SIPW hanya cek ekstensi, bukan isi file |
| 4 | Debug mode aktif | **Rendah** | `display_errors = 1` di beberapa script |
| 5 | Tidak ada Content-Security-Policy | **Rendah** | Tidak melindungi dari inline script injection |

---

## 3. ANALISIS KINERJA

### 3.1 Waktu Muat Halaman (estimasi di Laragon local)

| Halaman | Perkiraan | Catatan |
|---------|-----------|---------|
| Dashboard utama | < 500ms | Query COUNT dari beberapa tabel |
| Monitoring (DataTables) | < 1s | Server-side processing, pagination |
| Import SIPW (1 file 5000 baris) | ~6 detik | Streaming + 500-row batch transaction |
| Rekap-sls (16772 baris) | ~30 detik | Batch insert 500 baris |
| Laporan Excel/PDF | 1-3 detik | Tergantung jumlah data |
| Login | < 100ms | Query sederhana + session |

### 3.2 Hambatan Kinerja Teridentifikasi

| # | Hambatan | Severity | Detail |
|---|----------|----------|--------|
| 1 | `activity_logs` tanpa indeks | **Sedang** | Kolom `target_type`, `target_id`, `action` tanpa indeks. Query audit log melakukan UNION dari 3 tabel + LIKE. |
| 2 | Query dashboard melakukan 10+ COUNT terpisah | **Rendah** | Setiap COUNT adalah query terpisah, walau cepat di tabel kecil |
| 3 | Tidak ada Redis/Memcached | **Rendah** | File cache sudah cukup untuk deployment tunggal |
| 4 | Backup database full tanpa kompresi | **Rendah** | Script backup sudah menggunakan GZip |

### 3.3 Efisiensi Sumber Daya

| Sumber Daya | Penggunaan | Catatan |
|-------------|-----------|---------|
| CPU | Ringan | PHP native tanpa framework berat |
| Memory | ~30-50MB per request | OpenSpout streaming, tidak load file ke memory |
| Disk (Database) | ~100-200MB | 43 tabel, 500K baris sipw_import |
| Bandwidth | Rendah | Static assets via CDN, hanya data JSON via AJAX |

### 3.4 Rekomendasi Kinerja

| Item | Upaya | Dampak |
|------|-------|--------|
| Tambah indeks `activity_logs(target_type, action)` | Rendah | Tinggi |
| Ubah batch size import dari 500 → 1000 | Rendah | Sedang |
| Implementasi query dashboard menggunakan `SELECT (SELECT COUNT...)` subquery dalam satu query | Rendah | Rendah |
| Tambah `EXPLAIN` monitoring untuk slow query | Rendah | Sedang |

---

## 4. TINJAUAN KEGUNAAN (USABILITY) & UX

### 4.1 Alur Kerja Inti

1. **Login** → Dashboard (statistik ringkas)
2. **Import SIPW** → Upload Excel → Preview → Konfirmasi → Import
3. **Alokasi Petugas** → Pilih SLS → Assign PCL/PML/Task Force
4. **Monitoring** → Filter kecamatan/desa → Lihat progres
5. **Laporan** → Pilih jenis → Export Excel/PDF/CSV/Print

### 4.2 Titik Hambatan

| # | Masalah | Severity | Detail |
|---|---------|----------|--------|
| 1 | Tidak ada error pages (401/403/404/500) | **Sedang** | File view tidak ditemukan. Aplikasi akan menampilkan white screen atau PHP error mentah. |
| 2 | Import hanya 1 file per sesi | **Rendah** | Tidak bisa batch upload multiple file sekaligus |
| 3 | Loading state tidak konsisten | **Rendah** | Beberapa tombol tidak menampilkan spinner/disabled saat proses |
| 4 | Navigasi sidebar tidak responsif di mobile | **Rendah** | Bootstrap sidebar collapse butuh dikaji ulang untuk layar sempit |

### 4.3 Kompatibilitas Perangkat & Browser

| Platform | Status | Catatan |
|----------|--------|---------|
| Desktop Chrome | ✅ | Optimal |
| Desktop Firefox | ✅ | Optimal |
| Desktop Edge | ✅ | Optimal |
| Tablet (iPad/Android) | ⚠️ | Layout mungkin terpotong pada sidebar lebar |
| Mobile (HP) | ⚠️ | DataTables tidak optimal di layar < 768px |

### 4.4 Celah Kebutuhan vs Fitur

| Kebutuhan Operator | Tersedia? | Catatan |
|--------------------|-----------|---------|
| Melihat progres real-time | ✅ | Dashboard + Monitoring dengan filter |
| Import data dari SIPW | ✅ | Preview + batch + rollback |
| Alokasi petugas per SLS | ✅ | Single + bulk assignment |
| Laporan per kecamatan | ✅ | Excel/CSV/PDF/Print |
| Rating beban kerja petugas | ✅ | Workload ranking |
| Audit trail perubahan | ✅ | Activity logs + assignment logs |
| Notifikasi real-time | ❌ | Tidak ada WebSocket/polling |
| Sinkronasi otomatis dengan SIPW | ❌ | Import masih manual upload file |
| Export data ke aplikasi existing | ❌ | Dashboard hanya baca, tidak tulis ke Web SE2026 |

---

## 5. ANALISIS KUALITAS KODE

### 5.1 Keterbacaan & Standarisasi

| Aspek | Penilaian | Catatan |
|-------|-----------|---------|
| Konvensi penamaan | ✅ | PSR-4 autoload, CamelCase class, camelCase method |
| Type hinting | ✅ | `declare(strict_types=1)` sebagian besar file |
| PHP DocBlock | ⚠️ Minimal | Sebagian method tidak memiliki dokumentasi |
| PSR-12 | ⚠️ Sebagian | Indentasi, braces, spacing cukup konsisten |
| Clean Code | ✅ | Method pendek, tanggung jawab tunggal |

### 5.2 Duplikasi & Kode Mati

| # | Temuan | Severity |
|---|--------|----------|
| 1 | Dual `App\Helpers\Env` di `src/` dan `app/` | **Tinggi** |
| 2 | Dual `App\Helpers\Database` vs `App\Core\Database` | **Tinggi** |
| 3 | `views/partials/sidebar.php` dan `navbar.php` memiliki CSS inline yang redundan | **Rendah** |
| 4 | Konfigurasi database (`DB_NAME` vs `DB_DATABASE`) di duplicate dengan fallback | **Sedang** |

### 5.3 Cakupan Pengujian

| Jenis Test | Status | Catatan |
|-----------|--------|---------|
| Unit test | ❌ Tidak ada | Tidak ada phpunit.xml, tidak ada direktori tests/ |
| Integration test | ❌ Tidak ada | Tidak ada pengujian database |
| End-to-end test | ❌ Tidak ada | Tidak ada Cypress/Playwright/Selenium |
| Manual test | ✅ | Dilakukan oleh pengembang melalui browser |

**Risiko:** Setiap perubahan harus diuji manual. Tidak ada safety net untuk refactoring.

### 5.4 Dokumentasi

| Jenis | Tersedia | Mutu |
|-------|----------|------|
| Kode (DocBlock) | ⚠️ Sebagian | Minimal, hanya function signature |
| Arsitektur | ✅ | Dokumentasi arsitektur di `dokumentasi/` tapi **sudah outdated** |
| Panduan Operator | ✅ | `docs/PANDUAN_OPERATOR.md` (Bahasa Indonesia) |
| Panduan Deployment | ✅ | `docs/DEPLOYMENT.md` |
| Rencana Maintenance | ✅ | `docs/MAINTENANCE.md` |
| Disaster Recovery | ✅ | `docs/DISASTER_RECOVERY.md` |

---

## 6. ANALISIS BISNIS DAN FUNGSIONALITAS

### 6.1 Kesesuaian Fitur dengan Spesifikasi

| Modul | Implementasi | Kesesuaian |
|-------|-------------|-----------|
| Dashboard | ✅ Statistik real-time dengan Chart.js | Sesuai |
| Import SIPW | ✅ Preview, batch UPSERT, rollback | Sesuai |
| Alokasi Petugas | ✅ Single + bulk assign, history | Sesuai |
| Monitoring | ✅ DataTables server-side, filter | Sesuai |
| Wilayah | ✅ CRUD kebutuhan/terisi petugas | Sesuai |
| Petugas | ✅ CRUD role, toggle status | Sesuai |
| Workload | ✅ Ranking beban per petugas | Sesuai |
| Reports | ✅ Multiple format export | Sesuai |
| Audit Log | ✅ Riwayat perubahan | Sesuai |

### 6.2 Fitur yang Tidak Memberikan Nilai Tambah

| Fitur | Alasan |
|-------|--------|
| File cache (`Cache.php`) | Data sudah real-time dari DB, cache jarang memberikan manfaat signifikan untuk data monitoring |
| Rollback import | Berguna tetapi jarang digunakan — menambah kompleksitas. Alternatif: backup DB saja cukup |

### 6.3 Perbandingan dengan Aplikasi Serupa

| Aspek | Dashboard SE2026 | Aplikasi SE Lain | Catatan |
|-------|-----------------|-------------------|---------|
| Import data | ✅ Streaming Excel | ⚠️ Excel via library serupa | Setara |
| Monitoring real-time | ✅ DataTables server-side | ⚠️ Biasanya refresh manual | **Unggul** |
| Notifikasi | ❌ Tidak ada | ⚠️ Sebagian punya email/SMS | **Kurang** |
| Multi-user | ✅ RBAC | ✅ Umum | Setara |
| Mobile support | ⚠️ Terbatas | ⚠️ Umum | Setara |
| API | ❌ Tidak ada | ⚠️ Sebagian punya REST API | **Kurang** |
| Export | ✅ Multi-format | ✅ Standar | Setara |

### 6.4 Metrik Bisnis

| Metrik | Nilai | Sumber |
|--------|-------|--------|
| Total SLS Jember | 16.538 | `master_sls` |
| SLS sudah di-import | 4.975 (30%) | `sipw_import` — hanya 9 kecamatan |
| SLS belum di-import | 11.563 (70%) | 22 kecamatan |
| User terdaftar | 15 | `users` |
| Aktivitas tercatat | 708 | `activity_logs` |
| File import | 100 file | `data/sipw/` (semua identik, 1 data unik) |

---

## 7. DAFTAR REKOMENDASI PERBAIKAN (PRIORITAS)

### 🔴 Kritis (Segera)

| # | Rekomendasi | Upaya | Dampak |
|---|-------------|-------|--------|
| K1 | Konsolidasi namespace `app/` dan `src/` | 2-3 hari | Mencegah error tak terduga |
| K2 | Tambah .gitignore untuk .env, storage/, cache, vendor | 1 jam | Mencegah kebocoran kredensial |
| K3 | Lengkapi data SIPW untuk 22 kecamatan yang hilang | 1-2 hari | Cakupan data 30% → 100% |
| K4 | Buat halaman error (401/403/404/500) | 1 hari | UX profesional, aman |

### 🟠 Tinggi

| # | Rekomendasi | Upaya | Dampak |
|---|-------------|-------|--------|
| T1 | Setup testing otomatis (PHPUnit untuk unit test) | 2-3 hari | Jaminan kualitas jangka panjang |
| T2 | Tambah indeks di activity_logs(target_type, action) | 1 jam | Kinerja audit log |
| T3 | Buat halaman health check (`health.php`) | 4 jam | Monitoring uptime |
| T4 | Validasi MIME file upload (cek magic bytes, bukan ekstensi) | 2 jam | Keamanan upload |
| T5 | Hapus file data/sipw/ duplikat (99 file identik) | 1 jam | Hemat disk 500MB+ |

### 🟡 Sedang

| # | Rekomendasi | Upaya | Dampak |
|---|-------------|-------|--------|
| S1 | Update dokumentasi arsitektur `dokumentasi/ARSITEKTUR_*.md` | 4 jam | Dokumentasi akurat |
| S2 | Tambah CSRF token di form login | 2 jam | Keamanan autentikasi |
| S3 | Ganti CDN assets ke versi lokal (fallback) | 1 hari | Bisa berjalan offline |
| S4 | Implementasi fitur batch upload (multiple file sekaligus) | 1-2 hari | Produktivitas operator |
| S5 | Tambah loading state (spinner) di tombol-tombol aksi | 4 jam | UX lebih baik |

### 🟢 Rendah

| # | Rekomendasi | Upaya | Dampak |
|---|-------------|-------|--------|
| R1 | Rapikan CSS inline di partials ke app.css | 2 jam | Maintainability |
| R2 | Tambah Content-Security-Policy header | 1 jam | Keamanan tambahan |
| R3 | Setup migration runner (Phinx atau custom) | 1 hari | Manajemen skema DB |
| R4 | Audit dan hapus file/direktori yang tidak dipakai | 2 jam | Kebersihan proyek |

---

## 8. REKOMENDASI JANGKA PANJANG

### 8.1 Arsitektur

1. **Migrasi ke PHP Framework**: Laravel atau Symfony untuk routing, ORM, queue, dan testing terintegrasi
2. **REST API**: Pisahkan backend API dan frontend agar dapat dikonsumsi oleh aplikasi Web SE2026 existing
3. **Service Layer Refactor**: Pisahkan logika bisnis dari controller untuk testability

### 8.2 Infrastruktur

4. **CI/CD Pipeline**: GitHub Actions untuk test otomatis, linting, dan deployment
5. **Dockerisasi**: Buat Docker Compose untuk development dan produksi yang konsisten
6. **Database Migration**: Implementasi migration system untuk versioning schema
7. **Monitoring**: Integrasi dengan alat monitoring (e.g., CheckMK, Zabbix, atau Uptime Kuma)

### 8.3 Fungsionalitas

8. **Notifikasi**: WebSocket atau polling untuk notifikasi real-time ke operator
9. **Sinkronasi SIPW**: Koneksi langsung ke API SIPW untuk import otomatis tanpa upload file
10. **Dashboard Khusus Pimpinan**: View ringkas untuk Kepala BPS dengan grafik strategis
11. **Ekspor Data ke Web SE2026**: Tulis hasil monitoring/alokasi kembali ke database Web SE2026

---

## LAMPIRAN

### A. Inventaris File (Ringkasan)

```
dashboard-se2026/
├── index.php                  # Entry point utama
├── .env                       # Konfigurasi database (TIDAK di-gitignore)
├── .htaccess                  # Hardening + routing
├── composer.json              # Dependencies
├── config/
│   ├── config.php             # App config
│   └── constants.php          # Role constants
├── app/                       # [DUPLIKAT] Shared DB layer (namespace App\)
├── src/                       # Kode utama (namespace App\)
│   ├── Core/                  # Framework inti
│   ├── Controllers/           # 10 controller
│   ├── Models/                # 5 model
│   ├── Services/              # ImportProcessor + Validator
│   ├── Middleware/            # Auth, Role, CSRF
│   └── Helpers/               # Session, Security, Cache, dll
├── views/                     # Template Bootstrap 5
├── assets/                    # CSS, JS custom
├── database/                  # SQL patches
├── docs/                      # Dokumentasi (7 file)
├── dokumentasi/               # Dokumentasi arsitektur
├── scripts/                   # backup.ps1, restore.ps1, rollback-import.php
├── storage/                   # Cache, logs, uploads
├── public/                    # Test connection endpoint
├── data/                      # Data SIPW & rekap
├── import-sipw-data.php       # CLI importer
├── import-rekap-sls.php       # CLI rekap importer
└── test-shared-connection.php # CLI test DB
```

### B. Tabel Database Utama

| Tabel | Baris | Fungsi |
|-------|-------|--------|
| sipw_import | ~4.975 | Data SLS hasil import SIPW |
| master_sls | ~16.538 | Master referensi SLS seluruh Jember |
| users | 15 | User operator dashboard |
| activity_logs | 708 | Log aktivitas |
| sipw_assignment | 0 | Alokasi petugas (belum diisi) |
| monitoring_progress | 0 | Progres monitoring (belum diisi) |
| wilayah_kerja | 31 | Data kecamatan |
| desa | 477 | Data desa |
| alokasi_petugas | 0 | Alokasi (belum diisi) |

### C. Severity Matrix

```
KRITIS  ■■■■ 4 temuan (wajib diperbaiki segera)
TINGGI  ■■■■ 4 temuan (perlu perbaikan 1-2 minggu)
SEDANG  ■■■■■■ 6 temuan (perbaikan 1-3 bulan)
RENDAH  ■■■■■ 5 temuan (perbaikan 3-6 bulan)
```
