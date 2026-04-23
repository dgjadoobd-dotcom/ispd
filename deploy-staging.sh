#!/bin/bash
# ============================================================
# Digital ISP ERP — Staging Deploy Script
# Usage: bash deploy-staging.sh
# Requires: .env.staging configured with real staging credentials
# ============================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log()    { echo -e "${GREEN}[✓]${NC} $1"; }
warn()   { echo -e "${YELLOW}[!]${NC} $1"; }
error()  { echo -e "${RED}[✗]${NC} $1"; exit 1; }
header() { echo -e "\n${BLUE}══════════════════════════════════════${NC}"; echo -e "${BLUE}  $1${NC}"; echo -e "${BLUE}══════════════════════════════════════${NC}"; }

header "Digital ISP ERP — Staging Deploy"

# ── 1. Validate environment ───────────────────────────────────
[ ! -f .env.staging ] && error ".env.staging not found. Create it from .env.example and configure staging values."

# Copy staging env as active .env
cp .env.staging .env
log "Loaded .env.staging"

# Validate required vars
for VAR in APP_KEY DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD JWT_SECRET; do
    VAL=$(grep "^${VAR}=" .env | cut -d= -f2- | tr -d '"' | tr -d "'")
    if [ -z "$VAL" ] || echo "$VAL" | grep -qi "REPLACE\|your_\|change_\|example"; then
        error "$VAR is not set or still has a placeholder value in .env.staging"
    fi
done
log "Environment variables validated"

# ── 2. Pull latest code ───────────────────────────────────────
log "Pulling latest code..."
git fetch origin
git checkout main
git pull origin main
log "Code updated to latest main"

# ── 3. Composer dependencies ─────────────────────────────────
log "Installing PHP dependencies (no-dev)..."
composer install --no-dev --optimize-autoloader --no-interaction
log "Dependencies installed"

# ── 4. Storage & permissions ──────────────────────────────────
log "Setting up storage directories..."
mkdir -p storage/logs storage/cache public/uploads/kyc public/uploads/photos
chmod -R 775 storage public/uploads
chown -R www-data:www-data storage public/uploads 2>/dev/null || true
log "Permissions set"

# ── 5. Docker services ────────────────────────────────────────
if [ -f docker-compose.staging.yml ]; then
    log "Building Docker images..."
    docker-compose -f docker-compose.staging.yml build --no-cache

    log "Stopping existing containers..."
    docker-compose -f docker-compose.staging.yml down --remove-orphans

    log "Starting staging services..."
    docker-compose -f docker-compose.staging.yml up -d

    log "Waiting for services to be ready..."
    sleep 15
else
    warn "docker-compose.staging.yml not found — skipping Docker step"
fi

# ── 6. Database migrations ────────────────────────────────────
log "Running database migrations..."
DB_CONN=$(grep '^DB_CONNECTION' .env | cut -d= -f2 | tr -d '"' | tr -d "'")

if [ "$DB_CONN" = "sqlite" ]; then
    DB_PATH=$(grep '^DB_DATABASE' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    [ ! -f "$DB_PATH" ] && touch "$DB_PATH" && chmod 664 "$DB_PATH"
    log "SQLite database ready at $DB_PATH"
else
    # Apply schema if tables don't exist yet
    if command -v mysql &>/dev/null; then
        DB_HOST=$(grep '^DB_HOST=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
        DB_USER=$(grep '^DB_USERNAME=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
        DB_PASS=$(grep '^DB_PASSWORD=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
        DB_NAME=$(grep '^DB_DATABASE=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
        TABLE_COUNT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
            -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" \
            --skip-column-names 2>/dev/null || echo "0")
        if [ "$TABLE_COUNT" -lt "5" ]; then
            log "Applying database schema..."
            mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/schema.sql
            log "Schema applied"
        else
            log "Database already has tables — skipping full schema import"
        fi
        # Apply incremental migrations
        for migration in database/migrations/*.sql; do
            [ -f "$migration" ] || continue
            log "Applying migration: $(basename $migration)"
            mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$migration" 2>/dev/null || \
                warn "Migration $(basename $migration) may have already been applied"
        done
    else
        warn "mysql client not found — run migrations manually"
    fi
fi

# ── 7. Health check ───────────────────────────────────────────
STAGING_URL=$(grep '^APP_URL=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
STAGING_URL="${STAGING_URL:-http://localhost:8082}"

log "Running health check at $STAGING_URL/health ..."
sleep 5
if curl -sf "$STAGING_URL/health" -o /dev/null --max-time 10; then
    log "Health check passed ✓"
else
    warn "Health check failed — check logs: docker-compose -f docker-compose.staging.yml logs app"
fi

# ── 8. Done ───────────────────────────────────────────────────
header "Staging Deploy Complete"
echo ""
echo "  App URL:     $STAGING_URL"
echo "  phpMyAdmin:  ${STAGING_URL%:*}:8084"
echo ""
echo "  Logs:  docker-compose -f docker-compose.staging.yml logs -f app"
echo "  Shell: docker-compose -f docker-compose.staging.yml exec app bash"
echo ""
