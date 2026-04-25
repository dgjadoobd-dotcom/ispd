# Requirements Document

## Introduction

This document defines the requirements for making the Digital ISP ERP system fully deployable on Ubuntu 24.04 LTS. Ubuntu 24.04 ships with PHP 8.3 as the default PHP version, uses systemd for service management, and introduces updated package names and paths compared to earlier Ubuntu releases. The project is a PHP-based ISP management system with MySQL/SQLite databases, RADIUS integration, a Python agent component, cron jobs, Nginx/Apache web server support, Docker-based deployment, and multiple environment configurations. All components must be verified and updated to work correctly on Ubuntu 24.04 without manual workarounds.

## Glossary

- **Deploy_Script**: The shell scripts `deploy-prod.sh`, `deploy-staging.sh`, and `deploy-dev.sh` used to deploy the application.
- **System**: The Digital ISP ERP application and all its components.
- **PHP_FPM**: The PHP FastCGI Process Manager service (`php8.3-fpm`) that processes PHP requests.
- **Web_Server**: The Nginx or Apache HTTP server that serves the application.
- **RADIUS_Service**: The FreeRADIUS server and its associated MySQL database used for ISP authentication.
- **Python_Agent**: The Python-based component in the `agent/` directory that handles daily automation and WhatsApp bot functionality.
- **Cron_Jobs**: The scheduled PHP scripts (`cron_automation.php`, `cron_radius_rollup.php`, `cron_selfhosted_piprapay.php`) managed via system crontab.
- **Docker_Stack**: The Docker Compose-based deployment using `docker-compose.prod.yml`, `docker-compose.staging.yml`, and `docker-compose.dev.yml`.
- **Migration_Runner**: The portion of the Deploy_Script responsible for applying SQL migration files from `database/migrations/`.
- **Health_Check**: The HTTP endpoint at `/health` that returns HTTP 200 when the application is running correctly.
- **Storage_Dir**: The `storage/` directory containing logs, cache, and backup files.
- **Env_File**: The `.env` file and its environment-specific variants (`.env.production`, `.env.staging`, `.env.development`).
- **Composer**: The PHP dependency manager used to install packages defined in `composer.json`.
- **Ubuntu_24**: Ubuntu 24.04 LTS (Noble Numbat), the target deployment operating system.

---

## Requirements

### Requirement 1: PHP 8.3 Compatibility

**User Story:** As a system administrator, I want the application to run on PHP 8.3, so that I can deploy on Ubuntu 24.04 without installing a non-default PHP version.

#### Acceptance Criteria

1. THE System SHALL declare `"php": "^8.1"` or broader in `composer.json` to allow PHP 8.3.
2. WHEN `composer install` is executed on PHP 8.3, THE System SHALL complete without errors or deprecation-level failures.
3. THE Deploy_Script SHALL detect the installed PHP version and verify it is 8.1 or higher before proceeding.
4. IF the detected PHP version is below 8.1, THEN THE Deploy_Script SHALL print an error message stating the minimum required version and exit with a non-zero code.
5. THE Deploy_Script SHALL reload `php8.3-fpm` when restarting PHP-FPM on Ubuntu 24.04, falling back to `php8.2-fpm` and `php8.1-fpm` in that order if the primary is not found.

---

### Requirement 2: System Package Dependencies

**User Story:** As a system administrator, I want a documented and automated list of all required Ubuntu packages, so that I can provision a fresh Ubuntu 24.04 server without missing dependencies.

#### Acceptance Criteria

1. THE Deploy_Script SHALL verify that `php8.3-fpm`, `php8.3-mysql`, `php8.3-mbstring`, `php8.3-curl`, `php8.3-gd`, `php8.3-zip`, `php8.3-xml`, `php8.3-redis`, and `php8.3-sqlite3` are installed before proceeding.
2. IF any required PHP extension package is missing, THEN THE Deploy_Script SHALL print the names of all missing packages and exit with a non-zero code.
3. THE Deploy_Script SHALL verify that `nginx`, `mysql-client`, `git`, `curl`, and `composer` are available on the system PATH.
4. THE System SHALL include a `setup-ubuntu24.sh` script that installs all required system packages using `apt-get` with exact package names valid for Ubuntu 24.04.
5. WHERE the Python_Agent is enabled, THE `setup-ubuntu24.sh` script SHALL install `python3`, `python3-pip`, and `python3-venv` using Ubuntu 24.04 package names.
6. WHERE the Python_Agent is enabled, THE `setup-ubuntu24.sh` script SHALL create a Python virtual environment and install dependencies from `agent/requirements.txt` into it.

---

### Requirement 3: Web Server Configuration

**User Story:** As a system administrator, I want a correct Nginx configuration for Ubuntu 24.04, so that the application is served properly with PHP-FPM.

#### Acceptance Criteria

1. THE Web_Server configuration SHALL set the PHP-FPM socket path to `unix:/run/php/php8.3-fpm.sock` for non-Docker deployments on Ubuntu 24.04.
2. THE Web_Server configuration SHALL set `root` to the `public/` subdirectory of the application.
3. THE Web_Server configuration SHALL include a `try_files $uri $uri/ /index.php?$query_string` directive so all non-file requests are routed to `index.php`.
4. THE Web_Server configuration SHALL deny access to `.env`, `.git`, `composer.json`, `composer.lock`, and `storage/` paths.
5. THE Web_Server configuration SHALL include security headers: `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, and `Content-Security-Policy`.
6. THE System SHALL include a ready-to-use Nginx site configuration file at `docker/nginx/nginx-ubuntu24.conf` for bare-metal (non-Docker) Ubuntu 24.04 deployments.
7. WHEN the Deploy_Script runs on a non-Docker environment, THE Deploy_Script SHALL check whether the Nginx site configuration is symlinked in `/etc/nginx/sites-enabled/` and warn if it is not.

---

### Requirement 4: Database Setup and Migration

**User Story:** As a system administrator, I want the database to be set up and all migrations applied automatically during deployment, so that the application starts with a correct schema.

#### Acceptance Criteria

1. WHEN `DB_CONNECTION=mysql` is set in the Env_File, THE Deploy_Script SHALL verify MySQL connectivity using the configured `DB_HOST`, `DB_PORT`, `DB_USERNAME`, and `DB_PASSWORD` before applying migrations.
2. IF the MySQL connection check fails, THEN THE Deploy_Script SHALL print a descriptive error including the host and port and exit with a non-zero code.
3. WHEN `DB_CONNECTION=sqlite` is set in the Env_File, THE Deploy_Script SHALL create the SQLite file at the path specified by `DB_DATABASE` if it does not exist and set file permissions to `664`.
4. THE Deploy_Script SHALL apply all `.sql` files in `database/migrations/` in lexicographic order using the `mysql` client.
5. WHEN a migration file has already been applied, THE Deploy_Script SHALL skip it without exiting, logging a warning that the migration was skipped.
6. THE System SHALL support MySQL 8.0 as the production database, compatible with Ubuntu 24.04's default `mysql-server` package.
7. THE Deploy_Script SHALL set the MySQL character set to `utf8mb4` and collation to `utf8mb4_unicode_ci` when creating the application database.

---

### Requirement 5: Environment Configuration Validation

**User Story:** As a system administrator, I want the deploy script to validate all required environment variables before deployment, so that misconfigured deployments are caught early.

#### Acceptance Criteria

1. WHEN the Deploy_Script runs, THE Deploy_Script SHALL verify that `APP_KEY`, `APP_URL`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and `JWT_SECRET` are set and do not contain placeholder values such as `REPLACE`, `your_`, or `change_`.
2. IF any required variable is missing or contains a placeholder, THEN THE Deploy_Script SHALL list all failing variables and exit with a non-zero code before making any changes.
3. WHEN `APP_ENV=production` is set, THE Deploy_Script SHALL verify that `APP_DEBUG=false` is set in the Env_File.
4. IF `APP_DEBUG=true` is detected in a production deployment, THEN THE Deploy_Script SHALL exit with an error message stating that debug mode must be disabled in production.
5. THE Env_File SHALL include an `APP_TIMEZONE` variable defaulting to `Asia/Dhaka`, and THE System SHALL apply this timezone via `date_default_timezone_set()` on startup.
6. THE `.env.example` file SHALL document all required and optional environment variables with inline comments describing their purpose and valid values.

---

### Requirement 6: File Permissions and Ownership

**User Story:** As a system administrator, I want correct file permissions set during deployment, so that the web server can read application files and write to storage directories without security risks.

#### Acceptance Criteria

1. THE Deploy_Script SHALL set ownership of `storage/` and `public/uploads/` to `www-data:www-data`.
2. THE Deploy_Script SHALL set permissions on `storage/` and `public/uploads/` to `775`.
3. THE Deploy_Script SHALL set permissions on all PHP application files to `644` and directories to `755`.
4. THE Deploy_Script SHALL ensure the `.env` file has permissions of `640` and is owned by `root:www-data` in production.
5. IF the `storage/logs/`, `storage/cache/`, and `storage/backups/` directories do not exist, THEN THE Deploy_Script SHALL create them with permissions `775` and ownership `www-data:www-data`.
6. THE Deploy_Script SHALL ensure SQLite database files have permissions `664` and are owned by `www-data:www-data`.

---

### Requirement 7: Cron Job Setup

**User Story:** As a system administrator, I want cron jobs installed and configured automatically, so that billing automation and RADIUS rollup tasks run on schedule without manual setup.

#### Acceptance Criteria

1. THE Deploy_Script SHALL install cron entries for `cron_automation.php`, `cron_radius_rollup.php`, and `cron_selfhosted_piprapay.php` under the `www-data` user's crontab.
2. THE cron entry for `cron_automation.php` SHALL run daily at midnight: `0 0 * * * php /path/to/cron_automation.php`.
3. THE cron entry for `cron_automation.php due-reminders` SHALL run daily at 08:00: `0 8 * * * php /path/to/cron_automation.php due-reminders`.
4. THE cron entry for `cron_automation.php suspend` SHALL run every 6 hours: `0 */6 * * * php /path/to/cron_automation.php suspend`.
5. WHEN installing cron entries, THE Deploy_Script SHALL use the full absolute path to the PHP binary (e.g., `/usr/bin/php8.3`) to avoid PATH resolution issues in cron environments.
6. THE Deploy_Script SHALL verify that the `php` binary resolves to PHP 8.1 or higher before writing cron entries.
7. IF a cron entry for the application already exists in the `www-data` crontab, THEN THE Deploy_Script SHALL replace it rather than adding a duplicate.

---

### Requirement 8: Docker Compatibility on Ubuntu 24.04

**User Story:** As a system administrator, I want the Docker-based deployment to work on Ubuntu 24.04, so that I can use the containerized stack without compatibility issues.

#### Acceptance Criteria

1. THE Docker_Stack SHALL use `docker compose` (v2 plugin syntax) in all deploy scripts, with a fallback to `docker-compose` (v1 standalone) if the v2 plugin is not available.
2. THE `Dockerfile.prod` SHALL use `php:8.3-fpm` as the base image to match Ubuntu 24.04's default PHP version.
3. THE Docker_Stack configuration SHALL not bind port 3306 to `0.0.0.0` in production; MySQL SHALL only be accessible within the Docker network.
4. THE Docker_Stack SHALL define health checks for the `app`, `db`, and `redis` services.
5. WHEN the Docker_Stack starts, THE Deploy_Script SHALL wait for the `db` health check to pass before applying database migrations.
6. THE `docker-compose.prod.yml` SHALL reference a `Dockerfile.prod` that installs all required PHP extensions including `pdo_mysql`, `mbstring`, `gd`, `zip`, `redis`, and `opcache`.
7. WHERE Docker is not available, THE Deploy_Script SHALL fall back to bare-metal deployment using the system PHP-FPM and Nginx.

---

### Requirement 9: SSL/TLS Configuration

**User Story:** As a system administrator, I want HTTPS configured for production, so that all traffic to the ISP management system is encrypted.

#### Acceptance Criteria

1. THE Web_Server configuration SHALL redirect all HTTP traffic on port 80 to HTTPS on port 443 in production.
2. THE System SHALL include instructions in the deployment documentation for obtaining an SSL certificate using Certbot with the `certbot --nginx` command on Ubuntu 24.04.
3. THE Web_Server configuration SHALL set `ssl_protocols TLSv1.2 TLSv1.3` and disable older protocols.
4. THE Web_Server configuration SHALL set `ssl_ciphers` to a secure cipher suite excluding RC4, DES, and MD5-based ciphers.
5. IF the SSL certificate files specified in the Web_Server configuration do not exist, THEN THE Deploy_Script SHALL warn the operator and skip HTTPS configuration rather than failing silently.
6. THE `.htaccess` file SHALL include an uncommented HTTPS redirect rule for Apache-based deployments.

---

### Requirement 10: Logging and Log Rotation

**User Story:** As a system administrator, I want application logs rotated automatically, so that disk space is not exhausted by unbounded log growth.

#### Acceptance Criteria

1. THE System SHALL write application logs to `storage/logs/app.log` and cron logs to `storage/logs/automation_cron.log`.
2. THE Deploy_Script SHALL install a logrotate configuration file at `/etc/logrotate.d/digital-isp` that rotates logs in `storage/logs/` daily, keeps 30 days of history, and compresses rotated files.
3. THE logrotate configuration SHALL use the `create 664 www-data www-data` directive to recreate log files with correct ownership after rotation.
4. THE logrotate configuration SHALL include `postrotate` to send `USR1` signal to PHP-FPM after rotation so it reopens log file handles.
5. WHEN `APP_DEBUG=false`, THE System SHALL set `display_errors = Off` and `log_errors = On` in the PHP configuration.
6. THE `docker/php/php.ini` file SHALL set `display_errors = Off` for production use, with a separate development override that enables it.

---

### Requirement 11: Python Agent Deployment

**User Story:** As a system administrator, I want the Python agent component deployed and running as a systemd service, so that daily automation and WhatsApp bot tasks run reliably on Ubuntu 24.04.

#### Acceptance Criteria

1. THE `setup-ubuntu24.sh` script SHALL create a systemd service unit file at `/etc/systemd/system/digital-isp-agent.service` for the Python_Agent.
2. THE systemd service unit SHALL run the agent as the `www-data` user with the working directory set to the `agent/` subdirectory of the application.
3. THE systemd service unit SHALL set `Restart=on-failure` and `RestartSec=10` to automatically recover from crashes.
4. THE `setup-ubuntu24.sh` script SHALL run `systemctl daemon-reload` and `systemctl enable digital-isp-agent` after creating the service unit.
5. WHEN the Python_Agent service starts, THE Python_Agent SHALL load its configuration from `agent/config.py` and the application's Env_File.
6. THE `agent/requirements.txt` file SHALL pin all dependency versions to exact versions (e.g., `requests==2.31.0`) to ensure reproducible installs on Ubuntu 24.04.

---

### Requirement 12: Deploy Script Ubuntu 24.04 Compatibility

**User Story:** As a system administrator, I want the deploy scripts updated for Ubuntu 24.04 specifics, so that deployment succeeds on a fresh Ubuntu 24.04 server without manual intervention.

#### Acceptance Criteria

1. THE Deploy_Script SHALL detect whether it is running on Ubuntu 24.04 by reading `/etc/os-release` and log the detected OS version.
2. THE Deploy_Script SHALL use `systemctl reload php8.3-fpm` (not `php8.1-fpm`) as the primary PHP-FPM reload command on Ubuntu 24.04.
3. THE Deploy_Script SHALL use `docker compose` (space-separated, v2 syntax) when the Docker Compose v2 plugin is detected, and fall back to `docker-compose` (hyphenated, v1) otherwise.
4. THE Deploy_Script SHALL verify that the `www-data` user exists before setting file ownership, and create it if absent.
5. THE Deploy_Script SHALL create the `storage/backups/` directory and perform a pre-deployment database backup before pulling new code.
6. WHEN the Health_Check endpoint returns a non-200 response after deployment, THE Deploy_Script SHALL display the last 50 lines of `storage/logs/app.log` and prompt the operator to confirm before exiting.
7. THE Deploy_Script SHALL log all deployment steps with timestamps to `storage/logs/deploy.log`.
