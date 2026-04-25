-- ============================================================
-- Migration: 2024_01_06_001_create_btrc_report_tables
-- Date: 2024-01-06
-- Description: Creates tables for the BTRC Report module.
--              Supports Requirements 15.1–15.7:
--                - BTRC DIS report generation and storage
--                - Report generation audit log
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── BTRC Reports ─────────────────────────────────────────────────
-- Stores generated BTRC DIS reports with aggregated subscriber data.
-- Requirement 15.1: total subscribers, new connections, disconnections,
--                   active subscribers by division/district, revenue figures.
-- Requirement 15.7: zero-value reports are stored rather than erroring.
CREATE TABLE IF NOT EXISTS btrc_reports (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_period           DATE NOT NULL COMMENT 'First day of the reporting month (YYYY-MM-01)',
    report_year             YEAR NOT NULL,
    report_month            TINYINT UNSIGNED NOT NULL COMMENT '1-12',

    -- Subscriber counts
    total_subscribers       INT UNSIGNED DEFAULT 0,
    new_connections         INT UNSIGNED DEFAULT 0,
    disconnections          INT UNSIGNED DEFAULT 0,
    active_subscribers      INT UNSIGNED DEFAULT 0,

    -- Revenue figures (in local currency)
    total_revenue           DECIMAL(14,2) DEFAULT 0.00,
    new_connection_revenue  DECIMAL(14,2) DEFAULT 0.00,
    monthly_bill_revenue    DECIMAL(14,2) DEFAULT 0.00,

    -- Aggregated breakdown stored as JSON
    -- Format: [{"division":"Dhaka","district":"Dhaka","active":120,"new":5,"disconnected":2}, ...]
    division_district_data  TEXT COMMENT 'JSON: subscriber counts by division/district',

    -- Report metadata
    status                  ENUM('draft','final') DEFAULT 'draft',
    notes                   TEXT,
    generated_by            INT UNSIGNED COMMENT 'FK to users.id',
    generated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Prevent duplicate reports for the same period
    UNIQUE KEY uniq_report_period (report_period),
    KEY idx_period (report_year, report_month),
    KEY idx_generated_by (generated_by),
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='BTRC DIS monthly reports with subscriber and revenue aggregates';

-- ── BTRC Report Logs ─────────────────────────────────────────────
-- Audit trail for every report generation action.
-- Requirement 15.6: log each generation with user, timestamp, and period.
CREATE TABLE IF NOT EXISTS btrc_report_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id       INT UNSIGNED COMMENT 'FK to btrc_reports.id — null if generation failed',
    action          ENUM('generated','exported_csv','exported_pdf','previewed','deleted') NOT NULL,
    report_period   DATE NOT NULL COMMENT 'Period covered by the report',
    export_format   ENUM('csv','pdf') COMMENT 'Populated for export actions',
    file_path       VARCHAR(500) COMMENT 'Relative path to exported file, if applicable',
    performed_by    INT UNSIGNED COMMENT 'FK to users.id',
    performed_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address      VARCHAR(45),
    notes           TEXT,

    KEY idx_report (report_id),
    KEY idx_period (report_period),
    KEY idx_performed_by (performed_by),
    KEY idx_action (action),
    FOREIGN KEY (report_id) REFERENCES btrc_reports(id) ON DELETE SET NULL,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Audit log for BTRC report generation and export actions';

SET FOREIGN_KEY_CHECKS = 1;
