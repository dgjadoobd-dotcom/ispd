# Design Document: Missing Modules Implementation

## Overview

This design document outlines the implementation of 20 missing modules for the Digital ISP ERP system — a PHP 8+ MVC application with custom router, SQLite/MySQL database, Tailwind CSS views, and existing Database class. The system already has Customer Management, Billing, MikroTik/RADIUS integration, Customer Portal, SMS, Automation, Payment Gateway, Dashboard, OLT/ONU, Webhooks, AI, MFA, IP Access Control, Rate Limiting, Audit Logging, and Bulk Operations.

The 20 modules extend the system to cover branch management, HR, support, tasks, sales, purchasing, inventory, network diagrams, accounting, assets, bandwidth reselling, portals, employee tools, regulatory reporting, OTT, configuration, RBAC UI, bulk communications, and a mobile API.

## Architecture

### System Architecture Overview

The system follows a layered MVC architecture with the following components:

1. **Presentation Layer**: Views using Tailwind CSS, Blade-like PHP templates
2. **Controller Layer**: PHP controllers handling HTTP requests and routing
3. **Service Layer**: Business logic services encapsulating complex operations
4. **Data Access Layer**: Database class (`DatabasePool`) with `fetchOne`/`fetchAll`/`insert`/`update`/`delete` methods
5. **Integration Layer**: External service integrations (RADIUS, MikroTik, SMS gateways, etc.)

### Technology Stack

- **Backend**: PHP 8+ with custom MVC framework
- **Database**: MySQL/SQLite with PDO persistence
- **Frontend**: Tailwind CSS, JavaScript (Vanilla JS + Alpine.js for interactivity)
- **Routing**: Custom router (`app/Core/Router.php`)
- **Authentication**: Session-based with role-based permissions
- **API**: RESTful endpoints with JWT authentication for mobile app

### Directory Structure

```
app/
├── Controllers/           # Controller classes
│   ├── BranchController.php
│   ├── HrController.php
│   ├── SupportController.php
│   └── ... (20 new controllers)
├── Services/             # Business logic services
│   ├── HrService.php
│   ├── InventoryService.php
│   └── ... (new services)
├── Core/                # Framework core
│   ├── Router.php
│   ├── DatabasePool.php
│   └── Cache.php
├── Middleware/          # HTTP middleware
└── Models/              # Data models (optional)

views/
├── branches/           # Branch management views
├── hr/                 # HR & payroll views
├── support/           # Support ticket views
└── ... (module views)

database/
├── migrations/        # Database migrations
└── schema.sql         # Complete schema
```

## Components and Interfaces

### 1. Branch Management Module

**Controller**: `BranchController`
**Service**: `BranchService`
**Views**: `/views/branches/`

**Key Features**:
- CRUD operations for branches
- Branch-specific data isolation
- Branch summary reports (PDF/CSV export)
- Branch deactivation with data preservation

**Database Tables**:
- `branches` (existing)
- `branch_reports` (new) - for storing generated reports
- `branch_credentials` (new) - per-branch login credentials

### 2. HR & Payroll Module

**Controller**: `HrController`
**Service**: `HrService`, `PayrollService`
**Views**: `/views/hr/`

**Key Features**:
- Employee profile management
- Department and designation management
- Attendance tracking (present/absent/late/half_day/leave)
- Salary generation with deductions and allowances
- Performance appraisal records
- Leave balance tracking

**Database Tables**:
- `departments` (new)
- `designations` (new)
- `employees` (new) - linked to `users` table
- `attendance` (new)
- `salary_slips` (new)
- `performance_appraisals` (new)
- `leave_balances` (new)

### 3. Support & Ticketing Module (Admin Side)

**Controller**: `SupportController`
**Service**: `SupportService`, `SlaService`
**Views**: `/views/support/`

**Key Features**:
- Ticket management with SLA tracking
- Ticket assignment to employees
- SMS notifications for assignments
- Problem categorization
- Comment/activity threads
- SLA compliance dashboard

**Database Tables**:
- `support_tickets` (existing)
- `ticket_comments` (new)
- `ticket_assignments` (new)
- `sla_violations` (new)
- `problem_categories` (new)

### 4. Task Management Module

**Controller**: `TaskController`
**Service**: `TaskService`
**Views**: `/views/tasks/`

**Key Features**:
- Task creation and assignment
- Status tracking (pending/in_progress/completed/cancelled)
- Daily calendar view
- Task history logging
- Overdue task detection
- Bulk task assignment
- Completion rate reporting

**Database Tables**:
- `tasks` (new)
- `task_history` (new)
- `task_assignments` (new)

### 5. Sales & Service Invoicing Module

**Controller**: `SalesInvoiceController`
**Service**: `SalesInvoiceService`
**Views**: `/views/sales/`

**Key Features**:
- Installation fee invoices
- Product and service invoices
- Multi-line item support
- Partial payment tracking
- PDF invoice generation
- Cashbook integration

**Database Tables**:
- `sales_invoices` (new)
- `sales_invoice_items` (new)
- `sales_payments` (new)

### 6. Purchase Management Module

**Controller**: `PurchaseController`
**Service**: `PurchaseService`
**Views**: `/views/purchases/`

**Key Features**:
- Vendor management
- Purchase requisitions
- Purchase orders
- Purchase bill recording
- Inventory stock updates
- Vendor payment tracking
- Vendor ledger reports

**Database Tables**:
- `vendors` (new)
- `purchase_requisitions` (new)
- `purchase_orders` (existing)
- `purchase_bills` (new)
- `vendor_payments` (new)

### 7. Inventory Management Module

**Controller**: `InventoryController`
**Service**: `InventoryService`
**Views**: `/views/inventory/`

**Key Features**:
- Product catalog management
- Stock level tracking
- Low-stock alerts
- Stock movement tracking
- Inter-warehouse transfers
- Stock voucher generation (PDF)
- Stock summary reports

**Database Tables**:
- `inventory_items` (existing)
- `stock_movements` (existing)
- `warehouses` (existing)
- `stock_vouchers` (new)
- `stock_transfers` (new)

### 8. Network Diagram Module

**Controller**: `NetworkDiagramController`
**Service**: `NetworkDiagramService`
**Views**: `/views/network/`

**Key Features**:
- Interactive Leaflet.js map
- POP and BOX node management
- Customer location markers
- Network topology visualization
- Filtering by zone/branch/status
- Drag-and-drop coordinate editing
- PNG export functionality

**Database Tables**:
- `pop_nodes` (new)
- `box_nodes` (new)
- `network_connections` (new)

### 9. Accounts Management Module

**Controller**: `AccountsController`
**Service**: `AccountsService`
**Views**: `/views/accounts/`

**Key Features**:
- Expense category management
- Daily expense recording
- Non-billing income tracking
- Bank account management
- Bank deposit tracking
- Profit/loss reports
- Balance sheet generation
- PDF/CSV export

**Database Tables**:
- `expense_categories` (new)
- `expense_entries` (new)
- `income_entries` (new)
- `bank_accounts` (new)
- `bank_deposits` (new)

### 10. Asset Management Module

**Controller**: `AssetController`
**Service**: `AssetService`
**Views**: `/views/assets/`

**Key Features**:
- Asset register with serial numbers
- Asset disposal tracking
- Warranty expiry alerts
- Straight-line depreciation calculation
- Asset book value tracking
- Disposal log

**Database Tables**:
- `assets` (new)
- `asset_categories` (new)
- `asset_disposals` (new)
- `asset_depreciation` (new)

### 11. Bandwidth Purchase & Sales Module

**Controller**: `BandwidthController`
**Service**: `BandwidthService`
**Views**: `/views/bandwidth/`

**Key Features**:
- Bandwidth provider management
- Bandwidth reseller management
- Monthly purchase bill recording
- Reseller invoice generation
- Reseller ledger management
- Bandwidth utilization summary
- Credit limit enforcement

**Database Tables**:
- `bandwidth_providers` (new)
- `bandwidth_resellers` (new)
- `bandwidth_purchases` (new)
- `bandwidth_invoices` (new)
- `reseller_ledgers` (new)

### 12. Bandwidth Reseller Portal

**Controller**: `ResellerPortalController`
**Service**: `ResellerPortalService`
**Views**: `/views/reseller-portal/`

**Key Features**:
- Separate login page for resellers
- Dashboard with balance and invoice info
- Invoice history with PDF download
- Support ticket creation
- Profile management
- Session timeout enforcement
- Account suspension handling

**Database Tables**:
- `reseller_portal_sessions` (new)
- `reseller_tickets` (new)

### 13. MAC Reseller Portal

**Controller**: `MacResellerController`
**Service**: `MacResellerService`
**Views**: `/views/mac-reseller/`

**Key Features**:
- MAC reseller login portal
- Client management with MAC addresses
- Tariff plan management
- Daily billing generation
- Payment collection tracking
- Account balance monitoring
- Internet access suspension based on balance

**Database Tables**:
- `mac_resellers` (new)
- `mac_reseller_clients` (new)
- `mac_reseller_tariffs` (new)
- `mac_reseller_billing` (new)

### 14. Employee Portal

**Controller**: `EmployeePortalController`
**Service**: `EmployeePortalService`
**Views**: `/views/employee-portal/`

**Key Features**:
- Dedicated employee login
- Task and ticket dashboard
- Customer search and payment collection
- Payment recording with collector tracking
- Ticket status updates
- Daily collection summary
- Strict data isolation per employee

**Database Tables**:
- `employee_collection_sessions` (new)
- `employee_payments` (new)

### 15. BTRC Report Module

**Controller**: `BtrcReportController`
**Service**: `BtrcReportService`
**Views**: `/views/reports/btrc/`

**Key Features**:
- BTRC DIS report generation
- CSV export with BTRC format
- PDF export with company letterhead
- Data aggregation by division/district
- Report preview functionality
- Generation log tracking
- Zero-value report handling

**Database Tables**:
- `btrc_reports` (new)
- `btrc_report_logs` (new)

### 16. OTT Subscription Management

**Controller**: `OttController`
**Service**: `OttService`
**Views**: `/views/ott/`

**Key Features**:
- OTT provider management
- OTT package bundling
- Subscription creation and renewal
- Auto-renewal with failure handling
- Manual activation/deactivation
- Subscriber dashboard
- SMS notifications for failures

**Database Tables**:
- `ott_providers` (new)
- `ott_packages` (new)
- `ott_subscriptions` (new)
- `ott_renewal_logs` (new)

### 17. Business Configuration Module

**Controller**: `ConfigurationController`
**Service**: `ConfigurationService`
**Views**: `/views/configuration/`

**Key Features**:
- Zone and sub-zone management
- POP and BOX configuration
- Package setup with MikroTik/RADIUS profiles
- Billing automation rules
- Invoice template management
- SMS template configuration
- Default settings (currency, date format, timezone)

**Database Tables**:
- `configuration_settings` (new)
- `billing_rules` (new)
- `invoice_templates` (new)
- `sms_templates` (existing)

### 18. Role-Based Permissions UI

**Controller**: `RoleController`
**Service**: `RoleService`
**Views**: `/views/roles/`

**Key Features**:
- Role creation and editing
- Permission management by module
- User role assignment
- Permission change logging
- Permission enforcement middleware
- Default permission seeding
- Superadmin role protection

**Database Tables**:
- `roles` (existing)
- `permissions` (existing)
- `role_permissions` (existing)
- `role_change_logs` (new)

### 19. Bulk SMS & Mailing System

**Controller**: `CampaignController`
**Service**: `CampaignService`
**Views**: `/views/campaigns/`

**Key Features**:
- SMS campaign management
- Recipient filtering (zone/package/status/branch)
- SMS template management
- Email broadcast functionality
- Campaign delivery statistics
- Scheduled campaign execution
- Batch processing for large campaigns

**Database Tables**:
- `sms_campaigns` (existing)
- `email_campaigns` (new)
- `campaign_recipients` (new)
- `campaign_logs` (new)

### 20. Android App API

**Controller**: `ApiController` (with versioned endpoints)
**Service**: `ApiService`, `PushNotificationService`
**Views**: N/A (JSON API)

**Key Features**:
- Versioned REST API (`/api/v1/`)
- JWT authentication with refresh tokens
- Customer data endpoints
- Payment recording endpoint
- Dashboard statistics
- FCM push notification support
- Rate limiting (60 requests/minute/IP)
- Ticket management endpoints

**Database Tables**:
- `device_tokens` (new)
- `api_tokens` (new)
- `api_rate_limits` (new)

## Data Models

### Core Entity Relationships

```
Branch (1) --- (N) Zone (1) --- (N) Area (1) --- (N) Customer
Branch (1) --- (N) User
Branch (1) --- (N) Department
Branch (1) --- (N) Warehouse
Branch (1) --- (N) POP
Branch (1) --- (N) Bandwidth_Provider

User (1) --- (1) Employee
Employee (1) --- (N) Attendance
Employee (1) --- (N) Task
Employee (1) --- (N) Ticket_Assignment

Customer (1) --- (N) Invoice
Customer (1) --- (N) Payment
Customer (1) --- (N) Ticket
Customer (1) --- (1) OTT_Subscription

Vendor (1) --- (N) Purchase_Order
Purchase_Order (1) --- (N) Purchase_Bill
Purchase_Bill (1) --- (N) Stock_Movement

Inventory_Item (1) --- (N) Stock_Movement
Warehouse (1) --- (N) Inventory_Item
Warehouse (1) --- (N) Stock_Transfer

Bandwidth_Reseller (1) --- (N) Bandwidth_Invoice
Bandwidth_Reseller (1) --- (1) Reseller_Ledger

MAC_Reseller (1) --- (N) MAC_Reseller_Client
MAC_Reseller_Client (1) --- (N) MAC_Reseller_Billing

Role (1) --- (N) User
Role (1) --- (N) Permission (through Role_Permission)

SMS_Campaign (1) --- (N) Campaign_Recipient
Email_Campaign (1) --- (N) Campaign_Recipient
```

### Detailed Table Schemas

See Appendix A for complete SQL schema definitions for all new tables.

## Integration Points

### 1. Existing Database Class Integration

All new modules will use the existing `DatabasePool` class for data access:

```php
$db = DatabasePool::getInstance()->getConnection();
$result = $db->fetchAll("SELECT * FROM table WHERE condition = ?", [$value]);
$id = $db->insert('table', $data);
$db->update('table', $data, 'id = ?', [$id]);
$db->delete('table', 'id = ?', [$id]);
```

### 2. Authentication Integration

- Use existing session-based authentication (`$_SESSION['user_id']`, `$_SESSION['user_role']`)
- Extend role-based access control for new modules
- Integrate with existing `AuthMiddleware`

### 3. SMS Service Integration

- Use existing `SmsService` for notifications
- Extend SMS template system for new event types
- Integrate with campaign module for bulk SMS

### 4. RADIUS Integration

- Use existing `RadiusService` for user management
- Extend for bandwidth reseller client management
- Integrate with MAC reseller portal for access control

### 5. MikroTik Integration

- Use existing `MikroTikService` for bandwidth profile management
- Extend for new package types and reseller configurations

### 6. Webhook Integration

- Use existing `WebhookService` for event notifications
- Extend webhook events for new module activities

### 7. Audit Logging Integration

- Use existing `LoggingService` for activity tracking
- Extend audit logs for all new module operations

### 8. Rate Limiting Integration

- Use existing `RateLimiterService` for API endpoints
- Apply rate limiting to new API endpoints

## Error Handling

### Error Types

1. **Validation Errors**: Input validation failures (return 400 Bad Request)
2. **Authentication Errors**: Invalid credentials or expired tokens (return 401 Unauthorized)
3. **Authorization Errors**: Insufficient permissions (return 403 Forbidden)
4. **Resource Errors**: Missing or invalid resources (return 404 Not Found)
5. **Business Logic Errors**: Violation of business rules (return 409 Conflict)
6. **System Errors**: Internal server errors (return 500 Internal Server Error)

### Error Response Format

```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "details": "Additional error details if applicable",
    "timestamp": "2024-01-15T10:30:00Z"
  }
}
```

### Error Logging

- All errors logged to `error_log` table with stack trace
- Critical errors trigger email/SMS alerts to administrators
- API errors include request ID for debugging

## Testing Strategy

### Unit Testing

- Test individual service methods with PHPUnit
- Mock database dependencies
- Test business logic in isolation
- Focus on edge cases and error conditions

### Integration Testing

- Test controller endpoints with HTTP requests
- Test database interactions with test database
- Test external service integrations with mocks
- Test permission enforcement

### Property-Based Testing

*Note: Property-based testing is NOT appropriate for this feature set as it involves:*

1. **Infrastructure Configuration** (business rules, UI templates, settings)
2. **CRUD Operations** with complex validation logic
3. **External Service Integrations** (SMS, RADIUS, MikroTik)
4. **UI Rendering** (network diagrams, reports, dashboards)
5. **Workflow Management** (tickets, tasks, approvals)

**Alternative Testing Approaches**:
- **Snapshot Testing**: For configuration validation and report generation
- **Example-Based Unit Tests**: For business logic with concrete scenarios
- **Integration Tests**: For external service integrations (1-3 examples each)
- **UI/Visual Tests**: For network diagrams and report layouts
- **Performance Tests**: For bulk operations and report generation

### Test Coverage Goals

- 80% unit test coverage for service layer
- 100% integration test coverage for critical user journeys
- All error conditions tested
- All permission checks validated
- All export formats (PDF, CSV) tested

### Test Data Management

- Use factory patterns for test data generation
- Clean up test data after each test
- Use database transactions for test isolation
- Maintain separate test database

## Security Considerations

### 1. Input Validation

- All user input sanitized using `sanitize()` helper
- SQL injection prevention via prepared statements
- XSS prevention via output escaping in views
- File upload validation (type, size, virus scanning)

### 2. Authentication & Authorization

- Session timeout enforcement (30 minutes inactivity)
- Strong password policies
- Role-based access control for all endpoints
- API token rotation (24-hour JWT, 30-day refresh)

### 3. Data Protection

- Branch data isolation for branch_admin users
- Employee data isolation for employee portal
- Sensitive data encryption (passwords, API keys)
- Audit logging for all data modifications

### 4. API Security

- Rate limiting (60 requests/minute/IP)
- JWT token validation
- HTTPS enforcement
- CORS configuration for mobile app

### 5. File Security

- Upload directory outside web root
- File type validation
- Virus scanning for uploaded files
- Access control for file downloads

## Performance Considerations

### 1. Database Optimization

- Indexes on frequently queried columns
- Query optimization for reports
- Database connection pooling
- Caching of frequently accessed data

### 2. Report Generation

- Background job processing for large reports
- Pagination for large datasets
- Caching of generated reports
- Incremental data processing

### 3. Bulk Operations

- Batch processing for large imports
- Progress tracking for long-running operations
- Memory optimization for large datasets
- Transaction management for data consistency

### 4. Network Diagram

- Lazy loading of map markers
- Cluster markers for dense areas
- Caching of map tiles
- Optimized GeoJSON generation

## Deployment Considerations

### 1. Database Migrations

- All new tables created via migration scripts
- Backward compatibility maintained
- Rollback scripts provided
- Migration logging for audit trail

### 2. Configuration Management

- Environment-specific configuration files
- Database configuration in `.env` files
- Secret management for API keys
- Version control for configuration changes

### 3. Monitoring & Alerting

- Health checks for new modules
- Performance monitoring for critical operations
- Error alerting via email/SMS
- Usage statistics tracking

### 4. Backup & Recovery

- Regular database backups
- File upload backups
- Disaster recovery procedures
- Data export/import capabilities

## Appendix A: Complete SQL Schema

*(To be completed in next section)*

## Appendix B: API Endpoint Specifications

*(To be completed in next section)*

## Appendix C: View Template Specifications

*(To be completed in next section)*

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

**Assessment of PBT Applicability:**

After reviewing the 20 modules, property-based testing is **NOT appropriate** for the majority of this feature set because:

1. **CRUD Operations**: Most modules involve simple create/read/update/delete operations with validation logic, not complex algorithmic transformations
2. **UI/Configuration**: Network diagrams, report generation, and business configuration are UI/rendering focused
3. **External Integrations**: SMS, RADIUS, MikroTik integrations involve external service calls
4. **Workflow Management**: Ticket and task management involve state machines and human workflows
5. **Reporting**: BTRC reports and financial reports involve data aggregation and formatting

**Alternative Testing Strategy:**

Instead of property-based tests, we will use:
- **Example-Based Unit Tests**: For business logic validation
- **Integration Tests**: For external service integrations (1-3 examples each)
- **Snapshot Tests**: For configuration validation and report generation
- **UI/Visual Tests**: For network diagrams and report layouts
- **Performance Tests**: For bulk operations

**Limited PBT Applicability Areas:**

The following areas MAY benefit from limited property-based testing:

1. **Data Validation**: Email, phone number, NID validation logic
2. **Business Rule Calculations**: SLA deadline calculations, salary calculations with deductions
3. **Data Transformation**: Report data aggregation logic

However, these are better served by comprehensive example-based tests covering edge cases.

**Conclusion:** The Correctness Properties section is omitted as PBT is not the primary testing strategy for this feature set. Testing will focus on example-based unit tests and integration tests as outlined in the Testing Strategy section.

## Error Handling (Detailed)

### Error Categories by Module

#### 1. Branch Management Errors
- **BRANCH_DUPLICATE_CODE**: Branch code already exists (409 Conflict)
- **BRANCH_INACTIVE**: Attempt to create customer under inactive branch (400 Bad Request)
- **BRANCH_VALIDATION**: Invalid branch data (name, code, address validation) (400 Bad Request)

#### 2. HR & Payroll Errors
- **EMPLOYEE_DUPLICATE_NID**: NID number already registered (409 Conflict)
- **SALARY_DUPLICATE**: Salary slip already generated for employee/month (409 Conflict)
- **ATTENDANCE_INVALID**: Invalid attendance status or date (400 Bad Request)
- **LEAVE_BALANCE_INSUFFICIENT**: Insufficient leave balance (400 Bad Request)

#### 3. Support & Ticketing Errors
- **TICKET_DUPLICATE**: Duplicate ticket within 24 hours (409 Conflict)
- **SLA_BREACH**: Ticket SLA breached (record only, not error)
- **ASSIGNMENT_INVALID**: Invalid employee assignment (400 Bad Request)
- **CATEGORY_INVALID**: Invalid problem category (400 Bad Request)

#### 4. Task Management Errors
- **TASK_OVERDUE**: Task overdue (record only, not error)
- **ASSIGNMENT_INVALID**: Invalid employee assignment (400 Bad Request)
- **STATUS_TRANSITION_INVALID**: Invalid status transition (400 Bad Request)

#### 5. Sales & Service Invoicing Errors
- **INVOICE_DUPLICATE_NUMBER**: Invoice number already exists (409 Conflict)
- **PAYMENT_EXCEEDS_DUE**: Payment exceeds due amount (400 Bad Request)
- **CANCELLATION_INVALID**: Invalid invoice cancellation (400 Bad Request)

#### 6. Purchase Management Errors
- **PURCHASE_ORDER_EXCEED**: Bill total exceeds PO total by >5% (409 Conflict)
- **VENDOR_DUPLICATE**: Vendor already exists (409 Conflict)
- **APPROVAL_REQUIRED**: Secondary approval required (400 Bad Request)

#### 7. Inventory Management Errors
- **STOCK_INSUFFICIENT**: Stock quantity insufficient for issue (400 Bad Request)
- **ITEM_DUPLICATE_CODE**: Item code already exists (409 Conflict)
- **MOVEMENT_INVALID**: Invalid stock movement type (400 Bad Request)

#### 8. Network Diagram Errors
- **COORDINATE_INVALID**: Invalid latitude/longitude coordinates (400 Bad Request)
- **NODE_DUPLICATE**: Node already exists at coordinates (409 Conflict)
- **CONNECTION_INVALID**: Invalid network connection (400 Bad Request)

#### 9. Accounts Management Errors
- **EXPENSE_BACKDATED**: Expense entry >30 days old requires approval (400 Bad Request)
- **BANK_BALANCE_INSUFFICIENT**: Insufficient bank balance for withdrawal (400 Bad Request)
- **CATEGORY_INVALID**: Invalid expense category (400 Bad Request)

#### 10. Asset Management Errors
- **ASSET_DUPLICATE_SERIAL**: Asset serial number already exists (409 Conflict)
- **DISPOSAL_INVALID**: Invalid asset disposal (400 Bad Request)
- **DEPRECIATION_INVALID**: Invalid depreciation calculation (400 Bad Request)

#### 11. Bandwidth Management Errors
- **CREDIT_LIMIT_EXCEEDED**: Reseller credit limit exceeded (400 Bad Request)
- **CAPACITY_EXCEEDED**: Allocated capacity exceeds purchased capacity (400 Bad Request)
- **PROVIDER_DUPLICATE**: Bandwidth provider already exists (409 Conflict)

#### 12-14. Portal Errors (Reseller, MAC Reseller, Employee)
- **SESSION_TIMEOUT**: Session expired (401 Unauthorized)
- **ACCOUNT_SUSPENDED**: Account suspended (403 Forbidden)
- **ACCESS_DENIED**: Access to another user's data (403 Forbidden)
- **INVALID_CREDENTIALS**: Invalid login credentials (401 Unauthorized)

#### 15. BTRC Report Errors
- **REPORT_GENERATION_FAILED**: Report generation failed (500 Internal Server Error)
- **DATA_INSUFFICIENT**: Insufficient data for report period (400 Bad Request)

#### 16. OTT Subscription Errors
- **RENEWAL_FAILED**: OTT renewal failed (record only, not error)
- **PROVIDER_API_ERROR**: OTT provider API error (502 Bad Gateway)
- **SUBSCRIPTION_DUPLICATE**: Duplicate subscription (409 Conflict)

#### 17. Configuration Errors
- **PACKAGE_DUPLICATE_CODE**: Package code already exists (409 Conflict)
- **RULE_VALIDATION**: Invalid billing automation rule (400 Bad Request)
- **TEMPLATE_INVALID**: Invalid invoice/SMS template (400 Bad Request)

#### 18. Role & Permission Errors
- **PERMISSION_DENIED**: Insufficient permissions (403 Forbidden)
- **ROLE_DUPLICATE**: Role name already exists (409 Conflict)
- **SUPERADMIN_PROTECTED**: Attempt to delete superadmin role (403 Forbidden)

#### 19. Campaign Errors
- **CAMPAIGN_LARGE_BATCH**: Campaign exceeds 1000 recipients (process in batches)
- **GATEWAY_THROTTLED**: SMS gateway throttled (429 Too Many Requests)
- **TEMPLATE_INVALID**: Invalid SMS/email template (400 Bad Request)

#### 20. API Errors
- **JWT_EXPIRED**: JWT token expired (401 Unauthorized)
- **RATE_LIMIT_EXCEEDED**: Rate limit exceeded (429 Too Many Requests)
- **DEVICE_TOKEN_INVALID**: Invalid FCM device token (400 Bad Request)

### Error Recovery Strategies

#### Automatic Recovery
- **Retry Logic**: For transient failures (SMS gateway, OTT API)
- **Queue Processing**: For large operations (bulk imports, campaigns)
- **Partial Success**: For batch operations with some failures

#### Manual Recovery
- **Admin Intervention**: For critical errors requiring manual resolution
- **Data Correction**: For validation errors requiring data fixes
- **Configuration Updates**: For configuration-related errors

#### User Recovery
- **Clear Error Messages**: Actionable error messages for users
- **Alternative Flows**: Suggested alternative actions
- **Contact Information**: Support contact for unresolved issues

### Error Monitoring & Alerting

- **Real-time Monitoring**: Dashboard showing error rates by module
- **Alert Thresholds**: Email/SMS alerts for critical error rates
- **Error Trends**: Weekly error trend analysis
- **Root Cause Analysis**: Automated root cause identification for recurring errors

## Testing Strategy (Detailed)

### Testing Pyramid Implementation

```
          /¯¯¯¯¯¯¯¯¯¯\
         /  E2E Tests  \    (10%)
        /_______________\ 
       /                 \
      /   Integration     \   (20%)
     /      Tests          \
    /_______________________\ 
   /                         \
  /       Unit Tests          \  (70%)
 /_____________________________\
```

### 1. Unit Testing Strategy

#### Test Organization
- **Per Module**: Separate test files for each module's services
- **Per Service**: Test files for each service class
- **Test Data Factories**: Factory classes for test data generation

#### Key Unit Test Areas

**Branch Management**:
- Branch validation logic
- Branch code uniqueness validation
- Branch deactivation logic
- Branch report generation logic

**HR & Payroll**:
- Salary calculation with deductions/allowances
- Attendance validation
- Leave balance tracking
- Performance appraisal calculations

**Support & Ticketing**:
- SLA deadline calculation
- Ticket assignment validation
- Comment thread management
- SLA compliance calculation

**Task Management**:
- Task status transition validation
- Overdue task detection
- Bulk assignment logic
- Completion rate calculation

**Sales & Service Invoicing**:
- Invoice total calculation
- Partial payment validation
- Invoice cancellation logic
- Line item calculations

**Purchase Management**:
- Purchase order validation
- Bill total validation
- Approval workflow logic
- Vendor payment tracking

**Inventory Management**:
- Stock level validation
- Movement type validation
- Transfer validation
- Low-stock alert logic

**Accounts Management**:
- Expense validation
- Income tracking
- Bank balance calculations
- Report generation logic

**Configuration**:
- Rule validation
- Template validation
- Setting validation

#### Mocking Strategy
- **Database**: Mock `DatabasePool` for service tests
- **External Services**: Mock `SmsService`, `RadiusService`, `MikroTikService`
- **File System**: Mock file operations for uploads/exports
- **Session**: Mock `$_SESSION` for authentication tests

### 2. Integration Testing Strategy

#### Database Integration Tests
- **Test Database**: Separate test database with seeded data
- **Transaction Rollback**: Each test wrapped in transaction
- **Data Cleanup**: Automatic cleanup after tests

#### External Service Integration Tests
- **Test Environments**: Staging environments for external services
- **Mock Servers**: WireMock for API mocking
- **Contract Testing**: Verify service contracts

#### API Integration Tests
- **HTTP Client Tests**: Test API endpoints with actual HTTP requests
- **Authentication Tests**: Test JWT token flow
- **Rate Limiting Tests**: Test rate limiting behavior

### 3. End-to-End Testing Strategy

#### Critical User Journeys
1. **Customer Onboarding**: Branch → Zone → Customer → Invoice → Payment
2. **Support Flow**: Ticket Creation → Assignment → Resolution → Feedback
3. **Purchase Flow**: Requisition → PO → Bill → Payment → Stock Update
4. **Employee Portal**: Login → Task View → Payment Collection → Logout
5. **Report Generation**: Filter → Generate → Export → Download

#### Test Automation
- **Selenium**: For UI automation
- **Cypress**: For JavaScript-heavy interfaces (network diagram)
- **API Automation**: For mobile app API testing

### 4. Performance Testing Strategy

#### Load Testing
- **Concurrent Users**: Test with 50, 100, 500 concurrent users
- **Response Times**: Measure API response times under load
- **Database Performance**: Test query performance with large datasets

#### Stress Testing
- **Bulk Operations**: Import 1000+ customers, generate 1000+ invoices
- **Report Generation**: Generate reports for large date ranges
- **Campaign Processing**: Process campaigns with 10,000+ recipients

#### Scalability Testing
- **Horizontal Scaling**: Test with multiple application instances
- **Database Scaling**: Test with read replicas
- **Cache Performance**: Test Redis/memcached caching

### 5. Security Testing Strategy

#### Authentication & Authorization
- **Penetration Testing**: OWASP Top 10 vulnerabilities
- **Role Testing**: Verify role-based access control
- **Session Testing**: Test session management security

#### Data Protection
- **Data Leakage**: Test for sensitive data exposure
- **Encryption**: Test data encryption at rest and in transit
- **Access Control**: Test branch/employee data isolation

#### API Security
- **Token Security**: Test JWT token security
- **Rate Limiting**: Test rate limiting effectiveness
- **Input Validation**: Test for injection vulnerabilities

### 6. Test Data Management

#### Test Data Generation
- **Factory Classes**: PHP factories for test data
- **Faker Library**: For realistic test data
- **Scenario Data**: Predefined test scenarios

#### Test Data Isolation
- **Database Transactions**: Each test in transaction
- **Cleanup Hooks**: Automatic cleanup after tests
- **Parallel Execution**: Support for parallel test execution

#### Test Data Validation
- **Data Assertions**: Verify data integrity after operations
- **State Validation**: Verify system state after tests
- **Side Effect Validation**: Verify no unintended side effects

### 7. Test Reporting & Monitoring

#### Test Results
- **JUnit XML**: Standard test result format
- **HTML Reports**: Human-readable test reports
- **Code Coverage**: PHPUnit code coverage reports

#### Test Monitoring
- **Test Execution Times**: Monitor test performance
- **Flaky Tests**: Identify and fix flaky tests
- **Test Coverage Trends**: Monitor coverage over time

#### Continuous Integration
- **GitHub Actions**: Automated test execution on push
- **Quality Gates**: Minimum coverage requirements
- **Deployment Gates**: Tests must pass before deployment

### 8. Specialized Testing Areas

#### Network Diagram Testing
- **Visual Regression**: Compare map renders
- **Interaction Testing**: Test drag-and-drop, filtering
- **Performance Testing**: Test with 1000+ markers

#### Report Generation Testing
- **Format Validation**: Verify PDF/CSV formats
- **Content Accuracy**: Verify report calculations
- **Performance Testing**: Test large report generation

#### Mobile API Testing
- **Device Testing**: Test on actual mobile devices
- **Push Notification Testing**: Test FCM integration
- **Offline Testing**: Test offline functionality

### Test Environment Requirements

#### Development Environment
- **PHP 8+**: With all extensions enabled
- **MySQL/SQLite**: Test database
- **Composer**: For dependency management
- **Node.js**: For frontend build tools

#### Testing Environment
- **Docker**: Containerized test environment
- **Test Databases**: Isolated test databases
- **Mock Services**: Mock external services
- **CI/CD Pipeline**: Automated test execution

#### Production-like Environment
- **Staging Environment**: Mirror of production
- **Load Balancer**: For load testing
- **Monitoring**: Application performance monitoring

### Test Maintenance Strategy

#### Test Refactoring
- **Regular Review**: Quarterly test code review
- **Code Smells**: Identify and fix test code smells
- **Best Practices**: Follow testing best practices

#### Test Documentation
- **Test Cases**: Documented test cases
- **Test Data**: Documented test data scenarios
- **Test Environment**: Documented test environment setup

#### Test Training
- **Developer Training**: Testing best practices training
- **Test Workshops**: Regular testing workshops
- **Knowledge Sharing**: Test knowledge sharing sessions

## Implementation Roadmap

### Phase 1: Foundation (Weeks 1-2)
1. Database schema implementation for all new tables
2. Core service layer interfaces
3. Basic authentication and authorization extensions
4. Error handling framework

### Phase 2: Core Modules (Weeks 3-6)
1. Branch Management Module
2. HR & Payroll Module  
3. Support & Ticketing Module
4. Task Management Module
5. Sales & Service Invoicing Module

### Phase 3: Operations Modules (Weeks 7-10)
1. Purchase Management Module
2. Inventory Management Module
3. Accounts Management Module
4. Asset Management Module
5. Bandwidth Management Module

### Phase 4: Portal Modules (Weeks 11-14)
1. Bandwidth Reseller Portal
2. MAC Reseller Portal
3. Employee Portal
4. Configuration Module
5. Role-Based Permissions UI

### Phase 5: Advanced Features (Weeks 15-18)
1. Network Diagram Module
2. BTRC Report Module
3. OTT Subscription Management
4. Bulk SMS & Mailing System
5. Android App API

### Phase 6: Testing & Deployment (Weeks 19-20)
1. Comprehensive testing
2. Performance optimization
3. Security hardening
4. Deployment preparation
5. Documentation completion

## Risk Mitigation

### Technical Risks
1. **Database Performance**: Implement indexing, query optimization, caching
2. **External Service Dependencies**: Implement circuit breakers, fallbacks, queues
3. **Security Vulnerabilities**: Regular security audits, penetration testing
4. **Scalability Issues**: Load testing, horizontal scaling design

### Project Risks
1. **Scope Creep**: Strict requirement adherence, change control process
2. **Timeline Slippage**: Agile methodology, regular progress reviews
3. **Resource Constraints**: Prioritization, phased delivery
4. **Integration Complexity**: API contracts, integration testing

### Operational Risks
1. **Data Migration**: Backup strategy, rollback plans
2. **User Training**: Training materials, user documentation
3. **Support Readiness**: Support team training, knowledge base
4. **Monitoring Gaps**: Comprehensive monitoring, alerting setup

## Success Metrics

### Technical Metrics
- **Test Coverage**: >80% unit test coverage
- **Performance**: <2s page load, <100ms API response
- **Availability**: 99.9% uptime
- **Security**: Zero critical security vulnerabilities

### Business Metrics
- **User Adoption**: >90% module adoption rate
- **Process Efficiency**: >30% reduction in manual processes
- **Error Reduction**: >50% reduction in manual errors
- **Report Timeliness**: 100% on-time report generation

### Quality Metrics
- **Defect Density**: <0.1 defects per KLOC
- **Customer Satisfaction**: >4.5/5 satisfaction score
- **Support Volume**: <10% increase in support tickets
- **Training Effectiveness**: >90% training completion rate

## Conclusion

This design document provides a comprehensive blueprint for implementing the 20 missing modules in the Digital ISP ERP system. The architecture leverages existing system components while extending functionality to cover branch management, HR, support, tasks, sales, purchasing, inventory, network diagrams, accounting, assets, bandwidth reselling, portals, employee tools, regulatory reporting, OTT, configuration, RBAC UI, bulk communications, and mobile API.

The implementation follows a phased approach with rigorous testing strategies focused on example-based unit tests and integration tests rather than property-based testing, which is not appropriate for this feature set. Error handling, security, performance, and deployment considerations are comprehensively addressed.

The successful implementation of these modules will transform the Digital ISP ERP system into a complete enterprise resource planning solution for internet service providers, enabling efficient management of all business operations from a single integrated platform.