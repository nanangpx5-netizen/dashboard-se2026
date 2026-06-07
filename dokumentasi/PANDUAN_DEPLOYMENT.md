# Panduan Deployment — Dashboard SE2026 BPS Kabupaten Jember

> **Proyek**: Dashboard Sensus Ekonomi 2026  
> **PHP**: ^8.2 | **MySQL**: 8.0+ | **Server**: Apache / Nginx  
> **Repositori**: `bps-jember/dashboard-se2026`

---

## Daftar Isi

- [Fase 1 — Pra-Deployment (Persiapan Awal)](#fase-1--pra-deployment-persiapan-awal)
  - [1.1 Verifikasi Dependensi Lokal](#11-verifikasi-dependensi-lokal)
  - [1.2 Cek Prerequisites Hosting](#12-cek-prerequisites-hosting)
  - [1.3 Konfigurasi Environment Variable](#13-konfigurasi-environment-variable)
- [Fase 2 — Persiapan Repositori Git](#fase-2--persiapan-repositori-git)
  - [2.1 Commit & Push dari Localhost](#21-commit--push-dari-localhost)
  - [2.2 Inisiasi Repositori di Server Hosting](#22-inisiasi-repositori-di-server-hosting)
- [Fase 3 — Deployment Melalui Terminal](#fase-3--deployment-melalui-terminal)
  - [3.1 SSH ke Server Hosting](#31-ssh-ke-server-hosting)
  - [3.2 Tarik Kode Terbaru](#32-tarik-kode-terbaru)
  - [3.3 Instal Dependensi Produksi](#33-instal-dependensi-produksi)
  - [3.4 Konfigurasi Environment](#34-konfigurasi-environment)
  - [3.5 Migrasi Database](#35-migrasi-database)
  - [3.6 Build & Optimasi](#36-build--optimasi)
  - [3.7 Konfigurasi Web Server](#37-konfigurasi-web-server)
- [Fase 4 — PascadDeployment](#fase-4--pascadeployment)
  - [4.1 Verifikasi Fungsionalitas](#41-verifikasi-fungsionalitas)
  - [4.2 Backup Rutin](#42-backup-rutin)
- [Fase 5 — Troubleshooting](#fase-5--troubleshooting)
  - [5.1 Git Pull Gagal (Konflik)](#51-git-pull-gagal-konflik)
  - [5.2 Composer Install Gagal / Memory Limit](#52-composer-install-gagal--memory-limit)
  - [5.3 Koneksi Database Error](#53-koneksi-database-error)
  - [5.4 500 Internal Server Error / White Screen](#54-500-internal-server-error--white-screen)
  - [5.5 403 Forbidden pada Directory](#55-403-forbidden-pada-directory)
  - [5.6 OpenSpout / Temp Directory Error](#56-openspout--temp-directory-error)
  - [5.7 Session / Login Loop](#57-session--login-loop)

---

## Fase 1 — Pra-Deployment (Persiapan Awal)

### 1.1 Verifikasi Dependensi Lokal

Pastikan semua dependensi terinstal dan tidak ada error sintaks sebelum push.

```bash
# 1. Cek versi PHP (min 8.2)
php -v

# 2. Cek ekstensi PHP yang dibutuhkan
php -m | grep -E "pdo_mysql|mbstring"

# 3. Install dependensi Composer (tanpa dev)
composer install --no-dev --optimize-autoloader

# 4. Lint semua file PHP (kecuali vendor, cache)
find . -type f -name "*.php" \
  -not -path "./vendor/*" \
  -not -path "./storage/cache/*" \
  -print0 | xargs -0 -n1 php -l

# 5. Jika ada file CSS/JS build, jalankan:
# npm run build   # (jika ada package.json dengan build script)
```

> **Catatan**: Proyek ini **tidak menggunakan package.json** — semua aset frontend (Bootstrap, jQuery, DataTables, Chart.js, Leaflet, Select2) dimuat langsung dari CDN. Tidak diperlukan build frontend.

### 1.2 Cek Prerequisites Hosting

Pastikan server hosting memenuhi syarat berikut:

| Komponen | Spesifikasi Minimal |
|----------|-------------------|
| **PHP** | 8.2 atau lebih baru |
| **MySQL** | 8.0 atau lebih baru |
| **Ekstensi PHP** | `pdo_mysql`, `mbstring` |
| **Web Server** | Apache (mod_rewrite) atau Nginx |
| **SSH/Shell** | Akses terminal (shared hosting: pastikan ada opsi SSH) |
| **Git** | Terinstal di server |
| **Composer** | Terinstal di server |
| **Disk** | ~500 MB + storage data |
| **Memory** | Minimal 256 MB (recommended 512 MB+ untuk PHP) |

Verifikasi akses dari localhost:

```bash
# Uji koneksi SSH ke server
ssh user@ip-server-hostinger

# Cek versi PHP di server
ssh user@ip-server 'php -v'

# Cek Git
ssh user@ip-server 'git --version'

# Cek Composer
ssh user@ip-server 'composer --version'
```

### 1.3 Konfigurasi Environment Variable

Buat file `.env` dari template. Jangan commit `.env` ke repositori — isi manual di server.

```bash
# Salin dari template
cp .env.example .env
```

Sesuaikan nilai-nilai berikut untuk **lingkungan produksi**:

```ini
# ─── Database ────────────────────────────────────────────────
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database_produksi
DB_USERNAME=user_database_produksi
DB_PASSWORD=password_kuat_database

# ─── Application ─────────────────────────────────────────────
APP_NAME="Dashboard SE2026 Jember"
APP_ENV=production
APP_DEBUG=false                          # WAJIB false di produksi!
BASE_URL=/                               # Jika di root domain
# BASE_URL=/dashboard-se2026/            # Jika di subfolder

# ─── Session ─────────────────────────────────────────────────
SESSION_LIFETIME=7200                    # 2 jam

# ─── Security Headers ────────────────────────────────────────
CSP_ENABLED=true

# ─── CORS ────────────────────────────────────────────────────
CORS_ALLOWED_ORIGINS=                    # Kosongkan jika tidak ada API consumer
```

> **Penting**: `APP_DEBUG=false` di produksi mencegah kebocoran stack trace.

---

## Fase 2 — Persiapan Repositori Git

### 2.1 Commit & Push dari Localhost

```bash
# 1. Cek status perubahan
git status

# 2. Lihat perubahan yang belum di-commit
git diff

# 3. Stage file yang relevan
git add .

# 4. Commit dengan pesan deskriptif
git commit -m "feat: deskripsi perubahan"

# 5. Pastikan di branch yang benar
git branch                 # harus main atau master

# 6. Tarik perubahan terbaru dari remote (hindari konflik)
git pull origin main --rebase

# 7. Push ke repositori pusat
git push origin main
```

> **Jika terjadi konflik saat `git pull`**:
> ```bash
> # Selesaikan konflik secara manual, lalu:
> git add .
> git rebase --continue
> git push origin main
> ```

### 2.2 Inisiasi Repositori di Server Hosting

Jika server belum memiliki repositori:

```bash
# SSH ke server
ssh user@ip-server

# Masuk ke direktori web root
# Apache: /var/www/html atau /home/user/public_html
# Nginx: /usr/share/nginx/html
cd /home/user/public_html

# Clone repositori
git clone https://github.com/bps-jember/dashboard-se2026.git .

# Atau jika sudah ada folder, inisiasi baru:
# git init
# git remote add origin https://github.com/bps-jember/dashboard-se2026.git
# git fetch origin
# git checkout -b main origin/main
```

---

## Fase 3 — Deployment Melalui Terminal

### 3.1 SSH ke Server Hosting

```bash
# Akses server
ssh user@ip-server-hostinger

# Masuk ke direktori root website
cd /home/user/public_html
# atau
cd /var/www/dashboard-se2026
```

### 3.2 Tarik Kode Terbaru

```bash
# Backup dulu (Lihat Fase 4.2 untuk detail backup)

# Cek branch aktif
git branch

# Tarik kode terbaru
git pull origin main

# Lihat log perubahan
git log --oneline -5
```

### 3.3 Instal Dependensi Produksi

```bash
# Hanya dependensi production (tanpa dev)
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Verifikasi semua file autoload
composer dump-autoload --optimize
```

Hasil yang diharapkan:
- Folder `vendor/` terisi
- `composer.lock` tercatat
- Tidak ada error klas

### 3.4 Konfigurasi Environment

```bash
# Jika file .env belum ada
cp .env.example .env

# Edit dengan editor terminal
nano .env
# atau
vim .env
```

Isi nilai produksi sesuai [Fase 1.3](#13-konfigurasi-environment-variable).

### 3.5 Migrasi Database

Proyek ini menggunakan **patch SQL idempotent** — aman dijalankan berulang kali.

```bash
# Cek apakah koneksi database berfungsi
php -r "
require 'vendor/autoload.php';
\$db = App\\Core\\Database::instance()->pdo();
echo 'Koneksi OK: ' . \$db->query('SELECT VERSION()')->fetchColumn() . PHP_EOL;
"

# Jalankan patch database secara berurutan
# Patch 001-008:
for f in database/patch_*.sql; do
    echo "Menjalankan $f ..."
    mysql -u user -p nama_database < "$f"
done

# Atau jika ada script apply PHP:
php scripts/apply_patch_007.php
php scripts/apply_patch_008.php
php scripts/apply_patch_009.php
```

> **Catatan**: Patch bersifat idempotent — jika kolom/index sudah ada, patch akan dilewati.

### 3.6 Build & Optimasi

```bash
# Buat direktori yang diperlukan
mkdir -p storage/import
mkdir -p storage/cache
mkdir -p storage/backup
mkdir -p storage/tmp

# Set permission yang benar
chmod -R 755 storage
chmod -R 755 assets
chmod 644 .env

# Jika ada file CSS/JS build, jalankan:
# npm run build

# Verifikasi sintaks semua file
find . -type f -name "*.php" \
  -not -path "./vendor/*" \
  -not -path "./storage/cache/*" \
  -print0 | xargs -0 -n1 php -l | grep -v "No syntax errors"

# Hapus file yang tidak diperlukan di produksi
rm -rf storage/tmp
rm -rf .git/hooks
rm -f phpunit.xml
```

### 3.7 Konfigurasi Web Server

#### Apache (mod_rewrite)

File `.htaccess` sudah disertakan dalam repositori. Pastikan:

```apache
# Di httpd.conf atau vhost, aktifkan mod_rewrite:
RewriteEngine On

# AllowOverride harus All di Directory root
<Directory /var/www/dashboard-se2026>
    AllowOverride All
</Directory>
```

**Virtual host example**:

```apache
<VirtualHost *:80>
    ServerName se2026.bpsjember.id
    DocumentRoot /var/www/dashboard-se2026

    <Directory /var/www/dashboard-se2026>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/dashboard-error.log
    CustomLog ${APACHE_LOG_DIR}/dashboard-access.log combined
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name se2026.bpsjember.id;
    root /var/www/dashboard-se2026;
    index index.php index.html;

    # Deny direct access to protected directories
    location ~* ^/(app|src|config|storage|views|vendor) {
        deny all;
        return 403;
    }

    # Deny access to sensitive files
    location ~* \.(env|sql|md|log|cache)$ {
        deny all;
        return 403;
    }

    # Route ke front controller
    location / {
        try_files $uri $uri/ /index.php?url=$uri&$args;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Cache assets
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    access_log /var/log/nginx/dashboard-access.log;
    error_log /var/log/nginx/dashboard-error.log;
}
```

#### SSL/HTTPS (Let's Encrypt)

```bash
# Install Certbot, lalu:
sudo certbot --nginx -d se2026.bpsjember.id

# Atau untuk Apache:
sudo certbot --apache -d se2026.bpsjember.id

# Verifikasi renew otomatis
sudo certbot renew --dry-run
```

---

## Fase 4 — PascadDeployment

### 4.1 Verifikasi Fungsionalitas

Setelah deployment selesai, lakukan pengecekan berikut:

```bash
# 1. Cek error log web server
tail -f /var/log/apache2/error.log
# atau
tail -f /var/log/nginx/error.log

# 2. Cek error log PHP
tail -f /var/log/php8.2-fpm.log
```

**Cek dari browser / curl**:

```bash
# Health check endpoint
curl -I https://se2026.bpsjember.id/?page=dashboard

# Test koneksi database via web
curl https://se2026.bpsjember.id/test-connection/

# Health JSON endpoint
curl https://se2026.bpsjember.id/health.php
```

**Cek manual dari UI**:

| Halaman | URL | Yang Dicek |
|---------|-----|-----------|
| Login | `?page=auth` | Form login muncul, no JS error |
| Dashboard | `?page=dashboard` | KPI cards, charts, map render |
| Assignment | `?page=dashboard&sub=assignment` | Tabel, filter, pagination |
| Petugas | `?page=dashboard&sub=petugas-lapangan` | DataTable, modal CRUD |
| Monitoring | `?page=dashboard&sub=monitoring` | Widget polling |
| Report | `?page=dashboard&sub=report` | Preview, export Excel/PDF |
| Download SLS | `?action=download` | File XLSX terdownload |

### 4.2 Backup Rutin

Sebelum deployment pembaruan, lakukan backup:

```bash
# 1. Backup database
mysqldump -u user -p \
  --single-transaction \
  --routines \
  --triggers \
  nama_database > storage/backup/db_$(date +%Y%m%d_%H%M%S).sql

# 2. Backup file konfigurasi
cp .env storage/backup/.env_$(date +%Y%m%d_%H%M%S).backup

# 3. Backup seluruh proyek (opsional, alternatif: git tag)
tar -czf storage/backup/project_$(date +%Y%m%d_%H%M%S).tar.gz \
  --exclude=vendor \
  --exclude=storage/cache \
  --exclude=.git \
  .

# 4. Buat Git tag untuk rilis (rekomendasi)
git tag -a v1.0.0 -m "Rilis produksi v1.0.0"
git push origin v1.0.0
```

**Jadwal backup yang direkomendasikan**:
- **Database**: Setiap hari (cron job)
- **File konfigurasi**: Setiap sebelum update
- **Git tag**: Setiap rilis mayor/minor

---

## Fase 5 — Troubleshooting

### 5.1 Git Pull Gagal (Konflik)

**Gejala**: `git pull` menghasilkan error "merge conflict".

**Solusi**:

```bash
# Opsi 1: Rebase (riwayat bersih)
git fetch origin
git stash                    # simpan perubahan lokal
git rebase origin/main
git stash pop                # kembalikan perubahan

# Opsi 2: Force pull (timpa semua perubahan lokal)
git fetch origin
git reset --hard origin/main

# Opsi 3: Jika ada file tracked berubah di lokal
git checkout -- .
git pull origin main
```

### 5.2 Composer Install Gagal / Memory Limit

**Gejala**: `composer install` berhenti dengan `Allowed memory size exhausted` atau error `proc_open`.

**Solusi**:

```bash
# Naikkan memory limit PHP
php -d memory_limit=512M composer install --no-dev

# Atau set environment variable
COMPOSER_MEMORY_LIMIT=512M composer install --no-dev

# Jika proc_open di-disable di hosting:
# Gunakan --prefer-dist untuk menghindari compile dari source
composer install --no-dev --prefer-dist
```

### 5.3 Koneksi Database Error

**Gejala**: Halaman error "Connection refused" atau "Unknown database".

**Solusi**:

```bash
# 1. Verifikasi file .env
cat .env | grep DB_

# 2. Test koneksi dari terminal
php -r "
try {
    \$pdo = new PDO(
        'mysql:host=localhost;port=3306;dbname=nama_database;charset=utf8mb4',
        'user',
        'password'
    );
    echo 'OK: ' . \$pdo->query('SELECT VERSION()')->fetchColumn() . PHP_EOL;
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage() . PHP_EOL;
}
"

# 3. Pastikan MySQL running
systemctl status mysql
# atau
mysqladmin ping -u root -p

# 4. Cek hak akses user database
mysql -u root -p -e "SHOW GRANTS FOR 'user'@'localhost';"
```

**Penyebab umum**:
- **Hostinger / shared hosting**: DB_HOST bukan `localhost`, tapi `mysql.hostinger.com` (cek panel hosting)
- **Port bukan 3306**: Beberapa hosting menggunakan port berbeda
- **User hanya boleh dari localhost**: Tambahkan `'user'@'%'` jika aplikasi dan DB di server berbeda

### 5.4 500 Internal Server Error / White Screen

**Gejala**: Halaman kosong atau error 500 tanpa pesan jelas.

**Solusi**:

```bash
# 1. Cek error log
tail -100 /var/log/apache2/error.log
# atau
tail -100 /var/log/php8.2-fpm.log

# 2. Pastikan APP_DEBUG=true sementara (untuk lihat error)
#    Hanya untuk debugging! Jangan biarkan di produksi.
#    Ubah di .env: APP_DEBUG=true
#    Refresh halaman, lalu kembalikan ke false setelah selesai.

# 3. Cek permission file
ls -la
chmod -R 755 .
chmod 644 .env

# 4. Cek sintaks PHP
find . -name "*.php" -not -path "./vendor/*" | xargs -P4 -n1 php -l | grep -v "No syntax"

# 5. Cek log PHP
php -r "echo ini_get('error_log');"
cat $(php -r "echo ini_get('error_log');")
```

### 5.5 403 Forbidden pada Directory

**Gejala**: Akses folder tertentu menghasilkan 403 Forbidden.

**Solusi**:

```bash
# 1. Cek permission direktori
ls -la
chmod 755 .
chmod 644 index.php .htaccess

# 2. Pastikan .htaccess terbaca (Apache)
# Cek AllowOverride di httpd.conf

# 3. Untuk Nginx, pastikan konfigurasi:
# location / {
#     try_files $uri $uri/ /index.php?url=$uri&$args;
# }
```

### 5.6 OpenSpout / Temp Directory Error

**Gejala**: Error saat download/import Excel — "Temp folder is not writable".

**Solusi**:

```bash
# Pastikan folder storage/import writable
mkdir -p storage/import
chmod 755 storage/import

# Jika masih error, coba set temp folder manual di kode:
# ReaderOptions::setTempFolder('storage/import')
# atau set environment variable:
export OPENSPOUT_TEMP_DIR=/home/user/public_html/storage/import
```

### 5.7 Session / Login Loop

**Gejala**: Berhasil login tapi langsung redirect kembali ke halaman login.

**Solusi**:

```bash
# 1. Cek folder session writable
ls -la /var/lib/php/sessions/
# Atau
ls -la /tmp/

# 2. Pastikan session save path dikonfigurasi
php -r "echo session_save_path();"

# 3. Cek cookie domain — BASE_URL harus sesuai
#    Jika di subfolder: BASE_URL=/dashboard-se2026/
#    Jika di root domain: BASE_URL=/

# 4. Hapus session storage jika korup
rm -f /var/lib/php/sessions/sess_*
# (Hati-hati: ini menghapus semua session aktif)

# 5. Cek setting SESSION_LIFETIME di .env (default 7200 detik)
```
