-- ============================================================
-- Digital ISP ERP — Full MySQL Database Schema
-- Bangladesh ISP ERP System
-- Version: 1.0.0 | Timezone: Asia/Dhaka
-- ============================================================





-- ============================================================
-- BRANCHES, ZONES, AREAS
-- ============================================================
CREATE TABLE IF NOT EXISTS branches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    manager_name VARCHAR(100),
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS zones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20),
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS areas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    zone_id INTEGER NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE
);

-- ============================================================
-- ROLES & PERMISSIONS (RBAC)
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- ============================================================
-- USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER,
    zone_id INTEGER,
    role_id INTEGER NOT NULL,
    username VARCHAR(80) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    avatar VARCHAR(255),
    is_active INTEGER DEFAULT 1,
    last_login TIMESTAMP NULL,
    last_ip VARCHAR(45),
    api_token VARCHAR(255),
    api_token_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL
);

-- ============================================================
-- PACKAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS package_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS packages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER,
    branch_id INTEGER,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(30) UNIQUE NOT NULL,
    speed_download VARCHAR(20) NOT NULL ,
    speed_upload VARCHAR(20) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    otc DECIMAL(10,2) DEFAULT 0.00 ,
    data_limit VARCHAR(30) ,
    type TEXT DEFAULT 'pppoe',
    billing_type TEXT DEFAULT 'monthly',
    mikrotik_profile VARCHAR(100),
    radius_profile VARCHAR(100),
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES package_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

-- ============================================================
-- CUSTOMERS
-- ============================================================
CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_code VARCHAR(30) UNIQUE NOT NULL,
    branch_id INTEGER NOT NULL,
    zone_id INTEGER,
    area_id INTEGER,
    package_id INTEGER,
    reseller_id INTEGER,
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
    connection_type TEXT DEFAULT 'pppoe',
    pppoe_username VARCHAR(100),
    pppoe_password VARCHAR(100),
    static_ip VARCHAR(45),
    status TEXT DEFAULT 'pending',
    connection_date DATE,
    billing_day INTEGER DEFAULT 1 ,
    monthly_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    advance_balance DECIMAL(10,2) DEFAULT 0.00,
    due_amount DECIMAL(10,2) DEFAULT 0.00,
    otc_paid INTEGER DEFAULT 0,
    notes TEXT,
    lat DECIMAL(10,8),
    lng DECIMAL(11,8),
    nas_id INTEGER,
    mikrotik_profile VARCHAR(100),
    last_online_at DATETIME,
    current_ip VARCHAR(45),
    expiration DATE,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Extended client fields (from client list)
    mac_address VARCHAR(20),
    road_no VARCHAR(50),
    house_no VARCHAR(50),
    sub_zone VARCHAR(100),
    box_no VARCHAR(50),
    client_type TEXT DEFAULT 'home',
    thana VARCHAR(100),
    district VARCHAR(100),
    device_name VARCHAR(100),
    device_purchase_date DATE,
    assigned_employee VARCHAR(100),
    -- Customer Portal Columns
    portal_active INTEGER DEFAULT 0,
    portal_password VARCHAR(255),
    portal_last_login DATETIME,
    portal_otp VARCHAR(255),
    portal_otp_expires DATETIME,
    secret_question VARCHAR(255),
    secret_answer VARCHAR(255),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL,
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS customer_kyc (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    doc_type TEXT NOT NULL,
    doc_number VARCHAR(50),
    doc_front VARCHAR(255),
    doc_back VARCHAR(255),
    verified INTEGER DEFAULT 0,
    verified_by INTEGER,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS customer_status_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    old_status VARCHAR(30),
    new_status VARCHAR(30),
    reason TEXT,
    changed_by INTEGER,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- ============================================================
-- IP POOL MANAGEMENT
-- ============================================================
CREATE TABLE IF NOT EXISTS ip_pools (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER,
    name VARCHAR(100) NOT NULL,
    network_cidr VARCHAR(20) NOT NULL ,
    gateway VARCHAR(45),
    dns1 VARCHAR(45),
    dns2 VARCHAR(45),
    ip_type TEXT DEFAULT 'private',
    total_ips INTEGER DEFAULT 0,
    used_ips INTEGER DEFAULT 0,
    nas_id INTEGER,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

-- redundant alter removed

CREATE TABLE IF NOT EXISTS ip_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pool_id INTEGER NOT NULL,
    customer_id INTEGER,
    ip_address VARCHAR(45) NOT NULL,
    mac_address VARCHAR(20),
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    released_at TIMESTAMP NULL,
    is_assigned INTEGER DEFAULT 1,
    FOREIGN KEY (pool_id) REFERENCES ip_pools(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

-- ============================================================
-- NAS / MIKROTIK DEVICES
-- ============================================================
CREATE TABLE IF NOT EXISTS nas_devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER,
    name VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    api_port INTEGER DEFAULT 8728,
    username VARCHAR(100),
    password VARCHAR(100),
    secret VARCHAR(100),
    mikrotik_version VARCHAR(20) DEFAULT 'v2',
    timeout INTEGER DEFAULT 10,
    type TEXT DEFAULT 'mikrotik',
    description TEXT,
    connection_status INTEGER DEFAULT 0,
    last_checked TIMESTAMP NULL,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS mikrotik_bandwidth_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nas_id INTEGER NOT NULL,
    package_id INTEGER,
    profile_name VARCHAR(100) NOT NULL,
    rate_limit VARCHAR(100) ,
    burst_limit VARCHAR(100),
    burst_threshold VARCHAR(100),
    burst_time VARCHAR(20),
    is_synced INTEGER DEFAULT 0,
    last_synced TIMESTAMP NULL,
    FOREIGN KEY (nas_id) REFERENCES nas_devices(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
);

-- ============================================================
-- RADIUS AAA TABLES
-- ============================================================
CREATE TABLE IF NOT EXISTS radcheck (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(64) NOT NULL DEFAULT '',
    attribute VARCHAR(64) NOT NULL DEFAULT '',
    op VARCHAR(2) NOT NULL DEFAULT '==',
    value VARCHAR(253) NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS radcheck_username ON radcheck(username);

CREATE TABLE IF NOT EXISTS radreply (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(64) NOT NULL DEFAULT '',
    attribute VARCHAR(64) NOT NULL DEFAULT '',
    op VARCHAR(2) NOT NULL DEFAULT '=',
    value VARCHAR(253) NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS radreply_username ON radreply(username);

CREATE TABLE IF NOT EXISTS radgroupcheck (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    groupname VARCHAR(64) NOT NULL DEFAULT '',
    attribute VARCHAR(64) NOT NULL DEFAULT '',
    op VARCHAR(2) NOT NULL DEFAULT '==',
    value VARCHAR(253) NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS radgroupcheck_groupname ON radgroupcheck(groupname);

CREATE TABLE IF NOT EXISTS radgroupreply (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    groupname VARCHAR(64) NOT NULL DEFAULT '',
    attribute VARCHAR(64) NOT NULL DEFAULT '',
    op VARCHAR(2) NOT NULL DEFAULT '=',
    value VARCHAR(253) NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS radgroupreply_groupname ON radgroupreply(groupname);

CREATE TABLE IF NOT EXISTS radusergroup (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(64) NOT NULL DEFAULT '',
    groupname VARCHAR(64) NOT NULL DEFAULT '',
    priority INTEGER NOT NULL DEFAULT 1
);
CREATE INDEX IF NOT EXISTS radusergroup_username ON radusergroup(username);

CREATE TABLE IF NOT EXISTS radacct (
    radacctid INTEGER PRIMARY KEY AUTOINCREMENT,
    acctsessionid VARCHAR(64) NOT NULL DEFAULT '',
    acctuniqueid VARCHAR(32) NOT NULL DEFAULT '',
    username VARCHAR(64) NOT NULL DEFAULT '',
    groupname VARCHAR(64) NOT NULL DEFAULT '',
    realm VARCHAR(64) DEFAULT '',
    nasipaddress VARCHAR(45) NOT NULL DEFAULT '',
    nasportid VARCHAR(32) DEFAULT NULL,
    nasporttype VARCHAR(32) DEFAULT NULL,
    acctstarttime TIMESTAMP NULL DEFAULT NULL,
    acctupdatetime TIMESTAMP NULL DEFAULT NULL,
    acctstoptime TIMESTAMP NULL DEFAULT NULL,
    acctinterval INTEGER DEFAULT NULL,
    acctsessiontime INTEGER DEFAULT NULL,
    acctauthentic VARCHAR(32) DEFAULT NULL,
    connectinfo_start VARCHAR(50) DEFAULT NULL,
    connectinfo_stop VARCHAR(50) DEFAULT NULL,
    acctinputoctets INTEGER DEFAULT NULL,
    acctoutputoctets INTEGER DEFAULT NULL,
    calledstationid VARCHAR(50) NOT NULL DEFAULT '',
    callingstationid VARCHAR(50) NOT NULL DEFAULT '',
    acctterminatecause VARCHAR(32) NOT NULL DEFAULT '',
    servicetype VARCHAR(32) DEFAULT NULL,
    framedprotocol VARCHAR(32) DEFAULT NULL,
    framedipaddress VARCHAR(45) NOT NULL DEFAULT ''
);
CREATE INDEX IF NOT EXISTS radacct_username ON radacct(username);
CREATE INDEX IF NOT EXISTS radacct_framedipaddress ON radacct(framedipaddress);
CREATE INDEX IF NOT EXISTS radacct_acctstarttime ON radacct(acctstarttime);

CREATE TABLE IF NOT EXISTS radius_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    profile VARCHAR(100),
    simultaneous_use INTEGER DEFAULT 1,
    expiration DATE,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- ============================================================
-- MAC BINDING / CALLERID MANAGEMENT
-- ============================================================
CREATE TABLE IF NOT EXISTS mac_bindings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(100) NOT NULL,
    device_type TEXT DEFAULT 'router',          -- 'onu' | 'router' | 'other'
    binding_type TEXT DEFAULT 'pppoe_callerid', -- 'pppoe_callerid' | 'mac_auth' | 'static_ip'
    mac_address VARCHAR(20) NOT NULL,
    caller_id VARCHAR(50),
    onu_serial VARCHAR(100),
    router_brand VARCHAR(80),
    router_model VARCHAR(80),
    ip_address VARCHAR(45),
    nas_id INTEGER,
    customer_id INTEGER,
    is_active INTEGER DEFAULT 1,
    is_allowed INTEGER DEFAULT 1,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nas_id) REFERENCES nas_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS mac_bindings_username ON mac_bindings(username);
CREATE INDEX IF NOT EXISTS mac_bindings_mac ON mac_bindings(mac_address);

CREATE TABLE IF NOT EXISTS mac_filters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mac_address VARCHAR(20) NOT NULL,
    action TEXT DEFAULT 'allow',
    nas_id INTEGER,
    customer_id INTEGER,
    reason VARCHAR(255),
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (nas_id) REFERENCES nas_devices(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS mac_filters_mac ON mac_filters(mac_address);
CREATE INDEX IF NOT EXISTS mac_filters_action ON mac_filters(action);

-- ============================================================
-- BILLING & INVOICES
-- ============================================================
CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INTEGER NOT NULL,
    branch_id INTEGER NOT NULL,
    package_id INTEGER,
    billing_month DATE NOT NULL ,
    amount DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    vat DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    due_amount DECIMAL(10,2) NOT NULL,
    status TEXT DEFAULT 'unpaid',
    due_date DATE,
    is_prorata INTEGER DEFAULT 0,
    prorata_days INTEGER DEFAULT 0,
    notes TEXT,
    generated_by INTEGER,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    receipt_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INTEGER NOT NULL,
    invoice_id INTEGER,
    branch_id INTEGER NOT NULL,
    collector_id INTEGER,
    amount DECIMAL(10,2) NOT NULL,
    payment_method TEXT DEFAULT 'cash',
    mobile_banking_ref VARCHAR(100) ,
    notes TEXT,
    is_advance INTEGER DEFAULT 0,
    collected_by_reseller INTEGER DEFAULT 0,
    reseller_id INTEGER,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- ============================================================
-- GPON / FIBER NETWORK
-- ============================================================


CREATE TABLE IF NOT EXISTS olts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER,
    name VARCHAR(100) NOT NULL,
    model VARCHAR(100),
    ip_address VARCHAR(45),
    management_ip VARCHAR(45),
    protocol TEXT DEFAULT 'ssh',
    access_port INTEGER DEFAULT 22,
    telnet_port INTEGER DEFAULT 23,
    username VARCHAR(100),
    password VARCHAR(255),
    snmp_community VARCHAR(100),
    snmp_version TEXT DEFAULT 'v2c',
    connection_status TEXT DEFAULT 'unknown',
    last_checked_at DATETIME,
    total_ports INTEGER DEFAULT 16,
    location TEXT,
    is_active INTEGER DEFAULT 1,
    installed_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS splitters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    olt_id INTEGER,
    parent_splitter_id INTEGER ,
    name VARCHAR(100) NOT NULL,
    ratio TEXT DEFAULT '1:8',
    location TEXT,
    olt_port INTEGER,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    distance_from_olt DECIMAL(8,2) ,
    is_active INTEGER DEFAULT 1,
    installed_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (olt_id) REFERENCES olts(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_splitter_id) REFERENCES splitters(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS onus (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER,
    splitter_id INTEGER,
    branch_id INTEGER,
    serial_number VARCHAR(100) UNIQUE NOT NULL,
    model VARCHAR(100),
    brand VARCHAR(50),
    onu_type TEXT DEFAULT 'indoor',
    mac_address VARCHAR(20),
    ip_address VARCHAR(45),
    splitter_port INTEGER,
    olt_port INTEGER,
    olt_id INTEGER,
    signal_level DECIMAL(5,2) ,
    installed_date DATE,
    status TEXT DEFAULT 'stock',
    warranty_expiry DATE,
    purchase_price DECIMAL(10,2),
    deregister_time DATETIME,
    deregister_reason VARCHAR(200),
    last_synced_at DATETIME,
    previous_snapshot TEXT,
    description TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (splitter_id) REFERENCES splitters(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS fiber_incidents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER,
    zone_id INTEGER,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    location TEXT,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    affected_customers INTEGER DEFAULT 0,
    severity TEXT DEFAULT 'medium',
    status TEXT DEFAULT 'open',
    reported_by INTEGER,
    assigned_to INTEGER,
    resolved_at TIMESTAMP NULL,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL
);

-- ============================================================
-- INVENTORY
-- ============================================================
CREATE TABLE IF NOT EXISTS item_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    notes TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS warehouses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    manager_name VARCHAR(100),
    is_active INTEGER DEFAULT 1,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS inventory_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER,
    warehouse_id INTEGER,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50) UNIQUE,
    unit VARCHAR(20) DEFAULT 'pcs',
    quantity INTEGER DEFAULT 0,
    minimum_stock INTEGER DEFAULT 5 ,
    purchase_price DECIMAL(10,2),
    sale_price DECIMAL(10,2),
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES item_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS stock_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_id INTEGER NOT NULL,
    warehouse_id INTEGER,
    movement_type TEXT NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    reference_id INTEGER ,
    reference_type VARCHAR(30),
    customer_id INTEGER,
    performed_by INTEGER,
    notes TEXT,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS purchase_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    po_number VARCHAR(30) UNIQUE NOT NULL,
    supplier_id INTEGER NOT NULL,
    branch_id INTEGER,
    warehouse_id INTEGER,
    total_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    status TEXT DEFAULT 'draft',
    order_date DATE,
    received_date DATE,
    notes TEXT,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    po_id INTEGER NOT NULL,
    item_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id)
);

-- ============================================================
-- RESELLER SYSTEM
-- ============================================================
CREATE TABLE IF NOT EXISTS resellers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    parent_reseller_id INTEGER ,
    branch_id INTEGER,
    zone_id INTEGER,
    business_name VARCHAR(150),
    contact_person VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150),
    address TEXT,
    commission_rate DECIMAL(5,2) DEFAULT 0.00 ,
    balance DECIMAL(12,2) DEFAULT 0.00,
    credit_limit DECIMAL(12,2) DEFAULT 0.00,
    status TEXT DEFAULT 'active',
    joined_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_reseller_id) REFERENCES resellers(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS reseller_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reseller_id INTEGER NOT NULL,
    transaction_type TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_before DECIMAL(10,2),
    balance_after DECIMAL(10,2),
    reference VARCHAR(100),
    notes TEXT,
    performed_by INTEGER,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reseller_id) REFERENCES resellers(id)
);

-- ============================================================
-- MAC RESELLER SYSTEM
-- ============================================================
CREATE TABLE IF NOT EXISTS mac_resellers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER,
    business_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150),
    address TEXT,
    balance DECIMAL(12,2) DEFAULT 0.00,
    credit_limit DECIMAL(12,2) DEFAULT 0.00,
    commission_rate DECIMAL(5,2) DEFAULT 0.00,
    status TEXT DEFAULT 'active',
    joined_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS mac_reseller_tariffs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mac_reseller_id INTEGER NOT NULL,
    name VARCHAR(100) NOT NULL,
    speed_download INTEGER DEFAULT 0,
    speed_upload INTEGER DEFAULT 0,
    daily_rate DECIMAL(10,2) DEFAULT 0.00,
    monthly_rate DECIMAL(10,2) DEFAULT 0.00,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mac_reseller_id) REFERENCES mac_resellers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS mac_reseller_clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mac_reseller_id INTEGER NOT NULL,
    tariff_id INTEGER,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    mac_address VARCHAR(17) NOT NULL,
    ip_address VARCHAR(45),
    balance DECIMAL(10,2) DEFAULT 0.00,
    status TEXT DEFAULT 'active',
    joined_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mac_reseller_id) REFERENCES mac_resellers(id) ON DELETE CASCADE,
    FOREIGN KEY (tariff_id) REFERENCES mac_reseller_tariffs(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS mac_reseller_billing (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mac_reseller_id INTEGER NOT NULL,
    client_id INTEGER NOT NULL,
    billing_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status TEXT DEFAULT 'unpaid',
    paid_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mac_reseller_id) REFERENCES mac_resellers(id),
    FOREIGN KEY (client_id) REFERENCES mac_reseller_clients(id)
);

CREATE TABLE IF NOT EXISTS mac_reseller_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mac_reseller_id INTEGER NOT NULL,
    transaction_type TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    balance_before DECIMAL(10,2),
    balance_after DECIMAL(10,2),
    reference VARCHAR(100),
    notes TEXT,
    performed_by INTEGER,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mac_reseller_id) REFERENCES mac_resellers(id)
);

-- ============================================================
-- WORK ORDERS
-- ============================================================
CREATE TABLE IF NOT EXISTS technicians (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    branch_id INTEGER,
    zone_id INTEGER,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    specialization VARCHAR(100),
    is_available INTEGER DEFAULT 1,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS work_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    wo_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INTEGER,
    branch_id INTEGER NOT NULL,
    zone_id INTEGER,
    technician_id INTEGER,
    type TEXT NOT NULL,
    priority TEXT DEFAULT 'normal',
    title VARCHAR(200) NOT NULL,
    description TEXT,
    address TEXT,
    status TEXT DEFAULT 'pending',
    scheduled_date DATETIME,
    completed_at TIMESTAMP NULL,
    completion_notes TEXT,
    items_used TEXT ,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL,
    FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE SET NULL
);

-- ============================================================
-- COLLECTION SYSTEM
-- ============================================================
CREATE TABLE IF NOT EXISTS collection_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    collector_id INTEGER NOT NULL,
    branch_id INTEGER NOT NULL,
    zone_id INTEGER,
    session_date DATE NOT NULL,
    target_amount DECIMAL(10,2) DEFAULT 0.00,
    total_collected DECIMAL(10,2) DEFAULT 0.00,
    total_customers INTEGER DEFAULT 0,
    status TEXT DEFAULT 'active',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    FOREIGN KEY (collector_id) REFERENCES users(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- ============================================================
-- SMS SYSTEM
-- ============================================================
CREATE TABLE IF NOT EXISTS sms_gateways (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    api_url VARCHAR(255),
    api_key VARCHAR(255),
    sender_id VARCHAR(30),
    is_default INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS sms_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    event_type TEXT NOT NULL,
    message_bn TEXT ,
    message_en TEXT ,
    variables TEXT ,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sms_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    gateway_id INTEGER,
    customer_id INTEGER,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    template_id INTEGER,
    status TEXT DEFAULT 'pending',
    gateway_response TEXT,
    cost DECIMAL(5,4) DEFAULT 0.00,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS sms_campaigns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    filter_type TEXT DEFAULT 'all',
    filter_value VARCHAR(100),
    total_recipients INTEGER DEFAULT 0,
    sent_count INTEGER DEFAULT 0,
    failed_count INTEGER DEFAULT 0,
    status TEXT DEFAULT 'draft',
    scheduled_at DATETIME,
    started_at DATETIME,
    completed_at DATETIME,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS automation_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    job_type TEXT NOT NULL,
    status TEXT DEFAULT 'success',
    affected INTEGER DEFAULT 0,
    message TEXT,
    details TEXT,
    run_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- FINANCE
-- ============================================================
CREATE TABLE IF NOT EXISTS expense_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    parent_id INTEGER,
    description TEXT
);

CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    category_id INTEGER,
    title VARCHAR(200) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    vendor VARCHAR(150),
    payment_method TEXT DEFAULT 'cash',
    receipt_file VARCHAR(255),
    expense_date DATE NOT NULL,
    notes TEXT,
    approved_by INTEGER,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS cashbook_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    entry_type TEXT NOT NULL,
    entry_category TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reference_id INTEGER,
    reference_type VARCHAR(30),
    description TEXT,
    entry_date DATE NOT NULL,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE IF NOT EXISTS daily_closings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    closing_date DATE NOT NULL,
    opening_balance DECIMAL(12,2) DEFAULT 0.00,
    total_collection DECIMAL(12,2) DEFAULT 0.00,
    total_expense DECIMAL(12,2) DEFAULT 0.00,
    closing_balance DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT,
    closed_by INTEGER,
    closed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- ============================================================
-- ACTIVITY / AUDIT LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    record_id INTEGER,
    old_values TEXT ,
    new_values TEXT ,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    is_read INTEGER DEFAULT 0,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);



-- ============================================================
-- SEED DATA
-- ============================================================

-- Default roles
INSERT OR IGNORE INTO roles (name, display_name, description) VALUES
('superadmin', 'Super Admin', 'Full system access'),
('admin', 'Admin', 'Branch-level full access'),
('zone_manager', 'Zone Manager', 'Zone-restricted management'),
('staff', 'Staff', 'Limited operations'),
('collector', 'Collector', 'Collection only panel'),
('reseller', 'Reseller', 'Reseller self-service panel');

-- Default branch
INSERT OR IGNORE INTO branches (name, code, address, phone) VALUES
('Head Office', 'HO', 'Dhaka, Bangladesh', '01700000000');

-- Default superadmin user (password: Admin@1234)
INSERT OR IGNORE INTO users (branch_id, role_id, username, email, phone, password_hash, full_name, is_active)
VALUES (1, 1, 'admin', 'admin@digitalisp.com', '01700000000',
        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: Admin@1234
        'System Admin', 1);

-- Default package categories
INSERT OR IGNORE INTO package_categories (name) VALUES ('Home'), ('Business'), ('CGNAT'), ('Fiber');

-- Default packages
INSERT OR IGNORE INTO packages (category_id, branch_id, name, code, speed_download, speed_upload, price, otc, type, billing_type, mikrotik_profile, is_active)
VALUES
(1, 1, 'Home 10 Mbps', 'HOME-10M', '10M', '10M', 500.00, 1000.00, 'pppoe', 'monthly', '10M-HOME', 1),
(1, 1, 'Home 25 Mbps', 'HOME-25M', '25M', '25M', 800.00, 1000.00, 'pppoe', 'monthly', '25M-HOME', 1),
(1, 1, 'Home 50 Mbps', 'HOME-50M', '50M', '50M', 1200.00, 1000.00, 'pppoe', 'monthly', '50M-HOME', 1),
(2, 1, 'Business 100 Mbps', 'BIZ-100M', '100M', '100M', 3000.00, 3000.00, 'pppoe', 'monthly', '100M-BIZ', 1),
(4, 1, 'Fiber 50 Mbps', 'FIB-50M', '50M', '50M', 999.00, 2000.00, 'pppoe', 'monthly', '50M-FIB', 1);

-- SMS Templates
INSERT OR IGNORE INTO sms_templates (name, event_type, message_bn, message_en) VALUES
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
INSERT OR IGNORE INTO permissions (name, module, description) VALUES
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


-- ============================================================
-- SYSTEM SETTINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    `key` VARCHAR(100) UNIQUE NOT NULL,
    `value` TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT OR IGNORE INTO settings (`key`, `value`) VALUES 
('company_name', 'Digital ISP ERP'),
('currency', 'BDT'),
('vat_percent', '0');

-- ============================================================
-- CONFIG ITEMS
CREATE TABLE IF NOT EXISTS config_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    details TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- PPPoE PROFILES (Fallback)
-- ============================================================
CREATE TABLE IF NOT EXISTS pppoe_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nas_id INTEGER NULL,
    name VARCHAR(100) NOT NULL,
    speed_download INTEGER DEFAULT 0,
    speed_upload INTEGER DEFAULT 0,
    description VARCHAR(255),
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Extended fields for MikroTik integration
    local_address VARCHAR(50),
    remote_address VARCHAR(100),
    dns_server VARCHAR(100),
    session_timeout INTEGER DEFAULT 0,
    idle_timeout INTEGER DEFAULT 0,
    rate_limit VARCHAR(50),
    is_synced INTEGER DEFAULT 0,
    last_synced TIMESTAMP NULL,
    FOREIGN KEY (nas_id) REFERENCES nas_devices(id) ON DELETE SET NULL
);

-- ============================================================
-- CUSTOMER PORTAL TABLES
-- ============================================================
CREATE TABLE IF NOT EXISTS customer_mac_access (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    mac_address VARCHAR(20) NOT NULL,
    device_name VARCHAR(100),
    is_allowed INTEGER DEFAULT 1,
    is_active INTEGER DEFAULT 1,
    last_seen TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(customer_id, mac_address),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS customer_portal_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    username VARCHAR(150),
    session_token VARCHAR(255) NOT NULL UNIQUE,
    device_info VARCHAR(255),
    ip_address VARCHAR(45),
    login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active INTEGER DEFAULT 1,
    logout_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS customer_portal_login_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER,
    login_identifier VARCHAR(150),
    login_method TEXT DEFAULT 'phone',
    status TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    failure_reason VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS support_tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INTEGER NOT NULL,
    branch_id INTEGER NOT NULL,
    category TEXT NOT NULL DEFAULT 'general',
    priority TEXT DEFAULT 'normal',
    subject VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    attachment VARCHAR(255),
    status TEXT DEFAULT 'open',
    assigned_to INTEGER,
    resolved_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS support_ticket_replies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    customer_id INTEGER,
    staff_user_id INTEGER,
    message TEXT NOT NULL,
    attachment VARCHAR(255),
    is_internal INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- SUPPORT MODULE EXTENDED TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS problem_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ticket_comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    user_id INTEGER,
    author_name VARCHAR(100),
    message TEXT NOT NULL,
    is_internal INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ticket_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    assigned_to INTEGER,
    assigned_by INTEGER,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sla_violations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL UNIQUE,
    priority VARCHAR(20),
    sla_deadline TIMESTAMP,
    violated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolution_time_minutes INTEGER,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
);

-- Default problem categories seed data
INSERT OR IGNORE INTO problem_categories (name, description, is_active) VALUES
('no_connection',    'Customer has no internet connection',          1),
('slow_speed',       'Customer experiencing slow internet speed',    1),
('billing_dispute',  'Customer has a billing or payment dispute',    1),
('hardware_fault',   'Hardware or equipment fault reported',         1),
('new_request',      'New service or feature request',               1),
('other',            'Other / miscellaneous issue',                  1);

CREATE TABLE IF NOT EXISTS payment_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_id VARCHAR(50) UNIQUE NOT NULL,
    customer_id INTEGER NOT NULL,
    invoice_id INTEGER,
    amount DECIMAL(10,2) NOT NULL,
    payment_method TEXT NOT NULL,
    gateway_name VARCHAR(50) DEFAULT 'manual',
    gateway_response TEXT,
    status TEXT DEFAULT 'pending',
    payment_ref VARCHAR(100),
    customer_ip VARCHAR(45),
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS customer_notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    is_read INTEGER DEFAULT 0,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS portal_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    ip_address VARCHAR(45),
    timestamp INTEGER DEFAULT 0,
    data TEXT
);

-- ============================================================
-- SEED DATA EXTENSIONS
-- ============================================================
INSERT OR IGNORE INTO portal_settings (setting_key, setting_value, description) VALUES
('portal_name', 'Customer Portal', 'Name shown on portal'),
('allow_phone_login', '1', 'Allow login with phone number'),
('allow_email_login', '1', 'Allow login with email'),
('allow_pppoe_login', '1', 'Allow login with PPPoE username');


CREATE INDEX IF NOT EXISTS idx_username ON users (username);
CREATE INDEX IF NOT EXISTS idx_role ON users (role_id);
CREATE INDEX IF NOT EXISTS idx_branch ON users (branch_id);
CREATE INDEX IF NOT EXISTS idx_branch ON packages (branch_id);
CREATE INDEX IF NOT EXISTS idx_code ON customers (customer_code);
CREATE INDEX IF NOT EXISTS idx_phone ON customers (phone);
CREATE INDEX IF NOT EXISTS idx_status ON customers (status);
CREATE INDEX IF NOT EXISTS idx_branch ON customers (branch_id);
CREATE INDEX IF NOT EXISTS idx_zone ON customers (zone_id);
CREATE INDEX IF NOT EXISTS idx_pppoe ON customers (pppoe_username);
CREATE INDEX IF NOT EXISTS idx_ip ON ip_assignments (ip_address);
CREATE INDEX IF NOT EXISTS idx_customer ON ip_assignments (customer_id);
CREATE UNIQUE INDEX IF NOT EXISTS uniq_username ON radius_users (username);
CREATE INDEX IF NOT EXISTS idx_customer ON invoices (customer_id);
CREATE INDEX IF NOT EXISTS idx_status ON invoices (status);
CREATE INDEX IF NOT EXISTS idx_branch ON invoices (branch_id);
CREATE INDEX IF NOT EXISTS idx_month ON invoices (billing_month);
CREATE INDEX IF NOT EXISTS idx_customer ON payments (customer_id);
CREATE INDEX IF NOT EXISTS idx_receipt ON payments (receipt_number);
CREATE INDEX IF NOT EXISTS idx_date ON payments (payment_date);
CREATE INDEX IF NOT EXISTS idx_status ON work_orders (status);
CREATE INDEX IF NOT EXISTS idx_branch ON work_orders (branch_id);
CREATE INDEX IF NOT EXISTS idx_branch_date ON cashbook_entries (branch_id, entry_date);
CREATE UNIQUE INDEX IF NOT EXISTS uniq_branch_date ON daily_closings (branch_id, closing_date);
CREATE INDEX IF NOT EXISTS idx_user ON activity_logs (user_id);
CREATE INDEX IF NOT EXISTS idx_module ON activity_logs (module);
CREATE INDEX IF NOT EXISTS idx_date ON activity_logs (created_at);