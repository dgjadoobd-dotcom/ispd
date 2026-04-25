# Digital ISP ERP — Installation Guide

## Table of Contents
1. [Requirements](#requirements)
2. [XAMPP (Windows)](#xampp-windows)
3. [Shared Hosting (cPanel)](#shared-hosting-cpanel)
4. [Ubuntu Server](#ubuntu-server)
5. [Post-Install Checklist](#post-install-checklist)
6. [Troubleshooting](#troubleshooting)

---

## Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| PHP | 8.1 | 8.3 (Ubuntu 24.04 default) |
| Database | SQLite 3 (local/dev) or MySQL 8.0+ | MySQL 8.0 |
| PHP Extensions | `pdo`, `pdo_mysql`, `mbstring`, `curl`, `gd`, `zip`, `sqlite3`, `redis` | All listed |
| Web Server | Apache 2.4+ or Nginx 1.18+ | Nginx (Ubuntu 24.04) |
| OS | Ubuntu 22.04+ / any Linux | Ubuntu 24.04 LTS |
| RAM | 512 MB | 2 GB |
| Disk | 500 MB | 5 GB |

---

## XAMPP (Windows)

### Step 1 — Download & Place Files
```
C:\xampp\htdocs\ispd\
```
Copy all project files into that folder.

### Step 2 — Start Services
Open XAMPP Control Panel and start:
- **Apache**
- **MySQL**

### Step 3 — Run Installer
Open your browser:
```
http://localhost/ispd/public/install.php
```

### Step 4 — Follow the Wizard
1. **Requirements** — all checks must pass (green)
2. **Configuration** — set App URL to `http://localhost/ispd/public`
3. **Database** — choose SQLite (no setup needed) or MySQL
4. **Admin Account** — create your login credentials
5. **Complete** — note your credentials, then delete `install.php`

### Step 5 — Configure Virtual Host (Optional)
Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:
```apache
<VirtualHost *:8088>
    DocumentRoot "C:/xampp/htdocs/ispd/public"
    ServerName billing.local
    <Directory "C:/xampp/htdocs/ispd/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
Add to `C:\Windows\System32\drivers\etc\hosts`:
```
127.0.0.1  billing.local
```
Restart Apache. Access: `http://billing.local:8000`

### Cron Jobs (Windows Task Scheduler)
Run as Administrator:
```bat
C:\xampp\htdocs\ispd\scripts\windows\register_automation_task.bat
C:\xampp\htdocs\ispd\scripts\windows\register_selfhosted_piprapay_task.bat
```

---

## Shared Hosting (cPanel)

### Step 1 — Upload Files
Using File Manager or FTP, upload all files to:
```
public_html/ispd/        ← app files (app/, config/, database/, etc.)
public_html/             ← contents of the public/ folder
```

Or if you have a subdomain (e.g. `billing.fcnchbd.xyz`):
```
billing.fcnchbd.xyz/  ← contents of public/ folder
ispd/                    ← app files (one level above public_html)
```

> **Tip:** The `public/` folder should be your web root. Everything else should be outside it.

### Step 2 — Set File Permissions
In File Manager, set:
```
database/     → 755
storage/      → 755
.env          → 644 (after creation)
```

### Step 3 — Create MySQL Database
In cPanel → MySQL Databases:
1. Create database: `yourusername_ispd`
2. Create user: `yourusername_ispd`
3. Assign user to database with **All Privileges**

### Step 4 — Run Installer
```
https://billing.fcnchbd.xyz/install.php
```
- Choose **MySQL** as database type
- Enter the database credentials from Step 3

### Step 5 — Set Up Cron Job
In cPanel → Cron Jobs, add:
```
0 0 * * *   /usr/bin/php /home/yourusername/ispd/cron_automation.php
*/15 * * * * /usr/bin/php /home/yourusername/ispd/cron_selfhosted_piprapay.php
```

### Step 6 — .htaccess
Ensure `public/.htaccess` exists with:
```apache
Options -Indexes
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

---

## Ubuntu Server

> **Recommended:** Ubuntu 24.04 LTS (Noble Numbat) with PHP 8.3. The steps below use the automated provisioning script. For Ubuntu 22.04 or manual setup, see the legacy notes at the end of this section.

### Step 1 — Clone the repository

```bash
cd /var/www
sudo git clone https://github.com/dgjadoobd-dotcom/ispd.git digital-isp
sudo chown -R www-data:www-data /var/www/digital-isp
```

### Step 2 — Run the one-time provisioning script

```bash
cd /var/www/digital-isp
sudo bash setup-ubuntu24.sh
```

`setup-ubuntu24.sh` installs and configures everything in one shot:

| What | Details |
|------|---------|
| PHP 8.3 + extensions | `php8.3-fpm`, `mysql`, `mbstring`, `curl`, `gd`, `zip`, `xml`, `redis`, `sqlite3` |
| Web server | `nginx` — site config symlinked to `/etc/nginx/sites-enabled/digital-isp` |
| Database client | `mysql-client` |
| Python agent | `python3-venv` — creates `agent/venv`, installs `agent/requirements.txt` |
| systemd service | `/etc/systemd/system/digital-isp-agent.service` (enabled, starts on boot) |
| Log rotation | `/etc/logrotate.d/digital-isp` (daily, 30-day retention, USR1 reload) |

### Step 3 — Configure environment

```bash
sudo cp .env.example .env.production
sudo nano .env.production
```

Required variables (see `.env.example` for the full annotated list):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=base64:REPLACE_WITH_RANDOM_BASE64_KEY   # php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
APP_TIMEZONE=Asia/Dhaka

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=digital_isp
DB_USERNAME=ispd_user
DB_PASSWORD=REPLACE_WITH_DB_PASSWORD

JWT_SECRET=REPLACE_WITH_RANDOM_SECRET_MIN_32_CHARS   # openssl rand -hex 32
```

### Step 4 — Create the MySQL database

```bash
sudo mysql -u root
```
```sql
CREATE DATABASE digital_isp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ispd_user'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON digital_isp.* TO 'ispd_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 5 — Deploy

```bash
sudo bash deploy-prod.sh
```

The deploy script automatically:
- Validates all required env vars (reports all failures at once, never stops at first)
- Checks PHP ≥ 8.1 and all required packages are installed
- Backs up the database **before** pulling new code
- Runs `composer install --no-dev --optimize-autoloader`
- Sets correct file permissions (`www-data:www-data`, `.env` → `root:www-data 640`)
- Applies SQL migrations idempotently via `_migrations` tracking table
- Installs cron jobs for `www-data` (idempotent — no duplicates on re-run)
- Reloads `php8.3-fpm` (auto-falls back to 8.2 / 8.1 if needed)
- Runs a health check and shows the last 50 log lines on failure

### Step 6 — Enable HTTPS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
# Then uncomment the HTTPS server block in /etc/nginx/sites-available/digital-isp
sudo nginx -t && sudo systemctl reload nginx
```

### Step 7 — Firewall

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Step 8 — Start the Python agent

```bash
sudo systemctl start digital-isp-agent
journalctl -u digital-isp-agent -f   # view logs
```

---

### Ubuntu 22.04 / Manual setup (legacy)

If you cannot use `setup-ubuntu24.sh`, install dependencies manually:

```bash
sudo apt update && sudo apt install -y \
  php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-curl php8.1-gd \
  php8.1-zip php8.1-xml php8.1-sqlite3 nginx mysql-client git curl composer
```

Then configure Nginx manually using `docker/nginx/nginx-ubuntu24.conf` as a template (change the FPM socket to `/run/php/php8.1-fpm.sock`), and add cron jobs manually:

```bash
sudo crontab -e -u www-data
```
```cron
0 0 * * *   /usr/bin/php8.1 /var/www/digital-isp/cron_automation.php >> .../automation_cron.log 2>&1
0 8 * * *   /usr/bin/php8.1 /var/www/digital-isp/cron_automation.php due-reminders >> ...
0 */6 * * * /usr/bin/php8.1 /var/www/digital-isp/cron_automation.php suspend >> ...
5 0 * * *   /usr/bin/php8.1 /var/www/digital-isp/cron_radius_rollup.php >> ...
10 0 * * *  /usr/bin/php8.1 /var/www/digital-isp/cron_selfhosted_piprapay.php >> ...
```

---

## Post-Install Checklist

After completing setup:

- [ ] Log in at your App URL with the admin credentials (`admin` / `Admin@1234`)
- [ ] **Change the default admin password immediately**
- [ ] Go to **Settings → General** — verify company name and URL
- [ ] Go to **Settings → Payment Gateways** — enable Self-Hosted PipraPay, set webhook secret
- [ ] Go to **Network → MikroTik / NAS** — add your MikroTik router
- [ ] Go to **GPON/EPON → OLTs** — add your OLT device
- [ ] Verify cron jobs are running: `crontab -l -u www-data`
- [ ] Verify PHP agent service: `systemctl status digital-isp-agent`
- [ ] Confirm `APP_DEBUG=false` in `.env` (production)
- [ ] Confirm `.env` permissions: `stat .env` should show `640` owned by `root:www-data`
- [ ] Run a health check: `curl https://yourdomain.com/health`

---

## Troubleshooting

### "Invalid username or password" on login
```bash
php reset_admin_password.php
# or run directly:
php -r "
define('BASE_PATH', __DIR__);
require 'config/app.php';
require 'config/database.php';
\$db = Database::getInstance();
\$db->update('users', ['password_hash' => password_hash('Admin@1234', PASSWORD_BCRYPT)], 'username=?', ['admin']);
echo 'Password reset to Admin@1234';
"
```

### White screen / 500 error
- Set `APP_DEBUG=true` in `.env` temporarily
- Check Apache/Nginx error logs
- Ensure `public/.htaccess` exists and `mod_rewrite` is enabled

### Database permission error (SQLite)
```bash
sudo chown www-data:www-data database/digital_isp.sqlite
sudo chmod 664 database/digital_isp.sqlite
sudo chmod 775 database/
```

### "Cannot write .env file"
```bash
sudo chmod 775 /var/www/digital-isp
sudo chown www-data:www-data /var/www/digital-isp
```

### MikroTik connection fails
- Verify API port 8728 is open on MikroTik: `ip service enable api`
- Check firewall: `ip firewall filter` — allow port 8728 from server IP
- Test: `telnet <mikrotik-ip> 8728`

### RADIUS not connecting
- Verify `RADIUS_DB_HOST`, `RADIUS_DB_DATABASE`, `RADIUS_DB_USERNAME` in `.env`
- Test MySQL connection: `mysql -h localhost -u root -p radius`
- Check FreeRADIUS is running: `sudo systemctl status freeradius`

### Cron not running
```bash
# Check cron log
tail -f /var/www/digital-isp/storage/logs/automation_cron.log

# Test manually
/usr/bin/php8.3 /var/www/digital-isp/cron_automation.php

# Verify crontab
crontab -l -u www-data

# Re-install cron jobs via helper
sudo bash -c 'source /var/www/digital-isp/scripts/deploy-helpers.sh && install_cron_jobs /var/www/digital-isp'
```

---

*Digital ISP ERP — Made for Bangladesh ISPs*
