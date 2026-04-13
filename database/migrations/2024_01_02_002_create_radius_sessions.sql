-- Migration: 2024_01_02_002_create_radius_sessions
-- Date: 2024-01-02
-- Description: Creates the radius_sessions table for per-session tracking,
--              mirroring and supplementing the standard radacct table with
--              active/stopped status and structured byte counters.

USE radius;

CREATE TABLE IF NOT EXISTS radius_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(64) NOT NULL,
    nas_ip VARCHAR(45) NOT NULL,
    nas_port VARCHAR(32) DEFAULT NULL,
    session_id VARCHAR(64) NOT NULL COMMENT 'acct_session_id from NAS',
    framed_ip VARCHAR(45) DEFAULT NULL,
    start_time DATETIME NOT NULL,
    stop_time DATETIME DEFAULT NULL,
    bytes_in BIGINT UNSIGNED NOT NULL DEFAULT 0,
    bytes_out BIGINT UNSIGNED NOT NULL DEFAULT 0,
    terminate_cause VARCHAR(64) DEFAULT NULL,
    status ENUM('active','stopped') NOT NULL DEFAULT 'active',
    PRIMARY KEY (id),
    UNIQUE KEY uq_session_id (session_id),
    KEY idx_username (username),
    KEY idx_nas_ip (nas_ip),
    KEY idx_status (status),
    KEY idx_start_time (start_time),
    KEY idx_framed_ip (framed_ip)
) ENGINE=InnoDB COMMENT='RADIUS session tracking';
