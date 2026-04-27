-- ============================================================
-- Migration: 2024_01_03_001_create_all_new_modules_tables
-- Date: 2024-01-03
-- Description: Creates all new tables for 20 missing modules
--              across all phases of the FCNCHBD ISP ERP system.
--              Phase 2: HR/Support/Tasks
--              Phase 3: Sales/Purchase/Inventory
--              Phase 4: Network/Accounts/Assets
--              Phase 5: Bandwidth/Portals/Config
--              Phase 6: Reporting/OTT/Roles/Campaigns/API
--              Phase 7: Branch
-- ============================================================

USE digital_isp;

SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = '';

-- ============================================================
-- PHASE 2: HR & PAYROLL MODULE
-- ============================================================

-- Departments
CREATE TABLE IF NOT EXISTS departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    head_of_department INT UNSIGNED COMMENT 'FK to employees.id',
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='HR departments per branch';

-- Designations
CREATE TABLE IF NOT EXISTS designations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    grade VARCHAR(50),
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_department (department_id),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Job designations linked to departments';

-- Employees
CREATE TABLE IF NOT EXISTS employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    branch_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED,
    designation_id INT UNSIGNED,
    employee_code VARCHAR(30) UNIQUE NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(150),
    nid_number VARCHAR(50),
    joining_date DATE,
    basic_salary DECIMAL(10,2) DEFAULT 0.00,
    allowances DECIMAL(10,2) DEFAULT 0.00,
    bank_account VARCHAR(50),
    bank_name VARCHAR(100),
    emergency_contact VARCHAR(20),
    emergency_contact_name VARCHAR(100),
    status ENUM('active','inactive','terminated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    KEY idx_user (user_id),
    KEY idx_code (employee_code),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Employee profiles linked to users';

-- Add FK for head_of_department after employees table exists
ALTER TABLE departments ADD CONSTRAINT fk_dept_head FOREIGN KEY (head_of_department) REFERENCES employees(id) ON DELETE SET NULL;

-- Attendance
CREATE TABLE IF NOT EXISTS attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present','absent','late','half_day','leave') NOT NULL DEFAULT 'present',
    check_in TIME,
    check_out TIME,
    notes TEXT,
    recorded_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_employee_date (employee_id, attendance_date),
    KEY idx_employee (employee_id),
    KEY idx_date (attendance_date),
    KEY idx_branch (branch_id),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB COMMENT='Daily employee attendance records';

-- Salary Slips
CREATE TABLE IF NOT EXISTS salary_slips (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    salary_month DATE NOT NULL COMMENT 'First day of the salary month',
    basic_salary DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    allowances DECIMAL(10,2) DEFAULT 0.00,
    deductions DECIMAL(10,2) DEFAULT 0.00,
    gross_pay DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    net_pay DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    present_days INT DEFAULT 0,
    absent_days INT DEFAULT 0,
    leave_days INT DEFAULT 0,
    payment_status ENUM('pending','paid') DEFAULT 'pending',
    payment_date DATE,
    payment_method ENUM('cash','bank_transfer','mobile_banking') DEFAULT 'cash',
    notes TEXT,
    generated_by INT UNSIGNED,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_employee_month (employee_id, salary_month),
    KEY idx_employee (employee_id),
    KEY idx_month (salary_month),
    KEY idx_branch (branch_id),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB COMMENT='Monthly salary slips per employee';

-- Performance Appraisals
CREATE TABLE IF NOT EXISTS performance_appraisals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED,
    review_period_start DATE NOT NULL,
    review_period_end DATE NOT NULL,
    rating TINYINT UNSIGNED NOT NULL COMMENT '1-5 rating scale',
    comments TEXT,
    goals_achieved TEXT,
    areas_for_improvement TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_employee (employee_id),
    KEY idx_period (review_period_start, review_period_end),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Employee performance appraisal records';

-- Leave Balances
CREATE TABLE IF NOT EXISTS leave_balances (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    leave_type ENUM('annual','sick','casual') NOT NULL,
    year YEAR NOT NULL,
    total_days INT DEFAULT 0,
    used_days INT DEFAULT 0,
    remaining_days INT GENERATED ALWAYS AS (total_days - used_days) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_employee_leave_year (employee_id, leave_type, year),
    KEY idx_employee (employee_id),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Leave balance tracking per employee per type per year';


-- ============================================================
-- PHASE 2: SUPPORT & TICKETING MODULE (ADMIN SIDE)
-- ============================================================

-- Ticket Comments (activity thread)
CREATE TABLE IF NOT EXISTS ticket_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    author_id INT UNSIGNED,
    author_type ENUM('staff','customer','system') DEFAULT 'staff',
    message TEXT NOT NULL,
    is_internal TINYINT(1) DEFAULT 0 COMMENT 'Internal note not visible to customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ticket (ticket_id),
    KEY idx_author (author_id),
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Comment and activity thread per support ticket';

-- Ticket Assignments
CREATE TABLE IF NOT EXISTS ticket_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    assigned_to INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    KEY idx_ticket (ticket_id),
    KEY idx_assigned_to (assigned_to),
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Ticket assignment history';

-- SLA Violations
CREATE TABLE IF NOT EXISTS sla_violations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    sla_deadline TIMESTAMP NOT NULL,
    breached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    priority ENUM('low','medium','high','urgent') NOT NULL,
    resolution_time_minutes INT COMMENT 'Minutes taken to resolve after breach',
    notes TEXT,
    KEY idx_ticket (ticket_id),
    KEY idx_breached (breached_at),
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='SLA breach records for support tickets';

-- Problem Categories
CREATE TABLE IF NOT EXISTS problem_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    sla_hours_urgent INT DEFAULT 2,
    sla_hours_high INT DEFAULT 8,
    sla_hours_medium INT DEFAULT 24,
    sla_hours_low INT DEFAULT 72,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Support ticket problem categories with SLA hours';

-- Seed default problem categories
INSERT IGNORE INTO problem_categories (name, code, sla_hours_urgent, sla_hours_high, sla_hours_medium, sla_hours_low) VALUES
('No Connection', 'no_connection', 2, 8, 24, 72),
('Slow Speed', 'slow_speed', 2, 8, 24, 72),
('Billing Dispute', 'billing_dispute', 4, 12, 48, 96),
('Hardware Fault', 'hardware_fault', 2, 8, 24, 72),
('New Request', 'new_request', 4, 24, 72, 168),
('Other', 'other', 4, 24, 72, 168);

-- ============================================================
-- PHASE 2: TASK MANAGEMENT MODULE
-- ============================================================

-- Tasks
CREATE TABLE IF NOT EXISTS tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    assigned_to INT UNSIGNED COMMENT 'FK to employees.id',
    assigned_by INT UNSIGNED COMMENT 'FK to users.id',
    due_date DATETIME,
    priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
    status ENUM('pending','in_progress','completed','cancelled','overdue') DEFAULT 'pending',
    completion_note TEXT,
    completed_at TIMESTAMP NULL,
    is_recurring TINYINT(1) DEFAULT 0,
    recurrence_pattern VARCHAR(50) COMMENT 'daily, weekly, monthly',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    KEY idx_assigned_to (assigned_to),
    KEY idx_status (status),
    KEY idx_due_date (due_date),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (assigned_to) REFERENCES employees(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Task management with assignment and status tracking';

-- Task History
CREATE TABLE IF NOT EXISTS task_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    actor_id INT UNSIGNED,
    old_status VARCHAR(30),
    new_status VARCHAR(30),
    note TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_task (task_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Status change history for tasks';

-- Task Assignments (for bulk/multi-employee assignment)
CREATE TABLE IF NOT EXISTS task_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_task_employee (task_id, employee_id),
    KEY idx_task (task_id),
    KEY idx_employee (employee_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Many-to-many task to employee assignments';


-- ============================================================
-- PHASE 3: SALES & SERVICE INVOICING MODULE
-- ============================================================

-- Sales Invoices
CREATE TABLE IF NOT EXISTS sales_invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    invoice_type ENUM('installation','product','service') NOT NULL DEFAULT 'service',
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    vat DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    due_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('unpaid','partial','paid','cancelled') DEFAULT 'unpaid',
    connection_date DATE COMMENT 'For installation invoices',
    otc_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'One-time charge for installation',
    notes TEXT,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_customer (customer_id),
    KEY idx_branch (branch_id),
    KEY idx_status (payment_status),
    KEY idx_type (invoice_type),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Sales invoices for installation, product, and service charges';

-- Sales Invoice Items (line items)
CREATE TABLE IF NOT EXISTS sales_invoice_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    KEY idx_invoice (invoice_id),
    FOREIGN KEY (invoice_id) REFERENCES sales_invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Line items for sales invoices';

-- Sales Payments
CREATE TABLE IF NOT EXISTS sales_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','mobile_banking','bank_transfer','online','other') DEFAULT 'cash',
    reference VARCHAR(100),
    notes TEXT,
    collected_by INT UNSIGNED,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_invoice (invoice_id),
    KEY idx_customer (customer_id),
    KEY idx_date (payment_date),
    FOREIGN KEY (invoice_id) REFERENCES sales_invoices(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (collected_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Payment records against sales invoices';

-- ============================================================
-- PHASE 3: PURCHASE MANAGEMENT MODULE
-- ============================================================

-- Vendors
CREATE TABLE IF NOT EXISTS vendors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(150),
    address TEXT,
    payment_terms VARCHAR(100) COMMENT 'e.g. Net 30, Net 60',
    credit_limit DECIMAL(12,2) DEFAULT 0.00,
    outstanding_balance DECIMAL(12,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_name (name)
) ENGINE=InnoDB COMMENT='Vendor/supplier master records';

-- Purchase Requisitions
CREATE TABLE IF NOT EXISTS purchase_requisitions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requisition_number VARCHAR(30) UNIQUE NOT NULL,
    branch_id INT UNSIGNED,
    requested_by INT UNSIGNED,
    items TEXT NOT NULL COMMENT 'JSON: [{item, qty, estimated_cost}]',
    total_estimated_cost DECIMAL(10,2) DEFAULT 0.00,
    approval_status ENUM('pending','approved','rejected','converted') DEFAULT 'pending',
    approved_by INT UNSIGNED,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    KEY idx_status (approval_status),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Purchase requisitions with approval workflow';

-- Purchase Bills
CREATE TABLE IF NOT EXISTS purchase_bills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(30) UNIQUE NOT NULL,
    vendor_id INT UNSIGNED NOT NULL,
    purchase_order_id INT UNSIGNED COMMENT 'FK to purchase_orders.id',
    requisition_id INT UNSIGNED,
    branch_id INT UNSIGNED,
    warehouse_id INT UNSIGNED,
    items TEXT NOT NULL COMMENT 'JSON: [{item_id, qty, unit_price, total}]',
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    vat DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    due_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    due_date DATE,
    payment_status ENUM('unpaid','partial','paid') DEFAULT 'unpaid',
    requires_secondary_approval TINYINT(1) DEFAULT 0,
    secondary_approved_by INT UNSIGNED,
    secondary_approved_at TIMESTAMP NULL,
    notes TEXT,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_vendor (vendor_id),
    KEY idx_branch (branch_id),
    KEY idx_status (payment_status),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Purchase bills from vendors';

-- Vendor Payments
CREATE TABLE IF NOT EXISTS vendor_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT UNSIGNED NOT NULL,
    bill_id INT UNSIGNED,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','bank_transfer','cheque','mobile_banking','other') DEFAULT 'cash',
    reference VARCHAR(100),
    payment_date DATE NOT NULL,
    notes TEXT,
    recorded_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_vendor (vendor_id),
    KEY idx_bill (bill_id),
    KEY idx_date (payment_date),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    FOREIGN KEY (bill_id) REFERENCES purchase_bills(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Payment records to vendors';

-- ============================================================
-- PHASE 3: INVENTORY MANAGEMENT MODULE (new tables only)
-- ============================================================

-- Stock Vouchers (PDF voucher records for stock movements)
CREATE TABLE IF NOT EXISTS stock_vouchers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voucher_number VARCHAR(30) UNIQUE NOT NULL,
    voucher_type ENUM('issue','receipt','transfer','adjustment') NOT NULL,
    movement_id INT UNSIGNED COMMENT 'FK to stock_movements.id',
    warehouse_id INT UNSIGNED,
    branch_id INT UNSIGNED,
    items TEXT NOT NULL COMMENT 'JSON: snapshot of items at time of voucher',
    total_value DECIMAL(10,2) DEFAULT 0.00,
    reference VARCHAR(100),
    notes TEXT,
    generated_by INT UNSIGNED,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_type (voucher_type),
    KEY idx_warehouse (warehouse_id),
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Stock voucher records for audit trail';

-- Stock Transfers (inter-warehouse)
CREATE TABLE IF NOT EXISTS stock_transfers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transfer_number VARCHAR(30) UNIQUE NOT NULL,
    source_warehouse_id INT UNSIGNED NOT NULL,
    destination_warehouse_id INT UNSIGNED NOT NULL,
    items TEXT NOT NULL COMMENT 'JSON: [{item_id, quantity, unit_price}]',
    total_value DECIMAL(10,2) DEFAULT 0.00,
    transfer_date DATE NOT NULL,
    status ENUM('pending','in_transit','completed','cancelled') DEFAULT 'pending',
    notes TEXT,
    initiated_by INT UNSIGNED,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_source (source_warehouse_id),
    KEY idx_destination (destination_warehouse_id),
    KEY idx_date (transfer_date),
    FOREIGN KEY (source_warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (destination_warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (initiated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Inter-warehouse stock transfer records';


-- ============================================================
-- PHASE 4: NETWORK DIAGRAM MODULE
-- ============================================================

-- POP Nodes (Points of Presence)
CREATE TABLE IF NOT EXISTS pop_nodes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    location TEXT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    olt_ids TEXT COMMENT 'JSON: array of connected OLT IDs',
    capacity_mbps INT DEFAULT 0,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    KEY idx_coords (latitude, longitude),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB COMMENT='Network POP (Point of Presence) nodes';

-- BOX Nodes (street-level distribution cabinets)
CREATE TABLE IF NOT EXISTS box_nodes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pop_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    location TEXT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    splitter_refs TEXT COMMENT 'JSON: array of connected splitter IDs',
    capacity INT DEFAULT 0 COMMENT 'Number of ports/connections',
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_pop (pop_id),
    KEY idx_branch (branch_id),
    KEY idx_coords (latitude, longitude),
    FOREIGN KEY (pop_id) REFERENCES pop_nodes(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB COMMENT='Network BOX nodes linked to POP nodes';

-- Network Connections (topology links)
CREATE TABLE IF NOT EXISTS network_connections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_node_type ENUM('pop','box','olt','splitter','customer') NOT NULL,
    from_node_id INT UNSIGNED NOT NULL,
    to_node_type ENUM('pop','box','olt','splitter','customer') NOT NULL,
    to_node_id INT UNSIGNED NOT NULL,
    connection_type ENUM('fiber','copper','wireless','other') DEFAULT 'fiber',
    distance_meters DECIMAL(8,2),
    cable_type VARCHAR(50),
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_from (from_node_type, from_node_id),
    KEY idx_to (to_node_type, to_node_id)
) ENGINE=InnoDB COMMENT='Network topology connections between nodes';

-- ============================================================
-- PHASE 4: ACCOUNTS MANAGEMENT MODULE
-- ============================================================

-- Expense Categories
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED,
    name VARCHAR(150) NOT NULL,
    type ENUM('opex','capex') NOT NULL DEFAULT 'opex',
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    KEY idx_type (type),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Expense categories (OPEX/CAPEX) per branch';

-- Expense Entries
CREATE TABLE IF NOT EXISTS expense_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    payment_method ENUM('cash','bank_transfer','mobile_banking','cheque','other') DEFAULT 'cash',
    reference VARCHAR(100),
    expense_date DATE NOT NULL,
    is_backdated TINYINT(1) DEFAULT 0,
    approved_by INT UNSIGNED COMMENT 'Required for backdated entries',
    approved_at TIMESTAMP NULL,
    recorded_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    KEY idx_category (category_id),
    KEY idx_date (expense_date),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Daily expense entries';

-- Income Entries (non-billing income)
CREATE TABLE IF NOT EXISTS income_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    source VARCHAR(150) NOT NULL COMMENT 'e.g. installation fee, penalty, other',
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    reference_type VARCHAR(50) COMMENT 'payment, sales_invoice, manual',
    reference_id INT UNSIGNED,
    income_date DATE NOT NULL,
    recorded_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    KEY idx_date (income_date),
    KEY idx_source (source),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Non-billing income entries';

-- Bank Accounts
CREATE TABLE IF NOT EXISTS bank_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    bank_name VARCHAR(150) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_name VARCHAR(150),
    account_type ENUM('current','savings','fixed') DEFAULT 'current',
    current_balance DECIMAL(12,2) DEFAULT 0.00,
    opening_balance DECIMAL(12,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB COMMENT='Bank account records per branch';

-- Bank Deposits
CREATE TABLE IF NOT EXISTS bank_deposits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bank_account_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    deposit_type ENUM('cash','cheque','transfer','other') DEFAULT 'cash',
    reference VARCHAR(100),
    deposit_date DATE NOT NULL,
    description TEXT,
    recorded_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_account (bank_account_id),
    KEY idx_branch (branch_id),
    KEY idx_date (deposit_date),
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Bank deposit records';

-- ============================================================
-- PHASE 4: ASSET MANAGEMENT MODULE
-- ============================================================

-- Asset Categories
CREATE TABLE IF NOT EXISTS asset_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    useful_life_years INT DEFAULT 5 COMMENT 'For straight-line depreciation',
    depreciation_rate DECIMAL(5,2) GENERATED ALWAYS AS (100.00 / useful_life_years) STORED,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Asset categories with depreciation settings';

-- Assets
CREATE TABLE IF NOT EXISTS assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED,
    assigned_employee_id INT UNSIGNED,
    asset_name VARCHAR(200) NOT NULL,
    serial_number VARCHAR(100) UNIQUE NOT NULL,
    purchase_date DATE,
    purchase_price DECIMAL(10,2) DEFAULT 0.00,
    current_book_value DECIMAL(10,2) DEFAULT 0.00,
    warranty_expiry DATE,
    status ENUM('active','under_repair','disposed') DEFAULT 'active',
    location TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    KEY idx_category (category_id),
    KEY idx_serial (serial_number),
    KEY idx_status (status),
    KEY idx_warranty (warranty_expiry),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Company asset register';

-- Asset Disposals
CREATE TABLE IF NOT EXISTS asset_disposals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    disposal_date DATE NOT NULL,
    reason TEXT,
    disposal_method ENUM('sold','destroyed','donated') NOT NULL,
    residual_value DECIMAL(10,2) DEFAULT 0.00,
    sale_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'If sold',
    notes TEXT,
    disposed_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_asset (asset_id),
    KEY idx_date (disposal_date),
    FOREIGN KEY (asset_id) REFERENCES assets(id),
    FOREIGN KEY (disposed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Asset disposal log';

-- Asset Depreciation (annual records)
CREATE TABLE IF NOT EXISTS asset_depreciation (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    depreciation_year YEAR NOT NULL,
    opening_value DECIMAL(10,2) NOT NULL,
    depreciation_amount DECIMAL(10,2) NOT NULL,
    closing_value DECIMAL(10,2) NOT NULL,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_asset_year (asset_id, depreciation_year),
    KEY idx_asset (asset_id),
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Annual depreciation records per asset';


-- ============================================================
-- PHASE 5: BANDWIDTH PURCHASE & SALES MODULE
-- ============================================================

-- Bandwidth Providers
CREATE TABLE IF NOT EXISTS bandwidth_providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(150),
    contracted_capacity_mbps INT DEFAULT 0,
    monthly_cost DECIMAL(10,2) DEFAULT 0.00,
    billing_cycle ENUM('monthly','quarterly','annually') DEFAULT 'monthly',
    contract_expiry DATE,
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Upstream bandwidth providers';

-- Bandwidth Resellers
CREATE TABLE IF NOT EXISTS bandwidth_resellers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(150),
    address TEXT,
    allocated_capacity_mbps INT DEFAULT 0,
    rate_per_mbps DECIMAL(10,2) DEFAULT 0.00,
    billing_cycle ENUM('monthly','quarterly','annually') DEFAULT 'monthly',
    credit_limit DECIMAL(12,2) DEFAULT 0.00,
    outstanding_balance DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('active','suspended','inactive') DEFAULT 'active',
    portal_username VARCHAR(80) UNIQUE,
    portal_password_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_status (status)
) ENGINE=InnoDB COMMENT='Downstream bandwidth resellers';

-- Bandwidth Purchases (bills from providers)
CREATE TABLE IF NOT EXISTS bandwidth_purchases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_id INT UNSIGNED NOT NULL,
    bill_number VARCHAR(50),
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    capacity_mbps INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('unpaid','paid') DEFAULT 'unpaid',
    payment_date DATE,
    notes TEXT,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_provider (provider_id),
    KEY idx_period (period_start, period_end),
    FOREIGN KEY (provider_id) REFERENCES bandwidth_providers(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Monthly bandwidth purchase bills from providers';

-- Bandwidth Invoices (for resellers)
CREATE TABLE IF NOT EXISTS bandwidth_invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(30) UNIQUE NOT NULL,
    reseller_id INT UNSIGNED NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    allocated_mbps INT,
    rate_per_mbps DECIMAL(10,2),
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('unpaid','partial','paid') DEFAULT 'unpaid',
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    due_date DATE,
    notes TEXT,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_reseller (reseller_id),
    KEY idx_status (payment_status),
    FOREIGN KEY (reseller_id) REFERENCES bandwidth_resellers(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Monthly bandwidth invoices for resellers';

-- Reseller Ledgers
CREATE TABLE IF NOT EXISTS reseller_ledgers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reseller_id INT UNSIGNED NOT NULL,
    transaction_type ENUM('invoice','payment','adjustment','credit') NOT NULL,
    reference_type VARCHAR(50) COMMENT 'bandwidth_invoice, manual',
    reference_id INT UNSIGNED,
    amount DECIMAL(10,2) NOT NULL,
    balance_before DECIMAL(12,2),
    balance_after DECIMAL(12,2),
    notes TEXT,
    recorded_by INT UNSIGNED,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_reseller (reseller_id),
    KEY idx_date (transaction_date),
    FOREIGN KEY (reseller_id) REFERENCES bandwidth_resellers(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Ledger entries for bandwidth resellers';

-- ============================================================
-- PHASE 5: BANDWIDTH RESELLER PORTAL
-- ============================================================

-- Reseller Portal Sessions
CREATE TABLE IF NOT EXISTS reseller_portal_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reseller_id INT UNSIGNED NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    logout_at TIMESTAMP NULL,
    UNIQUE KEY uniq_token (session_token),
    KEY idx_reseller (reseller_id),
    KEY idx_expires (expires_at),
    FOREIGN KEY (reseller_id) REFERENCES bandwidth_resellers(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Session management for bandwidth reseller portal';

-- Reseller Tickets (support tickets from resellers)
CREATE TABLE IF NOT EXISTS reseller_tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(30) UNIQUE NOT NULL,
    reseller_id INT UNSIGNED NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    status ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
    assigned_to INT UNSIGNED,
    resolution_notes TEXT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_reseller (reseller_id),
    KEY idx_status (status),
    FOREIGN KEY (reseller_id) REFERENCES bandwidth_resellers(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Support tickets raised by bandwidth resellers';

-- ============================================================
-- PHASE 5: MAC RESELLER PORTAL
-- ============================================================

-- MAC Resellers
CREATE TABLE IF NOT EXISTS mac_resellers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED,
    business_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150),
    address TEXT,
    account_balance DECIMAL(12,2) DEFAULT 0.00,
    credit_limit DECIMAL(12,2) DEFAULT 0.00,
    portal_username VARCHAR(80) UNIQUE NOT NULL,
    portal_password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','suspended','inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    KEY idx_status (status),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='MAC-address-based reseller accounts';

-- MAC Reseller Clients
CREATE TABLE IF NOT EXISTS mac_reseller_clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mac_reseller_id INT UNSIGNED NOT NULL,
    tariff_id INT UNSIGNED,
    mac_address VARCHAR(20) NOT NULL,
    client_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    balance DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active','suspended','inactive') DEFAULT 'active',
    joined_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_mac (mac_address),
    KEY idx_reseller (mac_reseller_id),
    KEY idx_status (status),
    FOREIGN KEY (mac_reseller_id) REFERENCES mac_resellers(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Clients managed by MAC resellers';

-- MAC Reseller Tariffs
CREATE TABLE IF NOT EXISTS mac_reseller_tariffs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mac_reseller_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    speed_download VARCHAR(20),
    speed_upload VARCHAR(20),
    daily_rate DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    monthly_rate DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_reseller (mac_reseller_id),
    FOREIGN KEY (mac_reseller_id) REFERENCES mac_resellers(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Tariff plans per MAC reseller';

-- Add FK for tariff_id in mac_reseller_clients
ALTER TABLE mac_reseller_clients ADD CONSTRAINT fk_client_tariff FOREIGN KEY (tariff_id) REFERENCES mac_reseller_tariffs(id) ON DELETE SET NULL;

-- MAC Reseller Billing (daily billing records)
CREATE TABLE IF NOT EXISTS mac_reseller_billing (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mac_reseller_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    tariff_id INT UNSIGNED,
    billing_date DATE NOT NULL,
    amount DECIMAL(8,2) NOT NULL,
    is_paid TINYINT(1) DEFAULT 0,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_client_date (client_id, billing_date),
    KEY idx_reseller (mac_reseller_id),
    KEY idx_client (client_id),
    KEY idx_date (billing_date),
    FOREIGN KEY (mac_reseller_id) REFERENCES mac_resellers(id),
    FOREIGN KEY (client_id) REFERENCES mac_reseller_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (tariff_id) REFERENCES mac_reseller_tariffs(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Daily billing records for MAC reseller clients';

-- ============================================================
-- PHASE 5: EMPLOYEE PORTAL
-- ============================================================

-- Employee Collection Sessions
CREATE TABLE IF NOT EXISTS employee_collection_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    session_date DATE NOT NULL,
    total_collected DECIMAL(10,2) DEFAULT 0.00,
    total_receipts INT DEFAULT 0,
    status ENUM('active','closed','submitted') DEFAULT 'active',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    KEY idx_employee (employee_id),
    KEY idx_branch (branch_id),
    KEY idx_date (session_date),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB COMMENT='Daily collection sessions for field employees';

-- Employee Payments (payments collected by employees)
CREATE TABLE IF NOT EXISTS employee_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    employee_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','mobile_banking','other') DEFAULT 'cash',
    reference VARCHAR(100),
    receipt_number VARCHAR(30) UNIQUE,
    notes TEXT,
    collected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_session (session_id),
    KEY idx_employee (employee_id),
    KEY idx_customer (customer_id),
    FOREIGN KEY (session_id) REFERENCES employee_collection_sessions(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Payment records collected by field employees';

-- ============================================================
-- PHASE 5: BUSINESS CONFIGURATION MODULE
-- ============================================================

-- Configuration Settings
CREATE TABLE IF NOT EXISTS configuration_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string','integer','boolean','json','text') DEFAULT 'string',
    description VARCHAR(255),
    module VARCHAR(50) COMMENT 'Module this setting belongs to',
    is_public TINYINT(1) DEFAULT 0 COMMENT 'Visible to non-admin users',
    updated_by INT UNSIGNED,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_module (module),
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='System-wide configuration settings';

-- Billing Rules
CREATE TABLE IF NOT EXISTS billing_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED,
    rule_name VARCHAR(100) NOT NULL,
    invoice_generation_day TINYINT NOT NULL DEFAULT 1 COMMENT 'Day of month to generate invoices',
    due_date_offset_days INT NOT NULL DEFAULT 7 COMMENT 'Days after generation for due date',
    auto_suspension_day INT NOT NULL DEFAULT 15 COMMENT 'Days after due date to auto-suspend',
    auto_reconnect_on_payment TINYINT(1) DEFAULT 1,
    late_fee_amount DECIMAL(10,2) DEFAULT 0.00,
    late_fee_type ENUM('fixed','percentage') DEFAULT 'fixed',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Billing automation rules per branch';

-- Invoice Templates
CREATE TABLE IF NOT EXISTS invoice_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    template_type ENUM('invoice','receipt','salary_slip','stock_voucher') DEFAULT 'invoice',
    company_logo VARCHAR(255),
    header_text TEXT,
    footer_text TEXT,
    signature_fields TEXT COMMENT 'JSON: array of signature field labels',
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Configurable invoice and document templates';

-- Seed default configuration settings
INSERT IGNORE INTO configuration_settings (setting_key, setting_value, setting_type, description, module) VALUES
('currency_symbol', 'BDT', 'string', 'Default currency symbol', 'general'),
('date_format', 'd/m/Y', 'string', 'Default date format', 'general'),
('timezone', 'Asia/Dhaka', 'string', 'System timezone', 'general'),
('company_name', 'FCNCHBD ISP', 'string', 'Company name', 'general'),
('invoice_prefix', 'INV-', 'string', 'Invoice number prefix', 'billing'),
('receipt_prefix', 'RCP-', 'string', 'Receipt number prefix', 'billing'),
('sales_invoice_prefix', 'SI-', 'string', 'Sales invoice number prefix', 'sales'),
('task_prefix', 'TSK-', 'string', 'Task number prefix', 'tasks'),
('ticket_prefix', 'TKT-', 'string', 'Ticket number prefix', 'support');


-- ============================================================
-- PHASE 6: BTRC REPORT MODULE
-- ============================================================

-- BTRC Reports
CREATE TABLE IF NOT EXISTS btrc_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_period DATE NOT NULL COMMENT 'First day of the reporting month',
    total_subscribers INT DEFAULT 0,
    new_connections INT DEFAULT 0,
    disconnections INT DEFAULT 0,
    active_subscribers INT DEFAULT 0,
    total_revenue DECIMAL(12,2) DEFAULT 0.00,
    report_data TEXT COMMENT 'JSON: full aggregated report data by division/district',
    status ENUM('draft','finalized') DEFAULT 'draft',
    generated_by INT UNSIGNED,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finalized_at TIMESTAMP NULL,
    UNIQUE KEY uniq_period (report_period),
    KEY idx_period (report_period),
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='BTRC DIS regulatory reports';

-- BTRC Report Logs
CREATE TABLE IF NOT EXISTS btrc_report_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id INT UNSIGNED,
    action ENUM('generated','exported_csv','exported_pdf','finalized') NOT NULL,
    performed_by INT UNSIGNED,
    export_format VARCHAR(10),
    notes TEXT,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_report (report_id),
    FOREIGN KEY (report_id) REFERENCES btrc_reports(id) ON DELETE SET NULL,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Audit log for BTRC report generation and exports';

-- ============================================================
-- PHASE 6: OTT SUBSCRIPTION MANAGEMENT MODULE
-- ============================================================

-- OTT Providers
CREATE TABLE IF NOT EXISTS ott_providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    logo VARCHAR(255),
    api_endpoint VARCHAR(255),
    api_key VARCHAR(255),
    supported_plan_types TEXT COMMENT 'JSON: array of supported plan types',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='OTT streaming service providers';

-- OTT Packages (linking OTT plans to internet packages)
CREATE TABLE IF NOT EXISTS ott_packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED COMMENT 'FK to packages.id - linked internet package',
    name VARCHAR(150) NOT NULL,
    plan_type VARCHAR(50),
    price DECIMAL(10,2) DEFAULT 0.00,
    validity_days INT DEFAULT 30,
    auto_renewal TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_provider (provider_id),
    KEY idx_package (package_id),
    FOREIGN KEY (provider_id) REFERENCES ott_providers(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='OTT packages bundled with internet packages';

-- OTT Subscriptions
CREATE TABLE IF NOT EXISTS ott_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    ott_package_id INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('active','expired','suspended','cancelled') DEFAULT 'active',
    auto_renewal TINYINT(1) DEFAULT 1,
    deactivation_reason TEXT,
    deactivated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_customer (customer_id),
    KEY idx_package (ott_package_id),
    KEY idx_status (status),
    KEY idx_expiry (expiry_date),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (ott_package_id) REFERENCES ott_packages(id)
) ENGINE=InnoDB COMMENT='Customer OTT subscription records';

-- OTT Renewal Logs
CREATE TABLE IF NOT EXISTS ott_renewal_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT UNSIGNED NOT NULL,
    renewal_type ENUM('auto','manual') DEFAULT 'auto',
    status ENUM('success','failed') NOT NULL,
    amount DECIMAL(10,2) DEFAULT 0.00,
    failure_reason TEXT,
    api_response TEXT,
    renewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_subscription (subscription_id),
    FOREIGN KEY (subscription_id) REFERENCES ott_subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='OTT subscription renewal attempt logs';

-- ============================================================
-- PHASE 6: ROLE-BASED PERMISSIONS UI
-- ============================================================

-- Role Change Logs
CREATE TABLE IF NOT EXISTS role_change_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT 'User whose role was changed',
    old_role_id INT UNSIGNED,
    new_role_id INT UNSIGNED,
    old_role_name VARCHAR(50),
    new_role_name VARCHAR(50),
    changed_by INT UNSIGNED,
    reason TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user (user_id),
    KEY idx_changed_by (changed_by),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Audit log for user role changes';

-- ============================================================
-- PHASE 6: BULK SMS & MAILING SYSTEM
-- ============================================================

-- Email Campaigns
CREATE TABLE IF NOT EXISTS email_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL COMMENT 'HTML email body',
    filter_type ENUM('all','zone','package','status','branch') DEFAULT 'all',
    filter_value VARCHAR(100),
    total_recipients INT DEFAULT 0,
    sent_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    status ENUM('draft','sending','completed','failed') DEFAULT 'draft',
    scheduled_at DATETIME,
    started_at DATETIME,
    completed_at DATETIME,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_status (status),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Email broadcast campaigns';

-- Campaign Recipients (for both SMS and email campaigns)
CREATE TABLE IF NOT EXISTS campaign_recipients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_type ENUM('sms','email') NOT NULL,
    campaign_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED,
    recipient_phone VARCHAR(20),
    recipient_email VARCHAR(150),
    status ENUM('pending','sent','failed','delivered') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    KEY idx_campaign (campaign_type, campaign_id),
    KEY idx_customer (customer_id),
    KEY idx_status (status),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Recipient list for SMS and email campaigns';

-- Campaign Logs (individual send attempt logs)
CREATE TABLE IF NOT EXISTS campaign_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_type ENUM('sms','email') NOT NULL,
    campaign_id INT UNSIGNED NOT NULL,
    recipient_id INT UNSIGNED,
    customer_id INT UNSIGNED,
    status ENUM('sent','failed','delivered') NOT NULL,
    error_message TEXT,
    gateway_response TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_campaign (campaign_type, campaign_id),
    KEY idx_status (status),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Individual send attempt logs for campaigns';

-- ============================================================
-- PHASE 6: ANDROID APP API MODULE
-- ============================================================

-- Device Tokens (FCM push notification tokens)
CREATE TABLE IF NOT EXISTS device_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    customer_id INT UNSIGNED,
    device_token VARCHAR(255) NOT NULL,
    device_type ENUM('android','ios','web') DEFAULT 'android',
    device_name VARCHAR(100),
    app_version VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_token (device_token),
    KEY idx_user (user_id),
    KEY idx_customer (customer_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='FCM device tokens for push notifications';

-- API Tokens (JWT refresh tokens)
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    customer_id INT UNSIGNED,
    token_hash VARCHAR(255) NOT NULL COMMENT 'Hashed refresh token',
    token_type ENUM('refresh','access') DEFAULT 'refresh',
    expires_at TIMESTAMP NOT NULL,
    is_revoked TINYINT(1) DEFAULT 0,
    device_info VARCHAR(255),
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP NULL,
    UNIQUE KEY uniq_token_hash (token_hash),
    KEY idx_user (user_id),
    KEY idx_customer (customer_id),
    KEY idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='JWT refresh tokens for API authentication';

-- API Rate Limits
CREATE TABLE IF NOT EXISTS api_rate_limits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(100) NOT NULL COMMENT 'IP address or user identifier',
    endpoint VARCHAR(255),
    request_count INT DEFAULT 0,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    window_end TIMESTAMP NOT NULL,
    is_blocked TINYINT(1) DEFAULT 0,
    KEY idx_identifier (identifier),
    KEY idx_window (window_start, window_end)
) ENGINE=InnoDB COMMENT='API rate limiting tracking per IP/user';


-- ============================================================
-- PHASE 7: BRANCH MANAGEMENT MODULE (new tables)
-- ============================================================

-- Branch Reports (stored generated reports)
CREATE TABLE IF NOT EXISTS branch_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    report_type ENUM('summary','revenue','customers','tickets','tasks') DEFAULT 'summary',
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    customer_count INT DEFAULT 0,
    monthly_revenue DECIMAL(12,2) DEFAULT 0.00,
    outstanding_dues DECIMAL(12,2) DEFAULT 0.00,
    active_tickets INT DEFAULT 0,
    report_data TEXT COMMENT 'JSON: full report data',
    export_format ENUM('pdf','csv','both') DEFAULT 'pdf',
    file_path VARCHAR(255),
    generated_by INT UNSIGNED,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    KEY idx_period (period_start, period_end),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Generated branch summary reports';

-- Branch Credentials (per-branch login credentials)
CREATE TABLE IF NOT EXISTS branch_credentials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL COMMENT 'FK to users table with branch_id set',
    credential_type ENUM('admin','operator','viewer') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_branch_user (branch_id, user_id),
    KEY idx_branch (branch_id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Per-branch login credential assignments';

-- ============================================================
-- PERFORMANCE INDEXES
-- ============================================================

-- HR Module indexes
CREATE INDEX IF NOT EXISTS idx_employees_status ON employees (status);
CREATE INDEX IF NOT EXISTS idx_attendance_status ON attendance (status, attendance_date);
CREATE INDEX IF NOT EXISTS idx_salary_slips_status ON salary_slips (payment_status, salary_month);
CREATE INDEX IF NOT EXISTS idx_leave_balances_year ON leave_balances (year);

-- Support Module indexes
CREATE INDEX IF NOT EXISTS idx_ticket_comments_created ON ticket_comments (created_at);
CREATE INDEX IF NOT EXISTS idx_sla_violations_ticket ON sla_violations (ticket_id, breached_at);

-- Task Module indexes
CREATE INDEX IF NOT EXISTS idx_tasks_due_status ON tasks (due_date, status);
CREATE INDEX IF NOT EXISTS idx_tasks_branch_status ON tasks (branch_id, status);

-- Sales Module indexes
CREATE INDEX IF NOT EXISTS idx_sales_invoices_date ON sales_invoices (created_at);
CREATE INDEX IF NOT EXISTS idx_sales_payments_date ON sales_payments (payment_date);

-- Purchase Module indexes
CREATE INDEX IF NOT EXISTS idx_purchase_bills_due ON purchase_bills (due_date, payment_status);
CREATE INDEX IF NOT EXISTS idx_vendor_payments_date ON vendor_payments (payment_date);

-- Accounts Module indexes
CREATE INDEX IF NOT EXISTS idx_expense_entries_date ON expense_entries (expense_date, branch_id);
CREATE INDEX IF NOT EXISTS idx_income_entries_date ON income_entries (income_date, branch_id);
CREATE INDEX IF NOT EXISTS idx_bank_deposits_date ON bank_deposits (deposit_date);

-- Asset Module indexes
CREATE INDEX IF NOT EXISTS idx_assets_warranty ON assets (warranty_expiry, status);
CREATE INDEX IF NOT EXISTS idx_asset_depreciation_year ON asset_depreciation (depreciation_year);

-- Bandwidth Module indexes
CREATE INDEX IF NOT EXISTS idx_bandwidth_purchases_period ON bandwidth_purchases (period_start, period_end);
CREATE INDEX IF NOT EXISTS idx_bandwidth_invoices_due ON bandwidth_invoices (due_date, payment_status);
CREATE INDEX IF NOT EXISTS idx_reseller_ledgers_date ON reseller_ledgers (transaction_date);

-- MAC Reseller indexes
CREATE INDEX IF NOT EXISTS idx_mac_billing_date ON mac_reseller_billing (billing_date, is_paid);
CREATE INDEX IF NOT EXISTS idx_mac_clients_status ON mac_reseller_clients (status);

-- OTT Module indexes
CREATE INDEX IF NOT EXISTS idx_ott_subscriptions_expiry ON ott_subscriptions (expiry_date, status);
CREATE INDEX IF NOT EXISTS idx_ott_renewal_logs_date ON ott_renewal_logs (renewed_at);

-- Campaign indexes
CREATE INDEX IF NOT EXISTS idx_campaign_recipients_status ON campaign_recipients (status, campaign_type);
CREATE INDEX IF NOT EXISTS idx_campaign_logs_date ON campaign_logs (sent_at);

-- API indexes
CREATE INDEX IF NOT EXISTS idx_api_tokens_expires ON api_tokens (expires_at, is_revoked);
CREATE INDEX IF NOT EXISTS idx_api_rate_limits_window ON api_rate_limits (identifier, window_end);
CREATE INDEX IF NOT EXISTS idx_device_tokens_active ON device_tokens (is_active, device_type);

-- BTRC indexes
CREATE INDEX IF NOT EXISTS idx_btrc_reports_period ON btrc_reports (report_period, status);

-- Branch indexes
CREATE INDEX IF NOT EXISTS idx_branch_reports_period ON branch_reports (period_start, period_end);

-- ============================================================
-- RE-ENABLE FOREIGN KEY CHECKS
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;
