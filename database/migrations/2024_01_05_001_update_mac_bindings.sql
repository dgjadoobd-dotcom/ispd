-- ============================================================
-- Migration: 2024_01_05_001_update_mac_bindings
-- Description: Extend mac_bindings to support ONU MAC and
--              Router MAC/CallerID binding per customer
-- ============================================================

-- Add new columns to mac_bindings
ALTER TABLE mac_bindings
    ADD COLUMN IF NOT EXISTS device_type ENUM('onu','router','other') DEFAULT 'router' AFTER username,
    ADD COLUMN IF NOT EXISTS binding_type ENUM('pppoe_callerid','mac_auth','static_ip') DEFAULT 'pppoe_callerid' AFTER device_type,
    ADD COLUMN IF NOT EXISTS onu_serial VARCHAR(100) DEFAULT NULL AFTER caller_id,
    ADD COLUMN IF NOT EXISTS router_brand VARCHAR(80) DEFAULT NULL AFTER onu_serial,
    ADD COLUMN IF NOT EXISTS router_model VARCHAR(80) DEFAULT NULL AFTER router_brand,
    ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) DEFAULT NULL AFTER router_model;

-- Index for faster lookups
ALTER TABLE mac_bindings
    ADD INDEX IF NOT EXISTS idx_device_type (device_type),
    ADD INDEX IF NOT EXISTS idx_binding_type (binding_type);
