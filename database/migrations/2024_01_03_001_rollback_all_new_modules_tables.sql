-- ============================================================
-- Rollback: 2024_01_03_001_rollback_all_new_modules_tables
-- Date: 2024-01-03
-- Description: Rolls back all new module tables created in
--              2024_01_03_001_create_all_new_modules_tables.sql
--              Tables are dropped in reverse dependency order.
-- ============================================================

USE digital_isp;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- PHASE 7: BRANCH MODULE
-- ============================================================
DROP TABLE IF EXISTS branch_credentials;
DROP TABLE IF EXISTS branch_reports;

-- ============================================================
-- PHASE 6: API / CAMPAIGNS / ROLES / OTT / BTRC
-- ============================================================
DROP TABLE IF EXISTS api_rate_limits;
DROP TABLE IF EXISTS api_tokens;
DROP TABLE IF EXISTS device_tokens;
DROP TABLE IF EXISTS campaign_logs;
DROP TABLE IF EXISTS campaign_recipients;
DROP TABLE IF EXISTS email_campaigns;
DROP TABLE IF EXISTS role_change_logs;
DROP TABLE IF EXISTS ott_renewal_logs;
DROP TABLE IF EXISTS ott_subscriptions;
DROP TABLE IF EXISTS ott_packages;
DROP TABLE IF EXISTS ott_providers;
DROP TABLE IF EXISTS btrc_report_logs;
DROP TABLE IF EXISTS btrc_reports;

-- ============================================================
-- PHASE 5: CONFIGURATION / EMPLOYEE PORTAL / MAC RESELLER /
--          BANDWIDTH RESELLER PORTAL / BANDWIDTH
-- ============================================================
DROP TABLE IF EXISTS invoice_templates;
DROP TABLE IF EXISTS billing_rules;
DROP TABLE IF EXISTS configuration_settings;
DROP TABLE IF EXISTS employee_payments;
DROP TABLE IF EXISTS employee_collection_sessions;
DROP TABLE IF EXISTS mac_reseller_billing;
DROP TABLE IF EXISTS mac_reseller_clients;
DROP TABLE IF EXISTS mac_reseller_tariffs;
DROP TABLE IF EXISTS mac_resellers;
DROP TABLE IF EXISTS reseller_tickets;
DROP TABLE IF EXISTS reseller_portal_sessions;
DROP TABLE IF EXISTS reseller_ledgers;
DROP TABLE IF EXISTS bandwidth_invoices;
DROP TABLE IF EXISTS bandwidth_purchases;
DROP TABLE IF EXISTS bandwidth_resellers;
DROP TABLE IF EXISTS bandwidth_providers;

-- ============================================================
-- PHASE 4: ASSETS / ACCOUNTS / NETWORK
-- ============================================================
DROP TABLE IF EXISTS asset_depreciation;
DROP TABLE IF EXISTS asset_disposals;
DROP TABLE IF EXISTS assets;
DROP TABLE IF EXISTS asset_categories;
DROP TABLE IF EXISTS bank_deposits;
DROP TABLE IF EXISTS bank_accounts;
DROP TABLE IF EXISTS income_entries;
DROP TABLE IF EXISTS expense_entries;
DROP TABLE IF EXISTS expense_categories;
DROP TABLE IF EXISTS network_connections;
DROP TABLE IF EXISTS box_nodes;
DROP TABLE IF EXISTS pop_nodes;

-- ============================================================
-- PHASE 3: INVENTORY / PURCHASE / SALES
-- ============================================================
DROP TABLE IF EXISTS stock_transfers;
DROP TABLE IF EXISTS stock_vouchers;
DROP TABLE IF EXISTS vendor_payments;
DROP TABLE IF EXISTS purchase_bills;
DROP TABLE IF EXISTS purchase_requisitions;
DROP TABLE IF EXISTS vendors;
DROP TABLE IF EXISTS sales_payments;
DROP TABLE IF EXISTS sales_invoice_items;
DROP TABLE IF EXISTS sales_invoices;

-- ============================================================
-- PHASE 2: TASKS / SUPPORT / HR
-- ============================================================
DROP TABLE IF EXISTS task_assignments;
DROP TABLE IF EXISTS task_history;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS problem_categories;
DROP TABLE IF EXISTS sla_violations;
DROP TABLE IF EXISTS ticket_assignments;
DROP TABLE IF EXISTS ticket_comments;
DROP TABLE IF EXISTS leave_balances;
DROP TABLE IF EXISTS performance_appraisals;
DROP TABLE IF EXISTS salary_slips;
DROP TABLE IF EXISTS attendance;

-- Remove FK before dropping employees (departments.head_of_department references employees)
ALTER TABLE departments DROP FOREIGN KEY IF EXISTS fk_dept_head;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS designations;
DROP TABLE IF EXISTS departments;

SET FOREIGN_KEY_CHECKS = 1;
