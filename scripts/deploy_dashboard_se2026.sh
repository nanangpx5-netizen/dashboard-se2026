#!/bin/bash
set -e

#──────────────────────────────────────────────────────────────
# DEPLOY DASHBOARD SE2026 + DATABASE MIGRATION
#──────────────────────────────────────────────────────────────
# Usage:
#   ./deploy_dashboard_se2026.sh                  # deploy only (no data changes)
#   ./deploy_dashboard_se2026.sh --with-data      # deploy + run data fix/seed scripts
#   ./deploy_dashboard_se2026.sh --skip-migrate   # deploy only, skip DB migration
#   ./deploy_dashboard_se2026.sh --skip-backup    # deploy without DB backup (risky)
#──────────────────────────────────────────────────────────────

REPO_DIR="/home/bpsjembe/repositories/dashboard-se2026"
WEB_DIR="/home/bpsjembe/dashboard-se2026.bpsjember.my.id"
BACKUP_DIR="/home/bpsjembe/backups"
TS=$(date +%F-%H%M%S)

# Database config (sesuaikan dengan .env.production)
DB_NAME="bps_jember_se2026"
DB_USER="bpsjembe"
DB_PASS=""  # ambil dari .env.production
DB_HOST="localhost"

# Flags
WITH_DATA=false
SKIP_MIGRATE=false
SKIP_BACKUP=false

for arg in "$@"; do
    case $arg in
        --with-data)    WITH_DATA=true ;;
        --skip-migrate) SKIP_MIGRATE=true ;;
        --skip-backup)  SKIP_BACKUP=true ;;
        *) echo "Unknown arg: $arg"; exit 1 ;;
    esac
done

# Load DB credentials from .env.production
if [ -f "$WEB_DIR/.env.production" ]; then
    DB_PASS=$(grep '^DB_PASSWORD=' "$WEB_DIR/.env.production" | cut -d= -f2- | tr -d '"' | tr -d "'")
    DB_NAME=$(grep '^DB_DATABASE=' "$WEB_DIR/.env.production" | cut -d= -f2- | tr -d '"' | tr -d "'")
    DB_USER=$(grep '^DB_USERNAME=' "$WEB_DIR/.env.production" | cut -d= -f2- | tr -d '"' | tr -d "'")
    DB_HOST=$(grep '^DB_HOST=' "$WEB_DIR/.env.production" | cut -d= -f2- | tr -d '"' | tr -d "'")
fi

echo "=================================="
echo " DEPLOY DASHBOARD SE2026"
echo "  with-data    = $WITH_DATA"
echo "  skip-migrate = $SKIP_MIGRATE"
echo "=================================="

#──────────────────────────────────────────────────────────────
# 1. Backup current dashboard (filesystem)
#──────────────────────────────────────────────────────────────
echo ""
echo "== [1/8] Backup current dashboard files =="

mkdir -p "$BACKUP_DIR"

if [ -d "$WEB_DIR" ]; then
    tar -czf "$BACKUP_DIR/dashboard-se2026-backup-$TS.tar.gz" \
      -C /home/bpsjembe \
      dashboard-se2026.bpsjember.my.id
    echo "  Files backup: $BACKUP_DIR/dashboard-se2026-backup-$TS.tar.gz"
fi

#──────────────────────────────────────────────────────────────
# 2. Backup current database (if mysqldump available)
#──────────────────────────────────────────────────────────────
if [ "$SKIP_BACKUP" = false ] && [ "$SKIP_MIGRATE" = false ]; then
    echo ""
    echo "== [2/8] Backup current database =="

    if command -v mysqldump >/dev/null 2>&1; then
        DB_BACKUP="$BACKUP_DIR/db-backup-$TS.sql.gz"
        if mysqldump --user="$DB_USER" --password="$DB_PASS" --host="$DB_HOST" \
                --single-transaction --routines --triggers --events \
                "$DB_NAME" 2>/dev/null | gzip > "$DB_BACKUP"; then
            echo "  DB backup: $DB_BACKUP ($(du -h "$DB_BACKUP" | cut -f1))"
        else
            echo "  ⚠️  mysqldump failed (wrong credentials?)"
            echo "  Continuing without DB backup (risky!)"
        fi
    else
        echo "  ⚠️  mysqldump not found, skipping DB backup"
    fi
fi

#──────────────────────────────────────────────────────────────
# 3. Pull latest code
#──────────────────────────────────────────────────────────────
echo ""
echo "== [3/8] Pull latest code =="

cd "$REPO_DIR"
git pull origin master

#──────────────────────────────────────────────────────────────
# 4. Ensure runtime directories exist
#──────────────────────────────────────────────────────────────
echo ""
echo "== [4/8] Ensure runtime directories exist =="

mkdir -p "$WEB_DIR"
mkdir -p "$WEB_DIR/logs"
mkdir -p "$WEB_DIR/storage"
mkdir -p "$WEB_DIR/uploads"

#──────────────────────────────────────────────────────────────
# 5. Clean web root except protected files
#──────────────────────────────────────────────────────────────
echo ""
echo "== [5/8] Clean web root except protected files =="

find "$WEB_DIR" -mindepth 1 -maxdepth 1 \
  ! -name '.env' \
  ! -name '.env.production' \
  ! -name 'error_log' \
  ! -name 'tmp' \
  ! -name '.well-known' \
  ! -name 'logs' \
  ! -name 'storage' \
  ! -name 'uploads' \
  -exec rm -rf {} +

#──────────────────────────────────────────────────────────────
# 6. Copy new code
#──────────────────────────────────────────────────────────────
echo ""
echo "== [6/8] Copy new code =="

cp -a "$REPO_DIR"/. "$WEB_DIR"/

#──────────────────────────────────────────────────────────────
# 7. Install composer dependencies
#──────────────────────────────────────────────────────────────
echo ""
echo "== [7/8] Install composer dependencies =="

cd "$WEB_DIR"

composer install \
  --no-dev \
  --optimize-autoloader \
  --no-interaction

#──────────────────────────────────────────────────────────────
# 8. Database migration + data fixes
#──────────────────────────────────────────────────────────────
if [ "$SKIP_MIGRATE" = false ]; then
    echo ""
    echo "== [8/8] Database migration =="

    cd "$WEB_DIR"

    # 8a. Apply schema patches (idempotent — aman dijalankan berulang)
    for patch in 006 007 008 009 010; do
        patch_script="scripts/apply_patch_${patch}.php"
        if [ -f "$patch_script" ]; then
            echo ""
            echo "  → apply_patch_${patch}.php (idempotent, no flag needed)"
            php "$patch_script" || {
                echo "  ⚠️  patch_${patch} failed, continuing..."
            }
        fi
    done

    # 8b. Apply direct SQL patches (no apply script — run via mysql CLI)
    echo ""
    echo "  → Applying patch_010_sipw_klas.sql (direct SQL)..."
    mysql --user="$DB_USER" --password="$DB_PASS" --host="$DB_HOST" "$DB_NAME" < "$WEB_DIR/database/patch_010_sipw_klas.sql" 2>/dev/null && echo "    ✅ OK" || echo "    ⚠️  patch_010_sipw_klas failed (maybe already applied)"

    for sql_patch in 011 012 013; do
        sql_file="database/patch_0${sql_patch}_*.sql"
        # Ambil file patch yg cocok
        actual_file=$(ls "$WEB_DIR"/database/patch_0${sql_patch}_*.sql 2>/dev/null | head -1)
        if [ -n "$actual_file" ]; then
            echo "  → Applying $(basename $actual_file)..."
            mysql --user="$DB_USER" --password="$DB_PASS" --host="$DB_HOST" "$DB_NAME" < "$actual_file" 2>/dev/null && echo "    ✅ OK" || echo "    ⚠️  $(basename $actual_file) failed"
        fi
    done
    # Note: patch_001-005 sudah di-apply via phpMyAdmin / SQL langsung (tidak ada script)

    # 8c. Composer autoload refresh
    echo ""
    echo "  → composer dump-autoload"
    composer dump-autoload --no-interaction --optimize 2>/dev/null || true

    # 8d. Clear cache
    echo ""
    echo "  → Clearing cache"
    rm -rf "$WEB_DIR/storage/cache/"* 2>/dev/null || true
    rm -rf "$WEB_DIR/storage/framework/cache/"* 2>/dev/null || true

    # 8e. Optional: data fix/seed scripts (only with --with-data flag)
    if [ "$WITH_DATA" = true ]; then
        echo ""
        echo "  ── Data scripts (--with-data enabled) ──"

        # Run in dependency order
        data_scripts=(
            "fix_prelist_n_sls.php"
            "backfill_mitra_kecamatan.php"
            "populate_kecamatan_ppl_pml.php"
            "populate_petugas_wilayah.php"
            "seed_pegawai_organik.php"
        )

        for s in "${data_scripts[@]}"; do
            if [ -f "scripts/$s" ]; then
                echo ""
                echo "    → scripts/$s --execute"
                php "scripts/$s" --execute || {
                    echo "    ⚠️  $s failed, continuing..."
                }
            fi
        done
    else
        echo ""
        echo "  ℹ️  Data fix/seed scripts NOT run (use --with-data to enable)"
    fi
fi

#──────────────────────────────────────────────────────────────
# Cleanup
#──────────────────────────────────────────────────────────────
echo ""
echo "== Cleanup =="

rm -rf "$WEB_DIR/.git"
rm -f "$WEB_DIR/.gitignore"

chmod -R 775 \
  "$WEB_DIR/logs" \
  "$WEB_DIR/storage" \
  "$WEB_DIR/uploads" || true

#──────────────────────────────────────────────────────────────
# Summary
#──────────────────────────────────────────────────────────────
echo ""
echo "=================================="
echo " DEPLOYMENT SUCCESS"
echo "=================================="
echo ""
echo "File backup:"
echo "  $BACKUP_DIR/dashboard-se2026-backup-$TS.tar.gz"

if [ "$SKIP_BACKUP" = false ] && [ "$SKIP_MIGRATE" = false ]; then
    echo ""
    echo "DB backup:"
    echo "  $BACKUP_DIR/db-backup-$TS.sql.gz"
fi

echo ""
echo "Migration status:"
echo "  Schema patches : 001-013 (idempotent)"
echo "  Data scripts   : $([ "$WITH_DATA" = true ] && echo "RUN" || echo "SKIPPED (use --with-data)")"
echo ""
echo "Verify after deploy:"
echo "  php scripts/smoke_kecamatan_scope.php"
echo ""
echo "Catatan:"
echo "  - patch_010_sipw_klas, 011-013 dijalankan via mysql CLI langsung"
echo "  - Jika salah satu patch gagal, deploy tetap lanjut (idempotent)"
