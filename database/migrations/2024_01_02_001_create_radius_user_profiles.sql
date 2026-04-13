-- Migration: 2024_01_02_001_create_radius_user_profiles
-- Date: 2024-01-02
-- Description: Creates the radius_user_profiles table for extended user profile
--              data linked to radcheck usernames (MAC binding, IP binding,
--              concurrent session limits, and notes).

USE radius;

CREATE TABLE IF NOT EXISTS radius_user_profiles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id VARCHAR(64) NOT NULL COMMENT 'FK to radcheck.username',
    mac_address VARCHAR(20) DEFAULT NULL COMMENT 'Bound MAC address for filtering',
    ip_binding VARCHAR(45) DEFAULT NULL COMMENT 'Static IP binding for this user',
    concurrent_session_limit TINYINT UNSIGNED NOT NULL DEFAULT 1,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_id (user_id),
    KEY idx_mac_address (mac_address),
    KEY idx_ip_binding (ip_binding)
) ENGINE=InnoDB COMMENT='Extended RADIUS user profiles';
