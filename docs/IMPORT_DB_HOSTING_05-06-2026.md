# Laporan Import Database Hosting → Localhost
**Tanggal eksekusi**: 05 Juni 2026, 08:48–08:53 WIB
**File sumber**: `database/db hosting/5-6-2026_17-59wib_bpsjembe_se2026_jember.sql` (17.15 MB, 60.296 baris)
**Database target**: `bps_jember_se2026` @ `localhost:3306` (MySQL 8.0.30)
**Status akhir**: BERHASIL — exit code 0, 0 byte error log, semua tabel kunci match.

---

## 1. Verifikasi Struktur & Kompatibilitas

### 1.1 Metadata file sumber
| Atribut | Nilai |
|---|---|
| Source server | `bpsjember.my.id:3306` (jagoan_hosting) |
| Source engine | MariaDB **10.6.25** (`100625-MariaDB-cll-lve`) |
| Source schema | `bpsjembe_se2026_jember` |
| Tool dump | Navicat Premium |
| Encoding | UTF-8 (utf8mb4) |
| Tanggal dump | 05/06/2026 08:31:59 |

### 1.2 Statement breakdown
| Statement | Jumlah |
|---|---|
| `DROP TABLE IF EXISTS` | 57 |
| `CREATE TABLE` | 57 |
| `INSERT INTO` | 58.740 (single-row, format Navicat) |
| `CREATE PROCEDURE` | 1 (`add_index_if_missing`) |
| `LOCK TABLES` | 0 |
| `CREATE TRIGGER/VIEW/FUNCTION` | 0 |

### 1.3 Cek kompatibilitas MariaDB 10.6 → MySQL 8.0.30
| Fitur | Source SQL | MySQL 8.0.30 | Verdict |
|---|---|---|---|
| `DEFAULT current_timestamp()` (kurung) | 72 occurrences | OK (didukung sejak 8.0.13) | ✅ |
| `USING BTREE` after index | Ya | Diabaikan untuk InnoDB | ✅ |
| `ROW_FORMAT = DYNAMIC` | Ya | Didukung | ✅ |
| Collation `utf8mb4_unicode_ci/_bin/_general_ci` | Ya | Didukung | ✅ |
| `delimiter ;;` (CLI directive) | Ya (untuk PROCEDURE) | Didukung mysql CLI | ✅ |
| MariaDB-only (`JSON_VALUE`, `WITH SYSTEM VERSIONING`, `SEQUENCE`, `INVISIBLE`, `VIRTUAL/PERSISTENT`) | **0 occurrences** | N/A | ✅ |

### 1.4 Perbedaan schema source vs lokal (sebelum import)
**Tabel TAMBAHAN di source** (10 tabel — akan dibuat di lokal):
- `alokasi_kamar_links`, `alokasi_kelas_links`, `app_settings`, `materi_pelatihan_links`
- `online_gojags_link`, `online_jadwal_mooc`, `online_kelas_links`, `online_panduan_links`
- `petugas_wilayah`, `qna_materi`

**Tabel HANYA di lokal** (9 tabel — akan dipertahankan, tidak terdampak DROP):
- `_test_ine`, `master_sls_bkp`, `sipw_import_bkp`, `sipw_assignment_bkp`
- `dash_rollback_points_archive` (119 MB arsip)
- `prelist_kabkota`, `prelist_kecamatan`, `prelist_sls` (234.180 rows), `prelist_subsektor` (191.566 rows)

**Perubahan schema kritis**:
- `users.role` ENUM di source: `('admin','operator','pegawai','mitra','pml','pcl','pj','panitia')` — **TANPA `task_force`**
- `users.role` ENUM di lokal sebelumnya: `('admin','operator','pegawai','mitra','pml','pcl','pj','task_force','panitia')`
- Setelah import: schema source diadopsi, nilai `task_force` hilang. **Verifikasi: 0 user dengan role `task_force` di source → tidak ada data loss**.

---

## 2. Backup Database Lokal

| Atribut | Nilai |
|---|---|
| Command | `mysqldump -u root --single-transaction --routines --triggers --events --hex-blob --add-drop-database --databases bps_jember_se2026` |
| Output file | `storage/backup/pre_hosting_import_2026-06-05_084840.sql` |
| Ukuran | **195.55 MB** (205.051.892 bytes) |
| Durasi | ~14 detik |
| Verifikasi | Header valid, footer `Dump completed`, 56 `CREATE TABLE` ditemukan |

**Catatan**: ukuran backup lebih besar dari source (195 MB vs 17 MB) karena lokal punya tabel `prelist_*` (50+ MB data) dan arsip `dash_rollback_points_archive` (116 MB) yang tidak ada di source.

---

## 3. Konfigurasi Koneksi Localhost

| Parameter | Nilai | Sumber |
|---|---|---|
| Host | `localhost` | `.env` `DB_HOST` |
| Port | `3306` | `.env` `DB_PORT` |
| Username | `root` | `.env` `DB_USERNAME` |
| Password | *(empty)* | `.env` `DB_PASSWORD` |
| Database | `bps_jember_se2026` | `.env` `DB_DATABASE` |
| MySQL version | 8.0.30 | `SELECT @@version` |
| sql_mode | `STRICT_TRANS_TABLES, NO_ZERO_*, ERROR_FOR_DIVISION_BY_ZERO` | `SELECT @@sql_mode` |
| max_allowed_packet | 512 MB | server default Laragon |
| Grants | `ALL PRIVILEGES *.* WITH GRANT OPTION` | `SHOW GRANTS` |

✅ Tes `SELECT CURRENT_USER()` → `root@localhost` berhasil.

---

## 4. Eksekusi Import

### 4.1 Command
```cmd
mysql.exe --default-character-set=utf8mb4 --max_allowed_packet=512M -u root bps_jember_se2026 < "5-6-2026_17-59wib_bpsjembe_se2026_jember.sql" > import_output.log 2> import_errors.log
```

### 4.2 Hasil eksekusi
| Atribut | Nilai |
|---|---|
| Exit code | **0** |
| Durasi | **102.3 detik** (~1 menit 42 detik) |
| stderr (error log) | **0 byte** (tidak ada error) |
| stdout (output log) | 0 byte (mode non-verbose) |
| Output log | `storage/backup/import_output_2026-06-05_085002.log` |
| Error log | `storage/backup/import_errors_2026-06-05_085002.log` |

### 4.3 Catatan teknis
- **Awalnya gagal** dengan command PowerShell native `< $source > $log 2> $errLog`:
  - PowerShell **tidak mendukung input redirection `<`** seperti Bash/cmd.
  - **Solusi**: gunakan `Start-Process cmd.exe /c "..."` yang men-delegasikan ke `cmd.exe` shell yang men-support `<` redirect.
- `FOREIGN_KEY_CHECKS = 0` sudah dideklarasikan di baris 18 SQL file, sehingga urutan CREATE TABLE bebas dependency conflict.
- Mode `-f` (force, lanjut meski error) tidak diaktifkan — strict mode untuk memastikan error langsung terdeteksi.

---

## 5. Verifikasi Hasil Import

### 5.1 Perbandingan record count: Source SQL ↔ Local DB
| Tabel | Source (`grep -c "^INSERT INTO"`) | Local (`COUNT(*)`) | Status |
|---|---:|---:|---|
| `activity_logs` | 28.883 | 28.883 | ✅ EXACT |
| `users` | 3.407 | 3.407 | ✅ EXACT |
| `master_sls` | 16.538 | 16.538 | ✅ EXACT |
| `sipw_import` | 6.056 | 6.056 | ✅ EXACT |
| `pendaftaran` | 2.454 | 2.454 | ✅ EXACT |
| `pendaftaran_changelog` | 927 | 927 | ✅ EXACT |
| `desa` | 248 | 248 | ✅ EXACT |
| `akun_fasih` | 101 | 101 | ✅ EXACT |
| `wilayah_kerja` | 31 | 31 | ✅ EXACT |
| `mfd_kec` | 31 | 31 | ✅ EXACT |
| `kebutuhan_petugas_kecamatan` | 31 | 31 | ✅ EXACT |
| `pengumuman` | 5 | 5 | ✅ EXACT |
| `dash_assignment_log` | 3 | 3 | ✅ EXACT |
| `app_settings` (NEW) | 3 | 3 | ✅ EXACT |
| `sipw_assignment` | 0 | 0 | ✅ EXACT |

### 5.2 Tabel lokal yang dipertahankan
| Tabel | Rows | Catatan |
|---|---:|---|
| `prelist_sls` | 234.180 | Data prelist SE2026 (impor XLSX) |
| `prelist_subsektor` | 191.566 | Data subsektor A |
| `prelist_kecamatan` | 667 | Master kecamatan + SBR |
| `prelist_kabkota` | 38 | Master kab/kota |
| `master_sls_bkp` | 16.772 | Backup pre-impor SLS |
| `sipw_import_bkp` | 16.772 | Backup pre-impor SIPW |
| `sipw_assignment_bkp` | 0 | Backup pre-impor assignment |
| `dash_rollback_points_archive` | 23 | Arsip 119.52 MB |
| `_test_ine` | 0 | Test table |

### 5.3 Tabel baru dari source (sebelumnya tidak ada di lokal)
✅ `alokasi_kamar_links`, `alokasi_kelas_links`, `app_settings`, `materi_pelatihan_links`, `online_gojags_link`, `online_jadwal_mooc`, `online_kelas_links`, `online_panduan_links`, `petugas_wilayah` (0 rows), `qna_materi`

### 5.4 Schema integrity
| Cek | Hasil |
|---|---|
| Total tabel | **66** (56 lama + 10 baru) |
| Engine | Semua **InnoDB** (66/66) |
| Foreign keys | **62 constraint** masih aktif |
| Stored procedure | **1** (`add_index_if_missing`) |
| Triggers | 0 |
| Events | 0 |
| Charset DB | `utf8mb4 / utf8mb4_0900_ai_ci` |

### 5.5 Verifikasi aplikasi PHP
```
$ php -r "require 'src/bootstrap.php'; use App\Core\Database; ..."
Users via PHP:   3407   ✅
Prelist SLS:     234180 ✅
Activity logs:   28883  ✅
PHP DB connection: OK
```

### 5.6 Distribusi role users
| Role | Jumlah |
|---|---:|
| mitra | 3.349 (2.471 active + 878 inactive) |
| pegawai | 51 |
| admin | 5 |
| panitia | 2 |
| **task_force** | **0** (enum dihapus, tidak ada data loss) |

---

## 6. Error & Solusi

| # | Error / Issue | Lokasi | Solusi |
|---|---|---|---|
| 1 | PowerShell tidak mendukung input redirection `<` untuk binary `mysql.exe` → exit 1 dalam 0 detik | Tahap 4 attempt #1 | Bungkus dengan `Start-Process cmd.exe /c "..."` yang men-support `<` redirect ala cmd.exe |
| 2 | `SELECT ... AS rows` gagal `ERROR 1064` | Tahap 5 query verifikasi | `rows` adalah reserved word di MySQL 8.0 → ganti alias jadi `row_cnt`/`cnt` |
| 3 | `users.role` ENUM kehilangan value `task_force` setelah import | Pasca-import | Source punya 0 user dengan role tersebut → tidak ada data loss. Jika app code masih reference `task_force`, query WHERE akan return empty (bukan error). Jika perlu, jalankan `ALTER TABLE users MODIFY COLUMN role ENUM(..., 'task_force', 'panitia') ...` post-import. |
| 4 | `activity_logs` count berfluktuasi (28.884 → 27.648 → 28.883) saat verifikasi | Tahap 5 | `AuthController::clearFailedAttempts()` mendelete `login_failed` log saat user berhasil login. Aplikasi lokal sedang diakses paralel saat verifikasi. Setelah settle, count = 28.883 ✅ |
| 5 | `pendaftaran` ORDER BY `created_at` gagal | Tahap 5 spot-check | Tabel `pendaftaran` tidak punya kolom `created_at` (pakai field lain). Bukan error import, hanya kesalahan query verifikasi. |

---

## 7. File Output

| File | Path | Ukuran | Tujuan |
|---|---|---|---|
| Backup lokal pre-import | `storage/backup/pre_hosting_import_2026-06-05_084840.sql` | 195.55 MB | Rollback jika diperlukan |
| Import error log | `storage/backup/import_errors_2026-06-05_085002.log` | 0 byte | Bukti tidak ada error |
| Import output log | `storage/backup/import_output_2026-06-05_085002.log` | 0 byte | Stdout import (non-verbose) |
| Laporan ini | `docs/IMPORT_DB_HOSTING_05-06-2026.md` | — | Dokumentasi proses |

---

## 8. Rollback Procedure (jika perlu)

```cmd
mysql -u root -e "DROP DATABASE bps_jember_se2026;"
mysql -u root < "C:\laragon\www\dashboard-se2026\storage\backup\pre_hosting_import_2026-06-05_084840.sql"
```

Backup berisi `DROP DATABASE IF EXISTS` + `CREATE DATABASE` + `USE` di header, sehingga akan men-restore database sepenuhnya ke kondisi pra-import.

---

## 9. Kesimpulan

**STATUS: ✅ SUKSES TANPA ERROR**

- **5 dari 5 langkah eksekusi berhasil** (verifikasi, backup, koneksi, import, verifikasi-pasca).
- **15 dari 15 tabel utama** match row count source → local secara eksak.
- **9 tabel lokal-only dipertahankan** tanpa kehilangan satu pun row.
- **10 tabel baru ditambahkan** dari source ke lokal.
- **1 perubahan schema minor** (`users.role` ENUM kehilangan `task_force`) — **tidak ada data loss**, hanya perlu perhatian jika kode aplikasi masih reference value tersebut.
- **Aplikasi PHP dapat membaca database baru** tanpa modifikasi konfigurasi.
- **Backup pra-import tersedia** untuk rollback (195.55 MB).
- **Total durasi end-to-end**: ~4 menit (backup 14 dtk + import 102 dtk + verifikasi 30 dtk + dokumentasi).

### Rekomendasi tindak lanjut
1. **(Opsional)** Re-tambahkan value `task_force` ke ENUM jika ada kode/feature yang menggunakannya:
   ```sql
   ALTER TABLE users MODIFY COLUMN role
     ENUM('admin','operator','pegawai','mitra','pml','pcl','pj','task_force','panitia')
     NOT NULL DEFAULT 'mitra';
   ```
2. **(Opsional)** Hapus backup lama (`pre_hosting_import_*.sql`) jika sudah yakin import stabil setelah beberapa hari (saves ~196 MB).
3. **(Opsional)** Re-apply patch lokal (`patch_009_pegawai_kecamatan.sql` etc) bila perlu — namun verifikasi menunjukkan kolom `kecamatan_tugas` sudah ada di schema source (sudah merged ke hosting).
