-- ============================================================
-- Migration: 2024_01_09_001_create_ott_tables
-- Date: 2024-01-09
-- Description: Creates tables for the OTT Subscription Management module.
--              Supports Requirements 16.1–16.8:
--                - OTT provider management (16.1)
--                - OTT package bundling with internet packages (16.2)
--                - Subscription creation and auto-renewal (16.3, 16.4)
--                - Renewal failure handling with SMS notification (16.5)
--                - Subscriber dashboard data (16.6)
--                - Manual activation/deactivation with audit trail (16.7, 16.8)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── OTT Providers ────────────────────────────────────────────────
-- Requirement 16.1: provider name, logo, API endpoint, API key, supported plan types.
CREATE TABLE IF NOT EXISTS ott_providers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    logo_url        VARCHAR(500) COMMENT 'URL or relative path to provider logo',
    api_endpoint    VARCHAR(500) COMMENT 'Provider API base URL for activation/deactivation',
    api_key         VARCHAR(500) COMMENT 'Encrypted API key for provider integration',
    plan_types      VARCHAR(500) COMMENT 'Comma-separated supported plan types, e.g. "monthly,yearly"',
    is_active       TINYINT(1) DEFAULT 1,
    notes           TEXT,
    created_by      INT UNSIGNED COMMENT 'FK to users.id',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_active (is_active),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='OTT streaming service providers';

-- ── OTT Packages ─────────────────────────────────────────────────
-- Requirement 16.2: links OTT plans to internet packages with price,
--                   validity days, and auto-renewal flag.
CREATE TABLE IF NOT EXISTS ott_packages (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_id     INT UNSIGNED NOT NULL COMMENT 'FK to ott_providers.id',
    package_id      INT UNSIGNED COMMENT 'FK to packages.id — null means available for all packages',
    name            VARCHAR(200) NOT NULL COMMENT 'OTT plan/bundle name',
    description     TEXT,
    price           DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Additional charge for this OTT bundle',
    validity_days   SMALLINT UNSIGNED DEFAULT 30 COMMENT 'Subscription validity in days',
    auto_renewal    TINYINT(1) DEFAULT 1 COMMENT 'Whether to auto-renew on expiry',
    is_active       TINYINT(1) DEFAULT 1,
    created_by      INT UNSIGNED COMMENT 'FK to users.id',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_provider (provider_id),
    KEY idx_package (package_id),
    KEY idx_active (is_active),
    FOREIGN KEY (provider_id) REFERENCES ott_providers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='OTT subscription plans bundled with internet packages';

-- ── OTT Subscriptions ────────────────────────────────────────────
-- Requirement 16.3: subscription record with start date, expiry date, status.
-- Requirement 16.7: manual activation/deactivation per customer.
-- Requirement 16.8: deactivation reason and timestamp.
CREATE TABLE IF NOT EXISTS ott_subscriptions (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id         INT UNSIGNED NOT NULL COMMENT 'FK to customers.id',
    ott_package_id      INT UNSIGNED NOT NULL COMMENT 'FK to ott_packages.id',
    provider_id         INT UNSIGNED NOT NULL COMMENT 'FK to ott_providers.id (denormalised for fast queries)',
    start_date          DATE NOT NULL,
    expiry_date         DATE NOT NULL,
    status              ENUM('active','expired','suspended','cancelled') DEFAULT 'active',

    -- Renewal tracking
    auto_renewal        TINYINT(1) DEFAULT 1 COMMENT 'Inherited from ott_packages, can be overridden per subscription',
    last_renewed_at     DATETIME COMMENT 'Timestamp of last successful renewal',
    renewal_attempts    TINYINT UNSIGNED DEFAULT 0 COMMENT 'Consecutive failed renewal attempts',

    -- Deactivation audit (Requirement 16.8)
    deactivated_at      DATETIME COMMENT 'When the subscription was manually deactivated',
    deactivation_reason VARCHAR(500) COMMENT 'Reason provided for manual deactivation',
    deactivated_by      INT UNSIGNED COMMENT 'FK to users.id — who deactivated it',

    -- Metadata
    activated_by        INT UNSIGNED COMMENT 'FK to users.id — who activated it',
    notes               TEXT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_customer (customer_id),
    KEY idx_package (ott_package_id),
    KEY idx_provider (provider_id),
    KEY idx_status (status),
    KEY idx_expiry (expiry_date),
    KEY idx_auto_renewal (auto_renewal, status, expiry_date),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (ott_package_id) REFERENCES ott_packages(id) ON DELETE RESTRICT,
    FOREIGN KEY (provider_id) REFERENCES ott_providers(id) ON DELETE RESTRICT,
    FOREIGN KEY (deactivated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (activated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Customer OTT subscription records';

-- ── OTT Renewal Logs ─────────────────────────────────────────────
-- Requirement 16.4: log every renewal attempt and result.
-- Requirement 16.5: on failure, status set to expired and SMS sent.
CREATE TABLE IF NOT EXISTS ott_renewal_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT UNSIGNED NOT NULL COMMENT 'FK to ott_subscriptions.id',
    customer_id     INT UNSIGNED NOT NULL COMMENT 'FK to customers.id (denormalised)',
    action          ENUM('renewal_attempt','renewal_success','renewal_failed',
                         'manual_activate','manual_deactivate','sms_sent','sms_failed') NOT NULL,
    old_status      VARCHAR(50) COMMENT 'Subscription status before this action',
    new_status      VARCHAR(50) COMMENT 'Subscription status after this action',
    old_expiry      DATE COMMENT 'Expiry date before renewal',
    new_expiry      DATE COMMENT 'New expiry date after successful renewal',
    error_message   TEXT COMMENT 'Error details on failure',
    performed_by    INT UNSIGNED COMMENT 'FK to users.id — null for automated renewals',
    performed_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address      VARCHAR(45),
    notes           TEXT,

    KEY idx_subscription (subscription_id),
    KEY idx_customer (customer_id),
    KEY idx_action (action),
    KEY idx_performed_at (performed_at),
    FOREIGN KEY (subscription_id) REFERENCES ott_subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Audit log for OTT subscription renewal attempts and manual actions';

SET FOREIGN_KEY_CHECKS = 1;
