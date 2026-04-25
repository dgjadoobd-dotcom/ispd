-- ============================================================
-- Rollback: 2024_01_09_001_rollback_ott_tables
-- Date: 2024-01-09
-- Description: Drops all OTT Subscription Management module tables.
--              Run this to undo migration 2024_01_09_001_create_ott_tables.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS ott_renewal_logs;
DROP TABLE IF EXISTS ott_subscriptions;
DROP TABLE IF EXISTS ott_packages;
DROP TABLE IF EXISTS ott_providers;

SET FOREIGN_KEY_CHECKS = 1;
