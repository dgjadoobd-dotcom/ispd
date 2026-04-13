#!/bin/bash
set -e

# Rollback script for RADIUS service
# Usage: ./scripts/rollback.sh [--image-tag=TAG] [--db-backup=FILE]

COMPOSE_FILE="docker-compose.prod.yml"
IMAGE_TAG=""
DB_BACKUP=""

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# Parse arguments
for arg in "$@"; do
    case "$arg" in
        --image-tag=*)
            IMAGE_TAG="${arg#*=}"
            ;;
        --db-backup=*)
            DB_BACKUP="${arg#*=}"
            ;;
        *)
            echo "Unknown argument: $arg" >&2
            echo "Usage: $0 [--image-tag=TAG] [--db-backup=FILE]" >&2
            exit 1
            ;;
    esac
done

if [ -z "$IMAGE_TAG" ] && [ -z "$DB_BACKUP" ]; then
    echo "Usage: $0 [--image-tag=TAG] [--db-backup=FILE]" >&2
    echo "At least one option is required." >&2
    exit 1
fi

log "Starting RADIUS service rollback..."

# Step 1: Docker image rollback
if [ -n "$IMAGE_TAG" ]; then
    log "Rolling back Docker image to tag: $IMAGE_TAG"

    if [ ! -f "$COMPOSE_FILE" ]; then
        log "ERROR: $COMPOSE_FILE not found." >&2
        exit 1
    fi

    # Update image tags for app services in docker-compose.prod.yml
    sed -i.bak \
        -E "s|(image: .+/radius-app):[^ ]+|\1:${IMAGE_TAG}|g" \
        "$COMPOSE_FILE"

    log "Restarting services with image tag $IMAGE_TAG..."
    docker compose -f "$COMPOSE_FILE" up -d --no-build app1 app2 app3
    log "Services restarted."
fi

# Step 2: Database restore
if [ -n "$DB_BACKUP" ]; then
    log "Restoring database from backup: $DB_BACKUP"

    if [ ! -f "$DB_BACKUP" ]; then
        log "ERROR: Backup file not found: $DB_BACKUP" >&2
        exit 1
    fi

    bash scripts/restore_radius.sh "$DB_BACKUP"
    log "Database restore complete."
fi

# Step 3: Health check
log "Running post-rollback health check..."
HEALTH_URL="${APP_URL:-http://localhost}/health"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$HEALTH_URL" || true)

if [ "$HTTP_STATUS" = "200" ]; then
    log "Health check passed (HTTP $HTTP_STATUS)."
    log "Rollback completed successfully."
else
    log "ERROR: Health check failed (HTTP $HTTP_STATUS). Manual intervention may be required." >&2
    exit 1
fi
