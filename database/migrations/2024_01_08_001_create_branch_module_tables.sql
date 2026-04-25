-- ============================================================
-- Migration: 2024_01_08_001_create_branch_module_tables
-- Date: 2024-01-08
-- Description: Creates branch_reports and branch_credentials tables
--              for the Branch Management module.
--              NOTE: The `branches` table already exists — only new tables.
-- ============================================================

USE digital_isp;

SET FOREIGN_KEY_CHECKS = 0;

-- branch_reports: stores generated per-branch summary reports
CREATE TABLE IF NOT EXISTS branch_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL,
    report_type VARCHAR(50) DEFAULT 'summary',
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    customer_count INT DEFAULT 0,
    monthly_revenue DECIMAL(12,2) DEFAULT 0.00,
    outstanding_dues DECIMAL(12,2) DEFAULT 0.00,
    active_tickets INT DEFAULT 0,
    report_data JSON COMMENT 'Full report payload as JSON',
    generated_by INT UNSIGNED,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    KEY idx_period (period_start, period_end),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Per-branch summary reports';

-- branch_credentials: per-branch login credential assignments
CREATE TABLE IF NOT EXISTS branch_credentials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id INT UNSIGNED NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_branch (branch_id),
    KEY idx_user (user_id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Per-branch login credential assignments';

SET FOREIGN_KEY_CHECKS = 1;
