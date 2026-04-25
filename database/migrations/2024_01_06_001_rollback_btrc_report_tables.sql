-- ============================================================
-- Rollback: 2024_01_06_001_create_btrc_report_tables
-- Date: 2024-01-06
-- Description: Drops BTRC report tables (reverse of migration).
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS btrc_report_logs;
DROP TABLE IF EXISTS btrc_reports;

SET FOREIGN_KEY_CHECKS = 1;
