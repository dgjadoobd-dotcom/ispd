# FCN ISP ERP - Complete Route Reference

## All Working Routes

### Authentication
- `/` - Login page
- `/login` - Login form
- `/logout` - Logout
- `/admin` - Admin login (alt)

### Main Dashboard
- `/dashboard` - Main dashboard
- `/dashbord` - Dashboard (alt spelling)

### Customer Management
- `/customers` - Customer list
- `/customers/create` - New customer
- `/customers/view/{id}` - View customer
- `/customers/edit/{id}` - Edit customer
- `/customers/requests` - Signup requests
- `/customers/search` - Search API

### Billing
- `/billing` - Billing index
- `/billing/invoices` - All invoices
- `/billing/invoice/{id}` - View invoice
- `/billing/pay/{id}` - Payment form
- `/billing/receipt/{id}` - Print receipt
- `/billing/cashbook` - Cashbook

### Network
- `/network` - Network index
- `/network/nas` - NAS/MikroTik list
- `/network/radius` - RADIUS users
- `/network/radius/profiles` - RADIUS profiles
- `/network/live-sessions` - Live sessions
- `/network/online-clients` - Online clients
- `/network/mac-bindings` - MAC bindings
- `/network/ip-pools` - IP pools

### GPON/Fiber
- `/gpon` - GPON index
- `/gpon/olts` - OLT devices
- `/gpon/onus` - ONU devices
- `/gpon/splitters` - Splitters

### Configuration
- `/configuration` - Zones, POPs, Packages
- `/configuration/zones/create`
- `/configuration/pops/create`
- `/configuration/packages/create`

### Bandwidth
- `/bandwidth` - Bandwidth index
- `/bandwidth/providers` - ISPs
- `/bandwidth/resellers` - Resellers
- `/bandwidth/purchases` - Purchases
- `/bandwidth/invoices` - Invoices

### Purchases
- `/purchases` - Purchase index
- `/purchases/vendors` - Vendor list
- `/purchases/bills` - Bills
- `/purchases/ledger` - Vendor ledger

### Sales
- `/sales` - Sales index
- `/sales/invoices` - Sales invoices
- `/sales/create` - New invoice
- `/sales/payments` - Payment records

### HR & Payroll
- `/hr` - HR index
- `/hr/employees` - Employee list
- `/hr/attendance` - Attendance
- `/hr/payroll` - Payroll
- `/hr/departments` - Departments

### Support
- `/support` - Support index
- `/support/tickets` - Ticket list
- `/support/tickets/create` - New ticket
- `/support/dashboard` - Support dashboard

### Tasks
- `/tasks` - Task index
- `/tasks/list` - Task list
- `/tasks/create` - New task
- `/tasks/calendar` - Calendar view
- `/tasks/reports` - Task reports

### Inventory
- `/inventory` - Inventory index
- `/inventory/stock` - Stock items
- `/inventory/purchases` - Purchase orders
- `/inventory/suppliers` - Suppliers

### Resellers
- `/resellers` - Reseller list
- `/resellers/create` - New reseller
- `/resellers/view/{id}` - View reseller

### MAC Resellers
- `/mac-resellers` - MAC reseller list
- `/mac-resellers/clients` - MAC clients

### Work Orders
- `/workorders` - Work order list
- `/workorders/create` - New work order

### Reports
- `/reports` - Report index
- `/reports/income` - Income report
- `/reports/due` - Due report
- `/reports/collection` - Collection report

### BTRC Reports
- `/reports/btrc` - BTRC report
- `/reports/btrc/generate` - Generate report

### Finance
- `/finance` - Finance index
- `/finance/cashbook` - Cashbook
- `/finance/expenses` - Expenses

### Automation
- `/automation` - Automation index
- `/automation/logs` - Job logs

### Communication
- `/comms` - Communication index
- `/comms/bulk` - Bulk SMS
- `/comms/templates` - SMS templates

### Settings
- `/settings` - Settings index
- `/settings/profiles` - PPPoE profiles

### Branch Management
- `/branches` - Branch list
- `/branches/create` - New branch

### Roles & Permissions
- `/roles` - Role list
- `/roles/create` - New role

### Admin API Routes
- `/admin/api/` - Dashboard API
- `/admin/api/stats` - Live stats
- `/admin/proxy/` - Proxy routes
- `/admin/piprapay/` - Piprapay admin

### Customer Portal
- `/portal` - Customer portal
- `/portal/support` - Portal tickets
- `/portal/usage` - Usage data

### Payment
- `/payment/piprapay/*` - Piprapay callbacks
- `/payment/selfhosted/*` - Self-hosted payment

### OTT/MAC Reseller
- `/ott` - OTT index
- `/ott/providers` - ISP providers
- `/ott/packages` - Tariff packages
- `/ott/subscriptions` - Subscriptions