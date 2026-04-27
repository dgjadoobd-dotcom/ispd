#!/bin/bash
# ============================================================
# FCNCHBD ISP ERP — Staging Deploy Script
# Usage: bash deploy-staging.sh
# Requires: .env.staging configured with real staging credentials
# ============================================================

set -e

# Source shared deploy helper library
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/scripts/deploy-helpers.sh"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

header() { echo -e "\n${BLUE}══════════════════════════════════════${NC}"; echo -e "${BLUE}  $1${NC}"; echo -e "${BLUE}══════════════════════════════════════${NC}"; }

header "FCNCHBD ISP ERP — Staging Deploy"

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

# ── 1. Validate environment ───────────────────────────────────
[ ! -f .env.staging ] && { deploy_log "ERROR: .env.staging not found. Create it from .env.example and configure staging values."; exit 1; }

# Copy staging env as active .env
cp .env.staging .env
deploy_log "Loaded .env.staging"

# Validate all required staging vars using helper
validate_env .env
deploy_log "All required environment variables validated"

# ── 2. Pull latest code ───────────────────────────────────────
deploy_log "Pulling latest code..."
git fetch origin
git checkout main
git pull origin main
deploy_log "Code updated to latest main"

# ── 3. Composer dependencies ─────────────────────────────────
deploy_log "Checking PHP version..."
check_php_version
deploy_log "Installing PHP dependencies (no-dev)..."
composer install --no-dev --optimize-autoloader --no-interaction
deploy_log "Dependencies installed"

# ── 4. Storage & permissions ──────────────────────────────────
deploy_log "Setting up storage directories and permissions..."
ensure_www_data_user
set_file_permissions staging
deploy_log "Storage directories and permissions set"

# ── 5. Docker services ────────────────────────────────────────
if [ -f docker-compose.staging.yml ]; then
    if [ -z "$COMPOSE_CMD" ]; then
        deploy_log "ERROR: docker-compose.staging.yml found but no Docker Compose command available."
        exit 1
    fi
    deploy_log "Building Docker images..."
    $COMPOSE_CMD -f docker-compose.staging.yml build --no-cache

    deploy_log "Stopping existing containers..."
    $COMPOSE_CMD -f docker-compose.staging.yml down --remove-orphans

    deploy_log "Starting staging services..."
    $COMPOSE_CMD -f docker-compose.staging.yml up -d

    deploy_log "Waiting for services to be ready..."
    sleep 15
else
    deploy_log "WARNING: docker-compose.staging.yml not found — skipping Docker step"
    # Reload PHP-FPM on bare-metal
    FPM_SERVICE=$(detect_php_fpm_service 2>/dev/null) && systemctl reload "$FPM_SERVICE" && deploy_log "Reloaded $FPM_SERVICE" || \
        deploy_log "WARNING: Could not reload PHP-FPM — restart it manually"
fi

# ── 6. Database migrations ────────────────────────────────────
deploy_log "Running database migrations..."
DB_CONN=$(grep '^DB_CONNECTION' .env | cut -d= -f2 | tr -d '"' | tr -d "'")

if [ "$DB_CONN" = "sqlite" ]; then
    DB_PATH=$(grep '^DB_DATABASE' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
    [ ! -f "$DB_PATH" ] && touch "$DB_PATH" && chmod 664 "$DB_PATH"
    deploy_log "SQLite database ready at $DB_PATH"
elif command -v mysql &>/dev/null; then
    run_migrations .env
else
    deploy_log "WARNING: mysql client not found — run migrations manually"
fi

# ── 7. Health check ───────────────────────────────────────────
STAGING_URL=$(grep '^APP_URL=' .env | cut -d= -f2 | tr -d '"' | tr -d "'")
STAGING_URL="${STAGING_URL:-http://localhost:8082}"

deploy_log "Running health check at $STAGING_URL/health ..."
sleep 5
if curl -sf "$STAGING_URL/health" -o /dev/null --max-time 10; then
    deploy_log "Health check passed ✓"
else
    deploy_log "WARNING: Health check failed — check logs"
    if [ -f storage/logs/app.log ]; then
        echo ""
        echo "  Last 50 lines of storage/logs/app.log:"
        echo "  ─────────────────────────────────────────"
        tail -50 storage/logs/app.log
        echo ""
    fi
    if [ -f docker-compose.staging.yml ]; then
        deploy_log "  Logs: $COMPOSE_CMD -f docker-compose.staging.yml logs app"
    fi
fi

# ── 8. Done ───────────────────────────────────────────────────
header "Staging Deploy Complete"
echo ""
echo "  App URL:     $STAGING_URL"
echo "  phpMyAdmin:  ${STAGING_URL%:*}:8084"
echo ""
if [ -f docker-compose.staging.yml ]; then
    echo "  Logs:  $COMPOSE_CMD -f docker-compose.staging.yml logs -f app"
    echo "  Shell: $COMPOSE_CMD -f docker-compose.staging.yml exec app bash"
fi
echo ""
deploy_log "Staging deployment complete"
