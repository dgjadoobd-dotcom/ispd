#!/bin/bash
set -e

# Database credentials from environment variables with sensible defaults
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-radius}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

BACKUP_FILE="$1"

# Validate argument
if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file>" >&2
    exit 1
fi

# Validate file exists and is readable
if [ ! -f "$BACKUP_FILE" ]; then
    echo "ERROR: Backup file not found: ${BACKUP_FILE}" >&2
    exit 1
fi

if [ ! -r "$BACKUP_FILE" ]; then
    echo "ERROR: Backup file is not readable: ${BACKUP_FILE}" >&2
    exit 1
fi

echo "WARNING: This will overwrite the '${DB_DATABASE}' database on ${DB_HOST}:${DB_PORT}."
echo "Backup file: ${BACKUP_FILE}"
read -r -p "Are you sure you want to restore? [y/N] " CONFIRM

if [ "$CONFIRM" != "y" ] && [ "$CONFIRM" != "Y" ]; then
    echo "Restore cancelled."
    exit 0
fi

echo "Restoring database '${DB_DATABASE}' from ${BACKUP_FILE}..."

# Build mysql password argument
MYSQL_PWD_ARG=""
if [ -n "$DB_PASSWORD" ]; then
    MYSQL_PWD_ARG="-p${DB_PASSWORD}"
fi

# Decompress and restore
if gunzip -c "$BACKUP_FILE" | mysql \
    -h "$DB_HOST" \
    -P "$DB_PORT" \
    -u "$DB_USERNAME" \
    $MYSQL_PWD_ARG \
    "$DB_DATABASE"; then
    echo "Restore successful."
else
    echo "ERROR: Restore failed." >&2
    exit 1
fi
