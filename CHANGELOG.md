# Changelog

All notable changes to Digital ISP ERP will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2024-01-?? (Initial Release)

### Added
- **Web Installer** - Professional web-based installation (`install.php`)
- **Ubuntu 24.04 Support** - One-command provisioning (`setup-ubuntu24.sh`)
- **Docker Multi-stage** - dev, test, staging, prod Dockerfiles
- **Customer Management** - Full lifecycle with KYC, MAC, zone tracking
- **Billing & Invoicing** - Pro-rata, Bangla receipts, partial payments
- **MikroTik Integration** - RouterOS API, PPPoE session management
- **RADIUS AAA** - FreeRADIUS integration, session tracking
- **GPON/Fiber** - OLT management via SNMP/Telnet
- **Inventory** - Stock, purchase orders, warehouse tracking
- **Reseller System** - Multi-level, commission, balance top-up
- **MAC Reseller Portal** - Tariff plans, MAC-based clients
- **HR & Payroll** - Employees, departments, salary slips
- **Support Ticketing** - SLA tracking, assignment, SMS notifications
- **Roles & Permissions** - Granular RBAC UI
- **Work Orders** - Kanban board, technician dispatch
- **SMS (Bangla)** - SSL Wireless, BulkSMS BD integration
- **Finance** - Cashbook, expense tracking
- **Reports** - Income, collection, due, growth analytics
- **Customer Portal** - Self-service: invoices, usages, tickets
- **REST API** - Full CRUD with Bearer token auth
- **Automation** - Cron-based billing, suspension, alerts

### Security
- RBAC with bcrypt password hashing
- PDO prepared statements (no SQL injection)
- IP access control
- MFA support
- Audit trail logging
- API rate limiting
- Restricted CORS

### Infrastructure
- MySQL 8.0+ and SQLite 3 support
- Redis caching (optional)
- Custom MVC router (no framework bloat)
- Tailwind CSS frontend
- PHPUnit test suite
- PHPStan static analysis

---

## [Unreleased]

### Known Issues
- None reported

---

## Upgrading from Previous Versions

See [docs/UPGRADE.md](docs/UPGRADE.md) for detailed upgrade instructions.

---

## Reporting Security Issues

For security vulnerabilities, DO NOT open a public issue.
Email: security@digitalisp.xyz

---

*This changelog is maintained starting from v1.0.0*