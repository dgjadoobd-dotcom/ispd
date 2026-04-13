#!/bin/bash
set -e

# Database credentials from environment variables with sensible defaults
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-radius}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

BACKUP_DIR="backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/radius_${TIMESTAMP}.sql.gz"
KEEP_DAYS="${BACKUP_KEEP_DAYS:-30}"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo "Starting backup of database '${DB_DATABASE}' on ${DB_HOST}:${DB_PORT}..."

# Build mysqldump password argument
MYSQL_PWD_ARG=""
if [ -n "$DB_PASSWORD" ]; then
    MYSQL_PWD_ARG="-p${DB_PASSWORD}"
fi

# Perform the backup
if mysqldump \
    -h "$DB_HOST" \
    -P "$DB_PORT" \
    -u "$DB_USERNAME" \
    $MYSQL_PWD_ARG \
    --single-transaction \
    --routines \
    --triggers \
    "$DB_DATABASE" | gzip > "$BACKUP_FILE"; then
    echo "Backup successful: ${BACKUP_FILE}"
else
    echo "ERROR: Backup failed for database '${DB_DATABASE}'" >&2
    rm -f "$BACKUP_FILE"
    exit 1
fi

# Delete backups older than KEEP_DAYS days
echo "Removing backups older than ${KEEP_DAYS} days..."
find "$BACKUP_DIR" -name "radius_*.sql.gz" -mtime "+${KEEP_DAYS}" -delete

echo "Done."
