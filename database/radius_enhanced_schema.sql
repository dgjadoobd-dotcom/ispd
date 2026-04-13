-- ============================================================
-- RADIUS Enhanced Schema
-- Extends the standard FreeRADIUS schema (radius_schema.sql)
-- with session tracking, audit logging, usage rollups, and alerts.
-- MySQL syntax — run AFTER radius_schema.sql
-- ============================================================

USE radius;

-- ============================================================
-- 1. radius_user_profiles
-- Extended user profile linked to radcheck username
-- ============================================================
CREATE TABLE IF NOT EXISTS radius_user_profiles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id VARCHAR(64) NOT NULL COMMENT 'FK to radcheck.username',
    mac_address VARCHAR(20) DEFAULT NULL COMMENT 'Bound MAC address for filtering',
    ip_binding VARCHAR(45) DEFAULT NULL COMMENT 'Static IP binding for this user',
    concurrent_session_limit TINYINT UNSIGNED NOT NULL DEFAULT 1,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_id (user_id),
    KEY idx_mac_address (mac_address),
    KEY idx_ip_binding (ip_binding)
) ENGINE=InnoDB COMMENT='Extended RADIUS user profiles';

-- ============================================================
-- 2. radius_sessions
-- Per-session tracking (mirrors/supplements radacct)
-- ============================================================
CREATE TABLE IF NOT EXISTS radius_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(64) NOT NULL,
    nas_ip VARCHAR(45) NOT NULL,
    nas_port VARCHAR(32) DEFAULT NULL,
    session_id VARCHAR(64) NOT NULL COMMENT 'acct_session_id from NAS',
    framed_ip VARCHAR(45) DEFAULT NULL,
    start_time DATETIME NOT NULL,
    stop_time DATETIME DEFAULT NULL,
    bytes_in BIGINT UNSIGNED NOT NULL DEFAULT 0,
    bytes_out BIGINT UNSIGNED NOT NULL DEFAULT 0,
    terminate_cause VARCHAR(64) DEFAULT NULL,
    status ENUM('active','stopped') NOT NULL DEFAULT 'active',
    PRIMARY KEY (id),
    UNIQUE KEY uq_session_id (session_id),
    KEY idx_username (username),
    KEY idx_nas_ip (nas_ip),
    KEY idx_status (status),
    KEY idx_start_time (start_time),
    KEY idx_framed_ip (framed_ip)
) ENGINE=InnoDB COMMENT='RADIUS session tracking';

-- ============================================================
-- 3. radius_audit_logs
-- Admin action audit trail
-- ============================================================
CREATE TABLE IF NOT EXISTS radius_audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_user VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_username VARCHAR(64) DEFAULT NULL,
    details JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_admin_user (admin_user),
    KEY idx_target_username (target_username),
    KEY idx_action (action),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB COMMENT='Audit trail for admin actions on RADIUS users';

-- ============================================================
-- 4. radius_usage_daily
-- Daily usage rollup per user
-- ============================================================
CREATE TABLE IF NOT EXISTS radius_usage_daily (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(64) NOT NULL,
    date DATE NOT NULL,
    bytes_in BIGINT UNSIGNED NOT NULL DEFAULT 0,
    bytes_out BIGINT UNSIGNED NOT NULL DEFAULT 0,
    session_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    total_duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_username_date (username, date),
    KEY idx_username (username),
    KEY idx_date (date)
) ENGINE=InnoDB COMMENT='Daily usage rollup for billing and reporting';

-- ============================================================
-- 5. radius_alerts
-- System-generated alert records
-- ============================================================
CREATE TABLE IF NOT EXISTS radius_alerts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    alert_type VARCHAR(100) NOT NULL,
    severity ENUM('critical','warning','info') NOT NULL DEFAULT 'info',
    message TEXT NOT NULL,
    context JSON DEFAULT NULL,
    resolved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_alert_type (alert_type),
    KEY idx_severity (severity),
    KEY idx_resolved_at (resolved_at),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB COMMENT='RADIUS system alerts and notifications';
