#!/bin/bash
set -e

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

log "Starting production deployment..."

# ── Pre-deployment checks ──────────────────────────────────────────────────────

log "Running pre-deployment checks..."

if [ ! -f ".env.production" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: .env.production not found. Aborting." >&2
    exit 1
fi
log ".env.production found."

if ! docker info > /dev/null 2>&1; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: Docker is not running. Aborting." >&2
    exit 1
fi
log "Docker is running."

# Load production environment variables
set -a
# shellcheck disable=SC1091
source .env.production
set +a

# ── Backup ─────────────────────────────────────────────────────────────────────

if [ -f "scripts/backup_radius.sh" ]; then
    log "Creating pre-deployment backup..."
    bash scripts/backup_radius.sh
    log "Backup completed."
else
    log "Backup script not found, skipping backup."
fi

# ── Pull latest Docker images ──────────────────────────────────────────────────

log "Pulling latest Docker images..."
docker-compose -f docker-compose.prod.yml pull
log "Docker images updated."

# ── Database migrations ────────────────────────────────────────────────────────

log "Running database migrations..."

MIGRATION_DIR="database/migrations"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_DATABASE="${DB_DATABASE:-radius}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

MYSQL_PWD_ARG=""
if [ -n "$DB_PASSWORD" ]; then
    MYSQL_PWD_ARG="-p${DB_PASSWORD}"
fi

for sql_file in $(ls "$MIGRATION_DIR"/*.sql 2>/dev/null | sort); do
    # Skip rollback scripts
    if [[ "$sql_file" == *rollback* ]]; then
        log "Skipping rollback script: $sql_file"
        continue
    fi
    log "Applying migration: $sql_file"
    mysql -h "$DB_HOST" -u "$DB_USERNAME" $MYSQL_PWD_ARG "$DB_DATABASE" < "$sql_file"
done

log "Database migrations completed."

# ── Start/restart services ─────────────────────────────────────────────────────

log "Starting services..."
docker-compose -f docker-compose.prod.yml up -d --remove-orphans
log "Services started."

# ── Health check ───────────────────────────────────────────────────────────────

HEALTH_URL="${PROD_URL:-http://localhost}/health"
log "Running health check against $HEALTH_URL..."

MAX_RETRIES=3
RETRY_DELAY=10
ATTEMPT=1

until curl -sf "$HEALTH_URL" > /dev/null; do
    if [ "$ATTEMPT" -ge "$MAX_RETRIES" ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: Health check failed after $MAX_RETRIES attempts. Deployment may be unhealthy." >&2
        exit 1
    fi
    log "Health check attempt $ATTEMPT failed. Retrying in ${RETRY_DELAY}s..."
    sleep "$RETRY_DELAY"
    ATTEMPT=$((ATTEMPT + 1))
done

log "Health check passed."

# ── Done ───────────────────────────────────────────────────────────────────────

log "Deployment completed successfully at $(date '+%Y-%m-%d %H:%M:%S')."
