-- ============================================================
-- Rollback: 2024_01_08_001_rollback_branch_module_tables
-- Date: 2024-01-08
-- Description: Drops branch_reports and branch_credentials tables.
-- ============================================================

USE digital_isp;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS branch_credentials;
DROP TABLE IF EXISTS branch_reports;

SET FOREIGN_KEY_CHECKS = 1;
