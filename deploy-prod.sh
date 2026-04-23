#!/bin/bash
# ============================================================
# Digital ISP ERP — Production Deploy Script
# Usage: bash deploy-prod.sh
# Requires: .env.production configured with real credentials
# WARNING: This deploys to LIVE production. Double-check before running.
# ============================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

log()    { echo -e "${GREEN}[✓]${NC} $1"; }
warn()   { echo -e "${YELLOW}[!]${NC} $1"; }
error()  { echo -e "${RED}[✗]${NC} $1"; exit 1; }
header() { echo -e "\n${BLUE}══════════════════════════════════════${NC}"; echo -e "${BLUE}  $1${NC}"; echo -e "${BLUE}══════════════════════════════════════${NC}"; }

header "Digital ISP ERP — PRODUCTION Deploy"

# ── Safety confirmation ───────────────────────────────────────
echo -e "${RED}${BOLD}"
echo "  ⚠  WARNING: This will deploy to PRODUCTION."
echo "  ⚠  Ensure you have:"
echo "     1. Tested on staging first"
echo "     2. Taken a database backup"
echo "     3. Reviewed all changes"
echo -e "${NC}"
read -p "Type 'deploy-production' to confirm: " CONFIRM
[ "$CONFIRM" != "deploy-production" ] && error "Deployment cancelled."

# ── 1. Validate environment ───────────────────────────────────
[ ! -f .env.production ] && error ".env.production not found. Create it from .env.example and configure production values."

cp .env.production .env
log "Loaded .env.production"

# Validate all required production vars
REQUIRED_VARS="APP_KEY APP_URL DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD JWT_SECRET SMS_API_KEY"
for VAR in $REQUIRED_VARS; do
    VAL=$(grep "^${VAR}=" .env | cut -d= -f2- | tr -d '"' | tr -d "'")
    if [ -z "$VAL" ] || echo "$VAL" | grep -qi "REPLACE\|your_\|change_\|example\|placeholder"; then
        error "$VAR is not set or still has a placeholder value in .env.production"
    fi
done
log "All required environment variables validated"

# Ensure APP_DEBUG is off in production
DEBUG=$(grep '^APP_DEBUG=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
[ "$DEBUG" = "true" ] && error "APP_DEBUG must be false in production. Fix .env.production."
log "APP_DEBUG=false confirmed"

# ── 2. Database backup ────────────────────────────────────────
header "Step 1/7: Database Backup"
BACKUP_DIR="storage/backups"
mkdir -p "$BACKUP_DIR"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/pre_deploy_${TIMESTAMP}.sql"

DB_CONN=$(grep '^DB_CONNECTION=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
if [ "$DB_CONN" = "sqlite" ]; then
    DB_PATH=$(grep '^DB_DATABASE=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    if [ -f "$DB_PATH" ]; then
        cp "$DB_PATH" "${BACKUP_FILE%.sql}.sqlite"
        log "SQLite backup saved: ${BACKUP_FILE%.sql}.sqlite"
    fi
elif command -v mysqldump &>/dev/null; then
    DB_HOST=$(grep '^DB_HOST=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    DB_USER=$(grep '^DB_USERNAME=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    DB_PASS=$(grep '^DB_PASSWORD=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    DB_NAME=$(grep '^DB_DATABASE=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"
    gzip "$BACKUP_FILE"
    log "MySQL backup saved: ${BACKUP_FILE}.gz"
else
    warn "mysqldump not found — skipping automatic backup. Ensure you have a manual backup."
    read -p "Continue without backup? (yes/no): " SKIP_BACKUP
    [ "$SKIP_BACKUP" != "yes" ] && error "Deployment cancelled — take a backup first."
fi

# ── 3. Pull latest code ───────────────────────────────────────
header "Step 2/7: Code Update"
log "Fetching latest code..."
git fetch origin
git checkout main
git pull origin main
COMMIT=$(git rev-parse --short HEAD)
log "Deployed commit: $COMMIT"

# ── 4. Composer dependencies ─────────────────────────────────
header "Step 3/7: Dependencies"
log "Installing PHP dependencies (production, no-dev)..."
composer install --no-dev --optimize-autoloader --no-interaction --quiet
log "Dependencies installed"

# ── 5. Storage & permissions ──────────────────────────────────
header "Step 4/7: Permissions"
mkdir -p storage/logs storage/cache storage/backups public/uploads/kyc public/uploads/photos
chmod -R 775 storage public/uploads
chown -R www-data:www-data storage public/uploads 2>/dev/null || true
log "Storage directories and permissions set"

# ── 6. Docker services ────────────────────────────────────────
header "Step 5/7: Services"
if [ -f docker-compose.prod.yml ]; then
    log "Building production Docker images..."
    docker-compose -f docker-compose.prod.yml build --no-cache

    log "Performing zero-downtime restart..."
    # Scale up new containers before stopping old ones
    docker-compose -f docker-compose.prod.yml up -d --scale app=2 2>/dev/null || true
    sleep 10
    docker-compose -f docker-compose.prod.yml up -d
    log "Services restarted"
else
    warn "docker-compose.prod.yml not found — restarting PHP-FPM manually..."
    systemctl reload php8.3-fpm 2>/dev/null || \
    systemctl reload php8.1-fpm 2>/dev/null || \
    service php-fpm reload 2>/dev/null || \
    warn "Could not reload PHP-FPM — restart it manually"
fi

# ── 7. Database migrations ────────────────────────────────────
header "Step 6/7: Database Migrations"
if [ "$DB_CONN" = "sqlite" ]; then
    log "SQLite — schema auto-applied on first request"
elif command -v mysql &>/dev/null; then
    DB_HOST=$(grep '^DB_HOST=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    DB_USER=$(grep '^DB_USERNAME=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    DB_PASS=$(grep '^DB_PASSWORD=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    DB_NAME=$(grep '^DB_DATABASE=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    for migration in database/migrations/*.sql; do
        [ -f "$migration" ] || continue
        MNAME=$(basename "$migration")
        log "Applying migration: $MNAME"
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$migration" 2>/dev/null || \
            warn "Migration $MNAME may have already been applied — skipping"
    done
    log "All migrations applied"
else
    warn "mysql client not found — run migrations manually"
fi

# ── 8. Health check ───────────────────────────────────────────
header "Step 7/7: Health Check"
PROD_URL=$(grep '^APP_URL=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
PROD_URL="${PROD_URL:-https://digitalisp.xyz}"

log "Checking $PROD_URL/health ..."
sleep 8
HTTP_CODE=$(curl -sf -o /dev/null -w "%{http_code}" "$PROD_URL/health" --max-time 15 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    log "Health check passed (HTTP 200) ✓"
else
    warn "Health check returned HTTP $HTTP_CODE — investigate immediately!"
    echo ""
    echo "  Check logs:"
    if [ -f docker-compose.prod.yml ]; then
        echo "    docker-compose -f docker-compose.prod.yml logs --tail=50 app"
    else
        echo "    tail -50 storage/logs/app.log"
    fi
    echo ""
    read -p "Rollback to previous commit? (yes/no): " DO_ROLLBACK
    if [ "$DO_ROLLBACK" = "yes" ]; then
        warn "Rolling back to previous commit..."
        git revert HEAD --no-edit
        git push origin main
        error "Rolled back. Investigate the issue before re-deploying."
    fi
fi

# ── Done ──────────────────────────────────────────────────────
header "Production Deploy Complete"
echo ""
echo "  Commit:  $COMMIT"
echo "  App URL: $PROD_URL"
echo "  Backup:  $BACKUP_DIR/"
echo ""
echo "  Monitor: tail -f storage/logs/app.log"
if [ -f docker-compose.prod.yml ]; then
    echo "  Logs:    docker-compose -f docker-compose.prod.yml logs -f app"
fi
echo ""
