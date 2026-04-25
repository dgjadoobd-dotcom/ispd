-- ============================================================
-- Migration: 2024_01_07_001_create_role_change_logs
-- Date: 2024-01-07
-- Description: Standalone migration for role-related tables.
--              Ensures roles, permissions, role_permissions, and
--              role_change_logs tables exist for the RBAC module.
-- ============================================================

USE digital_isp;

SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = '';

-- ============================================================
-- ROLES & PERMISSIONS (RBAC)
-- ============================================================

CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Role definitions for RBAC';

CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT
) ENGINE=InnoDB COMMENT='Permission definitions per module';

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Many-to-many mapping of roles to permissions';

-- ============================================================
-- ROLE CHANGE AUDIT LOG
-- ============================================================

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

SET FOREIGN_KEY_CHECKS = 1;
