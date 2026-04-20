-- ============================================================
-- Digital ISP ERP — Full MySQL Database Schema
-- Bangladesh ISP ERP System
-- Version: 1.0.1 | Timezone: Asia/Dhaka
-- ============================================================

CREATE DATABASE IF NOT EXISTS digital_isp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE digital_isp;

SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = '';

-- ============================================================
-- BRANCHES, ZONES, AREAS
-- ============================================================
CREATE TABLE IF NOT EXISTS branches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    manager_name VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS zones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20),
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS areas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zone_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ROLES & PERMISSIONS (RBAC)
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED,
    zone_id INT UNSIGNED,
    role_id INT UNSIGNED NOT NULL,
    username VARCHAR(80) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    avatar VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    last_ip VARCHAR(45),
    api_token VARCHAR(255),
    api_token_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_username (username),
    KEY idx_role (role_id),
    KEY idx_branch (branch_id),
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- PACKAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS package_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED,
    branch_id INT UNSIGNED,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(30) UNIQUE NOT NULL,
    speed_download VARCHAR(20) NOT NULL COMMENT 'e.g. 10M, 50M, 100M',
    speed_upload VARCHAR(20) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    otc DECIMAL(10,2) DEFAULT 0.00 COMMENT 'One-time connection charge',
    data_limit VARCHAR(30) COMMENT 'Unlimited or GB limit',
    type ENUM('pppoe','hotspot','static','cgnat') DEFAULT 'pppoe',
    billing_type ENUM('monthly','daily','prepaid') DEFAULT 'monthly',
    mikrotik_profile VARCHAR(100),
    radius_profile VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    FOREIGN KEY (category_id) REFERENCES package_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- CUSTOMERS
-- ============================================================
CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(30) UNIQUE NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    zone_id INT UNSIGNED,
    area_id INT UNSIGNED,
    package_id INT UNSIGNED,
    reseller_id INT UNSIGNED,
    full_name VARCHAR(150) NOT NULL,
    father_name VARCHAR(150),
    mother_name VARCHAR(150),
    phone VARCHAR(20) NOT NULL,
    phone_alt VARCHAR(20),
    email VARCHAR(150),
    address TEXT NOT NULL,
    billing_address TEXT,
    nid_number VARCHAR(50),
    nid_photo VARCHAR(255),
    customer_photo VARCHAR(255),
    connection_type ENUM('pppoe','hotspot','static','cgnat') DEFAULT 'pppoe',
    pppoe_username VARCHAR(100),
    pppoe_password VARCHAR(100),
    static_ip VARCHAR(45),
    status ENUM('active','suspended','pending','cancelled') DEFAULT 'pending',
    connection_date DATE,
    billing_day TINYINT DEFAULT 1 COMMENT 'Day of month when bill generates',
    monthly_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    advance_balance DECIMAL(10,2) DEFAULT 0.00,
    due_amount DECIMAL(10,2) DEFAULT 0.00,
    otc_paid TINYINT(1) DEFAULT 0,
    notes TEXT,
    lat DECIMAL(10,8),
    lng DECIMAL(11,8),
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_code (customer_code),
    KEY idx_phone (phone),
    KEY idx_status (status),
    KEY idx_branch (branch_id),
    KEY idx_zone (zone_id),
    KEY idx_pppoe (pppoe_username),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL,
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customer_kyc (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    doc_type ENUM('nid','passport','driving_license','other') NOT NULL,
    doc_number VARCHAR(50),
    doc_front VARCHAR(255),
    doc_back VARCHAR(255),
    verified TINYINT(1) DEFAULT 0,
    verified_by INT UNSIGNED,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customer_status_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    old_status VARCHAR(30),
    new_status VARCHAR(30),
    reason TEXT,
    changed_by INT UNSIGNED,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SUPPORT TICKETS
-- ============================================================
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('technical', 'billing', 'general', 'complaint', 'feedback') DEFAULT 'technical',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'pending_customer', 'resolved', 'closed') DEFAULT 'open',
    assigned_to INT UNSIGNED,
    resolved_by INT UNSIGNED,
    resolution_notes TEXT,
    resolved_at TIMESTAMP NULL,
    customer_rating ENUM('1', '2', '3', '4', '5'),
    customer_feedback TEXT,
    source ENUM('phone', 'email', 'portal', 'walk_in', 'chat') DEFAULT 'phone',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ticket_number (ticket_number),
    KEY idx_customer (customer_id),
    KEY idx_status (status),
    KEY idx_category (category),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (resolved_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- IP POOL MANAGEMENT
-- ============================================================
CREATE TABLE IF NOT EXISTS ip_pools (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED,
    name VARCHAR(100) NOT NULL,
    network_cidr VARCHAR(20) NOT NULL COMMENT 'e.g. 10.0.0.0/24',
    gateway VARCHAR(45),
    dns1 VARCHAR(45),
    dns2 VARCHAR(45),
    ip_type ENUM('public','private','cgnat') DEFAULT 'private',
    total_ips INT UNSIGNED DEFAULT 0,
    used_ips INT UNSIGNED DEFAULT 0,
    nas_id INT UNSIGNED,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- redundant alter removed

CREATE TABLE IF NOT EXISTS ip_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pool_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED,
    ip_address VARCHAR(45) NOT NULL,
    mac_address VARCHAR(20),
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    released_at TIMESTAMP NULL,
    is_assigned TINYINT(1) DEFAULT 1,
    KEY idx_ip (ip_address),
    KEY idx_customer (customer_id),
    FOREIGN KEY (pool_id) REFERENCES ip_pools(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- NAS / MIKROTIK DEVICES
-- ============================================================
CREATE TABLE IF NOT EXISTS nas_devices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED,
    name VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    api_port INT DEFAULT 8728,
    username VARCHAR(100),
    password VARCHAR(100),
    secret VARCHAR(100) COMMENT 'Radius shared secret',
    mikrotik_version VARCHAR(20) DEFAULT 'v2',
    timeout INT DEFAULT 10,
    type ENUM('mikrotik','cisco','huawei','other') DEFAULT 'mikrotik',
    description TEXT,
    connection_status TINYINT(1) DEFAULT 0,
    last_checked TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mikrotik_bandwidth_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nas_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED,
    profile_name VARCHAR(100) NOT NULL,
    rate_limit VARCHAR(100) COMMENT 'e.g. 10M/10M',
    burst_limit VARCHAR(100),
    burst_threshold VARCHAR(100),
    burst_time VARCHAR(20),
    is_synced TINYINT(1) DEFAULT 0,
    last_synced TIMESTAMP NULL,
    FOREIGN KEY (nas_id) REFERENCES nas_devices(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS radius_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    profile VARCHAR(100),
    simultaneous_use INT DEFAULT 1,
    expiration DATE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_username (username),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- BILLING & INVOICES
-- ============================================================
CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED,
    billing_month DATE NOT NULL COMMENT 'First day of billing month',
    amount DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    vat DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    due_amount DECIMAL(10,2) NOT NULL,
    status ENUM('unpaid','partial','paid','waived','cancelled') DEFAULT 'unpaid',
    due_date DATE,
    is_prorata TINYINT(1) DEFAULT 0,
    prorata_days INT DEFAULT 0,
    notes TEXT,
    generated_by INT UNSIGNED,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_customer (customer_id),
    KEY idx_status (status),
    KEY idx_branch (branch_id),
    KEY idx_month (billing_month),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED,
    branch_id INT UNSIGNED NOT NULL,
    collector_id INT UNSIGNED,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','mobile_banking','bank_transfer','online','other') DEFAULT 'cash',
    mobile_banking_ref VARCHAR(100) COMMENT 'bKash/Nagad transaction ID',
    notes TEXT,
    is_advance TINYINT(1) DEFAULT 0,
    collected_by_reseller TINYINT(1) DEFAULT 0,
    reseller_id INT UNSIGNED,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_customer (customer_id),
    KEY idx_receipt (receipt_number),
    KEY idx_date (payment_date),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;

-- ============================================================
-- GPON / FIBER NETWORK
-- ============================================================
ALTER TABLE olts ADD COLUMN IF NOT EXISTS protocol ENUM('telnet','ssh','snmp','http','https') DEFAULT 'ssh' AFTER ip_address;
ALTER TABLE olts ADD COLUMN IF NOT EXISTS access_port INT DEFAULT 22 AFTER protocol;
ALTER TABLE olts ADD COLUMN IF NOT EXISTS username VARCHAR(100) AFTER access_port;
ALTER TABLE olts ADD COLUMN IF NOT EXISTS password VARCHAR(255) AFTER username;
ALTER TABLE olts ADD COLUMN IF NOT EXISTS snmp_community VARCHAR(100) AFTER password;
ALTER TABLE olts ADD COLUMN IF NOT EXISTS snmp_version ENUM('v1','v2c','v3') DEFAULT 'v2c' AFTER snmp_community;
ALTER TABLE olts ADD COLUMN IF NOT EXISTS connection_status ENUM('online','offline','unknown') DEFAULT 'unknown' AFTER is_active;
ALTER TABLE olts ADD COLUMN IF NOT EXISTS last_checked_at DATETIME AFTER connection_status;

ALTER TABLE onus ADD COLUMN IF NOT EXISTS olt_port INT AFTER splitter_port;
ALTER TABLE onus ADD COLUMN IF NOT EXISTS deregister_time DATETIME AFTER status;
ALTER TABLE onus ADD COLUMN IF NOT EXISTS deregister_reason VARCHAR(200) AFTER deregister_time;
ALTER TABLE onus ADD COLUMN IF NOT EXISTS last_synced_at DATETIME AFTER deregister_reason;
ALTER TABLE onus ADD COLUMN IF NOT EXISTS previous_snapshot TEXT AFTER last_synced_at;
ALTER TABLE onus ADD COLUMN IF NOT EXISTS description TEXT AFTER previous_snapshot;

CREATE TABLE IF NOT EXISTS olts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED,
    name VARCHAR(100) NOT NULL,
    model VARCHAR(100),
    ip_address VARCHAR(45),
    management_ip VARCHAR(45),
    protocol ENUM('telnet','ssh','snmp','http','https') DEFAULT 'ssh',
    access_port INT DEFAULT 22,
    username VARCHAR(100),
    password VARCHAR(255),
    snmp_community VARCHAR(100),
    snmp_version ENUM('v1','v2c','v3') DEFAULT 'v2c',
    total_ports INT DEFAULT 16,
    location TEXT,
    is_active TINYINT(1) DEFAULT 1,
    installed_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS splitters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    olt_id INT UNSIGNED,
    parent_splitter_id INT UNSIGNED COMMENT 'For cascaded splitters',
    name VARCHAR(100) NOT NULL,
    ratio ENUM('1:2','1:4','1:8','1:16','1:32','1:64') DEFAULT '1:8',
    location TEXT,
    olt_port INT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    distance_from_olt DECIMAL(8,2) COMMENT 'in meters',
    is_active TINYINT(1) DEFAULT 1,
    installed_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (olt_id) REFERENCES olts(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_splitter_id) REFERENCES splitters(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS onus (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED,
    splitter_id INT UNSIGNED,
    branch_id INT UNSIGNED,
    serial_number VARCHAR(100) UNIQUE NOT NULL,
    model VARCHAR(100),
    brand VARCHAR(50),
    onu_type ENUM('indoor','outdoor','router') DEFAULT 'indoor',
    mac_address VARCHAR(20),
    ip_address VARCHAR(45),
    splitter_port INT,
    signal_level DECIMAL(5,2) COMMENT 'dBm',
    installed_date DATE,
    status ENUM('stock','installed','faulty','returned') DEFAULT 'stock',
    warranty_expiry DATE,
    purchase_price DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (splitter_id) REFERENCES splitters(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fiber_incidents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED,
    zone_id INT UNSIGNED,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    location TEXT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    affected_customers INT DEFAULT 0,
    severity ENUM('low','medium','high','critical') DEFAULT 'medium',
    status ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
    reported_by INT UNSIGNED,
    assigned_to INT UNSIGNED,
    resolved_at TIMESTAMP NULL,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- INVENTORY
-- ============================================================
CREATE TABLE IF NOT EXISTS item_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS warehouses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    manager_name VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED,
    warehouse_id INT UNSIGNED,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) UNIQUE,
    unit VARCHAR(20) DEFAULT 'pcs',
    quantity INT DEFAULT 0,
    minimum_stock INT DEFAULT 5 COMMENT 'Alert threshold',
    purchase_price DECIMAL(10,2),
    sale_price DECIMAL(10,2),
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES item_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    warehouse_id INT UNSIGNED,
    movement_type ENUM('purchase','installation','return','transfer','damaged','adjustment') NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    reference_id INT UNSIGNED COMMENT 'work_order_id or purchase_order_id',
    reference_type VARCHAR(30),
    customer_id INT UNSIGNED,
    performed_by INT UNSIGNED,
    notes TEXT,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(30) UNIQUE NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED,
    warehouse_id INT UNSIGNED,
    total_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('draft','ordered','received','cancelled') DEFAULT 'draft',
    order_date DATE,
    received_date DATE,
    notes TEXT,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id)
) ENGINE=InnoDB;

-- ============================================================
-- RESELLER SYSTEM
-- ============================================================
CREATE TABLE IF NOT EXISTS resellers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    parent_reseller_id INT UNSIGNED COMMENT 'For sub-reseller tree',
    branch_id INT UNSIGNED,
    zone_id INT UNSIGNED,
    business_name VARCHAR(150),
    contact_person VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150),
    address TEXT,
    commission_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Percentage',
    balance DECIMAL(12,2) DEFAULT 0.00,
    credit_limit DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('active','suspended','inactive') DEFAULT 'active',
    joined_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_reseller_id) REFERENCES resellers(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reseller_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reseller_id INT UNSIGNED NOT NULL,
    transaction_type ENUM('topup','commission','payment_collected','deduction','refund') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_before DECIMAL(10,2),
    balance_after DECIMAL(10,2),
    reference VARCHAR(100),
    notes TEXT,
    performed_by INT UNSIGNED,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reseller_id) REFERENCES resellers(id)
) ENGINE=InnoDB;

-- ============================================================
-- WORK ORDERS
-- ============================================================
CREATE TABLE IF NOT EXISTS technicians (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    branch_id INT UNSIGNED,
    zone_id INT UNSIGNED,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    specialization VARCHAR(100),
    is_available TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS work_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wo_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INT UNSIGNED,
    branch_id INT UNSIGNED NOT NULL,
    zone_id INT UNSIGNED,
    technician_id INT UNSIGNED,
    type ENUM('new_connection','disconnection','reconnection','maintenance','fiber_repair','equipment_swap','other') NOT NULL,
    priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
    title VARCHAR(200) NOT NULL,
    description TEXT,
    address TEXT,
    status ENUM('pending','assigned','in_progress','completed','cancelled') DEFAULT 'pending',
    scheduled_date DATETIME,
    completed_at TIMESTAMP NULL,
    completion_notes TEXT,
    items_used TEXT COMMENT 'JSON: inventory items used',
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_status (status),
    KEY idx_branch (branch_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL,
    FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- COLLECTION SYSTEM
-- ============================================================
CREATE TABLE IF NOT EXISTS collection_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    collector_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    zone_id INT UNSIGNED,
    session_date DATE NOT NULL,
    target_amount DECIMAL(10,2) DEFAULT 0.00,
    total_collected DECIMAL(10,2) DEFAULT 0.00,
    total_customers INT DEFAULT 0,
    status ENUM('active','closed','submitted') DEFAULT 'active',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    FOREIGN KEY (collector_id) REFERENCES users(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;

-- ============================================================
-- SMS SYSTEM
-- ============================================================
CREATE TABLE IF NOT EXISTS sms_gateways (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    api_url VARCHAR(255),
    api_key VARCHAR(255),
    sender_id VARCHAR(30),
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sms_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    event_type ENUM('bill_generated','payment_received','due_reminder','suspension','reconnection','welcome','otp','custom') NOT NULL,
    message_bn TEXT COMMENT 'Bangla message template',
    message_en TEXT COMMENT 'English message template',
    variables TEXT COMMENT 'JSON: available variables',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sms_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gateway_id INT UNSIGNED,
    customer_id INT UNSIGNED,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    template_id INT UNSIGNED,
    status ENUM('pending','sent','failed','delivered') DEFAULT 'pending',
    gateway_response TEXT,
    cost DECIMAL(5,4) DEFAULT 0.00,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sms_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS automation_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_type VARCHAR(50) NOT NULL,
    status ENUM('success','error','skipped') DEFAULT 'success',
    affected INT DEFAULT 0,
    message TEXT,
    details TEXT,
    run_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- FINANCE
-- ============================================================
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    parent_id INT UNSIGNED,
    description TEXT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS expenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED,
    title VARCHAR(200) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    vendor VARCHAR(150),
    payment_method ENUM('cash','bank','mobile_banking') DEFAULT 'cash',
    receipt_file VARCHAR(255),
    expense_date DATE NOT NULL,
    notes TEXT,
    approved_by INT UNSIGNED,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cashbook_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    entry_type ENUM('debit','credit') NOT NULL,
    entry_category ENUM('payment_received','expense','advance','refund','transfer','other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reference_id INT UNSIGNED,
    reference_type VARCHAR(30),
    description TEXT,
    entry_date DATE NOT NULL,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_branch_date (branch_id, entry_date),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS daily_closings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    closing_date DATE NOT NULL,
    opening_balance DECIMAL(12,2) DEFAULT 0.00,
    total_collection DECIMAL(12,2) DEFAULT 0.00,
    total_expense DECIMAL(12,2) DEFAULT 0.00,
    closing_balance DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT,
    closed_by INT UNSIGNED,
    closed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_branch_date (branch_id, closing_date),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;

-- ============================================================
-- ACTIVITY / AUDIT LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    record_id INT UNSIGNED,
    old_values TEXT COMMENT 'JSON before',
    new_values TEXT COMMENT 'JSON after',
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user (user_id),
    KEY idx_module (module),
    KEY idx_date (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SETTINGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    module VARCHAR(50),
    description TEXT,
    is_public TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_module (module)
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default roles
INSERT IGNORE INTO roles (name, display_name, description) VALUES
('superadmin', 'Super Admin', 'Full system access'),
('admin', 'Admin', 'Branch-level full access'),
('zone_manager', 'Zone Manager', 'Zone-restricted management'),
('staff', 'Staff', 'Limited operations'),
('collector', 'Collector', 'Collection only panel'),
('reseller', 'Reseller', 'Reseller self-service panel');

-- Default branch
INSERT IGNORE INTO branches (name, code, address, phone) VALUES
('Head Office', 'HO', 'Dhaka, Bangladesh', '01700000000');

-- Default superadmin user (password: Admin@1234)
INSERT IGNORE INTO users (branch_id, role_id, username, email, phone, password_hash, full_name, is_active)
VALUES (1, 1, 'admin', 'admin@digitalisp.com', '01700000000',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: Admin@1234
        'System Admin', 1);

-- Default package categories
INSERT IGNORE INTO package_categories (name) VALUES ('Home'), ('Business'), ('CGNAT'), ('Fiber');

-- Default packages
INSERT IGNORE INTO packages (category_id, branch_id, name, code, speed_download, speed_upload, price, otc, type, billing_type, mikrotik_profile, is_active)
VALUES
(1, 1, 'Home 10 Mbps', 'HOME-10M', '10M', '10M', 500.00, 1000.00, 'pppoe', 'monthly', '10M-HOME', 1),
(1, 1, 'Home 25 Mbps', 'HOME-25M', '25M', '25M', 800.00, 1000.00, 'pppoe', 'monthly', '25M-HOME', 1),
(1, 1, 'Home 50 Mbps', 'HOME-50M', '50M', '50M', 1200.00, 1000.00, 'pppoe', 'monthly', '50M-HOME', 1),
(2, 1, 'Business 100 Mbps', 'BIZ-100M', '100M', '100M', 3000.00, 3000.00, 'pppoe', 'monthly', '100M-BIZ', 1),
(4, 1, 'Fiber 50 Mbps', 'FIB-50M', '50M', '50M', 999.00, 2000.00, 'pppoe', 'monthly', '50M-FIB', 1);

-- SMS Templates
INSERT IGNORE INTO sms_templates (name, event_type, message_bn, message_en) VALUES
('Bill Generated', 'bill_generated',
 'প্রিয় {name}, আপনার {month} মাসের বিল {amount} টাকা তৈরি হয়েছে। পরিশোধের শেষ তারিখ: {due_date}। Digital ISP ERP',
 'Dear {name}, Your {month} bill of {amount} BDT is generated. Due date: {due_date}. Digital ISP ERP'),
('Payment Received', 'payment_received',
 'প্রিয় {name}, আপনার {amount} টাকা পেমেন্ট গ্রহণ করা হয়েছে। রসিদ নং: {receipt}। ধন্যবাদ, Digital ISP ERP',
 'Dear {name}, Payment of {amount} BDT received. Receipt: {receipt}. Thank you, Digital ISP ERP'),
('Due Reminder', 'due_reminder',
 'প্রিয় {name}, আপনার {amount} টাকা বিল বকেয়া আছে। এখনই পরিশোধ করুন। Digital ISP ERP',
 'Dear {name}, Bill of {amount} BDT is overdue. Please pay now. Digital ISP ERP'),
('Welcome', 'welcome',
 'প্রিয় {name}, Digital ISP ERP-এ স্বাগতম! আপনার সংযোগ সক্রিয় হয়েছে। ব্যবহারকারী: {username}। Digital ISP ERP',
 'Dear {name}, Welcome to Digital ISP ERP! Your connection is active. Username: {username}. Digital ISP ERP');

-- Permissions
INSERT IGNORE INTO permissions (name, module, description) VALUES
('customers.view', 'customers', 'View customers'),
('customers.create', 'customers', 'Create new customer'),
('customers.edit', 'customers', 'Edit customer'),
('customers.delete', 'customers', 'Delete customer'),
('customers.suspend', 'customers', 'Suspend/reconnect customer'),
('billing.view', 'billing', 'View invoices'),
('billing.create', 'billing', 'Generate invoices'),
('billing.payment', 'billing', 'Record payments'),
('billing.report', 'billing', 'View billing reports'),
('network.view', 'network', 'View network resources'),
('network.manage', 'network', 'Manage IP pools and NAS'),
('network.mikrotik', 'network', 'Control MikroTik'),
('gpon.view', 'gpon', 'View GPON resources'),
('gpon.manage', 'gpon', 'Manage OLT/ONU'),
('inventory.view', 'inventory', 'View inventory'),
('inventory.manage', 'inventory', 'Manage stock'),
('resellers.view', 'resellers', 'View resellers'),
('resellers.manage', 'resellers', 'Manage resellers'),
('workorders.view', 'workorders', 'View work orders'),
('workorders.manage', 'workorders', 'Create/manage work orders'),
('reports.view', 'reports', 'View reports'),
('finance.view', 'finance', 'View finance'),
('finance.manage', 'finance', 'Manage cashbook/expenses'),
('users.manage', 'admin', 'Manage users and roles'),
('settings.manage', 'admin', 'System settings'),
('collection.do', 'collection', 'Perform collections');
