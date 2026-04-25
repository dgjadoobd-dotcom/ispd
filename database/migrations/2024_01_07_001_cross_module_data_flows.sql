-- ============================================================
-- Migration: 2024_01_07_001_cross_module_data_flows
-- Date: 2024-01-07
-- Description: Adds the employee_portal_notifications table and
--              supporting indexes for cross-module data flows:
--
--              1. Purchase Bill → Inventory Update
--                 (uses existing purchase_bills, inventory_items,
--                  stock_movements tables — no new tables needed)
--
--              2. Sales Invoice → Accounts Income Entry
--                 (uses existing sales_invoices, income_entries
--                  tables — no new tables needed)
--
--              3. Ticket Assignment → Employee Portal Notification
--                 (new: employee_portal_notifications table)
-- ============================================================

-- ============================================================
-- EMPLOYEE PORTAL NOTIFICATIONS
-- Stores in-app notifications for employees surfaced via the
-- employee portal. Created by CrossModuleDataFlowService when
-- a support ticket is assigned to an employee.
-- ============================================================

CREATE TABLE IF NOT EXISTS employee_portal_notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    type        VARCHAR(50)  NOT NULL COMMENT 'e.g. ticket_assigned, task_assigned',
    reference_id   INT UNSIGNED NOT NULL COMMENT 'ID of the referenced entity (ticket, task, etc.)',
    reference_type VARCHAR(50)  NOT NULL COMMENT 'Entity type: ticket, task, etc.',
    message     TEXT         NOT NULL,
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY idx_employee   (employee_id),
    KEY idx_type       (type),
    KEY idx_reference  (reference_type, reference_id),
    KEY idx_is_read    (employee_id, is_read),

    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COMMENT='In-app notifications for the employee portal';

-- ============================================================
-- ADDITIONAL INDEXES FOR CROSS-MODULE QUERIES
-- ============================================================

-- Index on income_entries(reference_type, reference_id) to speed up
-- the idempotency check in onSalesInvoicePaid.
-- Uses IF NOT EXISTS syntax via a conditional approach compatible with
-- MySQL 5.7+ (ALTER TABLE ignores duplicate key names).
ALTER TABLE income_entries
    ADD INDEX IF NOT EXISTS idx_reference (reference_type, reference_id);

-- Index on stock_movements(reference_type, reference_id) to speed up
-- the idempotency check in onPurchaseBillSaved.
ALTER TABLE stock_movements
    ADD INDEX IF NOT EXISTS idx_reference (reference_type, reference_id);

-- Index on stock_movements(item_id, reference_type) for the per-item
-- duplicate movement lookup.
ALTER TABLE stock_movements
    ADD INDEX IF NOT EXISTS idx_item_ref_type (item_id, reference_type);
