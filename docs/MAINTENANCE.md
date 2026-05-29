# Maintenance Plan — Dashboard SE2026

## SOP Maintenance Bulanan Aplikasi

**Domain**: `dashboard-se2026.bpsjember.id`
**Server**: Shared Hosting / VPS — PHP 8.2 + MySQL 8.0
**Tim**: IT BPS Kabupaten Jember

---

## Daftar Isi

1. [Jadwal Maintenance](#1-jadwal-maintenance)
2. [Backup Harian & Mingguan](#2-backup-harian--mingguan)
3. [Monitoring Error](#3-monitoring-error)
4. [Database Maintenance](#4-database-maintenance)
5. [Update Dependency](#5-update-dependency)
6. [SOP Jika Aplikasi Error](#6-sop-jika-aplikasi-error)
7. [Scaling Strategy](#7-scaling-strategy)
8. [Security Maintenance](#8-security-maintenance)
9. [Dokumentasi & Knowledge Base](#9-dokumentasi--knowledge-base)
10. [Checklist Maintenance Bulanan](#10-checklist-maintenance-bulanan)
11. [Checklist Maintenance Mingguan](#11-checklist-maintenance-mingguan)
12. [Checklist Maintenance Harian](#12-checklist-maintenance-harian)

---

## 1. Jadwal Maintenance

### 1.1. Ringkasan Jadwal

| Frekuensi | Aktivitas | PIC | Waktu |
|-----------|-----------|-----|-------|
| **Setiap jam** | Cek error log, response time | Otomatis (cron) | - |
| **Harian** | Verifikasi backup, cek disk usage | Operator | 08:00 |
| **Harian** | Cek log error 24 jam terakhir | Operator | 08:00 |
| **Mingguan** | maintenance ringan | IT | Senin 07:00-07:30 |
| **Bulanan** | Maintenance penuh | IT | Sabtu/Minggu 07:00-09:00 |
| **Per Kuartal** | Update dependency + security audit | IT | Sesuai jadwal |
| **Insidental** | Incident response | IT | Saat error terjadi |

### 1.2. Kalender Maintenance Bulanan

```
  Minggu 1  →  Backup verifikasi + log review   (30 menit)
  Minggu 2  →  Database maintenance + cache      (30 menit)
  Minggu 3  →  Dependency check + security scan  (30 menit)
  Minggu 4  →  Full maintenance + reporting       (60 menit)
```

### 1.3. Maintenance Window

- **Rutin**: Setiap hari Minggu pukul 06:00-07:00 WIB
- **Emergency**: Kapan saja dengan notifikasi ke semua pengguna
- **Perubahan besar**: Minimal H-7 notifikasi ke pimpinan

### 1.4. Status Page

Gunakan layanan status page (ataupunt `health.php`) agar pengguna bisa mengecek status aplikasi:

```
https://dashboard-se2026.bpsjember.id/health.php
```

---

## 2. Backup Harian & Mingguan

### 2.1. Jenis Backup

| Jenis | Isi | Frekuensi | Retensi | Ukuran Estimasi |
|-------|-----|-----------|---------|-----------------|
| **Database Full** | Semua tabel dashboard + stored procedure | Setiap hari jam 02:00 | 30 hari | ~50-100 MB |
| **Database Incremental** | Data yang berubah sejak backup terakhir | Setiap 6 jam | 7 hari | ~5-20 MB |
| **File Project** | Source code (exclude vendor, storage) | Setiap deploy | 3 versi terakhir | ~10 MB |
| **File Storage** | Upload, cache, logs | Setiap minggu | 7 hari | ~5-50 MB |
| **Pre-Deploy** | Database sebelum perubahan besar | Sebelum deploy | Sampai next deploy | ~50-100 MB |

### 2.2. Konfigurasi Cron (Linux Server)

```bash
# Backup harian jam 02:00 WIB
0 2 * * * root /usr/bin/mysqldump \
  --single-transaction \
  --routines \
  --triggers \
  --quick \
  -u backup_user \
  -p'password' \
  bps_jember_se2026 \
  | gzip > /var/backups/dashboard/daily_$(date +\%Y\%m\%d).sql.gz \
  2>> /var/log/dashboard-backup.log

# Hapus backup lebih dari 30 hari
0 4 * * * root find /var/backups/dashboard -name "daily_*.sql.gz" -mtime +30 -delete

# Backup file project setiap minggu
0 3 * * 0 root tar czf /var/backups/dashboard/files_$(date +\%Y\%m\%d).tar.gz \
  --exclude=vendor --exclude=storage --exclude=.git \
  /var/www/dashboard-se2026/
```

### 2.3. Verifikasi Backup Harian (08:00 WIB)

```bash
#!/bin/bash
# scripts/verify-backup.sh
BACKUP_DIR="/var/backups/dashboard"
LATEST=$(ls -t "$BACKUP_DIR"/daily_*.sql.gz 2>/dev/null | head -1)

if [ -z "$LATEST" ]; then
    echo "[ALERT] Tidak ada backup ditemukan!" | mail -s "Backup Alert" admin@bpsjember.id
    exit 1
fi

# Cek ukuran
SIZE=$(stat -c%s "$LATEST")
if [ "$SIZE" -lt 1000000 ]; then  # < 1 MB = mencurigakan
    echo "[ALERT] Backup terlalu kecil: $SIZE bytes" | mail -s "Backup Alert" admin@bpsjember.id
fi

# Cek integritas
gzip -t "$LATEST"
if [ $? -ne 0 ]; then
    echo "[ALERT] Backup corrupt: $LATEST" | mail -s "Backup Alert" admin@bpsjember.id
fi

echo "OK: $(basename $LATEST) - $(numfmt --to=iec $SIZE)"
```

### 2.4. Restore Test Bulanan

Setiap bulan, lakukan restore test ke database staging:

```bash
# 1. Copy backup terakhir ke staging
scp /var/backups/dashboard/daily_20260527.sql.gz staging:/tmp/

# 2. Restore
gunzip -c daily_20260527.sql.gz | mysql -u test_user -p bps_jember_se2026_staging

# 3. Verifikasi
mysql -u test_user -p bps_jember_se2026_staging -e "
  SELECT 'OK' AS status, COUNT(*) AS total_sls FROM sipw_import
  UNION ALL
  SELECT 'OK', COUNT(*) FROM sipw_assignment;
"
```

### 2.5. Offsite Backup

Backup juga harus disimpan di lokasi berbeda:

```bash
# Rsync ke server backup
rsync -avz /var/backups/dashboard/ backup@192.168.1.100:/backups/dashboard/

# Atau kirim ke cloud storage
rclone sync /var/backups/dashboard/ gdrive:dashboard-se2026-backups/
```

---

## 3. Monitoring Error

### 3.1. Log yang Dipantau

| Log File | Lokasi | Isi |
|----------|--------|-----|
| Aplikasi | `storage/logs/app-YYYY-MM-DD.log` | PHP errors, exceptions |
| Backup | `storage/logs/backup.log` | Hasil backup harian |
| Restore | `storage/logs/restore.log` | Aktivitas restore |
| Rollback | `storage/logs/rollback.log` | Rollback import |
| Cron | `storage/logs/cron-*.log` | Cron job results |
| Apache/Nginx | `/var/log/nginx/dashboard-error.log` | HTTP errors |

### 3.2. Script Cek Error Harian

```bash
#!/bin/bash
# scripts/check-errors.sh — Jalankan setiap jam via cron

LOG_DIR="/var/www/dashboard-se2026/storage/logs"
ALERT_EMAIL="admin@bpsjember.id"

# 1. PHP Fatal Errors (24 jam terakhir)
FATAL_COUNT=$(grep -c "Fatal\|Parse error" "$LOG_DIR/app-$(date +%Y-%m-%d).log" 2>/dev/null)
if [ "$FATAL_COUNT" -gt 0 ]; then
    echo "WARNING: $FATAL_COUNT fatal errors hari ini"
    tail -20 "$LOG_DIR/app-$(date +%Y-%m-%d).log" | mail -s "Dashboard: Fatal Error Alert" $ALERT_EMAIL
fi

# 2. HTTP 500 errors (24 jam terakhir)
HTTP_500=$(grep -c ' " 500 ' /var/log/nginx/dashboard-access.log 2>/dev/null)
if [ "$HTTP_500" -gt 10 ]; then
    echo "WARNING: $HTTP_500 x HTTP 500 hari ini"
fi

# 3. Disk usage
DISK_USAGE=$(df /var/www/dashboard-se2026/storage | tail -1 | awk '{print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 85 ]; then
    echo "CRITICAL: Disk $DISK_USAGE% full" | mail -s "Dashboard: Disk Alert" $ALERT_EMAIL
fi

# 4. Recent error log size
LOG_SIZE=$(stat -c%s "$LOG_DIR/app-$(date +%Y-%m-%d).log" 2>/dev/null)
if [ "$LOG_SIZE" -gt 50000000 ]; then  # > 50 MB
    echo "WARNING: Log file ${LOG_SIZE} bytes — perlu dirotasi"
fi
```

### 3.3. Monitoring Dashboard

Buat endpoint health check (`health.php`) yang sudah ada:

```bash
# Cek dari luar
curl -s https://dashboard-se2026.bpsjember.id/health.php | python -m json.tool
```

Integrasi dengan uptime monitoring:
- **Uptime Robot**: Check setiap 5 menit
- **Better Uptime** / **Pingdom**: Alert ke Telegram/WA
- **Grafana** (opsional): Visualisasi metrics

### 3.4. Alert Thresholds

| Metric | Warning | Critical | Notifikasi |
|--------|---------|----------|------------|
| Response time | > 3 detik | > 10 detik | Telegram |
| Error rate | > 5/jam | > 20/jam | Email + Telegram |
| Disk usage | > 75% | > 90% | Email |
| Backup size | < 1 MB | Tidak ada backup | Email + WA |
| Login failures | > 5/menit | > 20/menit | Email |
| Database connection | Timeout > 2s | Gagal | Telegram |

### 3.5. Format Log

Log aplikasi sudah ditulis dalam format terstruktur:

```
[2026-05-27 10:30:00] RuntimeError: Database connection failed in Database.php:36
[2026-05-27 10:30:01] PDOException: SQLSTATE[HY000] [2002] Connection refused
```

**Analisis mingguan**: Cari pola error yang berulang.

---

## 4. Database Maintenance

### 4.1. Jadwal Maintenance Database

| Aktivitas | Frekuensi | Waktu |
|-----------|-----------|-------|
| OPTIMIZE TABLE | Mingguan | Minggu 06:00 |
| ANALYZE TABLE | Mingguan | Minggu 06:00 |
| Cek slow query | Harian | 08:00 |
| Hapus data lama | Bulanan | Minggu 1 |
| Cek index usage | Bulanan | Minggu 2 |
| Update statistika | Bulanan | Minggu 2 |

### 4.2. Script Maintenance Bulanan

```sql
-- scripts/db-maintenance.sql
-- Jalankan: mysql -u root -p bps_jember_se2026 < scripts/db-maintenance.sql

-- 1. Optimasi tabel utama
OPTIMIZE TABLE sipw_import;
OPTIMIZE TABLE sipw_assignment;
OPTIMIZE TABLE dash_import_log;
OPTIMIZE TABLE dash_assignment_log;
OPTIMIZE TABLE dash_rollback_points;
OPTIMIZE TABLE dash_monitoring_summary;

-- 2. Update statistika
ANALYZE TABLE sipw_import;
ANALYZE TABLE sipw_assignment;
ANALYZE TABLE users;

-- 3. Cek fragmentasi
SELECT
    TABLE_NAME,
    ROUND(DATA_LENGTH / 1024 / 1024, 2) AS data_mb,
    ROUND(INDEX_LENGTH / 1024 / 1024, 2) AS index_mb,
    ROUND(DATA_FREE / 1024 / 1024, 2) AS free_mb,
    ROUND((DATA_FREE / (DATA_LENGTH + INDEX_LENGTH + DATA_FREE)) * 100, 1) AS frag_pct
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'bps_jember_se2026'
  AND TABLE_NAME IN ('sipw_import','sipw_assignment','dash_import_log')
ORDER BY free_mb DESC;

-- 4. Hapus rollback point > 30 hari (idempotent)
DELETE FROM dash_rollback_points
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
  AND is_used = 1;

-- 5. Hapus audit log > 90 hari
DELETE FROM dash_assignment_log
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- 6. Hapus import log > 90 hari
DELETE FROM dash_import_log
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- 7. Cek index yang tidak terpakai
SELECT
    INDEX_NAME,
    TABLE_NAME,
    SEQ_IN_INDEX,
    COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'bps_jember_se2026'
  AND TABLE_NAME IN ('sipw_import','sipw_assignment')
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
```

### 4.3. Slow Query Monitoring

Aktifkan slow query log di MySQL:

```ini
# /etc/mysql/my.cnf
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-dashboard.log
long_query_time = 2
log_queries_not_using_indexes = 1
```

Cek slow query harian:

```bash
pt-query-digest /var/log/mysql/slow-dashboard.log > /var/www/dashboard-se2026/storage/logs/slow-query-$(date +%Y%m%d).log
```

### 4.4. Index Health Check

Bulanan, cek apakah index masih optimal:

```sql
-- Cek index yang tidak pernah dipakai
SELECT
    OBJECT_SCHEMA,
    OBJECT_NAME,
    INDEX_NAME,
    COUNT_STAR,
    SUM_TIMER_WAIT / 1000000000000 AS sum_wait_sec
FROM performance_schema.table_io_waits_summary_by_index_usage
WHERE OBJECT_SCHEMA = 'bps_jember_se2026'
  AND INDEX_NAME != 'PRIMARY'
  AND COUNT_STAR = 0;
```

### 4.5. Cache Invalidation

Setelah maintenance database, invalidate cache:

```bash
# Hapus semua cache
rm -f /var/www/dashboard-se2026/storage/cache/*.cache

# Atau via PHP
php -r "
  require '/var/www/dashboard-se2026/vendor/autoload.php';
  App\Helpers\Cache::flush();
  echo 'Cache flushed: OK\n';
"
```

---

## 5. Update Dependency

### 5.1. Stack yang Digunakan

| Dependency | Versi Saat Ini | Frekuensi Update | Sumber |
|------------|---------------|------------------|--------|
| PHP | ^8.2 | Tahunan | php.net |
| openspout/openspout | ^4.24 | Per kuartal | packagist |
| dompdf/dompdf | ^3.1 | Per kuartal | packagist |
| Bootstrap | 5.3.3 (CDN) | Per kuartal | cdn.jsdelivr.net |
| jQuery | 3.7.1 (CDN) | Tahunan | code.jquery.com |
| DataTables | 1.13.6 (CDN) | Per kuartal | datatables.net |
| Chart.js | 4.4.1 (CDN) | Per kuartal | chartjs.org |
| Font Awesome | 6.5.1 (CDN) | Tahunan | fontawesome.com |

### 5.2. Prosedur Update Composer

```bash
# 1. Backup dulu
cp composer.json composer.json.bak
cp composer.lock composer.lock.bak

# 2. Cek update tersedia
composer outdated

# 3. Update semua dependency minor/patch
composer update --no-dev --optimize-autoloader --no-interaction

# 4. Atau update spesifik
composer update openspout/openspout --no-dev --with-dependencies

# 5. Test di staging dulu
git checkout -b test/update-deps-$(date +%Y%m%d)
# Deploy ke staging, test semua fitur

# 6. Jika OK, merge ke main
git checkout main
git merge test/update-deps-$(date +%Y%m%d)
git tag v$(date +%Y.%m.%d)-stable
```

### 5.3. Prosedur Update CDN

Untuk dependency CDN (Bootstrap, jQuery, dll), update di `views/layouts/main.php`:

```html
<!-- Ganti CDN URL dengan versi baru -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
```

Test di staging: pastikan tidak ada breaking changes di JS/CSS.

### 5.4. Prosedur Update PHP

```bash
# 1. Cek versi PHP saat ini
php -v

# 2. Cek kompatibilitas dependency
composer check-platform-reqs

# 3. Install PHP versi baru (contoh 8.2 → 8.3)
sudo apt update
sudo apt install php8.3 php8.3-{pdo,mysql,mbstring,gd,xml,curl,zip}

# 4. Update symlink
sudo update-alternatives --config php

# 5. Test aplikasi
php -r "require 'vendor/autoload.php'; echo 'OK';"
curl -s https://dashboard-se2026.bpsjember.id/health.php | python -m json.tool
```

### 5.5. Changelog & Dokumentasi

Setiap update dependency, catat di changelog:

```
## [2026-06-15] — Dependency Update

### Updated
- openspout/openspout: 4.24 → 4.26 (minor)
- dompdf/dompdf: 3.1 → 3.2 (minor)
- Bootstrap: 5.3.3 → 5.3.4 (CDN)

### Security
- No security fixes in this update

### Tested
- Import XLSX: OK
- Export PDF: OK
- Export Excel: OK
- Dashboard charts: OK
```

### 5.6. Security Vulnerability Scan

```bash
# Cek vulnerability di dependency
composer audit

# Install security checker
composer require --dev symfony/security-checker

# Cek secara berkala
php vendor/bin/security-checker security:check composer.lock
```

---

## 6. SOP Jika Aplikasi Error

### 6.1. Klasifikasi Error

| Level | Dampak | Response Time | Contoh |
|-------|--------|---------------|--------|
| **CRITICAL** | Aplikasi tidak bisa diakses sama sekali | < 1 jam | HTTP 500, blank page, database down |
| **HIGH** | Fungsi utama tidak berfungsi | < 4 jam | Import gagal, assign error |
| **MEDIUM** | Fungsi pendukung terganggu | < 24 jam | Export error, grafik tidak muncul |
| **LOW** | Gangguan minor | < 72 jam | Tampilan tidak rapi, typo |

### 6.2. Flow Response Error

```
        Error Terdeteksi
              │
              ▼
  ┌───────────────────┐
  │  Cek health.php   │
  └─────────┬─────────┘
            │
     ┌──────┴──────┐
     ▼             ▼
  HTTP 200        HTTP 503/500
     │                │
     │          ┌──────┴──────────┐
     │          ▼                 ▼
     │   Cek error log    Cek database
     │   storage/logs/    mysql -u ... -e
     │   app-*.log        "SELECT 1"
     │          │                 │
     └──────────┴─────────────────┘
                        │
              ┌─────────┴─────────┐
              ▼                   ▼
        Bisa di-fix?        Tidak bisa?
              │                   │
        ┌─────┴─────┐      ┌─────┴─────┐
        ▼           ▼      ▼           ▼
    Fix langsung  Rollback  Hubungi   Aktifkan
                   ke versi  IT Senior  maintenance
                   sebelumnya          page
```

### 6.3. SOP Step-by-Step

#### STEP 1: Deteksi & Isolasi

```bash
# 1. Cek health endpoint
curl -s https://dashboard-se2026.bpsjember.id/health.php | python -m json.tool

# 2. Cek HTTP status
curl -o /dev/null -s -w "%{http_code}" https://dashboard-se2026.bpsjember.id/

# 3. Cek error log 10 menit terakhir
tail -100 /var/www/dashboard-se2026/storage/logs/app-$(date +%Y-%m-%d).log

# 4. Cek database
mysqladmin ping -u dashboard_user -p'***' --silent

# 5. Cek disk
df -h /var/www/dashboard-se2026/storage

# 6. Cek PHP-FPM
systemctl status php8.2-fpm
```

#### STEP 2: Triage (5 menit)

| Temuan | Kemungkinan | Action |
|--------|-------------|--------|
| `php8.2-fpm DOWN` | Service mati | `systemctl restart php8.2-fpm` |
| `MySQL connection refused` | DB down | `systemctl restart mysql` |
| `Allowed memory size exhausted` | Memory leak | `ps aux --sort=-%mem \| head`, restart PHP |
| `Class not found` | Autoload corrupt | `composer dump-autoload --no-dev -o` |
| `Disk full` | Log/backup membesar | `du -sh storage/*`, hapus file lama |
| `500 error on all pages` | .htaccess atau config | Cek .env, cek file permission |

#### STEP 3: Fix (15 menit)

**Skenario A — Database Down:**

```bash
# 1. Restart MySQL
sudo systemctl restart mysql

# 2. Cek koneksi
mysql -u dashboard_user -p'***' -e "SELECT 1" bps_jember_se2026

# 3. Cek tabel corrupt
mysqlcheck -u dashboard_user -p'***' --check bps_jember_se2026

# 4. Repair jika perlu
mysqlcheck -u dashboard_user -p'***' --repair bps_jember_se2026 sipw_import
```

**Skenario B — PHP Error:**

```bash
# 1. Cek log spesifik
grep "Fatal\|Parse error\|exception" /var/www/dashboard-se2026/storage/logs/app-$(date +%Y-%m-%d).log

# 2. Fix file yang bermasalah (berdasarkan log)
# Contoh: error di ImportController.php line 150

# 3. Clear cache
rm -f /var/www/dashboard-se2026/storage/cache/*.cache

# 4. Restart PHP
sudo systemctl restart php8.2-fpm
```

**Skenario C — Blank Page / 500:**

```bash
# 1. Cek .env
cat /var/www/dashboard-se2026/.env | grep -v PASS

# 2. Cek file permission
ls -la /var/www/dashboard-se2026/storage/logs/

# 3. Coba akses dengan debug sementara
cp .env .env.bak
sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env
# Akses halaman yang error, lihat stack trace
# Kembalikan setelah selesai
cp .env.bak .env
```

**Skenario D — Slow Performance:**

```bash
# 1. Cek proses berat
ps aux --sort=-%cpu | head -10
ps aux --sort=-%mem | head -10

# 2. Cek slow query
tail -100 /var/log/mysql/slow-dashboard.log

# 3. Cek koneksi database
mysql -u dashboard_user -p'***' -e "SHOW FULL PROCESSLIST"

# 4. Flush cache
php -r "require 'vendor/autoload.php'; App\Helpers\Cache::flush();"

# 5. Clear MySQL query cache
mysql -u dashboard_user -p'***' -e "RESET QUERY CACHE;"
```

#### STEP 4: Rollback (30 menit)

Jika fix tidak memungkinkan dalam 30 menit:

```bash
# 1. Aktifkan maintenance page
cp maintenance.html /var/www/dashboard-se2026/maintenance.html
# Atau tambahkan rewrite rule sementara

# 2. Rollback file
cd /var/www/dashboard-se2026
git stash
git checkout <last-stable-tag>

# 3. Rollback database jika perlu
gunzip -c /var/backups/dashboard/daily_$(date +%Y%m%d --date="yesterday").sql.gz | \
  mysql -u dashboard_user -p'***' bps_jember_se2026

# 4. Clear cache
rm -f storage/cache/*.cache

# 5. Test
curl -s https://dashboard-se2026.bpsjember.id/ | head -5

# 6. Matikan maintenance page
rm /var/www/dashboard-se2026/maintenance.html
```

#### STEP 5: Notifikasi

Setelah error tertangani:

1. **Internal**: Lapor ke tim IT via grup Telegram
2. **Management**: Jika downtime > 1 jam, lapor ke pimpinan
3. **Post-mortem**: Dokumentasikan root cause dalam 1x24 jam

### 6.4. Template Post-Mortem

```markdown
## Post-Mortem: [Judul Error]

**Tanggal**: 2026-05-27
**Waktu**: 10:30 - 11:15 WIB
**Duration**: 45 menit
**Dampak**: Import SIPW tidak bisa diakses

### Timeline
- 10:30 - Error terdeteksi oleh user
- 10:32 - IT di-notifikasi
- 10:35 - Diagnosa: memory_limit exhausted
- 10:45 - Fix: increase memory_limit 128M → 256M
- 11:00 - Restart PHP-FPM
- 11:15 - Aplikasi normal kembali

### Root Cause
File import berukuran 15MB dengan ~50.000 baris
melebihi memory_limit 128M yang dikonfigurasi.

### Action Items
- [ ] Naikkan memory_limit menjadi 256M (DONE)
- [ ] Tambah monitoring memory usage (cron setiap jam)
- [ ] Optimasi ImportProcessor untuk batch processing

### Lessons Learned
- Perlu stress test dengan file besar sebelum production
- Monitoring harus mencakup resource usage
```

### 6.5. Kontak Emergency

| Kontak | Nomor | Waktu |
|--------|-------|-------|
| IT Support | 08xx-xxxx-xxxx | 24 jam |
| System Admin | 08xx-xxxx-xxxx | 06:00-22:00 |
| Database Admin | 08xx-xxxx-xxxx | 06:00-22:00 |
| Pimpinan | 08xx-xxxx-xxxx | Jam kerja |

---

## 7. Scaling Strategy

### 7.1. Current Architecture (Shared Hosting)

```
  User → Browser → Apache/Nginx → PHP 8.2 → MySQL 8.0
                                             │
                                        storage/
                                        ├─ cache/   (file)
                                        ├─ uploads/ (file)
                                        └─ logs/    (file)
```

### 7.2. Scaling Thresholds

| Resource | Current | Warning | Critical | Action |
|----------|---------|---------|----------|--------|
| Database size | ~500 MB | > 2 GB | > 5 GB | Optimize + archive |
| Concurrent users | ~20 | > 50 | > 100 | Upgrade hosting |
| SLS data | ~5.215 | > 20.000 | > 50.000 | Optimize queries |
| File uploads/month | ~50 MB | > 200 MB | > 500 MB | Archive + cleanup |
| Storage usage | ~200 MB | > 1 GB | > 2 GB | Cleanup + add disk |

### 7.3. Phase 1: Optimasi (0–50 concurrent users)

**Tanpa biaya tambahan — lakukan sekarang:**

- [x] File-based cache (`App\Helpers\Cache`)
- [ ] DataTables server-side (sudah implemented)
- [x] Database indexes (patch 003)
- [ ] Enable MySQL query cache
- [ ] Enable PHP OPcache

**Enable OPcache:**

```ini
; /etc/php/8.2/cli/conf.d/opcache.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.validate_timestamps=0  ; Production — clear cache manually
```

### 7.4. Phase 2: Upgrade VPS (50–200 concurrent users)

**Estimasi biaya**: Rp 200-500k/bulan

```yaml
# VPS Spec:
CPU: 2 core
RAM: 4 GB
Storage: 50 GB SSD
Bandwidth: 2 TB

# Software Stack:
- Ubuntu 22.04 LTS
- Nginx (ganti Apache)
- PHP 8.2-FPM
- MySQL 8.0
- Redis (cache + session)
```

**Nginx + PHP-FPM tuning:**

```nginx
# /etc/nginx/nginx.conf
worker_processes auto;
worker_connections 2048;

# PHP-FPM pool
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

### 7.5. Phase 3: Redis Cache (200–500 concurrent users)

Ganti file cache dengan Redis:

```php
// src/Helpers/Cache.php — Redis adapter
class Cache {
    private static ?\Redis $redis = null;

    private static function connect(): void {
        if (self::$redis === null) {
            self::$redis = new \Redis();
            self::$redis->connect('127.0.0.1', 6379);
        }
    }

    public static function get(string $key, mixed $default = null): mixed {
        self::connect();
        $val = self::$redis->get($key);
        return $val === false ? $default : unserialize($val);
    }

    public static function set(string $key, mixed $value, int $ttlSeconds = 60): void {
        self::connect();
        self::$redis->setex($key, $ttlSeconds, serialize($value));
    }
}
```

**Redis memory**: 256 MB (cukup untuk semua cache dashboard)

### 7.6. Phase 4: Read Replica (500+ concurrent users)

```
  User → App Server → MySQL Primary (write)
                        │
                  ┌─────┴─────┐
                  ▼           ▼
            Read Replica 1  Read Replica 2
                (SELECT)      (SELECT)
```

Update `Database.php` untuk read/write split:

```php
// Query SELECT → read replica
// Query INSERT/UPDATE/DELETE → primary
```

### 7.7. Phase 5: Microservices (1000+ concurrent users)

Pisahkan modul menjadi service terpisah:

```
  ┌──────────┐    ┌──────────┐    ┌──────────┐
  │ Dashboard │    │  Import  │    │  Export  │
  │  Service  │    │  Service │    │  Service │
  └─────┬─────┘   └─────┬─────┘   └─────┬─────┘
        │               │               │
        └───────────────┼───────────────┘
                        ▼
                  Message Queue
                   (RabbitMQ)
                        │
              ┌─────────┴─────────┐
              ▼                   ▼
        MySQL Primary         Redis Cache
```

### 7.8. Cost-Benefit Analysis

| Phase | Capacity | Estimasi Biaya | Kapan |
|-------|----------|---------------|-------|
| **Current** (Optimasi) | ~50 user | Rp 0 | Sekarang |
| **VPS Upgrade** | ~200 user | Rp 300-500k/bln | Saat shared hosting tidak mencukupi |
| **Redis** | ~500 user | Rp 50-100k/bln | Saat file cache bottleneck |
| **Read Replica** | ~1000 user | Rp 200k/bln | Saat query SELECT menjadi bottleneck |
| **Microservices** | 5000+ user | Rp 1-5jt/bln | Saat beban sangat tinggi |

---

## 8. Security Maintenance

### 8.1. Checklist Keamanan Bulanan

```bash
#!/bin/bash
# scripts/security-scan.sh

echo "=== Security Scan — Dashboard SE2026 ==="
echo "Tanggal: $(date)"
echo ""

# 1. Cek file permission sensitif
echo "--- File Permission Check ---"
stat -c '%a %U:%G %n' /var/www/dashboard-se2026/.env
# Expected: 640 deployer:www-data

# 2. Cek file yang dimodifikasi 7 hari terakhir
echo ""
echo "--- Recently Modified Files (7 days) ---"
find /var/www/dashboard-se2026 -name "*.php" -mtime -7 -not -path "*/vendor/*"

# 3. Cek user aneh di database
echo ""
echo "--- Database Users ---"
mysql -u dashboard_user -p'***' -e "SELECT id, username, role, status_akun, last_login FROM users WHERE status_akun != 'aktif' OR role = 'admin'"

# 4. Cek login gagal
echo ""
echo "--- Failed Login (24h) ---"
mysql -u dashboard_user -p'***' -e "
  SELECT COUNT(*) as failed_logins, detail
  FROM activity_logs
  WHERE action = 'login_failed'
    AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
  GROUP BY detail
  ORDER BY failed_logins DESC
  LIMIT 10
"

# 5. Cek file upload mencurigakan
echo ""
echo "--- Recent Uploads ---"
find /var/www/dashboard-se2026/storage/uploads -mtime -1 -type f
```

### 8.2. SSL Certificate Check

```bash
# Cek expiry date
echo | openssl s_client -servername dashboard-se2026.bpsjember.id -connect dashboard-se2026.bpsjember.id:443 2>/dev/null | openssl x509 -noout -dates

# Auto-renew (Let's Encrypt)
sudo certbot renew --dry-run
```

### 8.3. User Account Review (Bulanan)

```sql
-- Cek user yang tidak aktif
SELECT id, username, role, status_akun, last_login
FROM users
WHERE last_login IS NULL
   OR last_login < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Cek user dengan role tidak semestinya
SELECT id, username, role, COUNT(*) as assign_count
FROM users u
LEFT JOIN sipw_assignment sa ON sa.pencacah_id = u.id
WHERE u.role = 'pcl'
GROUP BY u.id, u.username, u.role
HAVING assign_count = 0;
```

---

## 9. Dokumentasi & Knowledge Base

### 9.1. Dokumen yang Wajib Ada

| Dokumen | Lokasi | Update |
|---------|--------|--------|
| Panduan Operator | `docs/PANDUAN_OPERATOR.md` | Saat ada fitur baru |
| Panduan Deployment | `docs/DEPLOYMENT.md` | Saat ada perubahan infra |
| Maintenance Plan | `docs/MAINTENANCE.md` | Per kuartal |
| Database Schema | `database/*.sql` | Saat ada migrasi |
| Changelog | `CHANGELOG.md` | Setiap perubahan |

### 9.2. Changelog Format

```markdown
# Changelog

## [2026.06.01] — 2026-06-01

### Added
- Fitur export PDF laporan per kecamatan
- Filter cascade desa di halaman monitoring

### Fixed
- Import gagal untuk file dengan kolom `id_frs` kosong
- Session timeout tidak bekerja di beberapa halaman

### Changed
- Upgrade OpenSpout 4.24 → 4.26
- Optimasi query dashboard (response time turun 40%)
```

### 9.3. Meeting Rutin

| Meeting | Frekuensi | Agenda |
|---------|-----------|--------|
| **Daily Standup** | Setiap hari (5 menit) | Cek error 24 jam terakhir, backup status |
| **Weekly Review** | Senin (15 menit) | Review log mingguan, performance |
| **Monthly Maintenance** | Sabtu minggu ke-4 (2 jam) | Full maintenance sesuai checklist |

---

## 10. Checklist Maintenance Bulanan

### ⬜ Pekan 1: Awal Bulan (30 menit)

**Backup & Restore Test:**
- [ ] Backup database full (cron sudah jalan)
- [ ] Restore test ke database staging
- [ ] Verifikasi integritas file backup
- [ ] Cek ukuran backup (bandingkan dengan bulan lalu)
- [ ] Cek retensi: backup > 30 hari terhapus
- [ ] Cek offsite backup (rsync/rclone)

**Error Log Review:**
- [ ] Review log aplikasi bulan lalu
- [ ] Identifikasi pola error berulang
- [ ] Catat error-count per hari di spreadsheet
- [ ] Laporkan error signifikan ke tim

### ⬜ Pekan 2: Database (30 menit)

**Database Optimization:**
- [ ] Jalankan `OPTIMIZE TABLE` untuk semua tabel dashboard
- [ ] Jalankan `ANALYZE TABLE`
- [ ] Hapus data audit > 90 hari
- [ ] Hapus rollback point > 30 hari
- [ ] Cek fragmentasi tabel
- [ ] Cek slow query log
- [ ] Flush cache aplikasi

**Index Health:**
- [ ] Cek index usage statistics
- [ ] Identifikasi index yang tidak terpakai
- [ ] Identifikasi query tanpa index (full table scan)
- [ ] Update index jika perlu (patch baru)

### ⬜ Pekan 3: Dependency & Security (30 menit)

**Update Dependency:**
- [ ] `composer outdated` — cek update tersedia
- [ ] `composer audit` — cek vulnerability
- [ ] Update CDN version jika perlu (Bootstrap, jQuery, dll)
- [ ] Test di staging sebelum deploy
- [ ] Update changelog

**Security Scan:**
- [ ] Jalankan `scripts/security-scan.sh`
- [ ] Cek file permission (.env, storage/)
- [ ] Cek user tidak aktif (database)
- [ ] Cek failed login attempts
- [ ] Cek SSL certificate expiry
- [ ] Review .htaccess / Nginx security headers

### ⬜ Pekan 4: Full Maintenance (60 menit)

**Performance Review:**
- [ ] Catat response time rata-rata
- [ ] Bandingkan dengan bulan lalu
- [ ] Cek CPU dan memory usage (graph)
- [ ] Cek disk usage trend
- [ ] Cek PHP-FPM pool status
- [ ] Review cache hit ratio

**User Management:**
- [ ] Review daftar user aktif
- [ ] Non-aktifkan user yang sudah tidak bertugas
- [ ] Cek role assignment
- [ ] Update kontak PIC jika ada perubahan

**Dokumentasi:**
- [ ] Update `PANDUAN_OPERATOR.md` jika ada fitur baru
- [ ] Update `DEPLOYMENT.md` jika ada perubahan infra
- [ ] Update `CHANGELOG.md`
- [ ] Backup semua dokumentasi

**Reporting:**
- [ ] Buat laporan maintenance bulanan
- [ ] Catat temuan dan tindakan
- [ ] Rekomendasi untuk bulan depan
- [ ] Kirim ke pimpinan (jika diperlukan)

### Template Laporan Bulanan

```markdown
## Laporan Maintenance Dashboard SE2026
**Periode**: Juni 2026
**Oleh**: Tim IT BPS Jember

### Ringkasan
- Uptime: 99.8% (12 jam downtime karena maintenance terjadwal)
- Jumlah error: 45 (30 minor, 12 medium, 3 high)
- Backup: 30/30 hari sukses

### Maintenance Dilakukan
1. Database optimization — selesai
2. Dependency update — OpenSpout 4.24 → 4.26
3. Security scan — tidak ada temuan
4. User review — 3 user non-aktif dinonaktifkan

### Issues
- [MEDIUM] Import lambat untuk file >10MB → akan dioptimasi bulan depan
- [LOW] Tampilan monitoring di HP kurang rapi → dijadwalkan

### Rekomendasi
1. Upgrade ke VPS jika concurrent user > 50
2. Implementasi Redis cache bulan depan
```

---

## 11. Checklist Maintenance Mingguan

### ⬜ Setiap Hari Senin (15 menit)

**Quick Check:**
- [ ] `health.php` mengembalikan HTTP 200
- [ ] Login dengan akun admin: OK
- [ ] Login dengan akun operator: OK
- [ ] Dashboard menampilkan data: OK
- [ ] Cek log error 7 hari terakhir
- [ ] Cek disk usage
- [ ] Cek backup 7 hari terakhir (ada semua?)
- [ ] Verifikasi cron jobs jalan

---

## 12. Checklist Maintenance Harian

### ⬜ Setiap Hari Pukul 08:00 (5 menit)

**Quick Check Otomatis (via cron):**
```bash
# scripts/daily-check.sh
echo "=== Daily Check $(date) ==="

# 1. Health check
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://dashboard-se2026.bpsjember.id/health.php)
echo "Health: $HTTP_CODE"

# 2. Backup check
LATEST_BACKUP=$(ls -t /var/backups/dashboard/daily_*.sql.gz 2>/dev/null | head -1)
echo "Latest backup: $(basename $LATEST_BACKUP 2>/dev/null || echo 'NONE!')"

# 3. Error check
ERROR_COUNT=$(grep -c "Fatal\|exception\|Error" /var/www/dashboard-se2026/storage/logs/app-$(date +%Y-%m-%d).log 2>/dev/null)
echo "Today errors: $ERROR_COUNT"

# 4. Disk check
DF=$(df -h /var/www/dashboard-se2026/storage | tail -1 | awk '{print $5}')
echo "Disk usage: $DF"
```

---

## Lampiran: Script & Tools

### A. Maintenance Dashboard

Script all-in-one untuk maintenance bulanan:

```bash
#!/bin/bash
# scripts/monthly-maintenance.sh
set -e

echo "=== Monthly Maintenance — Dashboard SE2026 ==="
echo "Start: $(date)"
echo ""

# 1. Backup pre-maintenance
echo "[1/8] Backup pre-maintenance..."
mysqldump --single-transaction -u dashboard_user -p'***' bps_jember_se2026 | gzip > /var/backups/dashboard/pre_maintenance_$(date +%Y%m%d).sql.gz

# 2. Database optimization
echo "[2/8] Optimasi tabel..."
mysql -u dashboard_user -p'***' bps_jember_se2026 < /var/www/dashboard-se2026/scripts/db-maintenance.sql

# 3. Cache flush
echo "[3/8] Flush cache..."
php -r "require '/var/www/dashboard-se2026/vendor/autoload.php'; App\Helpers\Cache::flush(); echo 'OK\n';"

# 4. Cleanup logs
echo "[4/8] Cleanup logs..."
find /var/www/dashboard-se2026/storage/logs -name "*.log" -mtime +30 -delete

# 5. Cleanup uploads
echo "[5/8] Cleanup uploads..."
find /var/www/dashboard-se2026/storage/uploads -mtime +7 -type f -delete

# 6. Security scan
echo "[6/8] Security scan..."
/var/www/dashboard-se2026/scripts/security-scan.sh

# 7. Dependency check
echo "[7/8] Dependency check..."
cd /var/www/dashboard-se2026 && composer audit

# 8. Final backup
echo "[8/8] Backup post-maintenance..."
mysqldump --single-transaction -u dashboard_user -p'***' bps_jember_se2026 | gzip > /var/backups/dashboard/post_maintenance_$(date +%Y%m%d).sql.gz

echo ""
echo "=== Maintenance Complete ==="
echo "End: $(date)"
```

### B. Health Dashboard

HTTP endpoint untuk monitoring tools:

```
GET /health.php → {
  "status": "ok",
  "timestamp": "2026-05-27T10:00:00+07:00",
  "checks": {
    "php_version": { "status": "ok", "value": "8.2.10" },
    "database": { "status": "ok" },
    "storage_writable": { "status": "ok" },
    "disk_usage": { "status": "ok", "usage": "45.2%" }
  }
}
```

### C. Alert Script

Notifikasi ke Telegram:

```bash
#!/bin/bash
# scripts/telegram-alert.sh
BOT_TOKEN="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11"
CHAT_ID="-123456789"
MESSAGE="$1"

curl -s -X POST "https://api.telegram.org/bot$BOT_TOKEN/sendMessage" \
  -d chat_id="$CHAT_ID" \
  -d text="$MESSAGE" \
  -d parse_mode="Markdown"
```

---

*Dokumen ini adalah SOP maintenance untuk Dashboard SE2026 BPS Kabupaten Jember.*
*Versi 1.0 — Juni 2026*
*Review berikutnya: [tanggal + 3 bulan]*
