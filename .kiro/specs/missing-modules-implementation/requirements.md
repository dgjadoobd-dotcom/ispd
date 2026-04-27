# Requirements Document

## Introduction

This document defines requirements for implementing 20 missing modules in the FCNCHBD ISP ERP system — a PHP 8+ MVC application (no framework, custom router) using SQLite/MySQL, Tailwind CSS views, and an existing `Database` class with `fetchOne`/`fetchAll`/`insert`/`update`/`delete` methods. The system already has Customer Management, Billing, MikroTik/RADIUS integration, Customer Portal, SMS, Automation, Payment Gateway, Dashboard, OLT/ONU, Webhooks, AI, MFA, IP Access Control, Rate Limiting, Audit Logging, and Bulk Operations.

The 20 modules below extend the system to cover branch management, HR, support, tasks, sales, purchasing, inventory, network diagrams, accounting, assets, bandwidth reselling, portals, employee tools, regulatory reporting, OTT, configuration, RBAC UI, bulk communications, and a mobile API.

---

## Glossary

- **System**: The FCNCHBD ISP ERP application
- **Branch**: A physical office or operational unit of the ISP
- **ComAdmin**: Company-level administrator role with access to all branches
- **BranchAdmin**: Branch-level administrator restricted to their own branch data
- **Employee**: A staff member linked to a user account and a branch
- **Reseller**: A third-party agent who sells ISP services and manages their own clients
- **MAC_Reseller**: A reseller who operates under a MAC-address-based billing model
- **Bandwidth_Provider**: An upstream ISP or transit provider from whom bandwidth is purchased
- **Bandwidth_Reseller**: A downstream entity to whom bandwidth is sold
- **Ticket**: A support request raised by a customer or employee
- **SLA**: Service Level Agreement — the maximum time allowed to resolve a ticket by priority
- **Task**: A scheduled unit of work assigned to an employee
- **POP**: Point of Presence — a network aggregation node
- **BOX**: A street-level distribution cabinet or junction box
- **Voucher**: A document recording a financial transaction (debit or credit)
- **Ledger**: A running account balance record for a reseller or vendor
- **OTT**: Over-The-Top streaming service subscription bundled with internet packages
- **BTRC**: Bangladesh Telecommunication Regulatory Commission
- **DIS**: BTRC's FCNCHBD ISP reporting format
- **Permission**: A named capability (e.g., `billing.view`, `customers.edit`) that can be granted to a role
- **Role**: A named collection of permissions assigned to users
- **Campaign**: A bulk SMS or email broadcast targeting a filtered set of recipients
- **Push_Notification**: A server-sent message delivered to a mobile device via FCM
- **FCM**: Firebase Cloud Messaging — Google's push notification service
- **JWT**: JSON Web Token used for stateless API authentication

---

## Requirements

---

### Requirement 1: Multiple Branch Management

**User Story:** As a ComAdmin, I want to manage multiple branches with isolated data and per-branch credentials, so that each branch operates independently while I retain a consolidated view.

#### Acceptance Criteria

1. THE System SHALL provide a `BranchController` with CRUD operations for branches, including name, code, address, phone, email, and manager fields.
2. WHEN a user with the `comadmin` role logs in, THE System SHALL display data from all branches without restriction.
3. WHEN a user with the `branch_admin` role logs in, THE System SHALL restrict all data queries to that user's assigned `branch_id`.
4. THE System SHALL allow assigning a unique login credential (username, password) to each branch via the `users` table with `branch_id` set.
5. THE System SHALL generate per-branch summary reports covering customer count, monthly revenue, outstanding dues, and active tickets, filterable by date range.
6. WHEN a branch is deactivated, THE System SHALL prevent new customers and invoices from being created under that branch.
7. THE Branch_Report SHALL export to PDF and CSV formats.
8. IF a branch code already exists, THEN THE System SHALL reject the duplicate and return a validation error message.

---

### Requirement 2: HR & Payroll

**User Story:** As an HR manager, I want to manage employee profiles, departments, attendance, and salary generation, so that payroll is accurate and HR records are centralised.

#### Acceptance Criteria

1. THE System SHALL maintain a `departments` table with name, branch, and head-of-department fields.
2. THE System SHALL maintain a `designations` table with title, department, and grade fields.
3. THE System SHALL maintain an `employees` table linked to `users`, storing joining date, designation, department, salary grade, bank account, NID, and emergency contact.
4. WHEN an employee record is created, THE System SHALL automatically create a linked `users` account if one does not already exist.
5. THE System SHALL record daily attendance with status values of `present`, `absent`, `late`, `half_day`, or `leave`.
6. WHEN monthly salary is generated, THE System SHALL calculate gross pay as (basic_salary + allowances − deductions), using the attendance record for the month.
7. THE Payroll_Generator SHALL produce a salary slip per employee in PDF format containing employee name, designation, month, earnings breakdown, deductions, and net pay.
8. THE System SHALL support performance appraisal records with rating (1–5), reviewer, review period, and comments fields.
9. IF a salary slip has already been generated for an employee for a given month, THEN THE System SHALL prevent duplicate generation and display an error.
10. THE System SHALL track leave balances per employee per leave type (annual, sick, casual).

---

### Requirement 3: Support & Ticketing (Admin Side)

**User Story:** As a support manager, I want to manage tickets, assign them to employees, track SLA compliance, and categorise problems, so that customer issues are resolved within agreed timeframes.

#### Acceptance Criteria

1. THE System SHALL display all open tickets in a paginated admin list with columns for ticket number, customer, category, priority, assigned employee, SLA deadline, and status.
2. WHEN a ticket is created, THE System SHALL set the SLA deadline based on priority: urgent = 2 hours, high = 8 hours, medium = 24 hours, low = 72 hours.
3. WHEN the current time exceeds the SLA deadline and the ticket status is not `resolved` or `closed`, THE System SHALL mark the ticket as `sla_breached`.
4. THE System SHALL allow assigning a ticket to any active employee via a dropdown.
5. WHEN a ticket is assigned, THE System SHALL send an SMS notification to the assigned employee's phone number using the existing `SmsService`.
6. THE System SHALL support problem categories including `no_connection`, `slow_speed`, `billing_dispute`, `hardware_fault`, `new_request`, and `other`.
7. THE System SHALL record a full comment/activity thread per ticket, with author, timestamp, and message.
8. WHEN a ticket is resolved, THE System SHALL record resolution notes, resolver identity, and resolved timestamp.
9. THE System SHALL display an SLA compliance dashboard showing percentage of tickets resolved within SLA per category and per employee.
10. IF a customer submits a duplicate ticket for the same issue within 24 hours, THEN THE System SHALL warn the agent but allow creation.

---

### Requirement 4: Task Management

**User Story:** As a manager, I want to plan, schedule, and track daily tasks for employees, so that work is organised and completion history is maintained.

#### Acceptance Criteria

1. THE System SHALL maintain a `tasks` table with title, description, assigned employee, assigned by, due date, priority, status, and branch fields.
2. WHEN a task is created, THE System SHALL set status to `pending` by default.
3. THE System SHALL allow employees to update task status to `in_progress`, `completed`, or `cancelled` with an optional completion note.
4. THE System SHALL display a daily task calendar view showing tasks grouped by employee and due date.
5. THE System SHALL maintain a `task_history` log recording every status change with actor, timestamp, and note.
6. WHEN a task due date passes and status is still `pending` or `in_progress`, THE System SHALL flag the task as `overdue`.
7. THE System SHALL allow bulk task assignment to multiple employees for recurring tasks.
8. THE Task_Report SHALL show completion rate per employee per week, exportable as CSV.

---

### Requirement 5: Sales & Service Invoicing

**User Story:** As a sales agent, I want to generate installation fee invoices, product invoices, and service invoices separately from monthly billing, so that one-time and ad-hoc charges are tracked independently.

#### Acceptance Criteria

1. THE System SHALL maintain a `sales_invoices` table with invoice number, customer, branch, invoice type (`installation`, `product`, `service`), line items, subtotal, discount, VAT, total, and payment status.
2. WHEN an installation fee invoice is created, THE System SHALL link it to the customer's `connection_date` and record the OTC (one-time charge) amount.
3. THE System SHALL support multi-line items per invoice, each with description, quantity, unit price, and line total.
4. WHEN a sales invoice is fully paid, THE System SHALL update its status to `paid` and create a corresponding `cashbook_entries` credit record.
5. THE System SHALL generate a printable PDF invoice using the company's configured invoice template.
6. THE System SHALL allow partial payments against a sales invoice, updating `paid_amount` and `due_amount` accordingly.
7. IF a sales invoice is cancelled, THEN THE System SHALL reverse any associated cashbook entries and set status to `cancelled`.

---

### Requirement 6: Purchase Management

**User Story:** As a procurement officer, I want to manage vendors, raise purchase requisitions, record purchase bills, and track payment to vendors, so that procurement is auditable.

#### Acceptance Criteria

1. THE System SHALL maintain a `vendors` table with name, contact person, phone, email, address, and payment terms fields.
2. THE System SHALL allow creating purchase requisitions with requested items, quantities, estimated cost, requester, and approval status.
3. WHEN a purchase requisition is approved, THE System SHALL allow converting it to a purchase order linked to a vendor.
4. THE System SHALL record purchase bills with bill number, vendor, purchase order reference, items received, unit prices, total, and due date.
5. WHEN a purchase bill is recorded, THE System SHALL update inventory stock quantities for each received item.
6. THE System SHALL track vendor payment history with payment date, amount, method, and reference.
7. THE System SHALL generate a vendor ledger report showing all bills, payments, and outstanding balance per vendor.
8. IF a purchase bill total exceeds the approved purchase order total by more than 5%, THEN THE System SHALL require a secondary approval before saving.

---

### Requirement 7: Inventory Management

**User Story:** As a warehouse manager, I want to track products, stock levels, supply movements, and generate vouchers, so that inventory is accurate and auditable.

#### Acceptance Criteria

1. THE System SHALL maintain inventory items with category, warehouse, name, code, unit, quantity, minimum stock threshold, purchase price, and sale price.
2. WHEN stock quantity falls below the minimum threshold, THE System SHALL display a low-stock alert on the dashboard.
3. THE System SHALL record all stock movements (purchase, installation, return, transfer, damaged, adjustment) with quantity, unit price, reference, and performer.
4. WHEN items are issued for a customer installation, THE System SHALL deduct the quantity from the warehouse stock and link the movement to the work order.
5. THE System SHALL generate a stock voucher (issue/receipt) in PDF format for each movement.
6. THE System SHALL support inter-warehouse stock transfers with source warehouse, destination warehouse, items, quantities, and transfer date.
7. THE System SHALL produce a stock summary report per warehouse showing opening stock, receipts, issues, and closing balance for a date range.
8. IF a stock issue quantity exceeds available stock, THEN THE System SHALL reject the transaction and return an error.

---

### Requirement 8: Network Diagram

**User Story:** As a network engineer, I want a visual network diagram showing POPs, BOXes, OLTs, splitters, and customer locations on a map, so that the physical network topology is easy to understand and maintain.

#### Acceptance Criteria

1. THE System SHALL render an interactive map using Leaflet.js displaying customer locations as markers using `lat`/`lng` from the `customers` table.
2. THE System SHALL allow creating POP nodes with name, location, coordinates, and connected OLT references.
3. THE System SHALL allow creating BOX nodes with name, POP parent, coordinates, and connected splitter references.
4. WHEN a customer marker is clicked, THE System SHALL display a popup with customer code, name, package, and connection status.
5. THE System SHALL draw lines between POP → BOX → OLT → Splitter nodes to represent the physical topology.
6. THE System SHALL allow filtering the map view by zone, branch, or connection status.
7. THE System SHALL allow adding and editing node coordinates via a drag-and-drop interface on the map.
8. THE Network_Diagram SHALL export the current map view as a PNG image.

---

### Requirement 9: Accounts Management

**User Story:** As an accountant, I want to record daily expenses, income entries, track bank deposits, and generate profit/loss and balance sheet reports, so that the company's financial position is always current.

#### Acceptance Criteria

1. THE System SHALL maintain an `expense_categories` table with name, type (`opex`, `capex`), and branch.
2. THE System SHALL allow recording daily expense entries with category, amount, description, payment method, branch, and date.
3. THE System SHALL allow recording non-billing income entries (e.g., installation fees, penalties) with source, amount, branch, and date.
4. WHEN a payment is recorded in the billing module, THE System SHALL automatically create a corresponding income entry in the accounts module.
5. THE System SHALL track bank accounts with bank name, account number, branch, and current balance.
6. WHEN a bank deposit is recorded, THE System SHALL update the linked bank account balance.
7. THE System SHALL generate a monthly profit/loss report showing total income, total expenses, and net profit per branch.
8. THE System SHALL generate a balance sheet showing assets (bank balances, receivables) and liabilities (payables) as of a selected date.
9. THE Accounts_Report SHALL export to PDF and CSV.
10. IF an expense entry date is more than 30 days in the past, THEN THE System SHALL require a manager-level user to approve the backdated entry.

---

### Requirement 10: Asset Management

**User Story:** As an operations manager, I want to track company assets and record destroyed or disposed items, so that asset registers are accurate.

#### Acceptance Criteria

1. THE System SHALL maintain an `assets` table with asset name, category, serial number, purchase date, purchase price, assigned branch, assigned employee, warranty expiry, and status (`active`, `under_repair`, `disposed`).
2. THE System SHALL allow recording asset disposal with disposal date, reason, disposal method (`sold`, `destroyed`, `donated`), and residual value.
3. WHEN an asset is disposed, THE System SHALL update its status to `disposed` and record the event in an `asset_disposal_log`.
4. THE System SHALL generate an asset register report listing all active assets with current book value (purchase price − accumulated depreciation).
5. THE System SHALL support straight-line depreciation calculation based on asset category's useful life in years.
6. THE System SHALL display assets nearing warranty expiry (within 30 days) as alerts on the dashboard.
7. IF an asset serial number already exists in the system, THEN THE System SHALL reject the duplicate entry.

---

### Requirement 11: Bandwidth Purchase & Sales

**User Story:** As a network manager, I want to manage bandwidth providers, record bandwidth purchases, manage reseller accounts, and generate bandwidth billing and ledger reports, so that bandwidth costs and revenues are tracked.

#### Acceptance Criteria

1. THE System SHALL maintain a `bandwidth_providers` table with provider name, contact, contracted capacity (Mbps), monthly cost, billing cycle, and contract expiry.
2. THE System SHALL maintain a `bandwidth_resellers` table with reseller name, contact, allocated capacity (Mbps), rate per Mbps, and billing cycle.
3. THE System SHALL allow recording monthly bandwidth purchase bills from providers with amount, period, and payment status.
4. THE System SHALL allow generating monthly bandwidth invoices for resellers based on allocated capacity and rate.
5. WHEN a bandwidth invoice is paid, THE System SHALL update the reseller's ledger balance.
6. THE System SHALL generate a reseller ledger report showing all invoices, payments, and outstanding balance per reseller.
7. THE System SHALL display a bandwidth utilisation summary showing purchased capacity vs. allocated capacity vs. actual usage.
8. IF a reseller's outstanding balance exceeds their credit limit, THEN THE System SHALL flag the account and prevent new invoice generation until payment is received.

---

### Requirement 12: Bandwidth Reseller Portal

**User Story:** As a bandwidth reseller, I want a self-service portal to view my invoices, make payments, raise tickets, and manage my profile, so that I can operate independently without calling the ISP.

#### Acceptance Criteria

1. THE System SHALL provide a separate login page for bandwidth resellers at `/reseller-portal/login`.
2. WHEN a bandwidth reseller logs in, THE System SHALL display a dashboard showing current balance, last invoice, next due date, and open tickets.
3. THE System SHALL allow resellers to view and download their invoice history in PDF format.
4. THE System SHALL allow resellers to raise support tickets with subject, description, and priority.
5. WHEN a reseller submits a ticket, THE System SHALL notify the ISP admin via the existing webhook/notification system.
6. THE System SHALL allow resellers to update their contact information and change their portal password.
7. WHILE a reseller session is active, THE System SHALL enforce session timeout after 30 minutes of inactivity.
8. IF a reseller account is suspended, THEN THE System SHALL deny login and display a suspension message with a contact number.

---

### Requirement 13: MAC Reseller Portal

**User Story:** As a MAC-based reseller, I want a panel to manage my clients, assign tariff plans, view daily billing, and track my account balance, so that I can run my reseller business independently.

#### Acceptance Criteria

1. THE System SHALL provide a separate login page for MAC resellers at `/mac-reseller/login`.
2. WHEN a MAC reseller logs in, THE System SHALL display a dashboard showing total clients, active clients, today's collections, and account balance.
3. THE System SHALL allow MAC resellers to add clients with MAC address, name, phone, and assigned tariff plan.
4. THE System SHALL maintain tariff plans per reseller with name, speed, daily rate, and monthly rate.
5. THE System SHALL generate daily billing records for each active MAC reseller client based on the assigned tariff plan's daily rate.
6. THE System SHALL display a daily billing summary showing total billed, total collected, and outstanding per day.
7. THE System SHALL allow MAC resellers to record client payments and update the client's balance.
8. WHEN a MAC reseller's account balance falls below zero, THE System SHALL suspend all their clients' internet access by flagging them in the system.
9. THE System SHALL generate a monthly statement for MAC resellers showing opening balance, total billed, total collected, and closing balance.

---

### Requirement 14: Employee Portal

**User Story:** As a field employee, I want a dedicated portal to collect bill payments, manage support tickets, and track my assigned tasks, so that I can work efficiently without needing full admin access.

#### Acceptance Criteria

1. THE System SHALL provide a separate login page for employees at `/employee-portal/login`.
2. WHEN an employee logs in, THE System SHALL display a dashboard showing today's assigned tasks, open tickets assigned to them, and their collection summary for the day.
3. THE System SHALL allow employees to search customers by name, phone, or customer code and record bill payments on their behalf.
4. WHEN an employee records a payment, THE System SHALL link the payment to that employee as `collector_id` and add it to their daily collection session.
5. THE System SHALL allow employees to view, comment on, and update the status of tickets assigned to them.
6. THE System SHALL allow employees to mark tasks as `in_progress` or `completed` with a completion note.
7. THE System SHALL display the employee's daily collection total and a list of receipts issued during the current session.
8. WHILE an employee portal session is active, THE System SHALL restrict access to only the employee's own data and assigned records.
9. IF an employee attempts to access another employee's records, THEN THE System SHALL return a 403 Forbidden response.

---

### Requirement 15: BTRC Report

**User Story:** As a compliance officer, I want to generate BTRC DIS reports in the required format, so that regulatory submissions are accurate and timely.

#### Acceptance Criteria

1. THE System SHALL generate a BTRC DIS report for a selected month containing: total subscribers, new connections, disconnections, active subscribers by division/district, and revenue figures.
2. THE BTRC_Report SHALL export in CSV format matching the BTRC-specified column headers and order.
3. THE BTRC_Report SHALL export in PDF format with the company's letterhead and authorised signatory fields.
4. WHEN generating the report, THE System SHALL aggregate customer data by `division` and `district` fields derived from the zone/area hierarchy.
5. THE System SHALL allow the compliance officer to preview the report before exporting.
6. THE System SHALL store a log of each BTRC report generation with the generating user, timestamp, and period covered.
7. IF no customer data exists for the selected month, THEN THE System SHALL generate a zero-value report rather than returning an error.

---

### Requirement 16: OTT Subscription Management

**User Story:** As a product manager, I want to bundle OTT subscriptions with internet packages, manage auto-renewals, and monitor subscriber status, so that OTT services are delivered reliably.

#### Acceptance Criteria

1. THE System SHALL maintain an `ott_providers` table with provider name, logo, API endpoint, API key, and supported plan types.
2. THE System SHALL maintain `ott_packages` linking OTT plans to internet packages with price, validity days, and auto-renewal flag.
3. WHEN a customer is assigned a package with an OTT bundle, THE System SHALL create an `ott_subscriptions` record with start date, expiry date, and status `active`.
4. WHEN an OTT subscription expires and auto-renewal is enabled, THE System SHALL attempt renewal and log the result.
5. IF an OTT renewal fails, THEN THE System SHALL set the subscription status to `expired` and send an SMS notification to the customer.
6. THE System SHALL display an OTT subscriber dashboard showing active, expired, and pending renewal subscriptions.
7. THE System SHALL allow manual activation and deactivation of OTT subscriptions per customer.
8. WHEN an OTT subscription is deactivated, THE System SHALL record the deactivation reason and timestamp.

---

### Requirement 17: Business Configuration

**User Story:** As a system administrator, I want to configure zones, sub-zones, POPs, BOXes, packages, billing automation rules, and invoice templates from a single settings area, so that the system reflects the business's operational structure.

#### Acceptance Criteria

1. THE System SHALL provide a configuration UI for managing zones (linked to branches) and sub-zones (linked to zones).
2. THE System SHALL provide a configuration UI for managing POPs with name, location, coordinates, and branch.
3. THE System SHALL provide a configuration UI for managing BOXes with name, POP parent, coordinates, and capacity.
4. THE System SHALL provide a package setup UI allowing creation and editing of internet packages with speed, price, billing type, MikroTik profile, and RADIUS profile.
5. THE System SHALL allow configuring billing automation rules including: invoice generation day, due date offset, auto-suspension day, and auto-reconnection on payment.
6. THE System SHALL allow uploading and configuring invoice templates with company logo, header text, footer text, and signature fields.
7. WHEN a billing automation rule is saved, THE System SHALL validate that suspension day is greater than due date offset.
8. THE System SHALL allow configuring SMS templates for each event type (bill generated, payment received, due reminder, suspension, reconnection).
9. THE System SHALL allow configuring the default currency symbol, date format, and timezone.
10. IF a package code already exists, THEN THE System SHALL reject the duplicate and display a validation error.

---

### Requirement 18: Role-Based Permissions UI

**User Story:** As a super admin, I want a UI to create roles, define granular permissions per module, and assign roles to users, so that access control is precise and auditable.

#### Acceptance Criteria

1. THE System SHALL display a roles list with name, display name, description, and user count.
2. THE System SHALL allow creating and editing roles with a name and display name.
3. THE System SHALL display all available permissions grouped by module (e.g., `customers`, `billing`, `hr`, `inventory`) with checkboxes.
4. WHEN a role's permissions are saved, THE System SHALL update the `role_permissions` table to reflect the exact set of checked permissions.
5. THE System SHALL allow assigning a role to a user from the user edit screen.
6. WHEN a user's role is changed, THE System SHALL log the change in the `activity_logs` table with old role, new role, and actor.
7. THE System SHALL enforce permission checks on every controller action using a `hasPermission(string $permission): bool` helper.
8. IF a user without the `roles.edit` permission attempts to access the roles management page, THEN THE System SHALL return a 403 Forbidden response.
9. THE System SHALL seed a default set of permissions for all modules on first installation.
10. THE System SHALL prevent deletion of the `superadmin` role.

---

### Requirement 19: Bulk SMS & Mailing System

**User Story:** As a marketing manager, I want to create SMS campaigns, manage SMS templates, and send email broadcasts to filtered customer segments, so that communications are targeted and trackable.

#### Acceptance Criteria

1. THE System SHALL provide a campaign manager UI listing all SMS campaigns with name, status, recipient count, sent count, and scheduled time.
2. THE System SHALL allow creating SMS campaigns with a message body, recipient filter (all, by zone, by package, by status, by branch), and optional scheduled send time.
3. WHEN a campaign is executed, THE System SHALL send SMS to each recipient using the existing `SmsService` and log each send attempt in `sms_logs`.
4. THE System SHALL allow creating and managing reusable SMS templates with name, event type, message body, and variable placeholders.
5. THE System SHALL provide an email broadcast feature allowing composition of an email subject and HTML body, with recipient filtering matching the SMS campaign filters.
6. WHEN an email broadcast is sent, THE System SHALL use PHP's `mail()` function or a configured SMTP service and log each send attempt.
7. THE System SHALL display campaign delivery statistics showing sent count, failed count, and delivery rate after execution.
8. WHEN a scheduled campaign's send time arrives, THE System SHALL execute it via the existing cron automation system.
9. IF a campaign recipient list exceeds 1000 contacts, THEN THE System SHALL process sends in batches of 100 with a 1-second delay between batches to avoid gateway throttling.

---

### Requirement 20: Android App API

**User Story:** As a mobile app developer, I want a versioned REST API with JWT authentication and push notification support, so that the Android app can securely access ISP data and receive real-time alerts.

#### Acceptance Criteria

1. THE System SHALL expose API endpoints under `/api/v1/` with JSON responses for all operations.
2. WHEN a mobile user submits valid credentials to `POST /api/v1/auth/login`, THE System SHALL return a JWT token with a 24-hour expiry and a refresh token with a 30-day expiry.
3. WHEN a request includes a valid JWT in the `Authorization: Bearer <token>` header, THE System SHALL authenticate the request without a session.
4. IF a JWT token is expired or invalid, THEN THE System SHALL return HTTP 401 with a JSON error body `{"error": "Unauthorized", "code": 401}`.
5. THE System SHALL provide `GET /api/v1/customers` returning a paginated list of customers with search and filter support.
6. THE System SHALL provide `GET /api/v1/customers/{id}` returning full customer details including package, invoices, and payment history.
7. THE System SHALL provide `POST /api/v1/payments` allowing bill payment recording from the mobile app.
8. THE System SHALL provide `GET /api/v1/dashboard/stats` returning key metrics (active customers, today's collection, open tickets, online users).
9. THE System SHALL store FCM device tokens per user in a `device_tokens` table with user ID, token, platform, and last active timestamp.
10. WHEN a significant event occurs (payment received, ticket updated, customer suspended), THE System SHALL send a push notification to all registered device tokens for the relevant user via FCM.
11. THE System SHALL provide `POST /api/v1/auth/refresh` to exchange a valid refresh token for a new JWT.
12. THE System SHALL rate-limit API endpoints to 60 requests per minute per IP using the existing `RateLimiterService`.
13. IF a push notification delivery fails, THEN THE System SHALL log the failure with the FCM error code and retry once after 60 seconds.
14. THE System SHALL provide `GET /api/v1/tickets` and `POST /api/v1/tickets` for mobile ticket management.
