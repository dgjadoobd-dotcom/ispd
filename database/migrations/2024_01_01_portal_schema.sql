-- ============================================================
-- FCNCHBD ISP ERP — Customer Portal Database Schema
-- Adds portal tables and extends customers table
-- ============================================================

-- Enable database
USE digital_isp;

SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = '';

-- ─────────────────────────────────────────────────────────────
-- ADD PORTAL FIELDS TO EXISTING CUSTOMERS TABLE
-- ─────────────────────────────────────────────────────────────
ALTER TABLE customers ADD COLUMN IF NOT EXISTS portal_active TINYINT(1) DEFAULT 0 AFTER `updated_at`;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS portal_password VARCHAR(255) NULL AFTER `portal_active`;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS portal_last_login TIMESTAMP NULL AFTER `portal_password`;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS portal_otp VARCHAR(10) NULL AFTER `portal_last_login`;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS portal_otp_expires TIMESTAMP NULL AFTER `portal_otp`;

-- Custom billing info fields
ALTER TABLE customers ADD COLUMN IF NOT EXISTS billing_company_name VARCHAR(150) NULL AFTER `billing_address`;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS billing_vat_reg VARCHAR(50) NULL AFTER `billing_company_name`;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS billing_notes TEXT NULL AFTER `billing_vat_reg`;

-- Secret question for password reset
ALTER TABLE customers ADD COLUMN IF NOT EXISTS secret_question VARCHAR(255) NULL AFTER `billing_notes`;
ALTER TABLE customers ADD COLUMN IF NOT EXISTS secret_answer VARCHAR(255) NULL AFTER `secret_question`;

-- Add index for portal login
ALTER TABLE customers ADD INDEX IF NOT EXISTS idx_portal_phone (phone);
ALTER TABLE customers ADD INDEX IF NOT EXISTS idx_portal_email (email);

-- ─────────────────────────────────────────────────────────────
-- MAC ADDRESS ACCESS CONTROL
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customer_mac_access (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    mac_address VARCHAR(20) NOT NULL,
    device_name VARCHAR(100) COMMENT 'e.g., iPhone 15, MacBook Pro',
    is_allowed TINYINT(1) DEFAULT 1 COMMENT '1=allowed, 0=blocked',
    is_active TINYINT(1) DEFAULT 1,
    last_seen TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_customer_mac (customer_id, mac_address),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- PORTAL SESSIONS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customer_portal_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    device_info VARCHAR(255) COMMENT 'User agent or device info',
    ip_address VARCHAR(45),
    login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    logout_at TIMESTAMP NULL,
    UNIQUE KEY uniq_token (session_token),
    KEY idx_customer (customer_id),
    KEY idx_expires (expires_at),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- PORTAL LOGIN LOGS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customer_portal_login_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED,
    login_identifier VARCHAR(150) COMMENT 'Phone, email, or username used',
    login_method ENUM('phone','email','pppoe','secret_question') DEFAULT 'phone',
    status ENUM('success','failed','blocked','otp_sent','otp_verified') NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    failure_reason VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_customer (customer_id),
    KEY idx_date (created_at),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- SUPPORT TICKETS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS support_tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    category ENUM('billing','technical','complaint','general','new_connection','disconnection') NOT NULL DEFAULT 'general',
    priority ENUM('low','normal','high','urgent') DEFAULT 'normal',
    subject VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    attachment VARCHAR(255) COMMENT 'File path or URL',
    status ENUM('open','in_progress','pending_customer','resolved','closed') DEFAULT 'open',
    assigned_to INT UNSIGNED,
    resolved_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_customer (customer_id),
    KEY idx_status (status),
    KEY idx_branch (branch_id),
    KEY idx_category (category),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- SUPPORT TICKET REPLIES
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS support_ticket_replies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED COMMENT 'Null if staff reply',
    staff_user_id INT UNSIGNED COMMENT 'Null if customer reply',
    message TEXT NOT NULL,
    attachment VARCHAR(255),
    is_internal TINYINT(1) DEFAULT 0 COMMENT 'Internal note - customer cannot see',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- ONLINE PAYMENT TRANSACTIONS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(50) UNIQUE NOT NULL COMMENT 'Gateway transaction ID',
    customer_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('bkash','nagad','bank','card','other') NOT NULL,
    gateway_name VARCHAR(50) DEFAULT 'manual',
    gateway_response TEXT COMMENT 'Raw response from payment gateway',
    status ENUM('pending','processing','completed','failed','cancelled','refunded') DEFAULT 'pending',
    payment_ref VARCHAR(100) COMMENT 'Gateway reference number',
    customer_ip VARCHAR(45),
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_customer (customer_id),
    KEY idx_status (status),
    KEY idx_transaction (transaction_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- CUSTOMER NOTIFICATIONS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customer_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    type ENUM('bill','payment','ticket','system','promo') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_customer (customer_id),
    KEY idx_is_read (is_read),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- PORTAL SETTINGS
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS portal_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────
-- SEED DATA FOR PORTAL SETTINGS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO portal_settings (setting_key, setting_value, description) VALUES
('portal_name', 'Customer Portal', 'Name shown on portal'),
('portal_logo', '', 'Logo URL for portal'),
('allow_phone_login', '1', 'Allow login with phone number'),
('allow_email_login', '1', 'Allow login with email'),
('allow_pppoe_login', '1', 'Allow login with PPPoE username'),
('allow_secret_question', '1', 'Allow password reset via secret question'),
('require_mac_verification', '0', 'Require MAC address verification'),
('max_mac_devices', '5', 'Maximum MAC devices per customer'),
('session_timeout_hours', '24', 'Portal session timeout in hours'),
('otp_expiry_minutes', '10', 'OTP validity period in minutes'),
('enable_bkash_payment', '1', 'Enable bKash payment option'),
('enable_nagad_payment', '1', 'Enable Nagad payment option'),
('invoice_prefix', 'INV-', 'Invoice number prefix'),
('support_email', 'support@digitalisp.com', 'Support email address'),
('support_phone', '01700000000', 'Support phone number');

SET FOREIGN_KEY_CHECKS = 1;
