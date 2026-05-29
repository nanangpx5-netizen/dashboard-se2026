# SOP Backup & Recovery — Dashboard SE2026 Jember

## 1. Tujuan

Dokumen ini menjelaskan prosedur backup dan recovery database dashboard SE2026. Meliputi backup otomatis terjadwal, backup incremental, restore dari backup, rollback import SIPW, dan audit trail perubahan assignment.

## 2. Lingkup

| Komponen | Keterangan |
|----------|------------|
| Database | `bps_jember_se2026` — tabel dashboard: `sipw_import`, `sipw_assignment`, `dash_import_log`, `dash_monitoring_summary`, `dash_assignment_log`, `dash_rollback_points` |
| Aplikasi | File konfigurasi: `.env`, `config/*.php` |
| Storage | File upload: `storage/uploads/`, cache: `storage/cache/` |

## 3. Strategi Backup

### 3.1 Full Backup (Harian)

- **Jadwal**: Setiap hari pukul 02:00 WIB
- **Metode**: `mysqldump --single-transaction` — struktur + data semua tabel dashboard
- **Retensi**: 30 hari
- **Lokasi**: `storage/backups/dashboard_full_YYYYMMDD_HHmmss.sql.gz`
- **Estimasi**: ~1-5 MB (tergantung jumlah SLS)

### 3.2 Incremental Backup (6 Jam)

- **Jadwal**: Pukul 08:00, 14:00, 20:00 WIB
- **Metode**: `mysqldump --where="updated_at >= last_full_backup"` per tabel
- **Syarat**: Hanya berjalan jika ada full backup sebelumnya
- **Retensi**: 30 hari (sejalan dengan full backup)
- **Lokasi**: `storage/backups/dashboard_inc_YYYYMMDD_HHmmss.sql.gz`

### 3.3 Pre-Restore Safety Backup

- **Trigger**: Otomatis sebelum setiap operasi restore
- **Metode**: Full backup sesaat sebelum restore
- **Lokasi**: `storage/backups/pre_restore_YYYYMMDD_HHmmss.sql.gz`
- **Tujuan**: Jika restore gagal, data bisa dikembalikan ke kondisi sebelum restore

### 3.4 Rollback Point (Per-Import)

- **Trigger**: Setiap kali import file SIPW
- **Metode**: Snapshot JSON semua baris `sipw_import` yang ada sebelum import
- **Penyimpanan**: Tabel `dash_rollback_points` di database
- **Retensi**: 30 hari (auto-cleanup via CLI)
- **Fungsi**: Mengembalikan data ke kondisi sebelum import tertentu

## 4. Infrastruktur Backup

### 4.1 File System

```
storage/
├── backups/                  # Hasil backup mysqldump
│   ├── dashboard_full_20260527_020000.sql.gz
│   ├── dashboard_inc_20260527_080000.sql.gz
│   └── pre_restore_20260527_120000.sql.gz
├── logs/
│   ├── backup.log            # Log aktivitas backup
│   └── restore.log           # Log aktivitas restore
└── cache/                    # Cache aplikasi (dibersihkan setelah restore)
```

### 4.2 Database

| Tabel | Fungsi | Ukuran Estimasi |
|-------|--------|-----------------|
| `dash_assignment_log` | Audit trail perubahan assignment | Bertambah ~500 baris/hari |
| `dash_rollback_points` | Snapshot untuk rollback import | ~10-50 baris/bulan |

## 5. Prosedur Backup

### 5.1 Menjalankan Backup Manual

```powershell
# Full backup
cd scripts
.\backup.ps1 -Type full

# Incremental backup
.\backup.ps1 -Type incremental

# Backup ke direktori kustom
.\backup.ps1 -Type full -BackupDir "D:\Backups\SE2026"

# Backup dengan retensi kustom (7 hari)
.\backup.ps1 -Type full -RetentionDays 7
```

### 5.2 Menjadwalkan Backup (Windows Task Scheduler)

```powershell
# Full backup — setiap hari jam 02:00
$action = New-ScheduledTaskAction -Execute "powershell.exe" `
    -Argument "-NoProfile -File `"$PWD\scripts\backup.ps1`" -Type full"
$trigger = New-ScheduledTaskTrigger -Daily -At 02:00
Register-ScheduledTask -TaskName "SE2026 Backup Full" `
    -Action $action -Trigger $trigger -RunLevel Highest

# Incremental backup — setiap 6 jam
$action = New-ScheduledTaskAction -Execute "powershell.exe" `
    -Argument "-NoProfile -File `"$PWD\scripts\backup.ps1`" -Type incremental"
$trigger = New-ScheduledTaskTrigger -Daily -At 08:00,14:00,20:00
Register-ScheduledTask -TaskName "SE2026 Backup Incremental" `
    -Action $action -Trigger $trigger -RunLevel Highest
```

### 5.3 Verifikasi Backup

```powershell
# Lihat daftar backup yang tersedia
.\restore.ps1 -List

# Verifikasi integritas file backup tertentu
.\restore.ps1 -File "storage\backups\dashboard_full_20260527_020000.sql.gz" -DryRun
```

## 6. Prosedur Restore

### 6.1 Restore dari Full Backup Terbaru

```powershell
# List backup terlebih dahulu
.\restore.ps1 -List

# Restore file tertentu
.\restore.ps1 -File "storage\backups\dashboard_full_20260527_020000.sql.gz"

# Restore dengan skip konfirmasi
.\restore.ps1 -File "storage\backups\dashboard_full_20260527_020000.sql.gz" -Force
```

### 6.2 Restore dengan Incremental

Untuk restore point-in-time yang membutuhkan full + incremental:

```powershell
# 1. Restore full backup dulu
.\restore.ps1 -File "storage\backups\dashboard_full_20260526_020000.sql.gz" -Force

# 2. Apply semua incremental setelahnya (urutkan berdasarkan timestamp)
#    (manual: catat nama file incremental dari hasil -List)
Get-ChildItem storage\backups\dashboard_inc_*.sql.gz | Sort-Object Name | ForEach-Object {
    .\restore.ps1 -File $_.FullName -Force
}
```

### 6.3 Restore Tabel Spesifik

Jika hanya perlu satu tabel (misal: `sipw_assignment`):

```bash
# Ekstrak dari dump
gzip -dc storage/backups/dashboard_full_20260527_020000.sql.gz | grep -E "^INSERT INTO `sipw_assignment`" > restore_asg.sql

# Import ke database
mysql -u root bps_jember_se2026 < restore_asg.sql
```

## 7. Prosedur Rollback Import SIPW

### 7.1 Melihat Daftar Import yang Bisa Di-rollback

```bash
cd scripts
php rollback-import.php list
```

Output:

```
=== Daftar Import SIPW ===
  BATCH ID                           FILE        STATUS     BARIS    BARU     UPDATE   GAGAL    ROLLBACK   WAKTU
  ---------------------------------- --------- ---------- -------- -------- -------- -------- ---------- ---------------
  a1b2c3d4-e5f6-7890-abcd-...        data.xlsx   success    1500     1200     300      0        READY      27/05 14:30
  b2c3d4e5-f6a7-8901-bcde-...        data2.xlsx  partial    500      400      50       50       N/A        26/05 09:15
```

### 7.2 Melihat Detail Import

```bash
php rollback-import.php info a1b2c3d4-e5f6-7890-abcd-123456789012
```

### 7.3 Melakukan Rollback

```bash
# PERHATIAN: Operasi ini TIDAK BISA DIBATALKAN
php rollback-import.php rollback a1b2c3d4-e5f6-7890-abcd-123456789012
```

Yang terjadi saat rollback:
1. Baris `sipw_import` yang sudah ada SEBELUM import dikembalikan ke data lama
2. Baris `sipw_import` yang BARU (hasil import) dihapus
3. Assignment terkait baris baru juga dihapus (CASCADE dari FK)
4. Status `dash_import_log` berubah menjadi `rolled_back`
5. Cache dashboard dibersihkan

### 7.4 Cleanup Data Lama

```bash
# Hapus rollback point > 30 hari dan import log > 90 hari
php rollback-import.php cleanup
```

## 8. Prosedur Assignment Audit Trail

### 8.1 Melihat Log Perubahan

Semua perubahan assignment tercatat di tabel `dash_assignment_log` dengan skema:

| Kolom | Isi |
|-------|-----|
| `action` | `INSERT`, `UPDATE`, `DELETE`, `STATUS_CHANGE` |
| `old_data` | Snapshot JSON data sebelum perubahan |
| `new_data` | Snapshot JSON data setelah perubahan |
| `changed_by` | ID user yang melakukan perubahan |
| `ip_address` | IP address pelaku |

### 8.2 Query Log Assignment

```sql
-- Log perubahan untuk satu SLS
SELECT * FROM dash_assignment_log
WHERE sipw_id = 12345
ORDER BY created_at DESC;

-- Log perubahan oleh user tertentu
SELECT * FROM dash_assignment_log
WHERE changed_by = 5
  AND created_at >= CURDATE() - INTERVAL 7 DAY
ORDER BY created_at DESC;

-- Rekap perubahan hari ini
SELECT action, COUNT(*) AS jumlah
FROM dash_assignment_log
WHERE DATE(created_at) = CURDATE()
GROUP BY action;
```

### 8.3 Siapa yang Mengubah Status?

```sql
SELECT
    dal.created_at,
    dal.action,
    dal.change_note,
    u.username AS pelaku,
    dal.ip_address
FROM dash_assignment_log dal
JOIN users u ON u.id = dal.changed_by
WHERE dal.sipw_id = 12345
ORDER BY dal.created_at;
```

## 9. Monitoring & Alerting

### 9.1 Cek Status Backup

```powershell
# Cek log backup terbaru
Get-Content storage/logs/backup.log -Tail 20

# Cek kapan full backup terakhir
Get-ChildItem storage/backups/dashboard_full_*.sql.gz |
    Sort-Object LastWriteTime -Descending | Select-Object -First 5
```

### 9.2 Backup Gagal — Tindakan

Jika backup gagal (tercatat di `backup.log`):
1. Cek koneksi database: `mysql -u root -e "SELECT 1"`
2. Cek disk space: `Get-PSDrive C | Select-Object Used,Free`
3. Cek direktori backup: pastikan `storage/backups/` writable
4. Jalankan manual: `.\scripts\backup.ps1 -Type full`

### 9.3 Rekomendasi Monitoring

- **Daily**: Cek log backup ada `ERROR` atau tidak
- **Weekly**: Verifikasi 1 file backup dengan `-DryRun`
- **Monthly**: Hapus manual backup > 30 hari (jika auto-cleanup mati)

## 10. Troubleshooting

### Backup gagal "Access denied for user"
Verifikasi kredensial database di `.env`. Coba koneksi langsung:
```powershell
mysql -u root -e "SHOW DATABASES"
```

### Restore gagal "Table already exists"
Gunakan `--force` atau manual drop tabel dulu:
```sql
DROP TABLE IF EXISTS sipw_import, sipw_assignment, dash_import_log,
    dash_monitoring_summary, dash_assignment_log, dash_rollback_points;
```
Lalu jalankan restore lagi.

### Rollback point tidak ditemukan
Rollback point hanya disimpan 30 hari. Jika lebih dari itu, gunakan backup database:
1. Restore full backup sebelum import
2. Aplikasikan incremental backup yang relevan
