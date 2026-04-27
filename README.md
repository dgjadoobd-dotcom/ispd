<div align="center">

<img src="https://img.shields.io/badge/PHP-8.1%20|%208.3-777BB4?style=for-the-badge&logo=php&logoColor=white" />
<img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
<img src="https://img.shields.io/badge/SQLite-3-003B57?style=for-the-badge&logo=sqlite&logoColor=white" />
<img src="https://img.shields.io/badge/MikroTik-RouterOS-FF6600?style=for-the-badge&logo=mikrotik&logoColor=white" />
<img src="https://img.shields.io/badge/PHPUnit-10-6C3483?style=for-the-badge&logo=php&logoColor=white" />
<img src="https://img.shields.io/badge/Ubuntu-24.04_LTS-E95420?style=for-the-badge&logo=ubuntu&logoColor=white" />
<img src="https://img.shields.io/badge/Version-3.0.0-blue?style=for-the-badge" />
<img src="https://img.shields.io/badge/License-Apache_2.0-green?style=for-the-badge" />

# 🌐 FCNCHBD ISP ERP v3.0.0

**Enterprise-grade ISP Management System built for Bangladesh ISPs**

Manage customers, billing, GPON/fiber networks, MikroTik routers, RADIUS AAA, HR & payroll, support tickets, resellers, MAC resellers, inventory, roles & permissions, and more — all from a single modern dashboard.

[Features](#-features) · [Modules](#-modules) · [Quick Start](#-quick-start) · [Installation](#-installation) · [API Docs](#-rest-api) · [Screenshots](#-screenshots)

</div>

---

## ✨ Features

- 👥 **Customer Management** — Full lifecycle: onboarding, KYC, MAC address, road/house/sub-zone, district, device tracking, import from Excel
- 💰 **Billing & Invoicing** — Pro-rata billing, Bangla receipts, advance balance, due tracking, partial payments
- 🌐 **MikroTik Integration** — RouterOS API: PPPoE sessions, bandwidth profiles, kick/suspend/reconnect
- 📡 **RADIUS AAA** — FreeRADIUS integration, session tracking, usage analytics, audit logs, bulk operations
- 🔆 **GPON / Fiber** — OLT → Splitter → ONU hierarchy, SNMP + Telnet sync, live ONU status
- 📦 **Inventory** — Stock management, purchase orders, warehouse tracking, low-stock alerts
- 🤝 **Reseller System** — Multi-level resellers, commission, balance top-up, transaction history
- 🖥️ **MAC Reseller Portal** — Tariff plans, client management by MAC address, daily billing generation, payment tracking
- 👔 **HR & Payroll** — Employee profiles, departments, designations, attendance calendar, salary calculation, salary slips (PDF), performance appraisals, leave balances
- 🎫 **Support & Ticketing** — SLA tracking (urgent=2h/high=8h/medium=24h/low=72h), ticket assignment with SMS, comment threads, SLA compliance dashboard
- 🛡️ **Roles & Permissions** — Granular RBAC UI: create custom roles, assign permissions per module, user role assignment, superadmin protection
- 📋 **Work Orders** — Kanban board, technician assignment, field dispatch
- 📊 **Reports & Analytics** — Income, collection, due, growth, RADIUS usage charts
- 💬 **SMS (Bangla)** — SSL Wireless / BulkSMS BD, Bangla templates, bulk campaigns
- 🏦 **Finance** — Cashbook, expense tracking, daily summaries
- 🔐 **Security** — RBAC, bcrypt, PDO prepared statements, IP access control, MFA, audit trail, rate limiting
- 🌙 **Dark / Light Mode** — Full theme support across all pages
- 📱 **Customer Portal** — Self-service: invoices, payments, usage, support tickets, AI chat
- 🔌 **REST API** — Bearer token auth, full CRUD for all major resources
- 🐳 **Docker Ready** — Full docker-compose stack with Nginx, PHP-FPM 8.3, MySQL, Redis; health checks on all services
- 🚀 **Ubuntu 24.04 Ready** — One-command provisioning (`setup-ubuntu24.sh`), PHP 8.3 FPM socket, systemd agent service, logrotate, idempotent deploy scripts
- 📥 **Excel Import** — 30-column client import with validation, duplicate detection, rules table, demo template download

---

## 📦 Modules

| # | Module | Description | Status |
|---|--------|-------------|--------|
| 1 | **Auth & RBAC** | Login, roles, permissions, MFA, session management | ✅ |
| 2 | **Customer Management** | Onboarding, KYC, MAC, zone, device, Excel import/export | ✅ |
| 3 | **Billing** | Pro-rata invoicing, Bangla receipts, advance balance, partial payments | ✅ |
| 4 | **MikroTik / NAS** | RouterOS API, PPPoE, bandwidth profiles, live sessions | ✅ |
| 5 | **RADIUS AAA** | FreeRADIUS, session tracking, usage analytics, audit logs | ✅ |
| 6 | **GPON / Fiber** | OLT (SNMP+Telnet), Splitters, ONUs, incidents | ✅ |
| 7 | **IP Pool Management** | CIDR pools, assignment tracking | ✅ |
| 8 | **Inventory** | Stock, purchase orders, warehouses, low-stock alerts | ✅ |
| 9 | **Reseller System** | Multi-level resellers, commission, balance top-up | ✅ |
| 10 | **MAC Reseller Portal** | Tariff plans, MAC-based clients, daily billing, payments | ✅ |
| 11 | **HR & Payroll** | Employees, departments, attendance, salary slips, appraisals, leave | ✅ |
| 12 | **Support & Ticketing** | SLA tracking, assignment + SMS, comment threads, compliance dashboard | ✅ |
| 13 | **Roles & Permissions UI** | Custom roles, granular per-module permissions, user assignment | ✅ |
| 14 | **Work Orders** | Kanban, technician dispatch, status tracking | ✅ |
| 15 | **Collection System** | Daily collection sessions, receipts | ✅ |
| 16 | **SMS (Bangla)** | SSL Wireless, BulkSMS BD, campaigns, templates | ✅ |
| 17 | **Finance** | Cashbook, expense tracking, daily summaries | ✅ |
| 18 | **Reports & Analytics** | Income, due, growth, RADIUS charts | ✅ |
| 19 | **Customer Portal** | Self-service: invoices, usage, tickets, AI chat | ✅ |
| 20 | **REST API** | Bearer token, full CRUD, JWT, push notifications | ✅ |
| 21 | **Automation** | Cron-based billing, suspension, reconnection, alerts | ✅ |
| 22 | **Docker** | Nginx + PHP-FPM + MySQL + Redis stack | ✅ |
| 23 | **Branch Management** | Multi-branch isolation, per-branch credentials, reports | ✅ |
| 24 | **Sales & Invoicing** | Installation fees, product/service invoices, partial payments | ✅ |
| 25 | **Purchase Management** | Vendors, requisitions, bills, vendor ledger | ✅ |
| 26 | **Network Diagram** | Leaflet.js map, POP/BOX nodes, topology visualization | 🔄 |
| 27 | **Accounts Management** | Expenses, income, bank accounts, P&L, balance sheet | 🔄 |
| 28 | **Asset Management** | Asset register, disposal, depreciation, warranty alerts | 🔄 |
| 29 | **Bandwidth Management** | Provider/reseller management, invoices, utilization | ✅ |
| 30 | **BTRC Reports** | Regulatory DIS report generation, CSV/PDF export | ✅ |
| 31 | **OTT Subscriptions** | Bundle management, auto-renewal, subscriber dashboard | ✅ |
| 32 | **Bulk SMS & Email** | Campaign manager, recipient filtering, scheduled sends | ✅ |

> ✅ Complete · 🔄 In Progress

---

## ⚡ Quick Start

```bash
# 1. Clone the repository
git clone https://github.com/dgjadoobd-dotcom/ispd.git
cd ispd

# 2. Copy environment file
cp .env.example .env

# 3. Run setup (auto-creates DB schema + admin account)
php setup.php

# 4. Start development server
php -S localhost:8000 -t public
```

Open **http://localhost:8000** and login with `admin` / `Admin@1234`

---

## 🛠 Installation

### Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.1+ (8.3 recommended for Ubuntu 24.04) |
| MySQL | 8.0+ (production) |
| SQLite | 3 (local/demo) |
| PHP Extensions | `pdo`, `pdo_mysql`, `mbstring`, `curl`, `gd`, `zip`, `sqlite3`, `redis` |
| Web Server | Apache (mod_rewrite) or Nginx |
| OS (production) | Ubuntu 24.04 LTS (Noble Numbat) recommended |

### Option A — XAMPP / Local (SQLite)

1. Place the project in `xampp/htdocs/ispd`
2. Copy `.env.example` → `.env` and set:
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=database/digital_isp.sqlite
   APP_URL=http://localhost/ispd/public
   ```
3. Enable `mod_rewrite` in `httpd.conf`:
   ```
   AllowOverride All
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
4. Run `php setup.php`
5. Visit **http://localhost/ispd/public/**

### Option B — cPanel / Shared Hosting (MySQL)

1. Upload and extract project to `/home/username/ispd`
2. Set domain document root → `/home/username/ispd/public`
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
   JWT_SECRET=your_random_secret_here
   ```
5. Set write permissions: `chmod 775 storage/ public/uploads/`

### Option C — VPS / Ubuntu 24.04 + Nginx (Recommended)

#### Step 1 — Provision the server (run once)

```bash
# Clone the repo first
cd /var/www
sudo git clone https://github.com/dgjadoobd-dotcom/ispd.git digital-isp
sudo chown -R www-data:www-data /var/www/digital-isp

# Run the one-time provisioning script (installs PHP 8.3, Nginx, MySQL client,
# Python venv, systemd service, logrotate, and Nginx site config)
cd /var/www/digital-isp
sudo bash setup-ubuntu24.sh
```

`setup-ubuntu24.sh` installs:
- `php8.3-fpm` + all required extensions (`mysql`, `mbstring`, `curl`, `gd`, `zip`, `xml`, `redis`, `sqlite3`)
- `nginx`, `mysql-client`, `git`, `curl`, `composer`
- `python3`, `python3-venv` — creates `agent/venv` and installs `agent/requirements.txt`
- systemd service `/etc/systemd/system/digital-isp-agent.service` (enabled, starts on boot)
- logrotate config `/etc/logrotate.d/digital-isp` (daily, 30-day retention, USR1 reload)
- Nginx site config symlinked to `/etc/nginx/sites-enabled/digital-isp`

#### Step 2 — Configure environment

```bash
sudo cp .env.example .env.production
sudo nano .env.production   # fill in DB_*, APP_KEY, JWT_SECRET, APP_URL
```

#### Step 3 — Deploy

```bash
sudo bash deploy-prod.sh
```

The deploy script automatically:
- Validates all required env vars (fails fast with a full list of issues)
- Checks PHP ≥ 8.1 and all required packages
- Backs up the database before pulling new code
- Runs `composer install --no-dev`
- Sets correct file permissions (`www-data:www-data`, `.env` → `root:www-data 640`)
- Applies SQL migrations idempotently via `_migrations` tracking table
- Installs cron jobs for `www-data` (idempotent, no duplicates)
- Reloads `php8.3-fpm` (falls back to 8.2 / 8.1 if needed)
- Runs a health check and shows the last 50 log lines on failure

#### Step 4 — Enable HTTPS (optional but recommended)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
# Then uncomment the HTTPS block in /etc/nginx/sites-available/digital-isp
sudo nginx -t && sudo systemctl reload nginx
```

#### Key differences from Ubuntu 22.04

| Area | Ubuntu 22.04 | Ubuntu 24.04 |
|------|-------------|-------------|
| Default PHP | 8.1 | 8.3 |
| PHP-FPM socket | `/run/php/php8.1-fpm.sock` | `/run/php/php8.3-fpm.sock` |
| PHP-FPM service | `php8.1-fpm` | `php8.3-fpm` |
| Docker Compose | `docker-compose` (v1) | `docker compose` (v2) |
| Python | 3.10 | 3.12 |

### Option D — Docker

```bash
cp .env.example .env
# Edit .env with your credentials
docker compose up -d        # Docker Compose v2 (Ubuntu 24.04)
# or: docker-compose up -d  # Docker Compose v1 (older systems)
```

Services: `nginx:80`, `php-fpm:9000`, `mysql:3306` (internal only), `redis:6379`, `phpmyadmin:8081`

The production stack (`docker-compose.prod.yml`) uses `php:8.3-fpm`, health checks on all services, and `depends_on: condition: service_healthy` so migrations only run after the database is ready.

---

## 📁 Project Structure

```
ispd/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── CustomerController.php
│   │   ├── BillingController.php
│   │   ├── NetworkController.php
│   │   ├── HrController.php          # HR & Payroll
│   │   ├── SupportController.php     # Support & Ticketing
│   │   ├── ModuleControllers.php     # Reseller, MAC Reseller, Inventory,
│   │   │                             # Role, WorkOrder, Finance, Settings...
│   │   └── CustomerPortal/           # Self-service portal controllers
│   ├── Services/
│   │   ├── HrService.php             # Salary calc, attendance, leave
│   │   ├── SupportService.php        # SLA tracking, assignment, compliance
│   │   ├── MikroTikService.php
│   │   ├── RadiusService.php
│   │   ├── SmsService.php
│   │   └── ...
│   ├── Helpers/
│   │   ├── PermissionHelper.php      # RBAC: hasPermission, requirePermission
│   │   └── ValidationHelper.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   └── ApiRateLimitMiddleware.php
│   └── Core/
│       ├── Router.php
│       ├── DatabasePool.php
│       └── Cache.php
├── config/
│   ├── app.php                       # Helpers, autoloader, session
│   └── database.php                  # PDO singleton + auto-migrations
├── database/
│   ├── schema.sql                    # MySQL full schema
│   ├── sqlite_schema.sql             # SQLite schema (auto-applied)
│   ├── radius_schema.sql             # FreeRADIUS tables
│   └── migrations/                   # Incremental migration files
├── docker/
│   ├── nginx/
│   │   ├── nginx-ubuntu24.conf       # Bare-metal Ubuntu 24.04 site config
│   │   └── nginx-prod.conf           # Docker production config
│   ├── php/
│   │   ├── php.ini                   # Production settings (display_errors Off)
│   │   └── php-dev.ini               # Development override (display_errors On)
│   └── ...                           # MySQL, monitoring configs
├── public/
│   ├── index.php                     # Front controller
│   └── assets/                       # CSS, JS, images, uploads
├── routes/
│   ├── web.php                       # All web routes
│   ├── api.php                       # REST API v1 routes
│   └── portal.php                    # Customer portal routes
├── views/
│   ├── layouts/main.php              # Shell: collapsible sidebar, dark/light
│   ├── customers/                    # Customer CRUD, import, list
│   ├── billing/                      # Invoices, payments, receipts
│   ├── hr/                           # Employees, attendance, payroll, slips
│   ├── support/                      # Tickets, SLA dashboard, comments
│   ├── roles/                        # Role management, permission editor
│   ├── reseller/                     # Reseller management
│   ├── mac-reseller/                 # MAC reseller + clients + billing
│   ├── network/                      # NAS, PPPoE, RADIUS, MAC bindings
│   ├── gpon/                         # OLT, Splitters, ONUs
│   ├── inventory/                    # Stock, purchase orders
│   ├── workorders/                   # Work order Kanban
│   ├── reports/                      # Income, due, collection, growth
│   ├── finance/                      # Cashbook, expenses
│   ├── portal/                       # Customer self-service portal
│   └── settings/                     # Users, branches, SMS, packages
├── scripts/
│   └── deploy-helpers.sh             # Shared shell functions for all deploy scripts
├── tests/
│   ├── Unit/
│   │   ├── AppConfigTest.php         # Timezone + debug mode config tests
│   │   ├── DatabaseMigrationTest.php # _migrations table idempotency test
│   │   ├── HrServiceTest.php         # 17 tests: salary, attendance, leave
│   │   └── ...
│   └── Integration/
├── agent/                            # Python WhatsApp bot + daily agent
├── setup-ubuntu24.sh                 # One-time Ubuntu 24.04 server provisioner
├── deploy-prod.sh                    # Production deploy script
├── deploy-staging.sh                 # Staging deploy script
├── deploy-dev.sh                     # Development deploy script
├── .env.example
└── docker-compose.yml
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

Response:
```json
{
  "token": "eyJ...",
  "refresh_token": "...",
  "expires_in": 86400
}
```

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/v1/auth/login` | Login — returns JWT + refresh token |
| `GET` | `/api/v1/dashboard/stats` | Dashboard statistics |
| `GET` | `/api/v1/customers` | List customers (paginated, filterable) |
| `GET` | `/api/v1/customers/search?q=` | Search customers |
| `GET` | `/api/v1/customers/{id}` | Customer detail with invoices |
| `POST` | `/api/v1/payments` | Record payment |
| `GET` | `/api/v1/collections/today` | Today's collections |
| `GET` | `/api/v1/workorders` | Work orders list |
| `POST` | `/api/v1/workorders` | Create work order |
| `POST` | `/api/v1/workorders/{id}/status` | Update work order status |
| `GET` | `/api/v1/notifications` | User notifications |
| `GET` | `/api/v1/radius/users` | RADIUS users |
| `GET` | `/api/v1/radius/sessions` | Active RADIUS sessions |

---

## 🛡️ Roles & Permissions

The system ships with 4 built-in roles and a full UI for creating custom roles:

| Role | Access |
|------|--------|
| `superadmin` | Full access — all modules, all branches |
| `comadmin` | Company admin — all branches, no role deletion |
| `branch_admin` | Branch-scoped — own branch data only |
| `employee` | Limited — assigned tasks, tickets, collections |

**Custom roles** can be created at `/roles` with granular per-module permissions:

```
customers.view    customers.create    customers.edit    customers.delete
billing.view      billing.create      billing.payments
hr.view           hr.payroll          hr.attendance     hr.appraisal
support.view      support.assign      support.resolve   support.reports
inventory.view    inventory.issue     inventory.transfer
reseller_portal.view  mac_reseller.billing  ...
```

Seed all default permissions with one click from the Roles dashboard.

---

## 👔 HR & Payroll

- **Employees** — profiles with department, designation, salary grade, bank account, NID, emergency contact
- **Attendance** — daily recording (present/absent/late/half_day/leave), monthly calendar view
- **Salary Calculation** — `gross = basic_salary + allowances − (absent_day_deductions)`
- **Salary Slips** — printable PDF with earnings/deductions breakdown, signature area
- **Performance Appraisals** — rating 1–5, reviewer, review period, comments
- **Leave Balances** — annual/sick/casual tracking per employee per year

---

## 🎫 Support & Ticketing

- **SLA Deadlines** — urgent: 2h · high: 8h · medium: 24h · low: 72h
- **Auto SLA Breach** — cron or manual check marks overdue tickets as `sla_breached`
- **Assignment + SMS** — assign to any active employee, SMS notification sent automatically
- **Comment Threads** — full activity log per ticket with internal/external flag
- **Resolution Recording** — notes, resolver identity, resolved timestamp
- **SLA Compliance Dashboard** — % within SLA per category and per employee with progress bars

---

## 🖥️ MAC Reseller Portal

- Create MAC resellers with tariff plans (daily/monthly rates, speed)
- Add clients by MAC address with duplicate detection
- Generate daily billing for all active clients in one click
- Mark bills as paid, track balance and transaction history
- Suspend/reactivate clients individually

---

## 📥 Excel Import (Customers)

The import system supports CSV and XLSX files with 30 columns matching the standard ISP client list format:

| Column | Required | Notes |
|--------|----------|-------|
| `Client Name` | ✅ | Full name |
| `Mobile` | ✅ | Must be unique |
| `ID/IP` | Optional | PPPoE username — must be unique |
| `MACAddress` | Optional | `AA:BB:CC:DD:EE:FF` — must be unique |
| `Package` | Optional | Must match existing package name |
| `Zone` | Optional | Must match existing zone name |
| `B.Status` | Optional | Active / Inactive |
| `ClientJoiningDate` | Optional | YYYY-MM-DD or MM/DD/YYYY |
| ... | | 22 more optional columns |

Download the demo template from the Import modal. Rejected rows are shown with row numbers and reasons.

---

## 🔐 Security

- Passwords hashed with `bcrypt` (cost 12)
- All DB queries via PDO prepared statements (SQL injection safe)
- XSS prevention via `htmlspecialchars()` on all output
- Role-based access control (RBAC) enforced on every route and controller action
- IP access control rules (allowlist/blocklist)
- Multi-factor authentication (MFA) support
- Rate limiting on API and login endpoints
- Full audit trail for all data mutations
- `.env` excluded from version control
- Session timeout enforcement

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
| **bKash** | Mobile banking payment gateway |
| **PipraPay** | Self-hosted payment gateway |
| **FCM** | Firebase Cloud Messaging for push notifications |

---

## ⚙️ Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | `local` / `staging` / `production` | `local` |
| `APP_URL` | Base URL (no trailing slash) | `http://localhost:8000` |
| `APP_DEBUG` | Show errors — **must be `false` in production** | `true` |
| `APP_TIMEZONE` | PHP timezone, applied via `date_default_timezone_set()` | `Asia/Dhaka` |
| `DB_CONNECTION` | `mysql` or `sqlite` | `sqlite` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_DATABASE` | Database name / SQLite path | `database/digital_isp.sqlite` |
| `RADIUS_HOST` | FreeRADIUS host | `127.0.0.1` |
| `SMS_GATEWAY` | SMS provider | `sslwireless` |
| `SMS_API_KEY` | SMS gateway API key | — |
| `JWT_SECRET` | API token secret — **min 32 chars** | *(set this!)* |
| `AI_ENABLED` | Enable AI features | `true` |
| `AI_BASE_URL` | LM Studio / OpenAI-compatible API | `http://localhost:1234/v1` |

See `.env.example` for the full list.

---

## 🚀 Cron Jobs

Cron jobs are installed automatically by `deploy-prod.sh` under the `www-data` user. To install manually:

```bash
sudo bash -c 'source scripts/deploy-helpers.sh && install_cron_jobs /var/www/digital-isp'
```

Installed schedule:

```cron
# digital-isp-cron
0 0    * * * /usr/bin/php8.3 /var/www/digital-isp/cron_automation.php >> .../automation_cron.log 2>&1
0 8    * * * /usr/bin/php8.3 /var/www/digital-isp/cron_automation.php due-reminders >> ...
0 */6  * * * /usr/bin/php8.3 /var/www/digital-isp/cron_automation.php suspend >> ...
5 0    * * * /usr/bin/php8.3 /var/www/digital-isp/cron_radius_rollup.php >> ...
10 0   * * * /usr/bin/php8.3 /var/www/digital-isp/cron_selfhosted_piprapay.php >> ...
# end-digital-isp-cron
```

The PHP binary path is detected automatically (`/usr/bin/php8.3` → `8.2` → `8.1` fallback). Running the deploy script again replaces the block without creating duplicates.

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

# Run with coverage
./vendor/bin/phpunit --coverage-text
```

Current test coverage includes:
- **App config**: timezone application from env, debug mode off in production
- **Database migrations**: `_migrations` tracking table idempotency (SQLite in-memory)
- HR module: salary calculation, attendance validation, leave balance, employee-user relationship (17 tests)
- RADIUS: bulk operations, session management, analytics
- Security: IP access control, rate limiting, MFA
- Validation helpers

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

This project is licensed under the **Apache License 2.0**. See the [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Author

**FCNCHBD ISP ERP Team**  
📧 admin@bkdnet.xyz  
🌐 [github.com/dgjadoobd-dotcom/ispd](https://github.com/dgjadoobd-dotcom/ispd)

---

<div align="center">
  <sub>Built with ❤️ for Bangladesh ISPs</sub>
</div>
