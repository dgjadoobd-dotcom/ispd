# Digital ISP ERP — README

Welcome to **Digital ISP ERP** — Enterprise-grade ISP Management System for Bangladesh ISPs.

## 🚀 Quick Setup

This system supports both MySQL and SQLite. Use MySQL for production and SQLite for a fast local/demo install.

### Quick Start Checklist

- [ ] Copy `.env.example` to `.env` and configure database settings
- [ ] Run `php setup.php` to initialize the system automatically
- [ ] Access at `http://localhost/ispd/public/` or start PHP server with `php -S 127.0.0.1:8088 -t public`
- [ ] Login with `admin` / `admin`

### Detailed Setup

#### Option A: Automatic Setup (Recommended)
```bash
# 1. Configure environment
cp .env.example .env
# Edit .env with your database settings

# 2. Run setup script
php setup.php
```

#### Option B: Manual Setup

##### 1. Database Setup

###### MySQL
```sql
-- Create database in phpMyAdmin or MySQL client
CREATE DATABASE digital_isp.sqlite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE digital_isp.sqlite;
SOURCE c:/xampp/htdocs/ispd/database/schema.sql;
```

###### SQLite (recommended for local testing)
- Ensure the `database/` folder is writable.
- Set `DB_CONNECTION=sqlite` and `DB_DATABASE=database/digital_isp.sqlite` in `.env`.
- The app will auto-create the schema from `database/sqlite_schema.sql`.

##### 2. Environment Config
Copy `.env.example` to `.env` and update values for your environment.

Example values:
```env
APP_NAME="Digital ISP ERP"
APP_URL=http://127.0.0.1:8088
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=Asia/Dhaka

DB_CONNECTION=sqlite
DB_DATABASE=database/digital-isp.sqlite
DB_USERNAME=root
DB_PASSWORD=
```

> Do not commit `.env` to version control. Use `.env.example` as the shared template.
>
> If you see a `config_items` table error after schema import, run:
> ```bash
> php create_config_table.php
> ```
>
##### 3. Enable mod_rewrite (XAMPP)
In `xampp/apache/conf/httpd.conf`, ensure:
```
AllowOverride All
LoadModule rewrite_module modules/mod_rewrite.so
```

##### 4. Access the System

**Option A: Using XAMPP Apache**
- **URL:** http://localhost/ispd/public/
*(Use `localhost:8081` if your Apache port is customized)*

**Option B: Using PHP Built-in Server**
1. Open terminal in the `ispd` directory
2. Run: `php -S 127.0.0.1:8088 -t public`
3. **URL:** http://localhost:8088/

- **Admin Login:** `admin` / `Admin@1234`

##### 5. Client Portal (Customer Self-Service)
- **URL:** `http://localhost/ispd/public/portal/` *(or `http://localhost:8088/portal/` via PHP Built-in server)*
- **Login:** Customer's Email, Phone, or PPPoE Username
- **Password:** Customer's Portal Password or PPPoE Password
*(Create a customer from the admin panel first to test this)*

---

## 📁 Project Structure

```
ispd/
├── app/
│   ├── Controllers/        # AuthController, CustomerController, BillingController...
│   ├── Services/           # MikroTikService, SmsService
│   ├── Middleware/         # AuthMiddleware, ApiMiddleware
│   └── Core/               # Router
├── config/
│   ├── app.php             # App config, helpers, autoloader
│   └── database.php        # PDO singleton
├── database/
│   ├── schema.sql          # MySQL schema
│   └── sqlite_schema.sql   # SQLite schema for local installs
├── public/
│   ├── index.php           # Front controller
│   └── assets/             # CSS, JS, images, uploads/
├── routes/
│   ├── web.php             # Web routes
│   └── api.php             # REST API v1 routes
├── views/
│   ├── layouts/main.php    # Main SPA shell (dark/light, sidebar, header)
│   ├── auth/login.php      # Login page
│   ├── dashboard/          # Dashboard with charts
│   ├── customers/          # Customer CRUD
│   ├── billing/            # Invoices, payments, Bangla receipt
│   ├── network/            # IP pools, NAS, Radius
│   ├── gpon/               # OLT, Splitters, ONUs, Incidents
│   ├── inventory/          # Stock, purchases
│   ├── reseller/           # Reseller management
│   ├── workorders/         # Work order Kanban
│   ├── reports/            # Income, due, collection, growth
│   ├── finance/            # Cashbook, expenses
│   └── settings/           # Users, branches, SMS gateways
└── .env.example            # Environment template
```

---

## 🔌 REST API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | Login (returns Bearer token) |
| GET | `/api/v1/dashboard/stats` | Dashboard statistics |
| GET | `/api/v1/customers/search?q=` | Search customers |
| GET | `/api/v1/customers/{id}` | Customer detail |
| GET | `/api/v1/customers/{id}/invoices` | Customer invoices |
| POST | `/api/v1/payments` | Record payment |
| GET | `/api/v1/collections/today` | Today's collections |
| GET | `/api/v1/workorders` | Work orders list |
| POST | `/api/v1/workorders` | Create work order |
| POST | `/api/v1/workorders/{id}/status` | Update WO status |
| GET | `/api/v1/notifications` | User notifications |

**Auth:** Add `Authorization: Bearer {token}` header to all `/api/v1/` requests.

---

## 🔑 Default Accounts

| Role | Username | Password |
|------|----------|----------|
| Super Admin | `admin` | `Admin@1234` |

---

## 📱 MikroTik API Integration

Edit the NAS device setup in Settings → MikroTik / NAS. The `MikroTikService` uses the RouterOS binary API (port 8728) to:
- Add/remove/disable PPPoE users
- Change bandwidth profiles
- Get active sessions
- Kick sessions on suspension

---

## 📲 SMS Integration (Bangladesh)

Configure SMS gateway in Settings → SMS Gateways. Supports:
- **SSL Wireless** (default API format)
- **BulkSMS BD**
- Any HTTP GET/POST based gateway

Bangla templates pre-loaded for: Bill Generated, Payment Received, Due Reminder, Welcome.

---

## 🛡️ Security Features

- Password hashing with `bcrypt` (cost 12)
- Session-based auth for web
- Token-based auth for API (24h expiry)
- SQL injection prevention via PDO prepared statements
- XSS prevention via `htmlspecialchars()`
- Role-based access control (RBAC)
- Activity logs for all mutations
- Directory listing disabled

---

## 🚀 Deployment Guide

### cPanel / Shared Hosting
1. **Upload Files:** Compress the `ispd` folder into a `.zip` file, upload it to your File Manager, and extract it in your home directory (e.g., `/home/username/ispd`).
2. **Document Root:** To keep your core files secure, do not put everything in `public_html`. Instead, go to cPanel -> **Domains** and change the document root of your domain/subdomain to point to `/home/username/ispd/public`.
3. **Database:** Create a new MySQL Database and User in cPanel. Assign all privileges.
4. **Import Schema:** Open phpMyAdmin and import `database/schema.sql` into your new database.
5. **Configuration:** Rename or edit the `.env` file in the project root with your live database credentials:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   DB_DATABASE=your_cpanel_db
   DB_USERNAME=your_cpanel_user
   DB_PASSWORD=your_password
   ```
6. **Permissions:** Ensure the `tmp/` and directory and `public/assets/uploads/` (if dealing with image uploads) have the correct write permissions (chmod `775` or `755`).

### VPS / Dedicated Server (Ubuntu + Nginx)
1. **Clone Project:** Place the project directory at `/var/www/ispd`.
2. **Nginx Config:** Create an Nginx server block pointing to the `public/` directory:
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
           fastcgi_pass unix:/var/run/php/php7.4-fpm.sock; # match your PHP version
       }
   }
   ```
3. **Permissions:** Set ownership so the web server can write to temporary and upload directories:
   ```bash
   sudo chown -R www-data:www-data /var/www/ispd
   sudo chmod -R 775 /var/www/ispd/tmp
   ```

---

## 📊 Module Summary

| # | Module | Status |
|---|--------|--------|
| 1 | User & Auth (RBAC) | ✅ Complete |
| 2 | Customer Management | ✅ Complete |
| 3 | Billing (pro-rata, Bangla receipt) | ✅ Complete |
| 4 | Network (IP Pool, MikroTik, Radius) | ✅ Complete |
| 5 | GPON / Fiber (OLT→Splitter→ONU) | ✅ Complete |
| 6 | Inventory | ✅ Complete |
| 7 | Reseller System | ✅ Complete |
| 8 | Collection System | ✅ Complete |
| 9 | SMS (Bangla) | ✅ Complete |
| 10 | Reports & Analytics | ✅ Complete |
| 11 | Work Orders | ✅ Complete |
| 12 | Finance (Cashbook, Expenses) | ✅ Complete |
| 13 | REST API | ✅ Complete |
| 14 | Dark/Light Mode Dashboard | ✅ Complete |
