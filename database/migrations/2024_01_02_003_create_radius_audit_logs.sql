-- Migration: 2024_01_02_003_create_radius_audit_logs
-- Date: 2024-01-02
-- Description: Creates the radius_audit_logs table for recording an audit trail
--              of all admin actions performed on RADIUS users, including the
--              acting admin, action type, target username, and request context.

USE radius;

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
