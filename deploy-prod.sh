#!/bin/bash
# ============================================================
# Digital ISP ERP — Production Deploy Script
# Usage: bash deploy-prod.sh
# Requires: .env.production configured with real credentials
# WARNING: This deploys to LIVE production. Double-check before running.
# ============================================================

set -e

# Source shared deploy helper library
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/scripts/deploy-helpers.sh"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

header() { echo -e "\n${BLUE}══════════════════════════════════════${NC}"; echo -e "${BLUE}  $1${NC}"; echo -e "${BLUE}══════════════════════════════════════${NC}"; }

header "Digital ISP ERP — PRODUCTION Deploy"

# ── OS Detection ──────────────────────────────────────────────
if [ -f /etc/os-release ]; then
    OS_NAME=$(grep '^NAME=' /etc/os-release | cut -d= -f2- | tr -d '"')
    OS_VERSION=$(grep '^VERSION_ID=' /etc/os-release | cut -d= -f2- | tr -d '"')
    deploy_log "Detected OS: ${OS_NAME} ${OS_VERSION}"
else
    deploy_log "WARNING: /etc/os-release not found — OS detection skipped"
fi

# ── Detect Docker Compose command ─────────────────────────────
COMPOSE_CMD=$(detect_docker_compose)
if [ -z "$COMPOSE_CMD" ]; then
    deploy_log "WARNING: Neither 'docker compose' nor 'docker-compose' found — Docker steps will be skipped"
fi

# ── Safety confirmation ───────────────────────────────────────
echo -e "${RED}${BOLD}"
echo "  ⚠  WARNING: This will deploy to PRODUCTION."
echo "  ⚠  Ensure you have:"
echo "     1. Tested on staging first"
echo "     2. Taken a database backup"
echo "     3. Reviewed all changes"
echo -e "${NC}"
read -p "Type 'deploy-production' to confirm: " CONFIRM
[ "$CONFIRM" != "deploy-production" ] && { deploy_log "Deployment cancelled by operator."; exit 1; }

# ── 1. Validate environment ───────────────────────────────────
[ ! -f .env.production ] && { deploy_log "ERROR: .env.production not found. Create it from .env.example and configure production values."; exit 1; }

cp .env.production .env
deploy_log "Loaded .env.production"

# Validate all required production vars using helper
validate_env .env
deploy_log "All required environment variables validated"

# ── 2. Check required packages ────────────────────────────────
deploy_log "Checking required system packages..."
check_required_packages

# ── 3. Database backup ────────────────────────────────────────
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
        deploy_log "SQLite backup saved: ${BACKUP_FILE%.sql}.sqlite"
    fi
elif command -v mysqldump &>/dev/null; then
    DB_HOST=$(grep '^DB_HOST=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    DB_USER=$(grep '^DB_USERNAME=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    DB_PASS=$(grep '^DB_PASSWORD=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    DB_NAME=$(grep '^DB_DATABASE=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"
    gzip "$BACKUP_FILE"
    deploy_log "MySQL backup saved: ${BACKUP_FILE}.gz"
else
    deploy_log "WARNING: mysqldump not found — skipping automatic backup. Ensure you have a manual backup."
    read -p "Continue without backup? (yes/no): " SKIP_BACKUP
    [ "$SKIP_BACKUP" != "yes" ] && { deploy_log "Deployment cancelled — take a backup first."; exit 1; }
fi

# ── 4. Pull latest code ───────────────────────────────────────
header "Step 2/7: Code Update"
deploy_log "Fetching latest code..."
git fetch origin
git checkout main
git pull origin main
COMMIT=$(git rev-parse --short HEAD)
deploy_log "Deployed commit: $COMMIT"

# ── 5. Composer dependencies ─────────────────────────────────
header "Step 3/7: Dependencies"
deploy_log "Checking PHP version..."
check_php_version
deploy_log "Installing PHP dependencies (production, no-dev)..."
composer install --no-dev --optimize-autoloader --no-interaction --quiet
deploy_log "Dependencies installed"

# ── 6. Storage & permissions ──────────────────────────────────
header "Step 4/7: Permissions"
ensure_www_data_user
set_file_permissions production
deploy_log "Storage directories and permissions set"

# ── 7. Cron jobs ──────────────────────────────────────────────
deploy_log "Installing cron jobs..."
install_cron_jobs

# ── 8. Nginx symlink check ────────────────────────────────────
if [ ! -e "/etc/nginx/sites-enabled/digital-isp" ]; then
    deploy_log "WARNING: Nginx site 'digital-isp' is not present in /etc/nginx/sites-enabled/ — the site may not be served correctly. Run setup-ubuntu24.sh or symlink manually."
fi

# ── 9. Docker services ────────────────────────────────────────
header "Step 5/7: Services"
if [ -f docker-compose.prod.yml ]; then
    if [ -z "$COMPOSE_CMD" ]; then
        deploy_log "ERROR: docker-compose.prod.yml found but no Docker Compose command available."
        exit 1
    fi
    deploy_log "Building production Docker images..."
    $COMPOSE_CMD -f docker-compose.prod.yml build --no-cache

    deploy_log "Performing zero-downtime restart..."
    # Scale up new containers before stopping old ones
    $COMPOSE_CMD -f docker-compose.prod.yml up -d --scale app=2 2>/dev/null || true
    sleep 10
    $COMPOSE_CMD -f docker-compose.prod.yml up -d
    deploy_log "Services restarted"
else
    deploy_log "WARNING: docker-compose.prod.yml not found — restarting PHP-FPM manually..."
    FPM_SERVICE=$(detect_php_fpm_service) && systemctl reload "$FPM_SERVICE" && deploy_log "Reloaded $FPM_SERVICE" || \
        deploy_log "WARNING: Could not reload PHP-FPM — restart it manually"
fi

# ── 10. Database migrations ────────────────────────────────────
header "Step 6/7: Database Migrations"
if [ "$DB_CONN" = "sqlite" ]; then
    deploy_log "SQLite — schema auto-applied on first request"
elif command -v mysql &>/dev/null; then
    run_migrations .env
else
    deploy_log "WARNING: mysql client not found — run migrations manually"
fi

# ── 11. Health check ───────────────────────────────────────────
header "Step 7/7: Health Check"
PROD_URL=$(grep '^APP_URL=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
PROD_URL="${PROD_URL:-https://digitalisp.xyz}"

deploy_log "Checking $PROD_URL/health ..."
sleep 8
HTTP_CODE=$(curl -sf -o /dev/null -w "%{http_code}" "$PROD_URL/health" --max-time 15 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    deploy_log "Health check passed (HTTP 200) ✓"
else
    deploy_log "WARNING: Health check returned HTTP $HTTP_CODE — investigate immediately!"
    echo ""
    echo "  Last 50 lines of storage/logs/app.log:"
    echo "  ─────────────────────────────────────────"
    if [ -f storage/logs/app.log ]; then
        tail -50 storage/logs/app.log
    else
        echo "  (storage/logs/app.log not found)"
    fi
    echo ""
    echo "  Check Docker logs:"
    if [ -f docker-compose.prod.yml ]; then
        echo "    $COMPOSE_CMD -f docker-compose.prod.yml logs --tail=50 app"
    else
        echo "    tail -50 storage/logs/app.log"
    fi
    echo ""
    read -p "Rollback to previous commit? (yes/no): " DO_ROLLBACK
    if [ "$DO_ROLLBACK" = "yes" ]; then
        deploy_log "WARNING: Rolling back to previous commit..."
        git revert HEAD --no-edit
        git push origin main
        deploy_log "ERROR: Rolled back. Investigate the issue before re-deploying."
        exit 1
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
    echo "  Logs:    $COMPOSE_CMD -f docker-compose.prod.yml logs -f app"
fi
echo ""
deploy_log "Production deployment complete — commit: $COMMIT"
