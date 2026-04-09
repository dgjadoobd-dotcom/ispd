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

| Component | Minimum |
|-----------|---------|
| PHP | 8.1 – 8.3 |
| Database | SQLite 3 (built-in) or MySQL 5.7+ / MariaDB 10.4+ |
| Extensions | PDO, pdo_sqlite, pdo_mysql, cURL, OpenSSL, Mbstring, JSON, BCMath |
| Web Server | Apache 2.4+ or Nginx 1.18+ |
| RAM | 512 MB minimum, 1 GB recommended |
| Disk | 500 MB minimum |

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
Restart Apache. Access: `http://billing.local:8088`

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

Or if you have a subdomain (e.g. `billing.yourdomain.com`):
```
billing.yourdomain.com/  ← contents of public/ folder
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
https://billing.yourdomain.com/install.php
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

### Step 1 — Install Dependencies
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 php8.2 php8.2-cli php8.2-pdo php8.2-sqlite3 \
  php8.2-mysql php8.2-curl php8.2-mbstring php8.2-xml php8.2-bcmath \
  php8.2-snmp php8.2-zip php8.2-gd libapache2-mod-php8.2 \
  mysql-server unzip git
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Step 2 — Clone / Upload Project
```bash
cd /var/www
sudo git clone https://github.com/yourrepo/digital-isp-erp.git ispd
# OR upload via SFTP and extract
sudo chown -R www-data:www-data /var/www/ispd
sudo chmod -R 755 /var/www/ispd
sudo chmod -R 775 /var/www/ispd/database /var/www/ispd/storage
```

### Step 3 — Apache Virtual Host
```bash
sudo nano /etc/apache2/sites-available/ispd.conf
```
```apache
<VirtualHost *:80>
    ServerName billing.yourdomain.com
    DocumentRoot /var/www/ispd/public

    <Directory /var/www/ispd/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/ispd_error.log
    CustomLog ${APACHE_LOG_DIR}/ispd_access.log combined
</VirtualHost>
```
```bash
sudo a2ensite ispd.conf
sudo systemctl reload apache2
```

### Step 4 — MySQL Setup (if using MySQL)
```bash
sudo mysql -u root
```
```sql
CREATE DATABASE digital_isp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ispd'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON digital_isp.* TO 'ispd'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 5 — Run Installer
```
http://billing.yourdomain.com/install.php
```

### Step 6 — SSL (Let's Encrypt)
```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d billing.yourdomain.com
```

### Step 7 — Cron Jobs
```bash
sudo crontab -e -u www-data
```
Add:
```cron
0 0 * * *    /usr/bin/php /var/www/ispd/cron_automation.php >> /var/www/ispd/storage/logs/automation_cron.log 2>&1
*/15 * * * * /usr/bin/php /var/www/ispd/cron_selfhosted_piprapay.php >> /var/www/ispd/storage/logs/selfhosted_piprapay_cron.log 2>&1
```

### Step 8 — Firewall
```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 8088/tcp   # if using custom port
sudo ufw enable
```

---

## Post-Install Checklist

After completing the installer:

- [ ] **Delete** `public/install.php` from the server
- [ ] Log in at your App URL with the admin credentials
- [ ] Go to **Settings → General** — verify company name and URL
- [ ] Go to **Settings → Payment Gateways** — enable Self-Hosted PipraPay, set webhook secret
- [ ] Go to **Network → MikroTik / NAS** — add your MikroTik router
- [ ] Go to **GPON → OLTs** — add your OLT device
- [ ] Set up the PipraPay panel at `http://piprapay.yourdomain.com:8090`
  - Run: `php migrate_selfhosted_piprapay.php`
  - Visit the paybill installer at `/install`
- [ ] Change the default admin password
- [ ] Set `APP_DEBUG=false` in `.env` (production)
- [ ] Verify cron jobs are running

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
sudo chown www-data:www-data database/digital-isp.sqlite
sudo chmod 664 database/digital-isp.sqlite
sudo chmod 775 database/
```

### "Cannot write .env file"
```bash
sudo chmod 775 /var/www/ispd
sudo chown www-data:www-data /var/www/ispd
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
tail -f /var/www/ispd/storage/logs/automation_cron.log

# Test manually
php /var/www/ispd/cron_automation.php

# Verify crontab
crontab -l -u www-data
```

---

*Digital ISP ERP — Made for Bangladesh ISPs*
