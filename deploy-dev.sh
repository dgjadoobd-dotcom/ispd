#!/bin/bash
# ============================================================
# Digital ISP ERP — Development Deploy Script
# Usage: bash deploy-dev.sh
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

header "Digital ISP ERP — Development Deploy"

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

# ── 1. Environment ────────────────────────────────────────────
if [ ! -f .env ]; then
    if [ -f .env.development ]; then
        cp .env.development .env
        deploy_log "Created .env from .env.development"
    elif [ -f .env.example ]; then
        cp .env.example .env
        deploy_log "WARNING: Created .env from .env.example — update DB credentials before continuing"
    else
        deploy_log "ERROR: .env file not found. Copy .env.example to .env and configure it."
        exit 1
    fi
else
    deploy_log ".env already exists"
fi

# ── 2. Pull latest code ───────────────────────────────────────
if [ -d .git ]; then
    deploy_log "Pulling latest code from git..."
    git pull origin main 2>/dev/null || deploy_log "WARNING: Git pull skipped (not a git repo or no remote)"
fi

# ── 3. Composer dependencies ──────────────────────────────────
if command -v composer &>/dev/null; then
    deploy_log "Installing PHP dependencies..."
    composer install --no-interaction 2>/dev/null
else
    deploy_log "WARNING: Composer not found — skipping dependency install"
fi

# ── 4. Storage directories ────────────────────────────────────
deploy_log "Creating storage directories..."
mkdir -p storage/logs storage/cache public/uploads/kyc public/uploads/photos
chmod -R 775 storage public/uploads 2>/dev/null || true

# ── 5. Docker (if available) ──────────────────────────────────
if [ -n "$COMPOSE_CMD" ] && [ -f docker-compose.dev.yml ]; then
    deploy_log "Starting Docker services..."
    $COMPOSE_CMD -f docker-compose.dev.yml up -d
    deploy_log "Docker services started"
elif command -v php &>/dev/null; then
    deploy_log "WARNING: Docker not found — use PHP built-in server:"
    echo "    php -S localhost:8000 -t public"
fi

# ── 6. Database setup ─────────────────────────────────────────
if command -v php &>/dev/null; then
    DB_CONN=$(grep '^DB_CONNECTION' .env 2>/dev/null | cut -d= -f2 | tr -d '"' | tr -d "'")
    if [ "$DB_CONN" = "sqlite" ]; then
        DB_PATH=$(grep '^DB_DATABASE' .env 2>/dev/null | cut -d= -f2 | tr -d '"' | tr -d "'")
        DB_PATH="${DB_PATH:-database/digital_isp.sqlite}"
        if [ ! -f "$DB_PATH" ]; then
            deploy_log "Creating SQLite database at $DB_PATH..."
            touch "$DB_PATH"
            chmod 664 "$DB_PATH"
        else
            deploy_log "SQLite database already exists"
        fi
    fi
fi

# ── 7. Done ───────────────────────────────────────────────────
header "Development Setup Complete"
echo ""
echo "  Local server:  php -S localhost:8000 -t public"
echo "  Docker app:    http://localhost:8080"
echo "  phpMyAdmin:    http://localhost:8081"
echo ""
echo "  Default login: admin / Admin@1234"
echo ""
deploy_log "Development setup complete"
