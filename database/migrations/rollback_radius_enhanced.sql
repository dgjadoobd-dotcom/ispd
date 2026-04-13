-- Migration: rollback_radius_enhanced
-- Date: 2024-01-02
-- Description: Rolls back all RADIUS enhanced schema migrations by dropping
--              the five extended tables in reverse dependency order:
--              alerts → usage_daily → audit_logs → sessions → user_profiles.

USE radius;

DROP TABLE IF EXISTS radius_alerts;
DROP TABLE IF EXISTS radius_usage_daily;
DROP TABLE IF EXISTS radius_audit_logs;
DROP TABLE IF EXISTS radius_sessions;
DROP TABLE IF EXISTS radius_user_profiles;
