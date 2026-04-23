#!/bin/bash
# ============================================================
# Digital ISP ERP — Development Deploy Script
# Usage: bash deploy-dev.sh
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

header "Digital ISP ERP — Development Deploy"

# ── 1. Environment ────────────────────────────────────────────
if [ ! -f .env ]; then
    if [ -f .env.development ]; then
        cp .env.development .env
        log "Created .env from .env.development"
    elif [ -f .env.example ]; then
        cp .env.example .env
        warn "Created .env from .env.example — update DB credentials before continuing"
    else
        error ".env file not found. Copy .env.example to .env and configure it."
    fi
else
    log ".env already exists"
fi

# ── 2. Pull latest code ───────────────────────────────────────
if [ -d .git ]; then
    log "Pulling latest code from git..."
    git pull origin main 2>/dev/null || warn "Git pull skipped (not a git repo or no remote)"
fi

# ── 3. Composer dependencies ──────────────────────────────────
if command -v composer &>/dev/null; then
    log "Installing PHP dependencies..."
    composer install --no-interaction 2>/dev/null
else
    warn "Composer not found — skipping dependency install"
fi

# ── 4. Storage directories ────────────────────────────────────
log "Creating storage directories..."
mkdir -p storage/logs storage/cache public/uploads/kyc public/uploads/photos
chmod -R 775 storage public/uploads 2>/dev/null || true

# ── 5. Docker (if available) ──────────────────────────────────
if command -v docker-compose &>/dev/null && [ -f docker-compose.dev.yml ]; then
    log "Starting Docker services..."
    docker-compose -f docker-compose.dev.yml up -d
    log "Docker services started"
elif command -v php &>/dev/null; then
    warn "Docker not found — use PHP built-in server:"
    echo "    php -S localhost:8000 -t public"
fi

# ── 6. Database setup ─────────────────────────────────────────
if command -v php &>/dev/null; then
    DB_CONN=$(grep '^DB_CONNECTION' .env 2>/dev/null | cut -d= -f2 | tr -d '"' | tr -d "'")
    if [ "$DB_CONN" = "sqlite" ]; then
        DB_PATH=$(grep '^DB_DATABASE' .env 2>/dev/null | cut -d= -f2 | tr -d '"' | tr -d "'")
        DB_PATH="${DB_PATH:-database/digital_isp.sqlite}"
        if [ ! -f "$DB_PATH" ]; then
            log "Creating SQLite database at $DB_PATH..."
            touch "$DB_PATH"
            chmod 664 "$DB_PATH"
        else
            log "SQLite database already exists"
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
