-- ============================================================
-- Migration: 2024_01_02_006_radius_indexes_optimizations.sql
-- Purpose:   Add performance indexes to standard FreeRADIUS tables
--            for query patterns common in ISP billing/management systems.
-- Applies to: radius_schema.sql tables (radacct, radpostauth, radcheck)
-- Run after:  radius_schema.sql
-- ============================================================

USE radius;

-- ------------------------------------------------------------
-- radacct indexes
-- The default FreeRADIUS schema has individual indexes on username,
-- acctstarttime, acctstoptime, and nasipaddress, but lacks composite
-- indexes needed for the multi-column WHERE clauses used in this system.
-- ------------------------------------------------------------

-- Active session queries: WHERE username = ? AND acctstoptime IS NULL
-- Used to find all open sessions for a given subscriber.
CREATE INDEX IF NOT EXISTS idx_radacct_username_stoptime
    ON radacct (username, acctstoptime);

-- NAS-based session queries: WHERE nasipaddress = ? AND acctstarttime >= ?
-- Used to list sessions originating from a specific NAS within a time window.
CREATE INDEX IF NOT EXISTS idx_radacct_nas_starttime
    ON radacct (nasipaddress, acctstarttime);

-- Time-range reports: WHERE acctstarttime BETWEEN ? AND ?
-- Used for billing period rollups and usage reports across all users.
-- Note: acctstarttime already has a single-column index in the base schema;
-- this entry is kept here as documentation — no duplicate is created.
-- CREATE INDEX IF NOT EXISTS idx_radacct_starttime ON radacct (acctstarttime);

-- ------------------------------------------------------------
-- radpostauth indexes
-- The standard schema has no indexes beyond the primary key.
-- ------------------------------------------------------------

-- Auth history queries: WHERE username = ? AND authdate >= ?
-- Used to retrieve recent authentication attempts for a subscriber,
-- e.g. for the customer portal or admin audit views.
CREATE INDEX IF NOT EXISTS idx_radpostauth_username_authdate
    ON radpostauth (username, authdate);

-- ------------------------------------------------------------
-- radcheck indexes
-- The base schema has a prefix index on username(32) only.
-- ------------------------------------------------------------

-- Attribute lookups: WHERE username = ? AND attribute = ?
-- Used when checking or updating a specific RADIUS attribute
-- (e.g. Cleartext-Password, Simultaneous-Use) for a given user.
CREATE INDEX IF NOT EXISTS idx_radcheck_username_attribute
    ON radcheck (username, attribute);
