<div align="center">

<img src="https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white" />
<img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
<img src="https://img.shields.io/badge/SQLite-3-003B57?style=for-the-badge&logo=sqlite&logoColor=white" />
<img src="https://img.shields.io/badge/MikroTik-RouterOS-FF6600?style=for-the-badge&logo=mikrotik&logoColor=white" />
<img src="https://img.shields.io/badge/PHPUnit-10-6C3483?style=for-the-badge&logo=php&logoColor=white" />
<img src="https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge" />

# 🌐 Digital ISP ERP

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
- 🐳 **Docker Ready** — Full docker-compose stack with Nginx, PHP-FPM, MySQL, Redis
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
| 23 | **Branch Management** | Multi-branch isolation, per-branch credentials, reports | 🔄 |
| 24 | **Sales & Invoicing** | Installation fees, product/service invoices, partial payments | 🔄 |
| 25 | **Purchase Management** | Vendors, requisitions, bills, vendor ledger | 🔄 |
| 26 | **Network Diagram** | Leaflet.js map, POP/BOX nodes, topology visualization | 🔄 |
| 27 | **Accounts Management** | Expenses, income, bank accounts, P&L, balance sheet | 🔄 |
| 28 | **Asset Management** | Asset register, disposal, depreciation, warranty alerts | 🔄 |
| 29 | **Bandwidth Management** | Provider/reseller management, invoices, utilization | 🔄 |
| 30 | **BTRC Reports** | Regulatory DIS report generation, CSV/PDF export | 🔄 |
| 31 | **OTT Subscriptions** | Bundle management, auto-renewal, subscriber dashboard | 🔄 |
| 32 | **Bulk SMS & Email** | Campaign manager, recipient filtering, scheduled sends | 🔄 |

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
| PHP | 8.1+ |
| MySQL | 8.0+ (production) |
| SQLite | 3 (local/demo) |
| PHP Extensions | `pdo`, `pdo_mysql`, `mbstring`, `curl`, `gd`, `zip`, `snmp` |
| Web Server | Apache (mod_rewrite) or Nginx |

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

### Option C — VPS / Ubuntu 24.04 + Nginx

```bash
# Install dependencies
sudo apt update && sudo apt install -y nginx mysql-server \
  php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-curl \
  php8.3-gd php8.3-snmp php8.3-xml php8.3-zip unzip curl

# Create database
sudo mysql -u root -p -e "
  CREATE DATABASE digitalisp;
  CREATE USER 'digitalisp'@'localhost' IDENTIFIED BY 'your_password';
  GRANT ALL PRIVILEGES ON digitalisp.* TO 'digitalisp'@'localhost';
  FLUSH PRIVILEGES;"

# Clone and setup
cd /var/www
sudo git clone https://github.com/dgjadoobd-dotcom/ispd.git
sudo chown -R www-data:www-data /var/www/ispd
cd /var/www/ispd
sudo cp .env.example .env && sudo nano .env
sudo mysql -u digitalisp -p digitalisp < database/schema.sql
sudo php setup.php
```

Nginx config (`/etc/nginx/sites-available/ispd`):
```nginx
server {
    listen 80;
    server_name isp.yourdomain.com;
    root /var/www/ispd/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

### Option D — Docker

```bash
cp .env.example .env
docker-compose up -d
```

Services: `nginx:80`, `php-fpm:9000`, `mysql:3306`, `redis:6379`, `phpmyadmin:8081`

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
├── docker/                           # Nginx, PHP, MySQL, monitoring configs
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
├── tests/
│   ├── Unit/
│   │   ├── HrServiceTest.php         # 17 tests: salary, attendance, leave
│   │   └── ...
│   └── Integration/
├── agent/                            # Python WhatsApp bot + daily agent
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
| `APP_ENV` | `local` / `production` | `local` |
| `APP_URL` | Base URL | `http://localhost:8000` |
| `APP_DEBUG` | Show errors | `true` |
| `DB_CONNECTION` | `mysql` or `sqlite` | `sqlite` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_DATABASE` | Database name / path | `database/digital_isp.sqlite` |
| `RADIUS_HOST` | FreeRADIUS host | `127.0.0.1` |
| `SMS_GATEWAY` | SMS provider | `sslwireless` |
| `SMS_API_KEY` | SMS gateway API key | — |
| `JWT_SECRET` | API token secret | *(set this!)* |
| `AI_ENABLED` | Enable AI features | `true` |
| `AI_BASE_URL` | LM Studio / OpenAI-compatible API | `http://localhost:1234/v1` |

See `.env.example` for the full list.

---

## 🚀 Cron Jobs

```cron
# Daily billing automation (midnight)
0 0 * * * php /var/www/ispd/cron_automation.php

# RADIUS usage rollup (every hour)
0 * * * * php /var/www/ispd/cron_radius_rollup.php

# Self-hosted payment sync (every 5 minutes)
*/5 * * * * php /var/www/ispd/cron_selfhosted_piprapay.php
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

# Run with coverage
./vendor/bin/phpunit --coverage-text
```

Current test coverage includes:
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

This project is **proprietary software**. All rights reserved.  
Unauthorized copying, distribution, or modification is prohibited.

---

## 👨‍💻 Author

**Digital ISP ERP Team**  
📧 admin@bkdnet.xyz  
🌐 [github.com/dgjadoobd-dotcom/ispd](https://github.com/dgjadoobd-dotcom/ispd)

---

<div align="center">
  <sub>Built with ❤️ for Bangladesh ISPs</sub>
</div>
