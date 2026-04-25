#!/usr/bin/env bash
# =============================================================================
# Digital ISP ERP — Ubuntu 24.04 Server Provisioning Script
# =============================================================================
# One-time setup script for a fresh Ubuntu 24.04 LTS (Noble Numbat) server.
# Run this ONCE before deploying with deploy-prod.sh.
#
# Usage:
#   sudo bash setup-ubuntu24.sh
#
# What this script does:
#   1. Installs all required system packages (PHP 8.3, Nginx, MySQL client, etc.)
#   2. Creates the Python virtual environment and installs agent dependencies
#   3. Creates and enables the digital-isp-agent systemd service
#   4. Installs the logrotate configuration
#   5. Installs and enables the Nginx site configuration
#
# Requirements: 2.4, 2.5, 2.6, 10.2, 10.3, 10.4, 11.1, 11.2, 11.3, 11.4, 11.5
# =============================================================================

set -euo pipefail

# Must run as root
[[ $EUID -ne 0 ]] && echo "Run as root" && exit 1

# =============================================================================
# Colour helpers
# =============================================================================
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

step()  { echo -e "\n${BLUE}──────────────────────────────────────────${NC}"; echo -e "${BLUE}  $1${NC}"; echo -e "${BLUE}──────────────────────────────────────────${NC}"; }
ok()    { echo -e "${GREEN}  ✓ $1${NC}"; }
warn()  { echo -e "${YELLOW}  ⚠ $1${NC}"; }
error() { echo -e "${RED}  ✗ $1${NC}"; exit 1; }

# =============================================================================
# Resolve the directory this script lives in (the application root)
# =============================================================================
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="${SCRIPT_DIR}"

echo ""
echo -e "${BLUE}╔══════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  Digital ISP ERP — Ubuntu 24.04 Provisioner  ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════╝${NC}"
echo ""
echo "  Application root : ${APP_ROOT}"
echo "  Running as       : $(id)"
echo ""

# =============================================================================
# Step 1: System package installation
# =============================================================================
step "Step 1/5: Installing system packages"

echo "  Updating package lists..."
apt-get update -qq

echo "  Installing PHP 8.3, Nginx, MySQL client, Git, Composer, Python 3..."
apt-get install -y \
    php8.3-fpm \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-curl \
    php8.3-gd \
    php8.3-zip \
    php8.3-xml \
    php8.3-redis \
    php8.3-sqlite3 \
    nginx \
    mysql-client \
    git \
    curl \
    composer \
    python3 \
    python3-pip \
    python3-venv

ok "All system packages installed"

# Verify key binaries are available after install
for bin in php8.3 php nginx git curl composer python3; do
    if command -v "${bin}" &>/dev/null; then
        ok "${bin} is available: $(command -v "${bin}")"
    else
        warn "${bin} not found on PATH after install — check package installation"
    fi
done

# =============================================================================
# Step 2: Python virtual environment and agent dependencies
# =============================================================================
step "Step 2/5: Setting up Python virtual environment for agent"

AGENT_DIR="${APP_ROOT}/agent"
VENV_DIR="${AGENT_DIR}/venv"
REQUIREMENTS_FILE="${AGENT_DIR}/requirements.txt"

if [[ ! -d "${AGENT_DIR}" ]]; then
    error "Agent directory not found: ${AGENT_DIR}. Ensure the application code is present before running this script."
fi

if [[ ! -f "${REQUIREMENTS_FILE}" ]]; then
    error "requirements.txt not found: ${REQUIREMENTS_FILE}. Ensure the application code is present before running this script."
fi

echo "  Creating Python venv at ${VENV_DIR}..."
python3 -m venv "${VENV_DIR}"
ok "Python venv created: ${VENV_DIR}"

echo "  Installing agent dependencies from ${REQUIREMENTS_FILE}..."
"${VENV_DIR}/bin/pip" install --quiet -r "${REQUIREMENTS_FILE}"
ok "Agent dependencies installed"

# Set ownership so www-data can run the agent
chown -R www-data:www-data "${VENV_DIR}"
ok "Venv ownership set to www-data:www-data"

# =============================================================================
# Step 3: systemd service unit for the Python agent
# =============================================================================
step "Step 3/5: Installing digital-isp-agent systemd service"

SERVICE_FILE="/etc/systemd/system/digital-isp-agent.service"

echo "  Writing ${SERVICE_FILE}..."
cat > "${SERVICE_FILE}" <<'SYSTEMD_UNIT'
[Unit]
Description=Digital ISP Agent
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/digital-isp/agent
ExecStart=/var/www/digital-isp/agent/venv/bin/python daily_agent.py
Restart=on-failure
RestartSec=10
StandardOutput=journal
StandardError=journal
EnvironmentFile=/var/www/digital-isp/.env

[Install]
WantedBy=multi-user.target
SYSTEMD_UNIT

ok "Service unit written: ${SERVICE_FILE}"

echo "  Reloading systemd daemon and enabling service..."
systemctl daemon-reload
systemctl enable digital-isp-agent
ok "digital-isp-agent service enabled (will start on next boot)"
echo "  To start now: systemctl start digital-isp-agent"
echo "  To view logs: journalctl -u digital-isp-agent -f"

# =============================================================================
# Step 4: logrotate configuration
# =============================================================================
step "Step 4/5: Installing logrotate configuration"

LOGROTATE_FILE="/etc/logrotate.d/digital-isp"

echo "  Writing ${LOGROTATE_FILE}..."
cat > "${LOGROTATE_FILE}" <<'LOGROTATE_CONF'
/var/www/digital-isp/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 664 www-data www-data
    postrotate
        /bin/kill -USR1 $(cat /run/php/php8.3-fpm.pid 2>/dev/null) 2>/dev/null || true
    endscript
}
LOGROTATE_CONF

ok "Logrotate config installed: ${LOGROTATE_FILE}"

# =============================================================================
# Step 5: Nginx site configuration
# =============================================================================
step "Step 5/5: Installing Nginx site configuration"

NGINX_CONF_SRC="${APP_ROOT}/docker/nginx/nginx-ubuntu24.conf"
NGINX_AVAILABLE="/etc/nginx/sites-available/digital-isp"
NGINX_ENABLED="/etc/nginx/sites-enabled/digital-isp"

if [[ ! -f "${NGINX_CONF_SRC}" ]]; then
    warn "Nginx config source not found: ${NGINX_CONF_SRC}"
    warn "Skipping Nginx configuration — create docker/nginx/nginx-ubuntu24.conf and re-run, or configure Nginx manually."
else
    echo "  Copying ${NGINX_CONF_SRC} → ${NGINX_AVAILABLE}..."
    cp "${NGINX_CONF_SRC}" "${NGINX_AVAILABLE}"
    ok "Nginx config installed: ${NGINX_AVAILABLE}"

    echo "  Creating symlink in sites-enabled..."
    ln -sf "${NGINX_AVAILABLE}" "${NGINX_ENABLED}"
    ok "Symlink created: ${NGINX_ENABLED} → ${NGINX_AVAILABLE}"

    # Remove the default site if it exists to avoid conflicts
    if [[ -e "/etc/nginx/sites-enabled/default" ]]; then
        rm -f /etc/nginx/sites-enabled/default
        warn "Removed default Nginx site (sites-enabled/default)"
    fi

    echo "  Testing Nginx configuration..."
    if nginx -t; then
        ok "Nginx configuration test passed"
        echo "  Reloading Nginx..."
        systemctl reload nginx
        ok "Nginx reloaded"
    else
        warn "Nginx configuration test FAILED — fix the config before reloading"
        warn "Config file: ${NGINX_AVAILABLE}"
    fi
fi

# =============================================================================
# Summary
# =============================================================================
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║           Ubuntu 24.04 Provisioning Complete             ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""
echo "  What was installed:"
echo "    ✓ PHP 8.3 FPM + extensions (mysql, mbstring, curl, gd, zip, xml, redis, sqlite3)"
echo "    ✓ Nginx, MySQL client, Git, Curl, Composer"
echo "    ✓ Python 3, python3-pip, python3-venv"
echo "    ✓ Python venv at ${VENV_DIR}"
echo "    ✓ systemd service: digital-isp-agent (enabled, not yet started)"
echo "    ✓ Logrotate config: ${LOGROTATE_FILE}"
if [[ -f "${NGINX_CONF_SRC}" ]]; then
    echo "    ✓ Nginx site config: ${NGINX_AVAILABLE}"
fi
echo ""
echo "  Next steps:"
echo "    1. Configure your environment file:"
echo "       cp ${APP_ROOT}/.env.example ${APP_ROOT}/.env.production"
echo "       nano ${APP_ROOT}/.env.production"
echo ""
echo "    2. Run the production deploy script:"
echo "       cd ${APP_ROOT} && sudo bash deploy-prod.sh"
echo ""
echo "    3. Obtain an SSL certificate (recommended):"
echo "       apt-get install -y certbot python3-certbot-nginx"
echo "       certbot --nginx -d yourdomain.com"
echo ""
echo "    4. Start the Python agent after .env is configured:"
echo "       systemctl start digital-isp-agent"
echo "       journalctl -u digital-isp-agent -f"
echo ""
echo "    5. Verify PHP-FPM is running:"
echo "       systemctl status php8.3-fpm"
echo ""
