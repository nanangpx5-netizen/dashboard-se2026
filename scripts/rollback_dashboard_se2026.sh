#!/bin/bash
#──────────────────────────────────────────────────────────────
# ROLLBACK DASHBOARD SE2026 DEPLOYMENT
#──────────────────────────────────────────────────────────────
# Usage:
#   ./rollback_dashboard_se2026.sh <backup_timestamp>
#   ./rollback_dashboard_se2026.sh 2026-06-04-143022
#──────────────────────────────────────────────────────────────
# Rollback source:
#   1. Files:   $BACKUP_DIR/dashboard-se2026-backup-<ts>.tar.gz
#   2. Database: $BACKUP_DIR/db-backup-<ts>.sql.gz
#──────────────────────────────────────────────────────────────

set -e

if [ -z "$1" ]; then
    echo "Usage: $0 <backup_timestamp>"
    echo ""
    echo "Available backups:"
    ls -lt /home/bpsjembe/backups/dashboard-se2026-backup-*.tar.gz 2>/dev/null | head -10 | awk '{print "  "$9}'
    echo ""
    echo "Example: $0 2026-06-04-143022"
    exit 1
fi

TS="$1"
REPO_DIR="/home/bpsjembe/repositories/dashboard-se2026"
WEB_DIR="/home/bpsjembe/dashboard-se2026.bpsjember.my.id"
BACKUP_DIR="/home/bpsjembe/backups"

DB_NAME="bps_jember_se2026"
DB_USER="bpsjembe"
DB_PASS=""

# Load credentials from .env.production
if [ -f "$WEB_DIR/.env.production" ]; then
    DB_PASS=$(grep '^DB_PASSWORD=' "$WEB_DIR/.env.production" | cut -d= -f2- | tr -d '"' | tr -d "'")
    DB_NAME=$(grep '^DB_DATABASE=' "$WEB_DIR/.env.production" | cut -d= -f2- | tr -d '"' | tr -d "'")
    DB_USER=$(grep '^DB_USERNAME=' "$WEB_DIR/.env.production" | cut -d= -f2- | tr -d '"' | tr -d "'")
fi

FILE_BACKUP="$BACKUP_DIR/dashboard-se2026-backup-$TS.tar.gz"
DB_BACKUP="$BACKUP_DIR/db-backup-$TS.sql.gz"

echo "=================================="
echo " ROLLBACK DASHBOARD SE2026"
echo "  Timestamp: $TS"
echo "=================================="

# Verify backups exist
if [ ! -f "$FILE_BACKUP" ]; then
    echo "✗ File backup not found: $FILE_BACKUP"
    exit 1
fi

echo ""
echo "  File backup: $FILE_BACKUP ($(du -h "$FILE_BACKUP" | cut -f1))"
if [ -f "$DB_BACKUP" ]; then
    echo "  DB backup:   $DB_BACKUP ($(du -h "$DB_BACKUP" | cut -f1))"
else
    echo "  DB backup:   (not found — will skip DB rollback)"
fi

echo ""
read -p "Are you sure you want to rollback to $TS? [y/N] " confirm
if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
    echo "Aborted."
    exit 0
fi

# 1. Rollback files
echo ""
echo "== Restoring files =="
if [ -d "$WEB_DIR" ]; then
    rm -rf "$WEB_DIR"
fi
mkdir -p "$WEB_DIR"
tar -xzf "$FILE_BACKUP" -C /home/bpsjembe/

# 2. Rollback database
if [ -f "$DB_BACKUP" ]; then
    echo ""
    echo "== Restoring database =="

    # Create safety backup of current state
    SAFETY="$BACKUP_DIR/db-pre-rollback-$(date +%F-%H%M%S).sql.gz"
    if mysqldump --user="$DB_USER" --password="$DB_PASS" \
            --single-transaction --routines --triggers --events \
            "$DB_NAME" 2>/dev/null | gzip > "$SAFETY"; then
        echo "  Pre-rollback safety backup: $SAFETY"
    fi

    # Restore from backup
    if gunzip -c "$DB_BACKUP" | mysql --user="$DB_USER" --password="$DB_PASS" "$DB_NAME" 2>/dev/null; then
        echo "  ✓ DB restored from $DB_BACKUP"
    else
        echo "  ✗ DB restore failed — restore manually from $SAFETY"
    fi
fi

# 3. Refresh autoload
echo ""
echo "== Composer dump-autoload =="
cd "$WEB_DIR"
composer dump-autoload --no-interaction --optimize 2>/dev/null || true

# 4. Clear cache
echo ""
echo "== Clearing cache =="
rm -rf "$WEB_DIR/storage/cache/"* 2>/dev/null || true
rm -rf "$WEB_DIR/storage/framework/cache/"* 2>/dev/null || true

chmod -R 775 "$WEB_DIR/logs" "$WEB_DIR/storage" "$WEB_DIR/uploads" 2>/dev/null || true

echo ""
echo "=================================="
echo " ROLLBACK COMPLETE"
echo "=================================="
echo ""
echo "Verify after rollback:"
echo "  php scripts/smoke_kecamatan_scope.php"
