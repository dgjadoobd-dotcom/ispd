-- ============================================================
-- Rollback: 2024_01_07_001_create_role_change_logs
-- Date: 2024-01-07
-- Description: Drops role_change_logs table created by the
--              2024_01_07_001_create_role_change_logs migration.
--              Note: roles, permissions, role_permissions are
--              shared tables and are NOT dropped here to avoid
--              breaking other modules.
-- ============================================================

USE digital_isp;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS role_change_logs;

SET FOREIGN_KEY_CHECKS = 1;
