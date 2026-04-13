-- Migration: 2024_01_02_005_create_radius_alerts
-- Date: 2024-01-02
-- Description: Creates the radius_alerts table for storing system-generated
--              alert records with severity levels (critical, warning, info),
--              optional JSON context, and resolution tracking.

USE radius;

CREATE TABLE IF NOT EXISTS radius_alerts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    alert_type VARCHAR(100) NOT NULL,
    severity ENUM('critical','warning','info') NOT NULL DEFAULT 'info',
    message TEXT NOT NULL,
    context JSON DEFAULT NULL,
    resolved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_alert_type (alert_type),
    KEY idx_severity (severity),
    KEY idx_resolved_at (resolved_at),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB COMMENT='RADIUS system alerts and notifications';
