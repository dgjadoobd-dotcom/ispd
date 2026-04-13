-- ============================================================
-- Migration: 2024_01_02_009_query_optimizations.sql
-- Purpose:   Additional composite index optimizations for the
--            enhanced RADIUS tables introduced in migrations 001-005.
-- Applies to: radius_sessions, radius_usage_daily,
--             radius_audit_logs, radius_alerts
-- Run after:  2024_01_02_005_create_radius_alerts.sql
-- ============================================================

USE radius;

-- ------------------------------------------------------------
-- radius_sessions composite indexes
-- ------------------------------------------------------------

-- Active-session-per-user queries: WHERE username = ? AND status = ?
-- Used to count or list active sessions for a given subscriber,
-- e.g. enforcing concurrent_session_limit checks.
CREATE INDEX IF NOT EXISTS idx_sessions_username_status
    ON radius_sessions (username, status);

-- Time-range queries on active sessions: WHERE status = ? AND start_time >= ?
-- Used for dashboard views and reports that filter open sessions
-- within a specific time window.
CREATE INDEX IF NOT EXISTS idx_sessions_status_start_time
    ON radius_sessions (status, start_time);

-- ------------------------------------------------------------
-- radius_usage_daily composite indexes
-- ------------------------------------------------------------

-- NOTE: The composite index on (username, date) already exists as the
-- UNIQUE KEY `uq_username_date` defined in migration 004. MySQL uses
-- unique keys as indexes, so no additional index is needed here.
-- Queries of the form WHERE username = ? AND date = ? (or date BETWEEN ...)
-- will automatically use `uq_username_date`.

-- ------------------------------------------------------------
-- radius_audit_logs composite indexes
-- ------------------------------------------------------------

-- Audit log filtering by action type and time:
-- WHERE action = ? AND created_at >= ?
-- Used for admin dashboards that display recent events of a specific
-- action type (e.g. 'user_disabled', 'password_reset').
CREATE INDEX IF NOT EXISTS idx_audit_action_created_at
    ON radius_audit_logs (action, created_at);

-- ------------------------------------------------------------
-- radius_alerts composite indexes
-- ------------------------------------------------------------

-- Unresolved alert queries by severity:
-- WHERE severity = ? AND resolved_at IS NULL (or resolved_at >= ?)
-- Used to surface open critical/warning alerts in the monitoring UI.
CREATE INDEX IF NOT EXISTS idx_alerts_severity_resolved_at
    ON radius_alerts (severity, resolved_at);

-- ============================================================
-- Optimizer Hints & Maintenance Notes
-- ============================================================
--
-- 1. UPDATE TABLE STATISTICS
--    Run periodically (e.g. weekly via cron) to keep the query
--    planner's row-count estimates accurate after bulk inserts:
--
--      ANALYZE TABLE radius_sessions;
--      ANALYZE TABLE radius_audit_logs;
--      ANALYZE TABLE radius_alerts;
--      ANALYZE TABLE radius_usage_daily;
--
-- 2. VERIFY INDEX USAGE ON SLOW QUERIES
--    Prefix any suspect query with EXPLAIN to confirm the optimizer
--    is picking the intended index:
--
--      EXPLAIN SELECT * FROM radius_sessions
--              WHERE username = 'alice' AND status = 'active';
--
--    Look for `key` in the output — it should show the composite
--    index name (e.g. idx_sessions_username_status).
--    If `type` is 'ALL' or `key` is NULL, the index is not being used
--    and the query or index definition may need adjustment.
--
-- 3. PARTITIONING FOR VERY LARGE DATASETS
--    If radius_sessions grows beyond tens of millions of rows,
--    consider partitioning by RANGE on start_time to allow
--    partition pruning on time-bounded queries:
--
--      ALTER TABLE radius_sessions
--        PARTITION BY RANGE (TO_DAYS(start_time)) (
--          PARTITION p2024_q1 VALUES LESS THAN (TO_DAYS('2024-04-01')),
--          PARTITION p2024_q2 VALUES LESS THAN (TO_DAYS('2024-07-01')),
--          PARTITION p2024_q3 VALUES LESS THAN (TO_DAYS('2024-10-01')),
--          PARTITION p2024_q4 VALUES LESS THAN (TO_DAYS('2025-01-01')),
--          PARTITION p_future  VALUES LESS THAN MAXVALUE
--        );
--
--    Note: Partitioning requires dropping foreign keys (if any) and
--    the partition column must be part of every unique key.
--    Evaluate with EXPLAIN PARTITIONS before applying in production.
-- ============================================================
