<div align="center">

<img src="https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white" />
<img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
<img src="https://img.shields.io/badge/SQLite-3-003B57?style=for-the-badge&logo=sqlite&logoColor=white" />
<img src="https://img.shields.io/badge/MikroTik-RouterOS-FF6600?style=for-the-badge&logo=mikrotik&logoColor=white" />
<img src="https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge" />

# 🌐 Digital ISP ERP

**Enterprise-grade ISP Management System built for Bangladesh ISPs**

Manage customers, billing, GPON/fiber networks, MikroTik routers, RADIUS AAA, inventory, resellers, and more — all from a single modern dashboard.

[Features](#-features) · [Screenshots](#-screenshots) · [Quick Start](#-quick-start) · [Installation](#-installation) · [API Docs](#-rest-api) · [Modules](#-modules)

</div>

---

## ✨ Features

- 👥 **Customer Management** — Full lifecycle: onboarding, KYC, status, billing day, PPPoE/static/hotspot
- 💰 **Billing & Invoicing** — Pro-rata billing, Bangla receipts, advance balance, due tracking
- 🌐 **MikroTik Integration** — RouterOS API: PPPoE sessions, bandwidth profiles, kick/suspend
- 📡 **RADIUS AAA** — FreeRADIUS integration, session tracking, usage analytics, audit logs
- 🔆 **GPON / Fiber** — OLT → Splitter → ONU hierarchy, SNMP + Telnet sync, live ONU status
- 📦 **Inventory** — Stock management, purchase orders, warehouse tracking
- 🤝 **Reseller System** — Multi-level resellers, commission, balance top-up
- 📋 **Work Orders** — Kanban board, technician assignment, field dispatch
- 📊 **Reports & Analytics** — Income, collection, due, growth, RADIUS usage charts
- 💬 **SMS (Bangla)** — SSL Wireless / BulkSMS BD, Bangla templates, campaigns
- 🏦 **Finance** — Cashbook, expense tracking, daily summaries
- 🔐 **Security** — RBAC, bcrypt, PDO prepared statements, IP access control, MFA, audit trail
- 🌙 **Dark / Light Mode** — Full theme support across all pages
- 📱 **Customer Portal** — Self-service: invoices, payments, usage, support tickets
- 🔌 **REST API** — Bearer token auth, full CRUD for all major resources
- 🐳 **Docker Ready** — Full docker-compose stack with Nginx, PHP-FPM, MySQL, Redis

---

## 📸 Screenshots

> Dashboard · Customer List · Billing · GPON OLT · RADIUS Analytics · Customer Portal

*Screenshots coming soon — contributions welcome.*

---

## ⚡ Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/jdkamrul/digitalisp.git
cd digitalisp

# 2. Copy environment file
cp .env.example .env

# 3. Run setup (auto-creates DB schema + admin account)
php setup.php

# 4. Start development server
php -S 127.0.0.1:8088 -t public
```

Open **http://localhost:8088** and login with `admin` / `Admin@1234`

---

## 🛠 Installation

### Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.1+ |
| MySQL | 8.0+ (production) |
| SQLite | 3 (local/demo) |
| PHP Extensions | `pdo`, `pdo_mysql`, `mbstring`, `curl`, `gd`, `snmp` |
| Web Server | Apache (mod_rewrite) or Nginx |

### Option A — XAMPP / Local (SQLite)

1. Place the project in `xampp/htdocs/digitalisp`
2. Copy `.env.example` → `.env` and set:
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=database/digital-isp.sqlite
   ```
3. Enable `mod_rewrite` in `httpd.conf`:
   ```
   AllowOverride All
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
4. Run `php setup.php`
5. Visit **http://localhost/digitalisp/public/**

### Option B — cPanel / Shared Hosting (MySQL)

1. Upload and extract project to `/home/username/digitalisp`
2. Set domain document root → `/home/username/digitalisp/public`
3. Create MySQL database + user in cPanel, import `database/schema.sql`
4. Configure `.env`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_DATABASE=your_db
   DB_USERNAME=your_user
   DB_PASSWORD=your_password
   ```
5. Set write permissions: `chmod 775 tmp/ public/assets/uploads/`

### Option C — VPS / Ubuntu 24.04 + Nginx

1. **Update system and install dependencies:**
   ```bash
   sudo apt update && sudo apt upgrade -y
   sudo apt install -y nginx mysql-server php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-curl php8.3-gd php8.3-snmp php8.3-xml php8.3-zip unzip curl
   ```

2. **Secure MySQL installation:**
   ```bash
   sudo mysql_secure_installation
   ```

3. **Create database and user:**
   ```bash
   sudo mysql -u root -p
   CREATE DATABASE digitalisp;
   CREATE USER 'digitalisp'@'localhost' IDENTIFIED BY 'your_password';
   GRANT ALL PRIVILEGES ON digitalisp.* TO 'digitalisp'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

4. **Clone and setup project:**
   ```bash
   sudo mkdir -p /var/www
   cd /var/www
   sudo git clone https://github.com/jdkamrul/digitalisp.git
   sudo chown -R www-data:www-data /var/www/digitalisp
   cd /var/www/digitalisp
   sudo cp .env.example .env
   # Edit .env with your database credentials
   sudo nano .env
   ```

5. **Install PHP dependencies:**
   ```bash
   sudo apt install -y composer
   composer install --no-dev --optimize-autoloader
   ```

6. **Setup database:**
   ```bash
   sudo mysql -u digitalisp -p digitalisp < database/schema.sql
   sudo php setup.php
   ```

7. **Configure Nginx:**
   ```nginx
   sudo nano /etc/nginx/sites-available/digitalisp
   ```
   Add this content:
   ```nginx
   server {
       listen 80;
       server_name isp.yourdomain.com;
       root /var/www/digitalisp/public;
       index index.php;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           include snippets/fastcgi-php.conf;
           fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

8. **Enable site and restart services:**
   ```bash
   sudo ln -s /etc/nginx/sites-available/digitalisp /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl reload nginx
   sudo systemctl enable php8.3-fpm
   sudo systemctl enable mysql
   ```

9. **Set permissions:**
   ```bash
   sudo chown -R www-data:www-data /var/www/digitalisp
   sudo chmod -R 775 /var/www/digitalisp/tmp /var/www/digitalisp/public/assets/uploads
   ```

10. **Access your application:**
    Visit `http://your_server_ip` and login with `admin` / `Admin@1234`

### Option D — Docker

```bash
cp .env.example .env
docker-compose up -d
```

Services: `nginx:80`, `php-fpm:9000`, `mysql:3306`, `redis:6379`

---

## 📁 Project Structure

```
digitalisp/
├── app/
│   ├── Controllers/          # AuthController, CustomerController, BillingController...
│   ├── Controllers/CustomerPortal/  # Self-service portal controllers
│   ├── Services/             # MikroTikService, RadiusService, SnmpOltService, SmsService...
│   ├── Middleware/           # AuthMiddleware, ApiRateLimitMiddleware
│   └── Core/                 # Router, Cache, DatabasePool
├── config/
│   ├── app.php               # App config, helpers, autoloader
│   └── database.php          # PDO singleton
├── database/
│   ├── schema.sql            # MySQL full schema
│   ├── sqlite_schema.sql     # SQLite schema
│   ├── radius_schema.sql     # FreeRADIUS tables
│   └── migrations/           # Incremental migration files
├── docker/                   # Nginx, PHP, MySQL, monitoring configs
├── public/
│   ├── index.php             # Front controller
│   └── assets/               # CSS, JS, images, uploads
├── routes/
│   ├── web.php               # Web routes
│   └── api.php               # REST API v1 routes
├── views/
│   ├── layouts/main.php      # Main shell (dark/light, sidebar, header)
│   ├── dashboard/            # Dashboard with charts
│   ├── customers/            # Customer CRUD + KYC
│   ├── billing/              # Invoices, payments, Bangla receipt
│   ├── network/              # IP pools, NAS/MikroTik, RADIUS, PPPoE
│   ├── gpon/                 # OLT, Splitters, ONUs, Fiber incidents
│   ├── radius/               # RADIUS analytics, sessions, audit
│   ├── inventory/            # Stock, purchase orders
│   ├── reseller/             # Reseller management
│   ├── workorders/           # Work order Kanban
│   ├── reports/              # Income, due, collection, growth
│   ├── finance/              # Cashbook, expenses
│   ├── portal/               # Customer self-service portal
│   └── settings/             # Users, branches, SMS gateways
├── scripts/                  # Backup, deploy, maintenance scripts
├── tests/                    # PHPUnit unit, integration, security tests
├── .env.example              # Environment template
└── docker-compose.yml        # Docker stack
```

---

## 🔌 REST API

All API endpoints require `Authorization: Bearer {token}` header.

### Authentication
```http
POST /api/v1/auth/login
Content-Type: application/json

{ "username": "admin", "password": "Admin@1234" }
```

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/auth/login` | Login — returns Bearer token |
| `GET` | `/api/v1/dashboard/stats` | Dashboard statistics |
| `GET` | `/api/v1/customers` | List customers |
| `GET` | `/api/v1/customers/search?q=` | Search customers |
| `GET` | `/api/v1/customers/{id}` | Customer detail |
| `GET` | `/api/v1/customers/{id}/invoices` | Customer invoices |
| `POST` | `/api/v1/payments` | Record payment |
| `GET` | `/api/v1/collections/today` | Today's collections |
| `GET` | `/api/v1/workorders` | Work orders list |
| `POST` | `/api/v1/workorders` | Create work order |
| `POST` | `/api/v1/workorders/{id}/status` | Update work order status |
| `GET` | `/api/v1/notifications` | User notifications |
| `GET` | `/api/v1/radius/users` | RADIUS users |
| `GET` | `/api/v1/radius/sessions` | Active RADIUS sessions |

---

## 📦 Modules

| # | Module | Description | Status |
|---|--------|-------------|--------|
| 1 | **Auth & RBAC** | Login, roles, permissions, MFA | ✅ |
| 2 | **Customer Management** | Onboarding, KYC, status lifecycle | ✅ |
| 3 | **Billing** | Pro-rata invoicing, Bangla receipts, advance balance | ✅ |
| 4 | **MikroTik / NAS** | RouterOS API, PPPoE, bandwidth profiles | ✅ |
| 5 | **RADIUS AAA** | FreeRADIUS, session tracking, usage analytics | ✅ |
| 6 | **GPON / Fiber** | OLT (SNMP+Telnet), Splitters, ONUs, incidents | ✅ |
| 7 | **IP Pool Management** | CIDR pools, assignment tracking | ✅ |
| 8 | **Inventory** | Stock, purchase orders, warehouses | ✅ |
| 9 | **Reseller System** | Multi-level, commission, balance | ✅ |
| 10 | **Work Orders** | Kanban, technician dispatch | ✅ |
| 11 | **Collection System** | Daily collection sessions, receipts | ✅ |
| 12 | **SMS (Bangla)** | SSL Wireless, BulkSMS BD, campaigns | ✅ |
| 13 | **Finance** | Cashbook, expense tracking | ✅ |
| 14 | **Reports & Analytics** | Income, due, growth, RADIUS charts | ✅ |
| 15 | **Customer Portal** | Self-service: invoices, usage, tickets | ✅ |
| 16 | **REST API** | Bearer token, full CRUD | ✅ |
| 17 | **Automation** | Cron-based billing, suspension, alerts | ✅ |
| 18 | **Docker** | Nginx + PHP-FPM + MySQL + Redis stack | ✅ |

---

## 🔐 Security

- Passwords hashed with `bcrypt` (cost 12)
- All DB queries via PDO prepared statements (SQL injection safe)
- XSS prevention via `htmlspecialchars()` on all output
- CSRF protection on state-changing forms
- Role-based access control (RBAC) on every route
- IP access control rules
- Multi-factor authentication (MFA) support
- Rate limiting on API endpoints
- Full audit trail for all mutations
- `.env` excluded from version control

---

## 📲 Integrations

| Integration | Details |
|-------------|---------|
| **MikroTik RouterOS** | Binary API port 8728, PPPoE/Hotspot/DHCP |
| **FreeRADIUS** | MySQL backend, radcheck/radreply/radacct |
| **SNMP OLT** | Huawei MA5600/MA5800, ZTE C300/C320, FiberHome |
| **Telnet OLT** | AC-FD1304E-B1 EPON and compatible CLIs |
| **SSL Wireless SMS** | Bangladesh SMS gateway |
| **BulkSMS BD** | Bangladesh SMS gateway |
| **bKash Personal** | Mobile banking payment gateway |
| **PipraPay** | Self-hosted payment gateway |

---

## ⚙️ Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | `local` / `production` | `local` |
| `APP_URL` | Base URL | `http://127.0.0.1:8088` |
| `DB_CONNECTION` | `mysql` or `sqlite` | `sqlite` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_DATABASE` | Database name / path | `database/digital-isp.sqlite` |
| `RADIUS_HOST` | FreeRADIUS host | `127.0.0.1` |
| `SMS_GATEWAY` | SMS provider | `sslwireless` |
| `JWT_SECRET` | API token secret | *(set this!)* |

See `.env.example` for the full list.

---

## 🚀 Cron Jobs

Add these to your server crontab for automated billing and alerts:

```cron
# Daily billing automation (runs at midnight)
0 0 * * * php /var/www/digitalisp/cron_automation.php

# RADIUS usage rollup (every hour)
0 * * * * php /var/www/digitalisp/cron_radius_rollup.php

# Self-hosted payment sync (every 5 minutes)
*/5 * * * * php /var/www/digitalisp/cron_selfhosted_piprapay.php
```

---

## 🧪 Testing

```bash
# Install dev dependencies
composer install

# Run all tests
./vendor/bin/phpunit

# Run specific suite
./vendor/bin/phpunit tests/Unit
./vendor/bin/phpunit tests/Integration
```

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m "feat: add your feature"`
4. Push to the branch: `git push origin feature/your-feature`
5. Open a Pull Request

Please follow [Conventional Commits](https://www.conventionalcommits.org/) for commit messages.

---

## 📄 License

This project is **proprietary software**. All rights reserved.  
Unauthorized copying, distribution, or modification is prohibited.

---

## 👨‍💻 Author

**Digital ISP ERP Team**  
📧 admin@bkdnet.xyz  
🌐 [github.com/jdkamrul/digitalisp](https://github.com/jdkamrul/digitalisp)

---

<div align="center">
  <sub>Built with ❤️ for Bangladesh ISPs</sub>
</div>
