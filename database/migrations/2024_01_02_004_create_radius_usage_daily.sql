-- Migration: 2024_01_02_004_create_radius_usage_daily
-- Date: 2024-01-02
-- Description: Creates the radius_usage_daily table for storing daily usage
--              rollups per user, used for billing calculations and reporting.
--              Aggregates bytes in/out, session count, and total duration.

USE radius;

CREATE TABLE IF NOT EXISTS radius_usage_daily (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(64) NOT NULL,
    date DATE NOT NULL,
    bytes_in BIGINT UNSIGNED NOT NULL DEFAULT 0,
    bytes_out BIGINT UNSIGNED NOT NULL DEFAULT 0,
    session_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    total_duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_username_date (username, date),
    KEY idx_username (username),
    KEY idx_date (date)
) ENGINE=InnoDB COMMENT='Daily usage rollup for billing and reporting';
