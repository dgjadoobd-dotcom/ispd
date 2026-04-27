# Implementation Plan: Missing Modules Implementation

## Overview

This implementation plan follows the 6-phase roadmap outlined in the design document to implement 20 missing modules for the FCNCHBD ISP ERP system. The system is a PHP 8+ MVC application with custom router, SQLite/MySQL database, Tailwind CSS views, and existing Database class. Implementation will be incremental, building on existing infrastructure and following the layered architecture pattern.

## Tasks

### Phase 1: Foundation & Core Infrastructure

- [x] 1. Set up database schema and migrations
  - Create migration scripts for all new tables defined in design document
  - Implement rollback scripts for each migration
  - Set up database indexes for performance optimization
  - _Requirements: All modules require database tables_

- [x] 2. Implement core service layer infrastructure
  - Create base service class with common database operations
  - Implement error handling and logging infrastructure
  - Set up validation helper functions for all modules
  - _Requirements: Error handling needed across all modules_

- [x] 3. Extend authentication and authorization system
  - Update role-based permission system for new modules
  - Implement branch data isolation for branch_admin users
  - Create permission check helper functions
  - _Requirements: 1.3, 1.4, 18.1-18.10_

- [x] 4. Write unit tests for core infrastructure
  - Test database migration scripts
  - Test validation helper functions
  - Test permission check functions

### Phase 2: HR, Support & Task Management Modules

- [ ] 5. Implement HR & Payroll module
  - [x] 5.1 Create HR database tables (departments, designations, employees, attendance, salary_slips, performance_appraisals, leave_balances)
    - Write migration scripts for HR tables
    - Implement data validation for employee records
    - Set up relationships between employees and users table
    - _Requirements: 2.1-2.10_

  - [x] 5.2 Implement HrController and HrService
    - Create employee CRUD operations with branch isolation
    - Implement attendance tracking with status validation
    - Develop salary calculation with deductions and allowances
    - _Requirements: 2.5, 2.6, 2.10_

  - [x] 5.3 Create HR views (employee list, attendance, payroll, performance)
    - Design Tailwind CSS views for HR management
    - Implement PDF generation for salary slips
    - Create attendance calendar interface
    - _Requirements: 2.7, 2.8_

  - [x] 5.4 Write unit tests for HR module
    - Test salary calculation logic
    - Test attendance validation
    - Test employee-user relationship

- [ ] 6. Implement Support & Ticketing module (Admin side)
  - [x] 6.1 Create support database tables (ticket_comments, ticket_assignments, sla_violations, problem_categories)
    - Write migration scripts for support tables
    - Implement SLA deadline calculation logic
    - Set up ticket assignment tracking
    - _Requirements: 3.1-3.10_

  - [x] 6.2 Implement SupportController and SupportService
    - Create ticket management with SLA tracking
    - Implement ticket assignment with SMS notifications
    - Develop SLA compliance dashboard logic
    - _Requirements: 3.2, 3.3, 3.5, 3.9_

  - [x] 6.3 Create support views (ticket list, ticket details, assignment, SLA dashboard)
    - Design ticket management interface
    - Implement comment thread display
    - Create SLA compliance visualization
    - _Requirements: 3.1, 3.9_

- [ ] 6.4 Write unit tests for support module
    - Test SLA deadline calculation
    - Test ticket assignment logic
    - Test duplicate ticket detection

  - [x] 7. Implement Task Management module
  - [x] 7.1 Create task database tables (tasks, task_history, task_assignments)
    - Write migration scripts for task tables
    - Implement task status transition validation
    - Set up task history logging
    - _Requirements: 4.1-4.8_

  - [x] 7.2 Implement TaskController and TaskService
    - Create task CRUD operations with assignment
    - Implement overdue task detection
    - Develop bulk task assignment functionality
    - _Requirements: 4.3, 4.6, 4.7_

- [x] 7.3 Create task views (task list, calendar view, assignment, reports)
    - Design daily task calendar interface
    - Implement task status update workflow
    - Create completion rate reports
    - _Requirements: 4.4, 4.8_

  - [x] 7.4 Write unit tests for task module
    - Test task status transitions
    - Test overdue task detection
    - Test bulk assignment logic

  - [ ] 8. Checkpoint - Complete Phase 2
  - Ensure all database migrations are applied
  - Verify HR, Support, and Task modules are functional
  - Test branch isolation for all modules
  - Ensure all tests pass, ask the user if questions arise.

### Phase 3: Sales, Purchase & Inventory Modules

- [x] 9. Implement Sales & Service Invoicing module
  - [x] 9.1 Create sales database tables (sales_invoices, sales_invoice_items, sales_payments)
    - Write migration scripts for sales tables
    - Implement invoice number generation
    - Set up partial payment tracking
    - _Requirements: 5.1-5.7_

  - [x] 9.2 Implement SalesInvoiceController and SalesInvoiceService
    - Create invoice CRUD with multi-line items
    - Implement partial payment processing
    - Develop invoice cancellation with reversal logic
    - _Requirements: 5.4, 5.6, 5.7_

  - [x] 9.3 Create sales views (invoice creation, payment recording, invoice history)
    - Design invoice creation interface
    - Implement PDF invoice generation
    - Create payment tracking dashboard
    - _Requirements: 5.5_

  - [x] 9.4 Write unit tests for sales module
    - Test invoice total calculation
    - Test partial payment validation
    - Test cancellation reversal logic

- [x] 10. Implement Purchase Management module
  - [x] 10.1 Create purchase database tables (vendors, purchase_requisitions, purchase_bills, vendor_payments)
    - Write migration scripts for purchase tables
    - Implement purchase order validation
    - Set up vendor payment tracking
    - _Requirements: 6.1-6.8_

  - [x] 10.2 Implement PurchaseController and PurchaseService
    - Create vendor management with validation
    - Implement purchase requisition workflow
    - Develop bill recording with inventory updates
    - _Requirements: 6.3, 6.5, 6.8_

  - [x] 10.3 Create purchase views (vendor list, requisitions, bills, payments)
    - Design purchase workflow interface
    - Implement approval workflow UI
    - Create vendor ledger reports
    - _Requirements: 6.7_

  - [x] 10.4 Write unit tests for purchase module
    - Test purchase order validation
    - Test bill total validation
    - Test approval workflow logic

- [ ] 11. Implement Inventory Management module
  - [ ] 11.1 Create inventory database tables (stock_vouchers, stock_transfers)
    - Write migration scripts for inventory tables
    - Implement stock movement validation
    - Set up low-stock alert logic
    - _Requirements: 7.1-7.8_

  - [ ] 11.2 Implement InventoryController and InventoryService
    - Create inventory item management
    - Implement stock movement tracking
    - Develop inter-warehouse transfer functionality
    - _Requirements: 7.3, 7.4, 7.6_

  - [ ] 11.3 Create inventory views (item catalog, stock levels, movements, transfers)
    - Design inventory management interface
    - Implement low-stock alert display
    - Create stock voucher PDF generation
    - _Requirements: 7.2, 7.5, 7.7_

  - [ ] 11.4 Write unit tests for inventory module
    - Test stock level validation
    - Test movement type validation
    - Test transfer validation

- [ ] 12. Checkpoint - Complete Phase 3
  - Ensure sales, purchase, and inventory modules integrate
  - Test invoice generation and payment tracking
  - Verify inventory updates from purchase bills
  - Ensure all tests pass, ask the user if questions arise.

### Phase 4: Network, Accounts & Asset Modules

- [ ] 13. Implement Network Diagram module
  - [ ] 13.1 Create network database tables (pop_nodes, box_nodes, network_connections)
    - Write migration scripts for network tables
    - Implement coordinate validation
    - Set up network topology relationships
    - _Requirements: 8.1-8.8_

  - [ ] 13.2 Implement NetworkDiagramController and NetworkDiagramService
    - Create POP and BOX node management
    - Implement network connection validation
    - Develop map coordinate editing
    - _Requirements: 8.2, 8.3, 8.7_

  - [ ] 13.3 Create network views (interactive map, node management, topology)
    - Design Leaflet.js interactive map interface
    - Implement customer marker display with popups
    - Create network topology visualization
    - _Requirements: 8.1, 8.4, 8.5, 8.8_

  - [ ] 13.4 Write unit tests for network module
    - Test coordinate validation
    - Test network connection validation
    - Test map export functionality

- [ ] 14. Implement Accounts Management module
  - [ ] 14.1 Create accounts database tables (expense_categories, expense_entries, income_entries, bank_accounts, bank_deposits)
    - Write migration scripts for accounts tables
    - Implement expense validation logic
    - Set up bank balance tracking
    - _Requirements: 9.1-9.10_

  - [ ] 14.2 Implement AccountsController and AccountsService
    - Create expense and income recording
    - Implement bank account management
    - Develop financial report generation
    - _Requirements: 9.2, 9.3, 9.6, 9.10_

  - [ ] 14.3 Create accounts views (expense recording, income tracking, bank management, reports)
    - Design financial management interface
    - Implement profit/loss report generation
    - Create balance sheet visualization
    - _Requirements: 9.7, 9.8, 9.9_

  - [ ] 14.4 Write unit tests for accounts module
    - Test expense validation
    - Test bank balance calculations
    - Test report generation logic

- [ ] 15. Implement Asset Management module
  - [ ] 15.1 Create asset database tables (assets, asset_categories, asset_disposals, asset_depreciation)
    - Write migration scripts for asset tables
    - Implement asset serial number validation
    - Set up depreciation calculation logic
    - _Requirements: 10.1-10.7_

  - [ ] 15.2 Implement AssetController and AssetService
    - Create asset register management
    - Implement asset disposal tracking
    - Develop depreciation calculation
    - _Requirements: 10.2, 10.3, 10.5_

  - [ ] 15.3 Create asset views (asset register, disposal tracking, depreciation, reports)
    - Design asset management interface
    - Implement warranty expiry alerts
    - Create asset register reports
    - _Requirements: 10.4, 10.6_

  - [ ] 15.4 Write unit tests for asset module
    - Test serial number validation
    - Test disposal validation
    - Test depreciation calculation

- [ ] 16. Checkpoint - Complete Phase 4
  - Ensure network, accounts, and asset modules are functional
  - Test map visualization and coordinate editing
  - Verify financial report generation
  - Ensure all tests pass, ask the user if questions arise.

### Phase 5: Bandwidth, Portals & Configuration Modules

- [ ] 17. Implement Bandwidth Purchase & Sales module
  - [ ] 17.1 Create bandwidth database tables (bandwidth_providers, bandwidth_resellers, bandwidth_purchases, bandwidth_invoices, reseller_ledgers)
    - Write migration scripts for bandwidth tables
    - Implement credit limit validation
    - Set up ledger balance tracking
    - _Requirements: 11.1-11.8_

  - [ ] 17.2 Implement BandwidthController and BandwidthService
    - Create bandwidth provider and reseller management
    - Implement purchase bill recording
    - Develop reseller invoice generation
    - _Requirements: 11.3, 11.4, 11.8_

  - [ ] 17.3 Create bandwidth views (provider management, reseller management, purchases, invoices)
    - Design bandwidth management interface
    - Implement utilization summary display
    - Create ledger report generation
    - _Requirements: 11.7_

  - [ ] 17.4 Write unit tests for bandwidth module
    - Test credit limit validation
    - Test capacity validation
    - Test ledger balance tracking

- [ ] 18. Implement Bandwidth Reseller Portal
  - [ ] 18.1 Create reseller portal database tables (reseller_portal_sessions, reseller_tickets)
    - Write migration scripts for portal tables
    - Implement session timeout enforcement
    - Set up account suspension handling
    - _Requirements: 12.1-12.8_

  - [ ] 18.2 Implement ResellerPortalController and ResellerPortalService
    - Create reseller authentication and session management
    - Implement ticket creation for resellers
    - Develop invoice viewing and download functionality
    - _Requirements: 12.2, 12.4, 12.7_

  - [ ] 18.3 Create reseller portal views (login, dashboard, invoices, tickets, profile)
    - Design separate reseller portal interface
    - Implement dashboard with balance display
    - Create ticket management for resellers
    - _Requirements: 12.1, 12.3, 12.6_

  - [ ] 18.4 Write unit tests for reseller portal
    - Test session timeout enforcement
    - Test account suspension handling
    - Test ticket creation validation

- [ ] 19. Implement MAC Reseller Portal
  - [ ] 19.1 Create MAC reseller database tables (mac_resellers, mac_reseller_clients, mac_reseller_tariffs, mac_reseller_billing)
    - Write migration scripts for MAC reseller tables
    - Implement client MAC address validation
    - Set up daily billing generation
    - _Requirements: 13.1-13.9_

  - [ ] 19.2 Implement MacResellerController and MacResellerService
    - Create MAC reseller authentication and client management
    - Implement tariff plan management
    - Develop daily billing and payment tracking
    - _Requirements: 13.3, 13.5, 13.8_

  - [ ] 19.3 Create MAC reseller views (login, dashboard, client management, billing, payments)
    - Design separate MAC reseller portal interface
    - Implement client management with MAC addresses
    - Create billing and payment tracking
    - _Requirements: 13.2, 13.6, 13.9_

  - [ ] 19.4 Write unit tests for MAC reseller portal
    - Test MAC address validation
    - Test daily billing generation
    - Test account balance monitoring

- [ ] 20. Implement Employee Portal
  - [ ] 20.1 Create employee portal database tables (employee_collection_sessions, employee_payments)
    - Write migration scripts for employee portal tables
    - Implement data isolation per employee
    - Set up collection session tracking
    - _Requirements: 14.1-14.9_

  - [ ] 20.2 Implement EmployeePortalController and EmployeePortalService
    - Create employee authentication with strict data isolation
    - Implement customer search and payment recording
    - Develop task and ticket management for employees
    - _Requirements: 14.3, 14.5, 14.8_

  - [ ] 20.3 Create employee portal views (login, dashboard, customer search, payment recording, tasks)
    - Design separate employee portal interface
    - Implement customer search functionality
    - Create payment recording with collector tracking
    - _Requirements: 14.2, 14.4, 14.7_

  - [ ] 20.4 Write unit tests for employee portal
    - Test data isolation validation
    - Test payment collector tracking
    - Test access restriction validation

- [ ] 21. Implement Business Configuration module
  - [ ] 21.1 Create configuration database tables (configuration_settings, billing_rules, invoice_templates)
    - Write migration scripts for configuration tables
    - Implement package code validation
    - Set up billing rule validation
    - _Requirements: 17.1-17.10_

  - [ ] 21.2 Implement ConfigurationController and ConfigurationService
    - Create zone, sub-zone, POP, and BOX management
    - Implement package setup with MikroTik/RADIUS profiles
    - Develop billing automation rule configuration
    - _Requirements: 17.4, 17.5, 17.7_

  - [ ] 21.3 Create configuration views (zone management, package setup, billing rules, templates)
    - Design configuration management interface
    - Implement package setup with profile linking
    - Create template management for invoices and SMS
    - _Requirements: 17.1, 17.6, 17.8_

  - [ ] 21.4 Write unit tests for configuration module
    - Test package code validation
    - Test billing rule validation
    - Test template validation

- [ ] 22. Checkpoint - Complete Phase 5
  - Ensure all portal modules are functional with proper authentication
  - Test data isolation for reseller and employee portals
  - Verify configuration management affects system behavior
  - Ensure all tests pass, ask the user if questions arise.

### Phase 6: Reporting, OTT, Roles, Campaigns & API

- [x] 23. Implement BTRC Report module
  - [x] 23.1 Create BTRC report database tables (btrc_reports, btrc_report_logs)
    - Write migration scripts for BTRC tables
    - Implement report data aggregation logic
    - Set up report generation logging
    - _Requirements: 15.1-15.7_

  - [x] 23.2 Implement BtrcReportController and BtrcReportService
    - Create BTRC DIS report generation
    - Implement data aggregation by division/district
    - Develop CSV and PDF export functionality
    - _Requirements: 15.1, 15.4, 15.7_

  - [x] 23.3 Create BTRC report views (report generation, preview, history)
    - Design report generation interface
    - Implement report preview functionality
    - Create report generation history
    - _Requirements: 15.5, 15.6_

  - [x] 23.4 Write unit tests for BTRC report module
    - Test data aggregation logic
    - Test report format validation
    - Test zero-value report handling

- [-] 24. Implement OTT Subscription Management module
  - [x] 24.1 Create OTT database tables (ott_providers, ott_packages, ott_subscriptions, ott_renewal_logs)
    - Write migration scripts for OTT tables
    - Implement subscription validation
    - Set up auto-renewal failure handling
    - _Requirements: 16.1-16.8_

  - [x] 24.2 Implement OttController and OttService
    - Create OTT provider and package management
    - Implement subscription creation and renewal
    - Develop manual activation/deactivation functionality
    - _Requirements: 16.3, 16.4, 16.7_

  - [x] 24.3 Create OTT views (provider management, package bundling, subscriptions, dashboard)
    - Design OTT management interface
    - Implement subscription dashboard
    - Create renewal failure notifications
    - _Requirements: 16.6, 16.8_

  - [-] 24.4 Write unit tests for OTT module
    - Test subscription validation
    - Test renewal failure handling
    - Test provider API integration

- [x] 25. Implement Role-Based Permissions UI
  - [x] 25.1 Create role permission database tables (role_change_logs)
    - Write migration scripts for role tables
    - Implement permission change logging
    - Set up superadmin role protection
    - _Requirements: 18.1-18.10_

  - [x] 25.2 Implement RoleController and RoleService
    - Create role CRUD operations with validation
    - Implement permission management by module
    - Develop user role assignment functionality
    - _Requirements: 18.2, 18.3, 18.5_

  - [x] 25.3 Create role views (role list, permission management, user assignment)
    - Design role management interface
    - Implement permission checkbox groups by module
    - Create role assignment interface
    - _Requirements: 18.1, 18.3, 18.6_

  - [x] 25.4 Write unit tests for role module
    - Test permission enforcement
    - Test role validation
    - Test superadmin protection

- [ ] 26. Implement Bulk SMS & Mailing System
  - [ ] 26.1 Create campaign database tables (email_campaigns, campaign_recipients, campaign_logs)
    - Write migration scripts for campaign tables
    - Implement recipient filtering logic
    - Set up batch processing for large campaigns
    - _Requirements: 19.1-19.9_

  - [ ] 26.2 Implement CampaignController and CampaignService
    - Create SMS and email campaign management
    - Implement recipient filtering by zone/package/status/branch
    - Develop scheduled campaign execution
    - _Requirements: 19.2, 19.5, 19.8_

  - [ ] 26.3 Create campaign views (campaign manager, template management, statistics)
    - Design campaign management interface
    - Implement template management
    - Create delivery statistics dashboard
    - _Requirements: 19.1, 19.4, 19.7_

  - [ ] 26.4 Write unit tests for campaign module
    - Test recipient filtering logic
    - Test batch processing validation
    - Test template validation

- [ ] 27. Implement Android App API
  - [ ] 27.1 Create API database tables (device_tokens, api_tokens, api_rate_limits)
    - Write migration scripts for API tables
    - Implement JWT token validation
    - Set up rate limiting infrastructure
    - _Requirements: 20.1-20.7_

  - [ ] 27.2 Implement ApiController and ApiService
    - Create versioned REST API endpoints under `/api/v1/`
    - Implement JWT authentication with refresh tokens
    - Develop customer, payment, and ticket endpoints
    - _Requirements: 20.2, 20.3, 20.5, 20.6_

  - [ ] 27.3 Implement push notification support
    - Integrate FCM push notification service
    - Implement device token management
    - Develop notification delivery logic
    - _Requirements: Push notification support mentioned in design_

  - [ ] 27.4 Write unit tests for API module
    - Test JWT token validation
    - Test rate limiting
    - Test API endpoint responses

- [ ] 28. Checkpoint - Complete Phase 6
  - Ensure all reporting, OTT, role, campaign, and API modules are functional
  - Test BTRC report generation and export
  - Verify API authentication and rate limiting
  - Ensure all tests pass, ask the user if questions arise.

### Phase 7: Integration & Final Validation

- [x] 29. Implement Branch Management module
  - [x] 29.1 Create branch database tables (branch_reports, branch_credentials)
    - Write migration scripts for branch tables
    - Implement branch code uniqueness validation
    - Set up branch deactivation logic
    - _Requirements: 1.1-1.8_

  - [x] 29.2 Implement BranchController and BranchService
    - Create branch CRUD operations with validation
    - Implement branch data isolation for branch_admin
    - Develop branch summary report generation
    - _Requirements: 1.1, 1.3, 1.5_

  - [x] 29.3 Create branch views (branch list, creation, editing, reports)
    - Design branch management interface
    - Implement branch credential assignment
    - Create PDF/CSV report export
    - _Requirements: 1.7_

  - [x] 29.4 Write unit tests for branch module
    - Test branch code uniqueness
    - Test data isolation validation
    - Test deactivation logic

- [x] 30. Final integration and wiring
  - [x] 30.1 Integrate all modules with existing authentication system
    - Update AuthMiddleware for new module permissions
    - Extend session management for portal users
    - Implement unified error handling across all modules
    - _Requirements: Cross-module integration_

  - [x] 30.2 Implement cross-module data flows
    - Connect purchase bills to inventory updates
    - Link sales invoices to accounts income entries
    - Integrate ticket assignments with employee portal
    - _Requirements: Data flow requirements across modules_

  - [x] 30.3 Create unified dashboard and navigation
    - Design module navigation menu based on user permissions
    - Implement dashboard widgets showing cross-module data
    - Create role-based view customization
    - _Requirements: User experience across modules_

  - [x] 30.4 Write integration tests
    - Test cross-module data flows
    - Test permission enforcement across modules
    - Test error handling in integrated scenarios

- [x] 31. Final checkpoint - Complete implementation
  - Ensure all 20 modules are fully integrated
  - Test end-to-end workflows across modules
  - Verify all requirements are met
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Unit tests validate specific examples and edge cases
- Integration tests validate cross-module workflows
- The design document explicitly states property-based testing is NOT appropriate for this feature set, so property test tasks are omitted
- Implementation follows the 6-phase roadmap: Foundation, HR/Support/Tasks, Sales/Purchase/Inventory, Network/Accounts/Assets, Bandwidth/Portals/Configuration, Reporting/OTT/Roles/Campaigns/API
- All code will be implemented in PHP 8+ following the existing MVC architecture pattern