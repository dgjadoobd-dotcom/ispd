# Implementation Plan: Ubuntu 24.04 Deployment Readiness

## Overview

This plan converts the design into discrete, incremental coding tasks that bring the FCNCHBD ISP ERP system to full Ubuntu 24.04 LTS compatibility. Tasks are ordered so each step builds on the previous one, with shared shell helper functions established first and then consumed by higher-level scripts. All shell work targets Bash; PHP tests use PHPUnit with data providers; shell script tests use Bats (Bash Automated Testing System).

## Tasks

- [x] 1. Update `composer.json` and pin Python agent dependencies
  - Update `"php": "^8.1"` constraint in `composer.json` to `"^8.1|^8.3"` (already `^8.1` which covers 8.3, but verify and document)
  - Add `"ext-sqlite3": "*"` to the `require` block to make the SQLite extension explicit
  - Update `agent/requirements.txt` to use exact version pins: `requests==2.31.0`, `openpyxl==3.1.2`, `schedule==1.2.0`
  - _Requirements: 1.1, 1.2, 11.6_

  - [ ]* 1.1 Write property test for Python dependency version pinning
    - Parse every non-comment, non-blank line in `agent/requirements.txt` and assert the version specifier uses `==` (not `>=`, `~=`, `^`, or unpinned)
    - **Property 13: Python dependency version pinning**
    - **Validates: Requirements 11.6**

- [x] 2. Create shared deploy helper functions library (`scripts/deploy-helpers.sh`)
  - Create `scripts/deploy-helpers.sh` containing all reusable shell functions used by the three deploy scripts
  - Implement `deploy_log()` — writes `[YYYY-MM-DD HH:MM:SS] <msg>` to both stdout and `storage/logs/deploy.log`
  - Implement `check_php_version()` — reads `php --version`, extracts major.minor, exits non-zero with error message if below 8.1
  - Implement `detect_php_fpm_service()` — probes `systemctl is-active` for `php8.3-fpm`, `php8.2-fpm`, `php8.1-fpm` in order; returns first active service name or exits with error
  - Implement `detect_docker_compose()` — returns `"docker compose"` if v2 plugin available, `"docker-compose"` if v1 standalone available, empty string otherwise
  - Implement `validate_env()` — reads `.env` file path argument; checks all required vars are set and free of placeholder patterns; collects ALL failures before exiting; also checks `APP_DEBUG=false` when `APP_ENV=production`
  - Implement `check_required_packages()` — uses `dpkg -l` to check PHP extension packages and `command -v` for system tools; collects ALL missing items before exiting
  - Implement `ensure_www_data_user()` — checks `id www-data`; creates user if absent
  - _Requirements: 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 5.1, 5.2, 5.3, 5.4, 8.1, 12.1, 12.2, 12.3, 12.4, 12.7_

  - [ ]* 2.1 Write property test for PHP version acceptance (Bats)
    - Generate version strings spanning the boundary: `7.4.33`, `8.0.30`, `8.0.99`, `8.1.0`, `8.1.27`, `8.2.0`, `8.3.6`, `8.4.0`
    - Assert `check_php_version()` exits 0 for ≥ 8.1 and exits non-zero for < 8.1
    - **Property 1: PHP version acceptance**
    - **Validates: Requirements 1.3, 1.4**

  - [ ]* 2.2 Write property test for PHP-FPM service fallback (Bats)
    - Mock `systemctl` with all 8 combinations of php8.3/8.2/8.1-fpm present/absent
    - Assert `detect_php_fpm_service()` selects highest available version; exits non-zero when none present
    - **Property 2: PHP-FPM service fallback**
    - **Validates: Requirements 1.5, 12.2**

  - [ ]* 2.3 Write property test for environment variable validation completeness (Bats)
    - Generate random subsets of the 7 required vars as missing or placeholder-valued
    - Assert `validate_env()` reports ALL failing vars in a single run (never stops at first failure)
    - **Property 4: Environment variable validation completeness**
    - **Validates: Requirements 5.1, 5.2**

  - [ ]* 2.4 Write property test for package dependency completeness (Bats)
    - Generate random subsets of required packages as "installed" (mock `dpkg -l`)
    - Assert `check_required_packages()` reports ALL missing packages before exiting
    - **Property 3: Package dependency completeness**
    - **Validates: Requirements 2.1, 2.2, 2.3**

  - [ ]* 2.5 Write property test for Docker Compose command selection (Bats)
    - Mock four system states: v2 only, v1 only, both, neither
    - Assert `detect_docker_compose()` returns correct command string for each state
    - **Property 9: Docker compose command selection**
    - **Validates: Requirements 8.1, 12.3**

  - [ ]* 2.6 Write property test for deploy step timestamped logging (Bats)
    - Source `deploy-helpers.sh`, call `deploy_log()` with several messages, read `storage/logs/deploy.log`
    - Assert every message appears with a `[YYYY-MM-DD HH:MM:SS]` prefix
    - **Property 12: Deploy step timestamped logging**
    - **Validates: Requirements 12.7**

- [x] 3. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Implement migration runner with `_migrations` tracking table
  - Implement `run_migrations()` shell function in `scripts/deploy-helpers.sh`
  - On first call, execute `CREATE TABLE IF NOT EXISTS _migrations (id INT AUTO_INCREMENT PRIMARY KEY, filename VARCHAR(255) NOT NULL UNIQUE, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_filename (filename)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;`
  - Iterate `.sql` files in `database/migrations/` using `ls -1 | sort` (lexicographic order)
  - For each file: query `SELECT COUNT(*) FROM _migrations WHERE filename = ?`; if already recorded, emit `deploy_log` warning and skip; otherwise apply with `mysql` client and `INSERT INTO _migrations (filename) VALUES (?)`
  - Verify MySQL connectivity before any migration work; print `host:port` in error message on failure and exit non-zero
  - _Requirements: 4.1, 4.2, 4.4, 4.5, 4.6, 4.7_

  - [ ]* 4.1 Write property test for migration lexicographic ordering (Bats)
    - Generate random sets of migration filenames (e.g., shuffled), run `run_migrations()` against a test MySQL instance
    - Assert the order of `INSERT INTO _migrations` calls matches lexicographic sort of filenames
    - **Property 5: Migration lexicographic ordering**
    - **Validates: Requirements 4.4**

  - [ ]* 4.2 Write property test for migration idempotency (Bats)
    - Pre-populate `_migrations` table with a subset of filenames, run `run_migrations()` again
    - Assert already-recorded migrations produce a warning log entry and no SQL execution; assert exit code is 0
    - **Property 6: Migration idempotency**
    - **Validates: Requirements 4.5**

- [x] 5. Implement file permissions manager and cron job installer in `scripts/deploy-helpers.sh`
  - Implement `set_file_permissions()` — applies the permission model from the design: `storage/` and `public/uploads/` to `www-data:www-data 775`; PHP files to `644`, directories to `755`; `.env` to `root:www-data 640` in production; SQLite files to `www-data:www-data 664`
  - Implement `install_cron_jobs()` — reads current `www-data` crontab; removes all lines between `# digital-isp-cron` markers (inclusive); appends the five cron entries from the design using absolute `/usr/bin/php8.3` path; writes back with `crontab -u www-data`
  - Verify PHP binary resolves to ≥ 8.1 before writing cron entries
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 7.7_

  - [ ]* 5.1 Write property test for cron entry absolute path (Bats)
    - Run `install_cron_jobs()` in a temp environment, capture resulting crontab
    - Assert every PHP binary path in the installed entries starts with `/`
    - **Property 7: Cron entry absolute path**
    - **Validates: Requirements 7.5**

  - [ ]* 5.2 Write property test for cron entry idempotency (Bats)
    - Run `install_cron_jobs()` twice with three initial crontab states: empty, partially configured, fully configured
    - Assert resulting crontab contains exactly one set of application cron entries (no duplicates)
    - **Property 8: Cron entry idempotency**
    - **Validates: Requirements 7.7**

- [x] 6. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Update `deploy-prod.sh` for Ubuntu 24.04 compatibility
  - Source `scripts/deploy-helpers.sh` at the top of the script
  - Replace inline `log()`/`warn()`/`error()` with calls to `deploy_log()` so all output is timestamped and written to `storage/logs/deploy.log`
  - Add OS detection at startup: read `/etc/os-release`, log detected OS name and version
  - Replace hardcoded `docker-compose` calls with `$(detect_docker_compose)` variable
  - Replace hardcoded `php8.1-fpm` reload with `$(detect_php_fpm_service)` result
  - Add `check_php_version()` call before composer install step
  - Add `check_required_packages()` call before any deployment work
  - Replace inline env validation loop with `validate_env .env` call
  - Replace inline migration loop with `run_migrations` call (which uses `_migrations` tracking)
  - Replace inline permission commands with `set_file_permissions` call
  - Add `install_cron_jobs` call after permissions step
  - Add `ensure_www_data_user` call before permissions step
  - Add Nginx symlink check: warn if `digital-isp` is not present in `/etc/nginx/sites-enabled/`
  - On health check failure: display last 50 lines of `storage/logs/app.log` before prompting operator
  - Verify `storage/backups/` exists and backup completes before `git pull` (backup-before-code-update ordering)
  - _Requirements: 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 3.7, 4.1, 4.2, 4.4, 4.5, 5.1, 5.2, 5.3, 5.4, 6.1–6.6, 7.1–7.7, 8.1, 12.1, 12.2, 12.3, 12.4, 12.5, 12.6, 12.7_

  - [ ]* 7.1 Write property test for backup-precedes-code-update ordering (Bats)
    - Instrument `deploy-prod.sh` with timestamp markers around backup and `git pull` steps
    - Assert backup step timestamp is strictly earlier than git pull timestamp across multiple runs
    - **Property 10: Backup precedes code update**
    - **Validates: Requirements 12.5**

- [x] 8. Update `deploy-staging.sh` and `deploy-dev.sh` for Ubuntu 24.04 compatibility
  - Apply the same helper-function refactoring to `deploy-staging.sh`: source `scripts/deploy-helpers.sh`, use `deploy_log()`, `detect_docker_compose()`, `detect_php_fpm_service()`, `validate_env()`, `run_migrations()`, `set_file_permissions()`
  - Apply the same refactoring to `deploy-dev.sh`: source `scripts/deploy-helpers.sh`, use `deploy_log()`, `detect_docker_compose()`
  - Add OS detection logging to both scripts
  - _Requirements: 1.3, 1.5, 5.1, 5.2, 8.1, 12.1, 12.2, 12.3, 12.7_

- [x] 9. Create `docker/nginx/nginx-ubuntu24.conf` for bare-metal Ubuntu 24.04
  - Create `docker/nginx/nginx-ubuntu24.conf` as a bare-metal Nginx site configuration (not a full `nginx.conf`)
  - Set `root /var/www/digital-isp/public`
  - Set `fastcgi_pass unix:/run/php/php8.3-fpm.sock` in the PHP location block
  - Include `try_files $uri $uri/ /index.php?$query_string` directive
  - Add `location` blocks denying access to `.env`, `.git`, `composer.json`, `composer.lock`, `storage/`
  - Include all five security headers: `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Content-Security-Policy`
  - Add HTTP → HTTPS redirect server block (port 80) and HTTPS server block (port 443) with TLS settings from the design (`TLSv1.2 TLSv1.3`, secure cipher suite)
  - Comment out the HTTPS block by default; add inline comment instructing operator to uncomment after running `certbot --nginx`
  - Add SSL certificate existence check in deploy script: warn and skip HTTPS config if cert files are absent
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 9.1, 9.2, 9.3, 9.4, 9.5_

- [x] 10. Update `.htaccess` with HTTPS redirect rule for Apache deployments
  - Add an uncommented HTTPS redirect rule to `.htaccess` using `RewriteCond %{HTTPS} off` and `RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]`
  - Place the rule before any existing rewrite rules so it takes effect first
  - _Requirements: 9.6_

- [x] 11. Update `docker/php/php.ini` for production and add development override
  - Change `display_errors = On` to `display_errors = Off` in `docker/php/php.ini` (production settings)
  - Change `display_startup_errors = On` to `display_startup_errors = Off`
  - Set `error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT`
  - Ensure `log_errors = On` is present
  - Create `docker/php/php-dev.ini` with `display_errors = On`, `display_startup_errors = On`, `error_reporting = E_ALL` for development use
  - _Requirements: 10.5, 10.6_

  - [ ]* 11.1 Write PHPUnit test for PHP application log routing
    - Bootstrap the application with `APP_DEBUG=false`, emit log messages at multiple levels via `LoggingService`
    - Assert messages appear in `storage/logs/app.log` and are NOT present in captured stdout/output buffer
    - **Property 11: Application log routing**
    - **Validates: Requirements 10.1, 10.5**

- [x] 12. Create `setup-ubuntu24.sh` server provisioning script
  - Create `setup-ubuntu24.sh` in the project root
  - Add `apt-get update && apt-get install -y` block with all required packages: `php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-curl php8.3-gd php8.3-zip php8.3-xml php8.3-redis php8.3-sqlite3 nginx mysql-client git curl composer python3 python3-pip python3-venv`
  - Add Python venv creation: `python3 -m venv agent/venv && agent/venv/bin/pip install -r agent/requirements.txt`
  - Create the systemd service unit file at `/etc/systemd/system/digital-isp-agent.service` with the exact content from the design (User=www-data, WorkingDirectory, ExecStart using venv python, Restart=on-failure, RestartSec=10, EnvironmentFile)
  - Run `systemctl daemon-reload && systemctl enable digital-isp-agent`
  - Install logrotate config at `/etc/logrotate.d/digital-isp` with the exact directives from the design (daily, rotate 30, compress, delaycompress, missingok, notifempty, `create 664 www-data www-data`, postrotate USR1 signal)
  - Install Nginx site config: copy `docker/nginx/nginx-ubuntu24.conf` to `/etc/nginx/sites-available/digital-isp` and symlink to `/etc/nginx/sites-enabled/digital-isp`
  - _Requirements: 2.4, 2.5, 2.6, 10.2, 10.3, 10.4, 11.1, 11.2, 11.3, 11.4, 11.5_

- [x] 13. Update `docker-compose.prod.yml` and create `Dockerfile.prod` for Ubuntu 24.04
  - Create `Dockerfile.prod` using `FROM php:8.3-fpm` as the base image
  - Install all required PHP extensions in `Dockerfile.prod`: `pdo_mysql`, `mbstring`, `gd`, `zip`, `redis`, `opcache`, `sqlite3`
  - Update `docker-compose.prod.yml` to reference `Dockerfile.prod`
  - Ensure MySQL service in `docker-compose.prod.yml` does NOT bind port 3306 to `0.0.0.0` (use internal network only)
  - Add `healthcheck` directives to `app`, `db`, and `redis` services in `docker-compose.prod.yml`
  - Add `depends_on` with `condition: service_healthy` on `db` for the `app` service so migrations wait for DB health
  - Update deploy scripts to wait for `db` health check before calling `run_migrations()`
  - _Requirements: 8.2, 8.3, 8.4, 8.5, 8.6, 8.7_

- [x] 14. Update `.env.example` with all required variables and inline documentation
  - Verify all required production variables are present: `APP_KEY`, `APP_URL`, `APP_ENV`, `APP_DEBUG`, `APP_TIMEZONE`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `JWT_SECRET`
  - Add `APP_TIMEZONE=Asia/Dhaka` with a comment explaining it is used by `date_default_timezone_set()` on startup
  - Add inline comments for every variable describing its purpose and valid values (e.g., `# Must be false in production`)
  - Ensure placeholder values use the `REPLACE_WITH_` prefix pattern so the env validator catches unconfigured deployments
  - _Requirements: 5.5, 5.6_

- [x] 15. Checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 16. Write PHPUnit tests for PHP application configuration
  - Create `tests/Unit/AppConfigTest.php`
  - `testTimezoneIsAppliedFromEnv()` — set `APP_TIMEZONE` env var, bootstrap app config, assert `date_default_timezone_get()` returns the configured value
  - `testDebugModeOffInProduction()` — set `APP_ENV=production` and `APP_DEBUG=false`, bootstrap app config, assert `ini_get('display_errors')` is `'0'` or `''`
  - Create `tests/Unit/DatabaseMigrationTest.php`
  - `testMigrationTrackingTableCreation()` — use in-memory SQLite, run the `CREATE TABLE IF NOT EXISTS _migrations` statement twice, assert no error on second run and table exists with correct columns
  - _Requirements: 4.4, 5.5, 10.5_

- [x] 17. Final checkpoint — Ensure all tests pass
  - Run `composer test` (PHPUnit) and `bats tests/shell/` to confirm all tests pass
  - Verify `docker/nginx/nginx-ubuntu24.conf` passes `nginx -t` syntax check (if nginx is available)
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP delivery
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at logical boundaries
- Property tests (Bats for shell, PHPUnit data providers for PHP) validate universal correctness properties
- Unit tests validate specific examples and edge cases
- The `scripts/deploy-helpers.sh` library is the foundation — all deploy scripts source it, so get that right first
- Bats tests should run in isolated temp directories (`BATS_TMPDIR`) to avoid filesystem side effects
- PHPUnit migration tests use in-memory SQLite to avoid requiring a live MySQL instance in CI
- Minimum 100 iterations recommended per property-based test
