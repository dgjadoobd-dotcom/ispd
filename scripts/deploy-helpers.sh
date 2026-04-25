#!/usr/bin/env bash
# =============================================================================
# Digital ISP ERP — Shared Deploy Helper Functions
# =============================================================================
# This library is sourced by deploy-prod.sh, deploy-staging.sh, and
# deploy-dev.sh. It provides all reusable shell functions for deployment.
#
# Usage:
#   source "$(dirname "$0")/scripts/deploy-helpers.sh"
#   # or from within scripts/:
#   source "$(dirname "$0")/deploy-helpers.sh"
#
# Requirements addressed: 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 5.1, 5.2, 5.3, 5.4,
#                         8.1, 12.1, 12.2, 12.3, 12.4, 12.7
# =============================================================================

set -euo pipefail

# Guard against double-sourcing
[[ -n "${_DEPLOY_HELPERS_LOADED:-}" ]] && return 0
_DEPLOY_HELPERS_LOADED=1

# =============================================================================
# deploy_log() — Timestamped logging to stdout and storage/logs/deploy.log
# =============================================================================
# Writes "[YYYY-MM-DD HH:MM:SS] <msg>" to both stdout and the deploy log file.
# Creates the log directory if it does not exist.
#
# Usage: deploy_log "message"
# Requirements: 12.7
# =============================================================================
deploy_log() {
    local msg="${1:-}"
    local ts
    ts=$(date '+%Y-%m-%d %H:%M:%S')
    local log_dir="storage/logs"
    local log_file="${log_dir}/deploy.log"

    # Ensure the log directory exists
    mkdir -p "${log_dir}"

    echo "[${ts}] ${msg}" | tee -a "${log_file}"
}

# =============================================================================
# check_php_version() — Verify PHP >= 8.1 is installed
# =============================================================================
# Reads `php --version`, extracts the major.minor version, and exits non-zero
# with a clear error message if the version is below 8.1.
#
# Handles version strings like: 8.1.27, 8.3.6, 7.4.33, 8.0.30
#
# Usage: check_php_version
# Requirements: 1.3, 1.4
# =============================================================================
check_php_version() {
    if ! command -v php &>/dev/null; then
        deploy_log "ERROR: php binary not found on PATH. Install PHP 8.1 or higher."
        return 1
    fi

    local version_output
    version_output=$(php --version 2>&1 | head -n1)

    # Extract major.minor (e.g. "8.3" from "PHP 8.3.6 (cli) ...")
    local version_str
    version_str=$(echo "${version_output}" | sed -n 's/^PHP \([0-9][0-9]*\.[0-9][0-9]*\)\.[0-9].*/\1/p')

    if [[ -z "${version_str}" ]]; then
        deploy_log "ERROR: Could not parse PHP version from: ${version_output}"
        return 1
    fi

    local major minor
    major=$(echo "${version_str}" | cut -d. -f1)
    minor=$(echo "${version_str}" | cut -d. -f2)

    # Require PHP >= 8.1
    if [[ "${major}" -lt 8 ]] || { [[ "${major}" -eq 8 ]] && [[ "${minor}" -lt 1 ]]; }; then
        deploy_log "ERROR: PHP ${version_str} detected. Minimum required version is PHP 8.1."
        deploy_log "       Please install php8.1, php8.2, or php8.3 before deploying."
        return 1
    fi

    deploy_log "PHP version ${version_str} detected — OK (>= 8.1 required)"
}

# =============================================================================
# detect_php_fpm_service() — Find the active PHP-FPM systemd service
# =============================================================================
# Probes systemctl is-active for php8.3-fpm, php8.2-fpm, php8.1-fpm in that
# order. Echoes the first active service name. Exits with error if none found.
#
# Usage: FPM_SERVICE=$(detect_php_fpm_service)
# Requirements: 1.5, 12.2
# =============================================================================
detect_php_fpm_service() {
    local candidates=("php8.3-fpm" "php8.2-fpm" "php8.1-fpm")

    for svc in "${candidates[@]}"; do
        if systemctl is-active --quiet "${svc}" 2>/dev/null; then
            echo "${svc}"
            return 0
        fi
    done

    deploy_log "ERROR: No active PHP-FPM service found."
    deploy_log "       Checked: ${candidates[*]}"
    deploy_log "       Install and start one of: php8.3-fpm, php8.2-fpm, or php8.1-fpm"
    return 1
}

# =============================================================================
# detect_docker_compose() — Determine the correct docker compose command
# =============================================================================
# Returns "docker compose" if the Docker Compose v2 plugin is available,
# "docker-compose" if the v1 standalone binary is available, or an empty
# string if neither is found.
#
# Usage: COMPOSE_CMD=$(detect_docker_compose)
# Requirements: 8.1, 12.3
# =============================================================================
detect_docker_compose() {
    if docker compose version &>/dev/null 2>&1; then
        echo "docker compose"
    elif command -v docker-compose &>/dev/null; then
        echo "docker-compose"
    else
        echo ""
    fi
}

# =============================================================================
# validate_env() — Validate required environment variables in a .env file
# =============================================================================
# Takes a .env file path as argument. Checks that all 7 required variables are:
#   - Present and non-empty
#   - Free of placeholder patterns: REPLACE, your_, change_, example, placeholder
#
# Also checks APP_DEBUG=false when APP_ENV=production.
#
# Collects ALL failures before exiting — never stops at the first failure.
# Exits non-zero if any failures are found.
#
# Usage: validate_env /path/to/.env
# Requirements: 5.1, 5.2, 5.3, 5.4
# =============================================================================
validate_env() {
    local env_file="${1:-}"

    if [[ -z "${env_file}" ]]; then
        deploy_log "ERROR: validate_env() requires a .env file path as argument."
        return 1
    fi

    if [[ ! -f "${env_file}" ]]; then
        deploy_log "ERROR: Environment file not found: ${env_file}"
        return 1
    fi

    local required_vars=(
        "APP_KEY"
        "APP_URL"
        "DB_HOST"
        "DB_DATABASE"
        "DB_USERNAME"
        "DB_PASSWORD"
        "JWT_SECRET"
    )

    # Placeholder patterns to reject (case-insensitive)
    local placeholder_pattern="REPLACE|your_|change_|example|placeholder"

    local failures=()

    for var in "${required_vars[@]}"; do
        # Extract value: match lines like VAR=value or VAR="value" or VAR='value'
        local val
        val=$(grep -E "^${var}=" "${env_file}" | head -n1 | cut -d= -f2- | sed "s/^['\"]//;s/['\"]$//")

        if [[ -z "${val}" ]]; then
            failures+=("  - ${var}: missing or empty")
            continue
        fi

        if echo "${val}" | grep -qiE "${placeholder_pattern}"; then
            failures+=("  - ${var}: contains placeholder value ('${val}')")
        fi
    done

    # Check APP_DEBUG=false when APP_ENV=production
    local app_env
    app_env=$(grep -E "^APP_ENV=" "${env_file}" | head -n1 | cut -d= -f2- | sed "s/^['\"]//;s/['\"]$//")

    if [[ "${app_env}" == "production" ]]; then
        local app_debug
        app_debug=$(grep -E "^APP_DEBUG=" "${env_file}" | head -n1 | cut -d= -f2- | sed "s/^['\"]//;s/['\"]$//")

        if [[ "${app_debug}" != "false" ]]; then
            failures+=("  - APP_DEBUG: must be 'false' in production (currently '${app_debug:-unset}')")
        fi
    fi

    if [[ ${#failures[@]} -gt 0 ]]; then
        deploy_log "ERROR: Environment validation failed for: ${env_file}"
        deploy_log "       The following variables are invalid or missing:"
        for failure in "${failures[@]}"; do
            deploy_log "${failure}"
        done
        return 1
    fi

    deploy_log "Environment validation passed for: ${env_file}"
}

# =============================================================================
# check_required_packages() — Verify all required system packages are installed
# =============================================================================
# Uses dpkg -l to check PHP extension packages and command -v for system tools.
# Collects ALL missing items before exiting — never stops at the first failure.
# Exits non-zero if any packages or tools are missing.
#
# PHP packages checked (via dpkg -l):
#   php8.3-fpm, php8.3-mysql, php8.3-mbstring, php8.3-curl, php8.3-gd,
#   php8.3-zip, php8.3-xml, php8.3-redis, php8.3-sqlite3
#
# System tools checked (via command -v):
#   nginx, mysql, git, curl, composer
#
# Usage: check_required_packages
# Requirements: 2.1, 2.2, 2.3
# =============================================================================
check_required_packages() {
    local php_packages=(
        "php8.3-fpm"
        "php8.3-mysql"
        "php8.3-mbstring"
        "php8.3-curl"
        "php8.3-gd"
        "php8.3-zip"
        "php8.3-xml"
        "php8.3-redis"
        "php8.3-sqlite3"
    )

    local system_tools=(
        "nginx"
        "mysql"
        "git"
        "curl"
        "composer"
    )

    local missing=()

    # Check PHP extension packages via dpkg
    for pkg in "${php_packages[@]}"; do
        if ! dpkg -l "${pkg}" &>/dev/null 2>&1 || \
           ! dpkg -l "${pkg}" 2>/dev/null | grep -qE "^ii\s+${pkg}"; then
            missing+=("  - package: ${pkg}")
        fi
    done

    # Check system tools via command -v
    for tool in "${system_tools[@]}"; do
        if ! command -v "${tool}" &>/dev/null; then
            missing+=("  - tool: ${tool}")
        fi
    done

    if [[ ${#missing[@]} -gt 0 ]]; then
        deploy_log "ERROR: The following required packages/tools are missing:"
        for item in "${missing[@]}"; do
            deploy_log "${item}"
        done
        deploy_log "       Run setup-ubuntu24.sh to install all required dependencies."
        return 1
    fi

    deploy_log "All required packages and tools are present — OK"
}

# =============================================================================
# ensure_www_data_user() — Ensure the www-data system user exists
# =============================================================================
# Checks whether the www-data user exists via `id www-data`. If absent,
# creates the user as a system account with no login shell.
#
# Usage: ensure_www_data_user
# Requirements: 12.4
# =============================================================================
ensure_www_data_user() {
    if id www-data &>/dev/null 2>&1; then
        deploy_log "User www-data exists — OK"
        return 0
    fi

    deploy_log "User www-data not found — creating system user..."
    useradd -r -s /usr/sbin/nologin www-data
    deploy_log "User www-data created successfully"
}

# =============================================================================
# _mysql_cmd() — Build the mysql client command with connection credentials
# =============================================================================
# Reads DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD from the
# current shell environment (caller must have sourced the .env file first).
# Echoes the mysql command prefix so callers can append SQL or file arguments.
#
# Usage:
#   $(_mysql_cmd) -e "SELECT 1"
#   $(_mysql_cmd) < migration.sql
#
# Note: DB_PORT defaults to 3306 if unset.
# =============================================================================
_mysql_cmd() {
    local host="${DB_HOST:-127.0.0.1}"
    local port="${DB_PORT:-3306}"
    local database="${DB_DATABASE:-}"
    local user="${DB_USERNAME:-}"
    local password="${DB_PASSWORD:-}"

    echo "mysql \
        --host=${host} \
        --port=${port} \
        --user=${user} \
        --password=${password} \
        --database=${database} \
        --batch \
        --skip-column-names"
}

# =============================================================================
# run_migrations() — Apply pending SQL migrations with idempotent tracking
# =============================================================================
# Reads DB connection parameters from a .env file (default: .env in CWD).
# Creates the _migrations tracking table if it does not exist, then iterates
# all .sql files in database/migrations/ in lexicographic order.
#
# For each file:
#   - If already recorded in _migrations: emit deploy_log warning and skip
#   - If not recorded: apply with mysql client, then INSERT into _migrations
#
# Verifies MySQL connectivity before any migration work. Prints host:port in
# the error message on failure and exits non-zero.
#
# Usage:
#   run_migrations [env_file]
#   run_migrations          # uses .env
#   run_migrations .env.staging
#
# Requirements: 4.1, 4.2, 4.4, 4.5, 4.6, 4.7
# =============================================================================
run_migrations() {
    local env_file="${1:-.env}"

    # -------------------------------------------------------------------------
    # 1. Load DB connection parameters from the .env file
    # -------------------------------------------------------------------------
    if [[ ! -f "${env_file}" ]]; then
        deploy_log "ERROR: run_migrations() — env file not found: ${env_file}"
        return 1
    fi

    # Source only the DB_* variables from the env file (avoid executing arbitrary code)
    local db_host db_port db_database db_username db_password
    db_host=$(grep -E '^DB_HOST=' "${env_file}" | head -n1 | cut -d= -f2- | sed "s/^['\"]//;s/['\"]$//")
    db_port=$(grep -E '^DB_PORT=' "${env_file}" | head -n1 | cut -d= -f2- | sed "s/^['\"]//;s/['\"]$//")
    db_database=$(grep -E '^DB_DATABASE=' "${env_file}" | head -n1 | cut -d= -f2- | sed "s/^['\"]//;s/['\"]$//")
    db_username=$(grep -E '^DB_USERNAME=' "${env_file}" | head -n1 | cut -d= -f2- | sed "s/^['\"]//;s/['\"]$//")
    db_password=$(grep -E '^DB_PASSWORD=' "${env_file}" | head -n1 | cut -d= -f2- | sed "s/^['\"]//;s/['\"]$//")

    # Apply defaults
    db_host="${db_host:-127.0.0.1}"
    db_port="${db_port:-3306}"

    # Export so _mysql_cmd() can read them
    export DB_HOST="${db_host}"
    export DB_PORT="${db_port}"
    export DB_DATABASE="${db_database}"
    export DB_USERNAME="${db_username}"
    export DB_PASSWORD="${db_password}"

    # -------------------------------------------------------------------------
    # 2. Verify MySQL connectivity before any migration work
    # -------------------------------------------------------------------------
    deploy_log "Verifying MySQL connectivity to ${db_host}:${db_port}..."

    if ! mysql \
            --host="${db_host}" \
            --port="${db_port}" \
            --user="${db_username}" \
            --password="${db_password}" \
            --database="${db_database}" \
            --connect-timeout=10 \
            --batch \
            --skip-column-names \
            -e "SELECT 1" &>/dev/null 2>&1; then
        deploy_log "ERROR: Cannot connect to MySQL at ${db_host}:${db_port}."
        deploy_log "       Check DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD in ${env_file}."
        return 1
    fi

    deploy_log "MySQL connectivity verified — OK (${db_host}:${db_port})"

    # -------------------------------------------------------------------------
    # 3. Create the _migrations tracking table (idempotent)
    # -------------------------------------------------------------------------
    deploy_log "Ensuring _migrations tracking table exists..."

    local create_table_sql
    create_table_sql="CREATE TABLE IF NOT EXISTS \`_migrations\` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_filename (filename)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"

    if ! mysql \
            --host="${db_host}" \
            --port="${db_port}" \
            --user="${db_username}" \
            --password="${db_password}" \
            --database="${db_database}" \
            --batch \
            --skip-column-names \
            -e "${create_table_sql}" 2>&1; then
        deploy_log "ERROR: Failed to create _migrations tracking table."
        return 1
    fi

    deploy_log "_migrations table ready"

    # -------------------------------------------------------------------------
    # 4. Iterate .sql files in lexicographic order
    # -------------------------------------------------------------------------
    local migrations_dir="database/migrations"

    if [[ ! -d "${migrations_dir}" ]]; then
        deploy_log "WARNING: Migrations directory not found: ${migrations_dir} — skipping migrations"
        return 0
    fi

    # Collect .sql files in lexicographic order
    local migration_files=()
    while IFS= read -r filename; do
        [[ -n "${filename}" ]] && migration_files+=("${filename}")
    done < <(ls -1 "${migrations_dir}"/*.sql 2>/dev/null | sort | xargs -I{} basename {})

    if [[ ${#migration_files[@]} -eq 0 ]]; then
        deploy_log "No .sql migration files found in ${migrations_dir} — nothing to apply"
        return 0
    fi

    deploy_log "Found ${#migration_files[@]} migration file(s) in ${migrations_dir}"

    local applied_count=0
    local skipped_count=0

    for filename in "${migration_files[@]}"; do
        local filepath="${migrations_dir}/${filename}"

        # ------------------------------------------------------------------
        # 4a. Check if this migration has already been applied
        # ------------------------------------------------------------------
        local count
        count=$(mysql \
            --host="${db_host}" \
            --port="${db_port}" \
            --user="${db_username}" \
            --password="${db_password}" \
            --database="${db_database}" \
            --batch \
            --skip-column-names \
            -e "SELECT COUNT(*) FROM \`_migrations\` WHERE filename = '${filename}';" 2>/dev/null)

        if [[ "${count}" -gt 0 ]]; then
            deploy_log "WARNING: Migration already applied, skipping: ${filename}"
            (( skipped_count++ )) || true
            continue
        fi

        # ------------------------------------------------------------------
        # 4b. Apply the migration
        # ------------------------------------------------------------------
        deploy_log "Applying migration: ${filename}"

        if ! mysql \
                --host="${db_host}" \
                --port="${db_port}" \
                --user="${db_username}" \
                --password="${db_password}" \
                --database="${db_database}" \
                --batch \
                < "${filepath}" 2>&1; then
            deploy_log "ERROR: Failed to apply migration: ${filename}"
            return 1
        fi

        # ------------------------------------------------------------------
        # 4c. Record the migration in the tracking table
        # ------------------------------------------------------------------
        if ! mysql \
                --host="${db_host}" \
                --port="${db_port}" \
                --user="${db_username}" \
                --password="${db_password}" \
                --database="${db_database}" \
                --batch \
                --skip-column-names \
                -e "INSERT INTO \`_migrations\` (filename) VALUES ('${filename}');" 2>&1; then
            deploy_log "ERROR: Failed to record migration in _migrations table: ${filename}"
            return 1
        fi

        deploy_log "Migration applied successfully: ${filename}"
        (( applied_count++ )) || true
    done

    deploy_log "Migrations complete — applied: ${applied_count}, skipped (already applied): ${skipped_count}"
}

# =============================================================================
# set_file_permissions() — Apply the deployment file permission model
# =============================================================================
# Sets ownership and permissions for storage/, public/uploads/, PHP application
# files, .env (production), and SQLite database files.
#
# Creates storage/logs/, storage/cache/, and storage/backups/ if absent.
#
# Usage:
#   set_file_permissions [APP_ENV]
#   set_file_permissions production
#   set_file_permissions          # reads APP_ENV from .env
#
# Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6
# =============================================================================
set_file_permissions() {
    local app_env="${1:-}"

    # If APP_ENV not passed as argument, read it from .env
    if [[ -z "${app_env}" ]] && [[ -f ".env" ]]; then
        app_env=$(grep -E '^APP_ENV=' .env | head -n1 | cut -d= -f2- | sed "s/^['\"]//;s/['\"]$//")
    fi
    app_env="${app_env:-local}"

    deploy_log "Setting file permissions (APP_ENV=${app_env})..."

    # -------------------------------------------------------------------------
    # 1. Create required storage subdirectories if they don't exist
    # -------------------------------------------------------------------------
    deploy_log "Ensuring storage subdirectories exist..."
    mkdir -p storage/logs storage/cache storage/backups

    # -------------------------------------------------------------------------
    # 2. Set ownership of storage/ and public/uploads/ to www-data:www-data
    # -------------------------------------------------------------------------
    deploy_log "Setting ownership of storage/ to www-data:www-data..."
    chown -R www-data:www-data storage/

    if [[ -d "public/uploads" ]]; then
        deploy_log "Setting ownership of public/uploads/ to www-data:www-data..."
        chown -R www-data:www-data public/uploads/
    else
        deploy_log "Creating public/uploads/ and setting ownership to www-data:www-data..."
        mkdir -p public/uploads
        chown -R www-data:www-data public/uploads/
    fi

    # -------------------------------------------------------------------------
    # 3. Set permissions on storage/ — directories 775, files 664
    # -------------------------------------------------------------------------
    deploy_log "Setting permissions on storage/ (dirs: 775, files: 664)..."
    find storage/ -type d -exec chmod 775 {} \;
    find storage/ -type f -exec chmod 664 {} \;

    # -------------------------------------------------------------------------
    # 4. Set permissions on public/uploads/ to 775
    # -------------------------------------------------------------------------
    deploy_log "Setting permissions on public/uploads/ to 775..."
    find public/uploads/ -type d -exec chmod 775 {} \;
    find public/uploads/ -type f -exec chmod 664 {} \;

    # -------------------------------------------------------------------------
    # 5. Set PHP application files to root:www-data 644, directories to 755
    # -------------------------------------------------------------------------
    deploy_log "Setting PHP application file permissions (files: 644, dirs: 755)..."
    find . -name "*.php" \
        -not -path "./vendor/*" \
        -not -path "./storage/*" \
        -exec chmod 644 {} \;

    # Set directories (excluding vendor, storage, .git) to 755
    find . -type d \
        -not -path "./vendor/*" \
        -not -path "./storage/*" \
        -not -path "./.git/*" \
        -exec chmod 755 {} \;

    # Set ownership of PHP files to root:www-data
    find . -name "*.php" \
        -not -path "./vendor/*" \
        -not -path "./storage/*" \
        -exec chown root:www-data {} \;

    # -------------------------------------------------------------------------
    # 6. In production: set .env to root:www-data 640
    # -------------------------------------------------------------------------
    if [[ "${app_env}" == "production" ]]; then
        if [[ -f ".env" ]]; then
            deploy_log "Production: setting .env permissions to root:www-data 640..."
            chown root:www-data .env
            chmod 640 .env
        else
            deploy_log "WARNING: .env file not found — skipping .env permission hardening"
        fi
    fi

    # -------------------------------------------------------------------------
    # 7. Set SQLite database files to www-data:www-data 664
    # -------------------------------------------------------------------------
    deploy_log "Setting SQLite database file permissions (www-data:www-data 664)..."
    if [[ -d "database" ]]; then
        find database/ \( -name "*.sqlite" -o -name "*.db" \) \
            -exec chown www-data:www-data {} \; \
            -exec chmod 664 {} \;
    fi

    deploy_log "File permissions set successfully"
}

# =============================================================================
# install_cron_jobs() — Install www-data cron entries idempotently
# =============================================================================
# Reads the current www-data crontab, removes any existing application cron
# block (between # digital-isp-cron markers), then appends fresh entries.
# Uses the detected PHP binary path rather than a hardcoded value.
#
# Verifies PHP >= 8.1 is available before writing cron entries.
#
# Usage:
#   install_cron_jobs [APP_ROOT]
#   install_cron_jobs /var/www/digital-isp
#   install_cron_jobs          # defaults to /var/www/digital-isp
#
# Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7
# =============================================================================
install_cron_jobs() {
    local app_root="${1:-/var/www/digital-isp}"

    deploy_log "Installing cron jobs for www-data (app_root=${app_root})..."

    # -------------------------------------------------------------------------
    # 1. Verify PHP >= 8.1 before writing cron entries
    # -------------------------------------------------------------------------
    deploy_log "Verifying PHP version before installing cron jobs..."
    if ! check_php_version; then
        deploy_log "ERROR: PHP version check failed — aborting cron job installation"
        return 1
    fi

    # -------------------------------------------------------------------------
    # 2. Detect the PHP binary path (prefer versioned binaries)
    # -------------------------------------------------------------------------
    local php_bin=""
    local php_candidates=("/usr/bin/php8.3" "/usr/bin/php8.2" "/usr/bin/php8.1" "/usr/bin/php")

    for candidate in "${php_candidates[@]}"; do
        if [[ -x "${candidate}" ]]; then
            php_bin="${candidate}"
            break
        fi
    done

    if [[ -z "${php_bin}" ]]; then
        deploy_log "ERROR: No PHP binary found in: ${php_candidates[*]}"
        return 1
    fi

    deploy_log "Using PHP binary: ${php_bin}"

    # -------------------------------------------------------------------------
    # 3. Read current www-data crontab
    # -------------------------------------------------------------------------
    deploy_log "Reading current www-data crontab..."
    local current_crontab
    current_crontab=$(crontab -u www-data -l 2>/dev/null || true)

    # -------------------------------------------------------------------------
    # 4. Remove existing application cron block (idempotent replacement)
    #    Strip all lines from "# digital-isp-cron" to "# end-digital-isp-cron"
    #    inclusive, to handle re-runs cleanly.
    # -------------------------------------------------------------------------
    deploy_log "Removing existing digital-isp cron block (if present)..."
    local cleaned_crontab
    cleaned_crontab=$(echo "${current_crontab}" | \
        awk '/^# digital-isp-cron$/{skip=1} skip{if(/^# end-digital-isp-cron$/){skip=0} next} {print}')

    # -------------------------------------------------------------------------
    # 5. Build the new cron block with the detected PHP binary
    # -------------------------------------------------------------------------
    local log_file="${app_root}/storage/logs/automation_cron.log"

    local new_cron_block
    new_cron_block=$(cat <<EOF
# digital-isp-cron
0 0 * * * ${php_bin} ${app_root}/cron_automation.php >> ${log_file} 2>&1
0 8 * * * ${php_bin} ${app_root}/cron_automation.php due-reminders >> ${log_file} 2>&1
0 */6 * * * ${php_bin} ${app_root}/cron_automation.php suspend >> ${log_file} 2>&1
5 0 * * * ${php_bin} ${app_root}/cron_radius_rollup.php >> ${log_file} 2>&1
10 0 * * * ${php_bin} ${app_root}/cron_selfhosted_piprapay.php >> ${log_file} 2>&1
# end-digital-isp-cron
EOF
)

    # -------------------------------------------------------------------------
    # 6. Write the updated crontab back
    # -------------------------------------------------------------------------
    deploy_log "Writing updated crontab for www-data..."

    # Combine cleaned crontab with new block, stripping leading/trailing blank lines
    local final_crontab
    if [[ -n "${cleaned_crontab}" ]]; then
        final_crontab="${cleaned_crontab}
${new_cron_block}"
    else
        final_crontab="${new_cron_block}"
    fi

    echo "${final_crontab}" | crontab -u www-data -

    deploy_log "Cron jobs installed successfully for www-data"
    deploy_log "  - cron_automation.php        @ 0 0 * * * (midnight)"
    deploy_log "  - cron_automation.php due-reminders @ 0 8 * * * (08:00)"
    deploy_log "  - cron_automation.php suspend @ 0 */6 * * * (every 6h)"
    deploy_log "  - cron_radius_rollup.php     @ 5 0 * * * (00:05)"
    deploy_log "  - cron_selfhosted_piprapay.php @ 10 0 * * * (00:10)"
}
