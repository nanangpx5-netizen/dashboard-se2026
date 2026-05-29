# Panduan Deployment Produksi — Dashboard SE2026

## Daftar Isi

1. [Kebutuhan Sistem](#1-kebutuhan-sistem)
2. [Environment Configuration](#2-environment-configuration)
3. [Database Migration](#3-database-migration)
4. [Deployment Steps](#4-deployment-steps)
5. [Permission Setup](#5-permission-setup)
6. [Web Server Configuration](#6-web-server-configuration)
7. [Caching Strategy](#7-caching-strategy)
8. [Logging Configuration](#8-logging-configuration)
9. [Cron Jobs](#9-cron-jobs)
10. [Security Checklist](#10-security-checklist)
11. [Deployment Checklist](#11-deployment-checklist)
12. [Rollback Plan](#12-rollback-plan)
13. [Monitoring & Health Check](#13-monitoring--health-check)
14. [Backup Strategy](#14-backup-strategy)

---

## 1. Kebutuhan Sistem

### Minimum Requirements

| Komponen | Spesifikasi |
|----------|-------------|
| PHP | 8.2.x atau lebih tinggi |
| MySQL | 8.0.x atau lebih tinggi (MariaDB 10.6+) |
| Web Server | Apache 2.4+ (mod_rewrite ON) atau Nginx |
| RAM | 512 MB (recommended 1 GB) |
| Storage | 500 MB + ruang untuk file upload |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `gd` (untuk Dompdf) |

### Ekstensi PHP yang Wajib

```bash
php -m | grep -E 'pdo_mysql|mbstring|json|fileinfo|gd|openssl|zip'
```

Hasil harus mencakup: `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `gd`, `openssl`, `zip`

### Cek Environment

Jalankan script berikut di server:

```bash
php -v                          # Minimal 8.2.0
php -m | grep pdo_mysql         # Harus terinstal
php -m | grep mbstring          # Harus terinstal
mysql --version                 # Minimal 8.0
composer --version              # Untuk install dependencies
```

---

## 2. Environment Configuration

### 2.1. Copy Environment File

```bash
cd /var/www/dashboard-se2026
cp .env.example .env
```

### 2.2. Konfigurasi .env

```ini
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=bps_jember_se2026
DB_USER=dashboard_user
DB_PASS=KuatDanAman2026!

# Aplikasi
BASE_URL=/                        # Untuk root domain, atau /dashboard/ untuk subfolder
APP_NAME="Dashboard SE2026 Jember"
APP_ENV=production
APP_DEBUG=false

# Session (30 menit idle timeout)
SESSION_LIFETIME=1800
```

> **Keamanan**: 
> - `APP_DEBUG=false` menonaktifkan stack trace di response error
> - Gunakan password database yang kuat (min 16 karakter, kombinasi)
> - `BASE_URL` harus diakhiri `/`

### 2.3. Production Config Override

File `config/config.php` sudah membaca dari `.env`. Pastikan tidak ada hardcoded development values.

```php
// config/config.php — sudah benar untuk production
defined('APP_ENV') || define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
defined('APP_DEBUG') || define('APP_DEBUG', (bool) ($_ENV['APP_DEBUG'] ?? false));
```

### 2.4. PHP Production Settings

File `php.ini` yang direkomendasikan:

```ini
; /etc/php/8.2/cli/conf.d/dashboard.ini atau via .user.ini di root project

upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
session.gc_maxlifetime = 1800
session.cookie_httponly = 1
session.cookie_secure = 1        ; Hanya jika HTTPS
session.use_only_cookies = 1
session.cookie_samesite = "Strict"
date.timezone = Asia/Jakarta
```

Letakkan file `.user.ini` di root project:

```ini
; .user.ini — PHP per-directory config (shared hosting friendly)
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 300
memory_limit = 256M
```

---

## 3. Database Migration

### 3.1. Buat Database & User

```sql
CREATE DATABASE IF NOT EXISTS bps_jember_se2026
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'dashboard_user'@'localhost'
  IDENTIFIED BY 'KuatDanAman2026!';

GRANT ALL PRIVILEGES ON bps_jember_se2026.*
  TO 'dashboard_user'@'localhost';

FLUSH PRIVILEGES;
```

### 3.2. Jalankan Patch Database Berurutan

Patch sudah idempotent (aman dijalankan berulang). Jalankan **berurutan**:

```bash
# 1. Base tables
mysql -u dashboard_user -p bps_jember_se2026 < database/patch_001_dashboard_base.sql

# 2. Import log + unique constraints
mysql -u dashboard_user -p bps_jember_se2026 < database/patch_002_import_log.sql

# 3. Performance indexes
mysql -u dashboard_user -p bps_jember_se2026 < database/patch_003_performance_indexes.sql

# 4. Backup & recovery infrastructure
mysql -u dashboard_user -p bps_jember_se2026 < database/patch_004_backup_recovery.sql
```

Atau eksekusi satu perintah:

```bash
for f in database/patch_*.sql; do
    echo "=== Running $f ==="
    mysql -u dashboard_user -p bps_jember_se2026 < "$f"
done
```

### 3.3. Stored Procedure Helper

Patch `003` dan `004` menggunakan stored procedure `add_index_if_missing`. Jika belum ada, buat:

```sql
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS add_index_if_missing(
    IN p_table_name VARCHAR(100),
    IN p_index_name VARCHAR(100),
    IN p_create_sql TEXT
)
BEGIN
    DECLARE index_exists INT DEFAULT 0;
    SELECT COUNT(*) INTO index_exists
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table_name
      AND INDEX_NAME = p_index_name;

    IF index_exists = 0 THEN
        SET @sql = p_create_sql;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SELECT CONCAT('INDEX CREATED: ', p_index_name, ' ON ', p_table_name) AS info;
    ELSE
        SELECT CONCAT('SKIP — ', p_index_name, ' already exists on ', p_table_name) AS info;
    END IF;
END$$
DELIMITER ;
```

### 3.4. Verifikasi Database

```sql
USE bps_jember_se2026;
SHOW TABLES;
-- Harus ada: sipw_import, sipw_assignment, dash_import_log,
--           dash_monitoring_summary, dash_assignment_log, dash_rollback_points

SELECT COUNT(*) FROM users;
-- Minimal 1 user admin harus ada
```

### 3.5. Seed Admin User (jika database kosong)

```sql
INSERT INTO users (username, password, nama_lengkap, role, status_akun)
VALUES (
    'admin',
    '$2y$12$...',  -- Hasil dari: php -r "echo password_hash('Admin2026!', PASSWORD_BCRYPT, ['cost'=>12]);"
    'Administrator',
    'admin',
    'aktif'
);
```

---

## 4. Deployment Steps

### 4.1. Persiapan File

```bash
# Upload ke server (contoh via rsync)
rsync -avz --exclude-from='.deployignore' \
  ./ user@server:/var/www/dashboard-se2026/

# Atau via git (recommended)
git clone https://github.com/bps-jember/dashboard-se2026.git
cd dashboard-se2026
git checkout main
```

### 4.2. Buat .deployignore

```gitignore
.git/
.gitignore
.env
.env.example
storage/backups/*
!storage/backups/.gitkeep
storage/logs/*
!storage/logs/.gitkeep
storage/cache/*
!storage/cache/.gitkeep
storage/uploads/*
!storage/uploads/.gitkeep
*.sql
docs/
node_modules/
README.md
.vscode/
```

### 4.3. Install Dependencies (Production)

```bash
cd /var/www/dashboard-se2026

# Install composer tanpa dev dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Verifikasi
php vendor/composer/platform_check.php
```

### 4.4. Generate Autoload Optimization

```bash
composer dump-autoload --no-dev --optimize
```

### 4.5. Setup Storage Struktur

```bash
cd /var/www/dashboard-se2026

# Buat direktori storage jika belum ada
mkdir -p storage/{cache,logs,uploads/sipw,backups,import}

# Buat file gitkeep agar direktori tidak kosong
for dir in cache logs uploads/sipw backups import; do
    touch "storage/$dir/.gitkeep"
done
```

### 4.6. Verifikasi Instalasi

```bash
# Cek PHP
php -v
php -m | grep -E 'pdo_mysql|mbstring|json|fileinfo|gd'

# Cek akses file
php -r "echo 'Config OK: ' . (defined('APP_ENV') ? 'yes' : 'no');"

# Test koneksi database
php -r "
    require 'vendor/autoload.php';
    require 'config/config.php';
    use App\Helpers\Env;
    Env::load('.env');
    echo 'DB_HOST: ' . Env::get('DB_HOST') . PHP_EOL;
    echo 'DB_NAME: ' . Env::get('DB_NAME') . PHP_EOL;
"
```

---

## 5. Permission Setup

### 5.1. Owner & Group

```bash
cd /var/www/dashboard-se2026

# Jika menggunakan www-data (Apache/Nginx)
sudo chown -R www-data:www-data .

# Atau jika menggunakan user terpisah
sudo chown -R deployer:www-data .
```

### 5.2. Direktori Permission

```bash
# Storage harus writable oleh web server
find storage -type d -exec chmod 755 {} \;
find storage -type f -exec chmod 644 {} \;

# Cache, logs, uploads — writable
chmod -R 775 storage/cache
chmod -R 775 storage/logs
chmod -R 775 storage/uploads
chmod -R 775 storage/backups
chmod -R 775 storage/import

# Pastikan direktori memiliki sticky bit
chmod g+s storage/cache storage/logs storage/uploads storage/backups
```

### 5.3. File Permission

```bash
# File umum
find . -type f -name '*.php' -exec chmod 644 {} \;
find . -type f -name '*.js' -exec chmod 644 {} \;
find . -type f -name '*.css' -exec chmod 644 {} \;

# .env — readable only by web server
chmod 640 .env
sudo chown :www-data .env

# index.php — executable
chmod 644 index.php
```

### 5.4. Permission Matrix

| Path | Owner | Permission | Keterangan |
|------|-------|------------|------------|
| `.env` | deployer:www-data | 640 | Konfigurasi sensitif |
| `storage/cache` | www-data:www-data | 775 | File cache writable |
| `storage/logs` | www-data:www-data | 775 | Log file writable |
| `storage/uploads` | www-data:www-data | 775 | Upload file |
| `storage/backups` | www-data:www-data | 775 | Backup database |
| `config/` | deployer:www-data | 755 | Read-only |
| `vendor/` | deployer:www-data | 755 | Read-only |
| `index.php` | deployer:www-data | 644 | Entry point |
| `.htaccess` | deployer:www-data | 644 | Apache config |

---

## 6. Web Server Configuration

### 6.1. Apache (sudah ada .htaccess)

File `.htaccess` sudah include:

```apache
RewriteEngine On

# Deny access to sensitive directories
RewriteRule ^(src|config|storage|views|vendor) - [F,L]

# Serve existing files directly
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Route everything to index.php
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Deny access to .env and SQL files
<FilesMatch "\.(env|sql|md|log)$">
    Require all denied
</FilesMatch>
```

**Pastikan mod_rewrite aktif:**

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

### 6.2. Virtual Host Apache

```apache
<VirtualHost *:80>
    ServerName dashboard-se2026.bpsjember.id
    DocumentRoot /var/www/dashboard-se2026
    
    <Directory /var/www/dashboard-se2026>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Redirect to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]

    ErrorLog ${APACHE_LOG_DIR}/dashboard-se2026-error.log
    CustomLog ${APACHE_LOG_DIR}/dashboard-se2026-access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName dashboard-se2026.bpsjember.id
    DocumentRoot /var/www/dashboard-se2026

    SSLEngine On
    SSLCertificateFile /etc/letsencrypt/live/dashboard-se2026.bpsjember.id/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/dashboard-se2026.bpsjember.id/privkey.pem

    <Directory /var/www/dashboard-se2026>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/dashboard-se2026-error.log
    CustomLog ${APACHE_LOG_DIR}/dashboard-se2026-access.log combined
</VirtualHost>
```

### 6.3. Nginx Configuration

Untuk Nginx, buat konfigurasi berikut:

```nginx
server {
    listen 80;
    server_name dashboard-se2026.bpsjember.id;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name dashboard-se2026.bpsjember.id;

    root /var/www/dashboard-se2026;
    index index.php;

    # SSL
    ssl_certificate     /etc/letsencrypt/live/dashboard-se2026.bpsjember.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/dashboard-se2026.bpsjember.id/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Deny access to sensitive directories
    location ~ ^/(src|config|storage|views|vendor) {
        deny all;
        return 403;
    }

    # Deny access to .env and SQL files
    location ~* \.(env|sql|md|log)$ {
        deny all;
        return 403;
    }

    # Block .git directory
    location ~ /\.git {
        deny all;
        return 403;
    }

    # Serve static files directly
    location /assets/ {
        expires 7d;
        add_header Cache-Control "public, immutable";
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        fastcgi_param PHP_VALUE "
            upload_max_filesize = 20M
            post_max_size = 25M
            max_execution_time = 300
        ";
    }

    # Route all other requests to index.php
    location / {
        try_files $uri $uri/ /index.php?url=$uri&$args;
    }

    # Prevent directory listing
    location /storage/ {
        internal;  # Hanya bisa diakses via PHP (readfile())
    }

    access_log /var/log/nginx/dashboard-se2026-access.log;
    error_log  /var/log/nginx/dashboard-se2026-error.log;
}
```

---

## 7. Caching Strategy

### 7.1. File Cache (Built-in)

Aplikasi menggunakan `App\Helpers\Cache` — file-based cache di `storage/cache/`.

**Konfigurasi TTL untuk production:**

| Data | TTL | Key Prefix | Catatan |
|------|-----|------------|---------|
| Statistik dashboard | 300s (5 menit) | `dashboard_stats_*` | Refresh tiap 5 menit |
| Daftar kecamatan | 3600s (1 jam) | `kecamatan_list` | Jarang berubah |
| Daftar desa | 3600s (1 jam) | `desa_list_*` | Per kecamatan |
| Ringkasan monitoring | 180s (3 menit) | `monitoring_summary_*` | Data near-real-time |

**Contoh penggunaan di controller:**

```php
use App\Helpers\Cache;

$stats = Cache::remember('dashboard_stats', 300, function () {
    return $this->dashboardModel->getAggregatedStats();
});
```

### 7.2. Browser Cache (Static Assets)

File `.htaccess` atau Nginx config sudah mengatur cache untuk assets.

```apache
# Apache — override di .htaccess
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 7 days"
    ExpiresByType application/javascript "access plus 7 days"
    ExpiresByType image/png "access plus 30 days"
    ExpiresByType image/jpeg "access plus 30 days"
</IfModule>
```

### 7.3. Application Cache (Cache Warmup)

Buat script cache warmer untuk menjaga cache tetap hangat:

```bash
#!/bin/bash
# scripts/cache-warm.sh — jalankan via cron setiap 5 menit

BASE_URL="https://dashboard-se2026.bpsjember.id"
COOKIE_FILE="/tmp/dashboard_cookie.txt"

# Login (gunakan service account)
curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" \
  -d "username=cache_bot&password=..." \
  "$BASE_URL?page=login" > /dev/null

# Warm cache endpoints
curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" "$BASE_URL?page=dashboard" > /dev/null
curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" "$BASE_URL?page=dashboard&sub=monitoring" > /dev/null
```

### 7.4. Cache Invalidation

Cache otomatis invalid saat data berubah:

- **Import data**: `Cache::forget('dashboard_stats')`
- **Assignment baru**: `Cache::forget('monitoring_summary_*')`
- **Update petugas**: `Cache::forget('kecamatan_list')`

---

## 8. Logging Configuration

### 8.1. Application Logs

Log ditulis ke `storage/logs/app-YYYY-MM-DD.log`.

**Format log:**

```
[2026-05-27 10:30:00] RuntimeError: Database connection failed in /var/www/.../Database.php:36
```

**Production log level:** `error_log()` — hanya mencatat error dan exception.

### 8.2. Log Rotation

Buat logrotate config untuk mencegah log membesar tanpa batas:

```bash
# /etc/logrotate.d/dashboard-se2026
/var/www/dashboard-se2026/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
    create 775 www-data www-data
    maxsize 50M
}
```

Verifikasi:

```bash
sudo logrotate -d /etc/logrotate.d/dashboard-se2026
sudo logrotate -f /etc/logrotate.d/dashboard-se2026
```

### 8.3. Audit Trail

Semua aktivitas penting sudah tercatat melalui:

| Table | Mencatat |
|-------|----------|
| `dash_import_log` | Aktivitas import file (siapa, file, hasil) |
| `dash_assignment_log` | Perubahan assignment (siapa, sebelum, sesudah) |
| `activity_logs` | Login/logout attempt |

### 8.4. Monitoring Log Size

Script monitoring ukuran log:

```bash
#!/bin/bash
# scripts/check-logs.sh
MAX_SIZE=$((100 * 1024 * 1024))  # 100 MB
LOG_DIR="/var/www/dashboard-se2026/storage/logs"

for log in "$LOG_DIR"/*.log; do
    if [ -f "$log" ]; then
        size=$(stat -c%s "$log")
        if [ "$size" -gt "$MAX_SIZE" ]; then
            echo "WARNING: $log is $(numfmt --to=iec $size)"
        fi
    fi
done
```

---

## 9. Cron Jobs

### 9.1. Database Backup

```bash
# Setiap hari jam 02:00 WIB — full backup
0 2 * * * /usr/bin/mysqldump -u dashboard_user -p'password' --single-transaction --routines --triggers bps_jember_se2026 | gzip > /var/www/dashboard-se2026/storage/backups/dashboard_$(date +\%Y\%m\%d).sql.gz 2>> /var/www/dashboard-se2026/storage/logs/cron-backup.log
```

### 9.2. Cache Warmup (Opsional)

```bash
# Setiap 10 menit
*/10 * * * * /var/www/dashboard-se2026/scripts/cache-warm.sh >> /var/www/dashboard-se2026/storage/logs/cron-cache.log 2>&1
```

### 9.3. Monitoring Summary Refresh

Script untuk update `dash_monitoring_summary`:

```bash
# Setiap 15 menit
*/15 * * * * php /var/www/dashboard-se2026/scripts/refresh-summary.php >> /var/www/dashboard-se2026/storage/logs/cron-summary.log 2>&1
```

### 9.4. Session Cleanup

```bash
# Setiap jam — hapus session expired
0 * * * * php -r "session_start(); session_gc();" >> /var/www/dashboard-se2026/storage/logs/cron-session.log 2>&1
```

### 9.5. Ringkasan Semua Cron

```bash
# Crontab
# ────────────────────────────────────────────────────────────
# Backup harian
0 2 * * * /usr/bin/mysqldump ... > /dev/null 2>&1

# Refresh summary monitoring
*/15 * * * * php /var/www/dashboard-se2026/scripts/refresh-summary.php > /dev/null 2>&1

# Cache warmup
*/10 * * * * /var/www/dashboard-se2026/scripts/cache-warm.sh > /dev/null 2>&1

# Hapus backup lebih dari 30 hari
0 3 * * 0 find /var/www/dashboard-se2026/storage/backups -name "dashboard_*.sql.gz" -mtime +30 -delete
```

---

## 10. Security Checklist

### 10.1. File Protection

- [ ] `.env` — permission 640, tidak readable oleh publik
- [ ] `storage/` — tidak accessible via web (Apache RewriteRule / Nginx internal directive)
- [ ] `src/`, `config/`, `views/`, `vendor/` — langsung diblokir
- [ ] Directori listing disabled (`Options -Indexes`)

### 10.2. Database Security

- [ ] User database terpisah (bukan root)
- [ ] Password database kuat (min 16 karakter)
- [ ] Hanya grant privileges ke satu database
- [ ] Batasi koneksi database ke localhost

### 10.3. Session Security

- [ ] Session cookie: `HttpOnly`, `Secure`, `SameSite=Strict`
- [ ] Session fingerprint: user-agent + IP terverifikasi
- [ ] Lifetime: 30 menit (1800 detik) untuk production
- [ ] Regenerasi session ID setelah login

### 10.4. CSRF & XSS

- [ ] CSRF token di setiap form POST (sudah diimplementasikan)
- [ ] Output escaping via `htmlspecialchars()` di semua view
- [ ] Security headers di .htaccess:
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: SAMEORIGIN`
  - `X-XSS-Protection: 1; mode=block`
  - `Referrer-Policy: strict-origin-when-cross-origin`

### 10.5. File Upload

- [ ] Ekstensi dibatasi: hanya `.xlsx`, `.xls`, `.csv`
- [ ] Ukuran file: maks 10 MB
- [ ] Upload disimpan di luar public directory (storage/uploads/)
- [ ] Nama file di-sanitize

### 10.6. HTTPS & SSL

- [ ] Selalu gunakan HTTPS
- [ ] Redirect HTTP → HTTPS
- [ ] HSTS header (setelah testing):
  ```
  Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
  ```

### 10.7. Production Checks

```bash
# Test rewrite rules
curl -I https://dashboard-se2026.bpsjember.id/.env
# Expected: 403 Forbidden atau 404

curl -I https://dashboard-se2026.bpsjember.id/src/Core/Database.php
# Expected: 403 Forbidden

curl -I https://dashboard-se2026.bpsjember.id/storage/logs/app-2026-05-27.log
# Expected: 403 Forbidden

# Test security headers
curl -I https://dashboard-se2026.bpsjember.id/ | grep -E 'X-Content|X-Frame|X-XSS|Referrer'
```

---

## 11. Deployment Checklist

### 11.1. Pre-Deployment

- [ ] Code sudah di-test di staging/local
- [ ] Database migration sudah dijalankan di staging
- [ ] Semua fitur berfungsi di staging environment
- [ ] Backup database terakhir sudah dibuat
- [ ] Backup file project sudah dibuat
- [ ] `APP_DEBUG=false` di .env
- [ ] `APP_ENV=production` di .env
- [ ] `BASE_URL` sudah sesuai domain

### 11.2. Deployment Steps

#### A. Deploy ke Shared Hosting (via FTP/SFTP)

```bash
# 1. Backup existing files
ssh user@host "cp -r ~/public_html ~/backups/dashboard_$(date +%Y%m%d_%H%M%S)"

# 2. Upload file (exclude development files)
rsync -avz --exclude-from='.deployignore' \
  ./ user@host:~/public_html/

# 3. Install dependencies
ssh user@host "cd ~/public_html && composer install --no-dev --optimize-autoloader --no-interaction"

# 4. Setup environment
ssh user@host "cd ~/public_html && cp .env.example .env"
# Edit .env via SSH atau panel hosting

# 5. Set permissions
ssh user@host "cd ~/public_html && chmod -R 775 storage/cache storage/logs storage/uploads"

# 6. Run database migration
mysql -u user -p bps_jember_se2026 < database/patch_001_dashboard_base.sql
mysql -u user -p bps_jember_se2026 < database/patch_002_import_log.sql
mysql -u user -p bps_jember_se2026 < database/patch_003_performance_indexes.sql
mysql -u user -p bps_jember_se2026 < database/patch_004_backup_recovery.sql
```

#### B. Deploy ke VPS (via Git)

```bash
# 1. Backup database
mysqldump -u dashboard_user -p bps_jember_se2026 > ~/backups/pre_deploy_$(date +%Y%m%d).sql

# 2. Pull latest code
cd /var/www/dashboard-se2026
git fetch origin
git checkout main
git pull origin main

# 3. Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Run migrations
for f in database/patch_*.sql; do
    mysql -u dashboard_user -p bps_jember_se2026 < "$f"
done

# 5. Clear cache
rm -rf storage/cache/*

# 6. Set permissions
sudo chown -R www-data:www-data storage/
```

### 11.3. Post-Deployment

- [ ] Akses halaman login → OK
- [ ] Login sebagai admin → OK
- [ ] Dashboard menampilkan data → OK
- [ ] Import file Excel berhasil → OK
- [ ] Assignment petugas berfungsi → OK
- [ ] Monitoring menampilkan data → OK
- [ ] Export Excel/PDF berfungsi → OK
- [ ] Session timeout bekerja → OK
- [ ] Cache di-flush dan terisi ulang → OK

### 11.4. Rollback Preparation

Sebelum deploy, pastikan rollback file siap:

```bash
# Simpan current state
cp -r /var/www/dashboard-se2026 /var/www/dashboard-se2026_rollback_$(date +%Y%m%d)

# Simpan database
mysqldump -u dashboard_user -p bps_jember_se2026 > /var/www/dashboard-se2026/storage/backups/pre_deploy_$(date +%Y%m%d_%H%M%S).sql
```

---

## 12. Rollback Plan

### 12.1. File Rollback

```bash
#!/bin/bash
# scripts/rollback.sh
# Gunakan jika deployment baru bermasalah

BACKUP_DIR="/var/www/backups"
PROJECT_DIR="/var/www/dashboard-se2026"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Cari backup terakhir
LATEST_BACKUP=$(ls -dt "$BACKUP_DIR"/dashboard_* 2>/dev/null | head -1)

if [ -z "$LATEST_BACKUP" ]; then
    echo "ERROR: Tidak ada backup ditemukan di $BACKUP_DIR"
    exit 1
fi

echo "Rollback ke: $LATEST_BACKUP"

# Backup current state dulu (save jika ingin dibandingkan)
cp -r "$PROJECT_DIR" "${PROJECT_DIR}_error_${TIMESTAMP}"

# Restore
rm -rf "$PROJECT_DIR"
cp -r "$LATEST_BACKUP" "$PROJECT_DIR"

# Restore permissions
chown -R www-data:www-data "$PROJECT_DIR/storage"
chmod -R 775 "$PROJECT_DIR/storage/cache"
chmod -R 775 "$PROJECT_DIR/storage/logs"
chmod -R 775 "$PROJECT_DIR/storage/uploads"

echo "Rollback file selesai. Restarting web server..."
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx  # atau apache2

echo "Rollback selesai: $TIMESTAMP"
```

### 12.2. Database Rollback

```bash
#!/bin/bash
# scripts/db-rollback.sh
# Rollback database ke state sebelum deployment

BACKUP_DIR="/var/www/dashboard-se2026/storage/backups"
DB_USER="dashboard_user"
DB_PASS="KuatDanAman2026!"
DB_NAME="bps_jember_se2026"

# Cari backup pre-deploy terakhir
LATEST_SQL=$(ls -t "$BACKUP_DIR"/pre_deploy_*.sql 2>/dev/null | head -1)

if [ -z "$LATEST_SQL" ]; then
    echo "ERROR: Tidak ada pre-deploy backup ditemukan"
    echo "Gunakan backup harian terakhir:"
    ls -t "$BACKUP_DIR"/dashboard_*.sql.gz 2>/dev/null | head -3
    exit 1
fi

echo "Rollback database menggunakan: $LATEST_SQL"

# Backup current state
CURRENT_BACKUP="$BACKUP_DIR/rollback_$(date +%Y%m%d_%H%M%S).sql"
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$CURRENT_BACKUP"
echo "Current state disimpan: $CURRENT_BACKUP"

# Restore
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$LATEST_SQL"

echo "Database rollback selesai: $(date)"
```

### 12.3. Rollback Decision Matrix

| Skenario | File Rollback | DB Rollback | Waktu |
|----------|---------------|-------------|-------|
| Halaman error 500 | Ya | Tidak | 5 menit |
| Fitur rusak | Ya | Tidak | 10 menit |
| Data hilang | Ya | Ya | 15 menit |
| Performance drop | Ya (kembali ke versi stabil) | Tidak | 5 menit |
| Security issue | Ya (patch darurat) | Tidak | Segera |

### 12.4. Quick Rollback (Emergency)

```bash
# Emergency rollback — jika halaman utama error total
# 1. Nonaktifkan sementara dengan maintenance page
cp maintenance.html /var/www/dashboard-se2026/index.html

# 2. Restore dari git
cd /var/www/dashboard-se2026
git stash
git checkout <previous-stable-tag>

# 3. Restore database jika perlu
mysql -u dashboard_user -p bps_jember_se2026 < storage/backups/pre_deploy_latest.sql

# 4. Hapus maintenance page
rm /var/www/dashboard-se2026/index.html
```

### 12.5. Maintenance Page

Buat file `maintenance.html`:

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pemeliharaan — Dashboard SE2026</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; justify-content: center; 
               align-items: center; min-height: 100vh; margin: 0; background: #f8f9fa; }
        .card { text-align: center; padding: 3rem; background: white; border-radius: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #dc3545; }
        p { color: #6c757d; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🛠️ Sedang Pemeliharaan</h1>
        <p>Dashboard SE2026 sedang diperbarui.<br>Silakan coba lagi dalam beberapa menit.</p>
        <p><small>BPS Kabupaten Jember</small></p>
    </div>
</body>
</html>
```

---

## 13. Monitoring & Health Check

### 13.1. Health Endpoint

Buat file `health.php` di root:

```php
<?php
// health.php — monitoring uptime & dependencies
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$status = 'ok';
$checks = [];

// PHP version
$checks['php_version'] = [
    'status' => PHP_VERSION_ID >= 80200 ? 'ok' : 'fail',
    'value'  => PHP_VERSION,
];

// Database connection
try {
    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/config/config.php';
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'], $_ENV['DB_PASS'],
        [PDO::ATTR_TIMEOUT => 3]
    );
    $pdo->query('SELECT 1');
    $checks['database'] = ['status' => 'ok'];
} catch (\Throwable $e) {
    $checks['database'] = ['status' => 'fail', 'message' => $e->getMessage()];
    $status = 'degraded';
}

// Storage writable
$storageDir = __DIR__ . '/storage';
$checks['storage_writable'] = [
    'status' => is_writable($storageDir) ? 'ok' : 'fail',
];

// Disk space
$free = disk_free_space($storageDir);
$total = disk_total_space($storageDir);
$usagePercent = round((1 - $free / $total) * 100, 1);
$checks['disk_usage'] = [
    'status' => $usagePercent < 90 ? 'ok' : 'warn',
    'usage' => $usagePercent . '%',
];

// Session configured
$checks['session'] = [
    'status' => session_status() === PHP_SESSION_NONE ? 'ok' : 'ok',
];

$response = [
    'status'    => $status,
    'timestamp' => date('c'),
    'uptime'    => shell_exec('uptime -p') ?: 'N/A',
    'checks'    => $checks,
];

http_response_code($status === 'ok' ? 200 : 503);
echo json_encode($response, JSON_PRETTY_PRINT);
```

Akses: `https://dashboard-se2026.bpsjember.id/health.php`

### 13.2. Monitoring via Uptime Robot / Better Uptime

Configure monitor:
- **URL**: `https://dashboard-se2026.bpsjember.id/`
- **Check interval**: 5 menit
- **Alert**: Email + Telegram/Slack

### 13.3. Alert Thresholds

| Metric | Warning | Critical | Action |
|--------|---------|----------|--------|
| Disk usage | > 80% | > 90% | Hapus backup lama, periksa logs |
| PHP errors | > 10/jam | > 50/jam | Check logs, rollback jika perlu |
| Response time | > 2s | > 5s | Periksa database queries, cache |
| Login failures | > 5/menit | > 20/menit | Cek aktivitas mencurigakan |
| Database connection | - | Gagal | Restart MySQL, cek koneksi |

### 13.4. Production Health Dashboard

Buat script monitoring sederhana:

```bash
#!/bin/bash
# scripts/health-check.sh

echo "=== Dashboard SE2026 Health Check ==="
echo "Waktu: $(date)"

# 1. HTTP Status
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://dashboard-se2026.bpsjember.id/)
echo "HTTP Status: $HTTP_CODE"

# 2. PHP FPM
if systemctl is-active php8.2-fpm > /dev/null 2>&1; then
    echo "PHP-FPM: running"
else
    echo "PHP-FPM: DOWN!"
fi

# 3. Database
if mysqladmin ping -u dashboard_user -p'***' --silent 2>/dev/null; then
    echo "Database: connected"
else
    echo "Database: DISCONNECTED!"
fi

# 4. Storage
STORAGE_USAGE=$(df -h /var/www/dashboard-se2026/storage | tail -1 | awk '{print $5}')
echo "Storage Usage: $STORAGE_USAGE"

# 5. Recent errors
ERROR_COUNT=$(find /var/www/dashboard-se2026/storage/logs -name "*.log" -mmin -60 -exec grep -l "ERROR\|Fatal" {} \; 2>/dev/null | wc -l)
echo "Errors (last 1hr): $ERROR_COUNT"
```

---

## 14. Backup Strategy

### 14.1. Database Backup

Jadwal backup harian menggunakan cron:

```bash
# /etc/cron.d/dashboard-backup
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# Full backup setiap hari jam 02:00 WIB
0 2 * * * www-data /usr/bin/mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    --quick \
    -u dashboard_user \
    -p'KuatDanAman2026!' \
    bps_jember_se2026 \
    | gzip > /var/www/dashboard-se2026/storage/backups/daily_$(date +\%Y\%m\%d).sql.gz \
    2>> /var/www/dashboard-se2026/storage/logs/backup.log

# Hapus backup lebih dari 30 hari
0 4 * * * www-data find /var/www/dashboard-se2026/storage/backups -name "daily_*.sql.gz" -mtime +30 -delete
```

### 14.2. File Backup

```bash
# Backup project files (tanpa vendor, tanpa storage)
tar czf /var/backups/dashboard-files-$(date +%Y%m%d).tar.gz \
  --exclude=vendor \
  --exclude=storage \
  --exclude=.git \
  /var/www/dashboard-se2026/
```

### 14.3. Restore Procedure

```bash
# 1. Restore database
gunzip < daily_20260527.sql.gz | mysql -u dashboard_user -p bps_jember_se2026

# 2. Restore files
tar xzf dashboard-files-20260527.tar.gz -C /var/www/

# 3. Reinstall dependencies
cd /var/www/dashboard-se2026 && composer install --no-dev --optimize-autoloader

# 4. Set permissions
chown -R www-data:www-data storage/
chmod -R 775 storage/cache storage/logs storage/uploads

# 5. Clear cache
rm -rf storage/cache/*
```

### 14.4. Backup Verification

```bash
# Test bahwa file backup tidak corrupt
gzip -t /var/www/dashboard-se2026/storage/backups/daily_20260527.sql.gz && echo "OK"
```

---

## Quick Reference

### Perintah Penting

```bash
# Install dependencies production
composer install --no-dev --optimize-autoloader --no-interaction

# Clear cache
rm -rf storage/cache/*

# View logs
tail -f storage/logs/app-$(date +%Y-%m-%d).log

# Check permissions
ls -la storage/cache storage/logs storage/uploads
stat .env

# Test database connection
mysql -u dashboard_user -p -e "SELECT 1" bps_jember_se2026

# Run all migrations
for f in database/patch_*.sql; do mysql -u dashboard_user -p bps_jember_se2026 < "$f"; done
```

### File & Directory Structure

```
dashboard-se2026/
├── .env                    # [PROTECTED] Konfigurasi environment
├── .htaccess               # Apache rewrite rules (production)
├── index.php               # Front controller
├── composer.json           # Dependencies
├── config/
│   ├── config.php          # Aplikasi config
│   └── constants.php       # Role & access constants
├── src/
│   ├── Core/               # Framework core
│   ├── Controllers/        # Business logic
│   ├── Models/             # Database queries
│   ├── Helpers/            # Utility classes
│   ├── Middleware/         # Auth, CSRF, Role
│   └── Services/           # Import processor
├── views/                  # HTML templates
├── assets/                 # CSS, JS, images (cacheable)
├── storage/                # [WRITABLE]
│   ├── cache/              # File cache
│   ├── logs/               # Error logs
│   ├── uploads/            # Uploaded files
│   └── backups/            # Database dumps
├── database/               # SQL migrations
└── scripts/                # Utility scripts
```
