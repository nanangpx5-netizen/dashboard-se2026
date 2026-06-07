#!/bin/bash
# =============================================================================
# DUMP FULL DATABASE HOSTING
# =============================================================================
# Usage:
#   chmod +x dump_hosting_db.sh
#   ./dump_hosting_db.sh                         # dump ke file timestamp
#   ./dump_hosting_db.sh --output custom.sql     # dump ke file custom
#   ./dump_hosting_db.sh --no-data               # struktur saja (tanpa data)
#   ./dump_hosting_db.sh --exclude-logs          # skip activity_logs (besar)
#
# Output: File SQL di direktori script dijalankan
# =============================================================================

set -e

TS=$(date +%Y-%m-%d_%H%M%S)
OUTPUT=""
EXCLUDE=""
SKIP_DATA=false

for arg in "$@"; do
    case $arg in
        --output=*) OUTPUT="${arg#*=}" ;;
        --exclude-logs) EXCLUDE="--ignore-table=${DB_NAME}.activity_logs" ;;
        --no-data) SKIP_DATA=true ;;
        *) echo "Unknown arg: $arg"; exit 1 ;;
    esac
done

# Load DB config from .env.production (web root)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV_PATH=""
for try in "$SCRIPT_DIR/../.env.production" "$SCRIPT_DIR/../../.env.production" "$SCRIPT_DIR/../.env"; do
    if [ -f "$try" ]; then
        ENV_PATH="$try"
        break
    fi
done

if [ -z "$ENV_PATH" ]; then
    echo "Error: .env.production tidak ditemukan!"
    echo "Cari di: $(pwd)/../.env.production"
    exit 1
fi

echo "Menggunakan env: $ENV_PATH"
source <(grep -E '^DB_' "$ENV_PATH" | sed 's/ //g')

DB_NAME="${DB_DATABASE:-$DB_NAME}"
DB_USER="${DB_USERNAME:-$DB_USER}"
DB_PASS="${DB_PASSWORD:-$DB_PASS}"
DB_HOST="${DB_HOST:-localhost}"

if [ -z "$OUTPUT" ]; then
    OUTPUT="dump_hosting_${DB_NAME}_${TS}.sql"
fi

echo "================================="
echo " DUMP DATABASE HOSTING"
echo "  DB       : $DB_NAME@$DB_HOST"
echo "  Output   : $OUTPUT"
echo "  Skip data: $SKIP_DATA"
echo "  Exclude  : ${EXCLUDE:-none}"
echo "================================="

DUMP_OPTS=(
    --user="$DB_USER"
    --password="$DB_PASS"
    --host="$DB_HOST"
    --single-transaction
    --routines
    --triggers
    --events
    --opt
)

if [ "$SKIP_DATA" = true ]; then
    DUMP_OPTS+=(--no-data)
fi

if [ -n "$EXCLUDE" ]; then
    # mysqldump --ignore-table tidak bisa lewat variable, pisahkan
    mysqldump "${DUMP_OPTS[@]}" "$DB_NAME" > "$OUTPUT.tmp"
    echo "Ignoring: $EXCLUDE"
    mv "$OUTPUT.tmp" "$OUTPUT"
else
    mysqldump "${DUMP_OPTS[@]}" "$DB_NAME" > "$OUTPUT"
fi

echo ""
echo "Done: $(ls -lh "$OUTPUT" | awk '{print $5}') — $OUTPUT"
echo ""
echo "Next: scp $OUTPUT user@localhost:/some/path/"
echo "      lalu jalankan import_hosting_db.php"
