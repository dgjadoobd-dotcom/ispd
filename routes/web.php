<?php

require_once BASE_PATH . '/app/Core/Router.php';

// ── Customer Portal ───────────────────────────────────────────────
require_once BASE_PATH . '/routes/portal.php';

// ── Auth ──────────────────────────────────────────────────────────
Router::get('/', 'AuthController@showLogin');
Router::get('/login', 'AuthController@showLogin');
Router::post('/login', 'AuthController@login');
Router::get('/logout', 'AuthController@logout');

// ── Dashboard ─────────────────────────────────────────────────────
Router::get('/dashboard', 'DashboardController@index', ['AuthMiddleware']);
Router::get('/api/dashboard/live-stats', 'DashboardController@getLiveNetworkStats', ['AuthMiddleware']);

// ── Customers ─────────────────────────────────────────────────────
Router::prefix('/customers', function() {
    Router::get('', 'CustomerController@index', ['AuthMiddleware']);
    Router::get('/search', 'CustomerController@apiSearch', ['AuthMiddleware']);
    Router::get('/create', 'CustomerController@create', ['AuthMiddleware']);
    Router::post('/store', 'CustomerController@store', ['AuthMiddleware']);
    Router::get('/view/{id}', 'CustomerController@view', ['AuthMiddleware']);
    Router::get('/edit/{id}', 'CustomerController@edit', ['AuthMiddleware']);
    Router::post('/update/{id}', 'CustomerController@update', ['AuthMiddleware']);
    Router::post('/suspend/{id}', 'CustomerController@suspend', ['AuthMiddleware']);
    Router::post('/reconnect/{id}', 'CustomerController@reconnect', ['AuthMiddleware']);
    Router::post('/delete/{id}', 'CustomerController@delete', ['AuthMiddleware']);
    Router::post('/import', 'CustomerController@import', ['AuthMiddleware']);
    Router::get('/download-template', 'CustomerController@downloadTemplate', ['AuthMiddleware']);
});

// ── Billing ───────────────────────────────────────────────────────
Router::prefix('/billing', function() {
    Router::get('', 'BillingController@index', ['AuthMiddleware']);
    Router::get('/invoices', 'BillingController@invoices', ['AuthMiddleware']);
    Router::post('/generate', 'BillingController@generateInvoices', ['AuthMiddleware']);
    Router::get('/invoice/{id}', 'BillingController@viewInvoice', ['AuthMiddleware']);
    Router::get('/pay/{id}', 'BillingController@payForm', ['AuthMiddleware']);
    Router::post('/pay/{id}', 'BillingController@recordPayment', ['AuthMiddleware']);
    Router::get('/receipt/{id}', 'BillingController@printReceipt', ['AuthMiddleware']);
    Router::get('/cashbook', 'BillingController@cashbook', ['AuthMiddleware']);
});

// ── Network ───────────────────────────────────────────────────────
Router::prefix('/network', function() {
    Router::get('', 'NetworkController@index', ['AuthMiddleware']);
    Router::get('/ip-pools', 'NetworkController@ipPools', ['AuthMiddleware']);
    Router::post('/ip-pools/store', 'NetworkController@storePool', ['AuthMiddleware']);
    Router::get('/nas', 'NetworkController@nas', ['AuthMiddleware']);
    Router::post('/nas/store', 'NetworkController@storeNas', ['AuthMiddleware']);
    Router::post('/nas/update/{id}', 'NetworkController@updateNas', ['AuthMiddleware']);
    Router::post('/nas/delete/{id}', 'NetworkController@deleteNas', ['AuthMiddleware']);
    Router::post('/nas/test/{id}', 'NetworkController@testConnection', ['AuthMiddleware']);
    Router::post('/nas/toggle/{id}', 'NetworkController@toggleNas', ['AuthMiddleware']);
    Router::get('/nas/get/{id}', 'NetworkController@getNas', ['AuthMiddleware']);
    Router::post('/nas/refresh-all', 'NetworkController@refreshStatusAll', ['AuthMiddleware']);
    Router::get('/pppoe-active', 'NetworkController@pppoeActive', ['AuthMiddleware']);
    Router::post('/pppoe-kick/{nas_id}/{username}', 'NetworkController@kickPppoeSession', ['AuthMiddleware']);
    Router::get('/nas-profiles', 'NetworkController@apiGetProfiles', ['AuthMiddleware']);
    Router::get('/live-sessions', 'NetworkController@apiLiveSessions', ['AuthMiddleware']);
    Router::get('/radius', 'NetworkController@radius', ['AuthMiddleware']);
    Router::post('/radius/store', 'NetworkController@storeRadiusUser', ['AuthMiddleware']);
    Router::post('/radius/update/{username}', 'NetworkController@updateRadiusUser', ['AuthMiddleware']);
    Router::post('/radius/delete/{username}', 'NetworkController@deleteRadiusUser', ['AuthMiddleware']);
    Router::post('/radius/kick/{username}', 'NetworkController@kickRadiusUser', ['AuthMiddleware']);
    Router::get('/radius/profiles', 'NetworkController@radiusProfiles', ['AuthMiddleware']);
    Router::post('/radius/profiles/store', 'NetworkController@storeRadiusProfile', ['AuthMiddleware']);
    Router::post('/radius/profiles/delete/{name}', 'NetworkController@deleteRadiusProfile', ['AuthMiddleware']);
    Router::post('/radius/profiles/sync-from-mikrotik', 'NetworkController@syncProfilesFromMikrotik', ['AuthMiddleware']);
    Router::get('/radius/dashboard', 'NetworkController@radiusDashboard', ['AuthMiddleware']);
    Router::get('/radius/sessions', 'NetworkController@radiusSessions', ['AuthMiddleware']);
    Router::post('/radius/sessions/terminate/{session_id}', 'NetworkController@terminateRadiusSession', ['AuthMiddleware']);
    Router::get('/radius/analytics', 'NetworkController@radiusAnalytics', ['AuthMiddleware']);
    Router::get('/radius/audit', 'NetworkController@radiusAudit', ['AuthMiddleware']);
    
    // PPPoE Users Management
    Router::get('/pppoe-users', 'NetworkController@pppoeUsers', ['AuthMiddleware']);
    Router::post('/pppoe-users/update/{id}', 'NetworkController@updatePppoeUser', ['AuthMiddleware']);
    Router::post('/pppoe-users/reset-password/{id}', 'NetworkController@resetPppoePassword', ['AuthMiddleware']);
    Router::post('/pppoe-users/disable/{id}', 'NetworkController@disablePppoe', ['AuthMiddleware']);
    Router::post('/pppoe-users/create/{id}', 'NetworkController@createPppoeForCustomer', ['AuthMiddleware']);
    Router::post('/pppoe-users/kick/{id}', 'NetworkController@kickPppoeUser', ['AuthMiddleware']);

    // MikroTik RADIUS Configuration
    Router::get('/mikrotik-radius/{nas_id}', 'NetworkController@mikrotikRadiusConfig', ['AuthMiddleware']);
    Router::post('/mikrotik-radius/configure/{nas_id}', 'NetworkController@configureMikrotikRadius', ['AuthMiddleware']);
    Router::post('/mikrotik-radius/enable-ppp/{nas_id}', 'NetworkController@enablePppRadius', ['AuthMiddleware']);
    Router::post('/mikrotik-radius/sync-users/{nas_id}', 'NetworkController@syncMikrotikUsers', ['AuthMiddleware']);
    
    // PPPoE Profiles
    Router::get('/pppoe-profiles', 'NetworkController@pppoeProfiles', ['AuthMiddleware']);
    Router::post('/pppoe-profiles/store', 'NetworkController@storePppoeProfile', ['AuthMiddleware']);
    Router::post('/pppoe-profiles/update/{id}', 'NetworkController@updatePppoeProfile', ['AuthMiddleware']);
    Router::post('/pppoe-profiles/delete/{id}', 'NetworkController@deletePppoeProfile', ['AuthMiddleware']);
    Router::post('/pppoe-profiles/sync/{id}', 'NetworkController@syncProfileToNas', ['AuthMiddleware']);
    
    Router::get('/online-clients', 'NetworkController@onlineClients', ['AuthMiddleware']);
    Router::get('/online-clients/data', 'NetworkController@onlineClientsData', ['AuthMiddleware']);
    
    // MAC Binding & CallerID
    Router::get('/mac-bindings', 'NetworkController@macBindings', ['AuthMiddleware']);
    Router::post('/mac-bindings/store', 'NetworkController@storeMacBinding', ['AuthMiddleware']);
    Router::post('/mac-bindings/update/{id}', 'NetworkController@updateMacBinding', ['AuthMiddleware']);
    Router::post('/mac-bindings/delete/{id}', 'NetworkController@deleteMacBinding', ['AuthMiddleware']);
    Router::post('/mac-bindings/toggle/{id}', 'NetworkController@toggleMacBinding', ['AuthMiddleware']);
    
    // MAC Filters
    Router::get('/mac-filters', 'NetworkController@macFilters', ['AuthMiddleware']);
    Router::post('/mac-filters/store', 'NetworkController@storeMacFilter', ['AuthMiddleware']);
    Router::post('/mac-filters/delete/{id}', 'NetworkController@deleteMacFilter', ['AuthMiddleware']);
    Router::post('/mac-filters/toggle/{id}', 'NetworkController@toggleMacFilter', ['AuthMiddleware']);
});

// ── GPON ──────────────────────────────────────────────────────────
Router::prefix('/gpon', function() {
    Router::get('', 'GponController@index', ['AuthMiddleware']);
    Router::get('/olts', 'GponController@olts', ['AuthMiddleware']);
    Router::post('/olts/store', 'GponController@storeOlt', ['AuthMiddleware']);
    Router::post('/olts/update', 'GponController@updateOlt', ['AuthMiddleware']);
    Router::post('/olts/delete/{id}', 'GponController@deleteOlt', ['AuthMiddleware']);
    Router::get('/olts/onus', 'GponController@oltOnus', ['AuthMiddleware']);
    Router::get('/api/olts/check/{id}', 'GponController@checkOltConnection', ['AuthMiddleware']);
    Router::get('/api/olts/check-all', 'GponController@checkAllOltConnections', ['AuthMiddleware']);
    Router::get('/api/snmp/test/{id}', 'GponController@snmpTest', ['AuthMiddleware']);
    Router::post('/api/snmp/sync/{id}', 'GponController@syncOnus', ['AuthMiddleware']);
    Router::get('/api/olts/{id}/onus', 'GponController@getOltOnuList', ['AuthMiddleware']);
    Router::get('/api/olts/{id}/onus/live', 'GponController@getLiveOnuList', ['AuthMiddleware']);
    Router::post('/api/onus/update/{id}', 'GponController@updateOnuApi', ['AuthMiddleware']);
    Router::post('/api/onus/delete/{id}', 'GponController@deleteOnuApi', ['AuthMiddleware']);
    Router::get('/splitters', 'GponController@splitters', ['AuthMiddleware']);
    Router::post('/splitters/store', 'GponController@storeSplitter', ['AuthMiddleware']);
    Router::post('/splitters/update', 'GponController@updateSplitter', ['AuthMiddleware']);
    Router::post('/splitters/delete/{id}', 'GponController@deleteSplitter', ['AuthMiddleware']);
    Router::get('/onus', 'GponController@onus', ['AuthMiddleware']);
    Router::post('/onus/store', 'GponController@storeOnu', ['AuthMiddleware']);
    Router::post('/onus/update', 'GponController@updateOnu', ['AuthMiddleware']);
    Router::post('/onus/delete/{id}', 'GponController@deleteOnu', ['AuthMiddleware']);
    Router::get('/incidents', 'GponController@incidents', ['AuthMiddleware']);
    Router::post('/incidents/store', 'GponController@storeIncident', ['AuthMiddleware']);
    Router::post('/incidents/update', 'GponController@updateIncident', ['AuthMiddleware']);
    Router::post('/incidents/delete/{id}', 'GponController@deleteIncident', ['AuthMiddleware']);
});

// ── Inventory ─────────────────────────────────────────────────────
Router::prefix('/inventory', function() {
    Router::get('', 'InventoryController@index', ['AuthMiddleware']);
    Router::get('/stock', 'InventoryController@stock', ['AuthMiddleware']);
    Router::post('/stock/store', 'InventoryController@storeItem', ['AuthMiddleware']);
    Router::post('/stock/update', 'InventoryController@updateItem', ['AuthMiddleware']);
    Router::post('/stock/delete/{id}', 'InventoryController@deleteItem', ['AuthMiddleware']);
    Router::post('/stock/in', 'InventoryController@stockIn', ['AuthMiddleware']);
    Router::post('/stock/out', 'InventoryController@stockOut', ['AuthMiddleware']);
    Router::get('/purchases', 'InventoryController@purchases', ['AuthMiddleware']);
    Router::post('/purchases/store', 'InventoryController@storePurchase', ['AuthMiddleware']);
    Router::post('/purchases/receive/{id}', 'InventoryController@receivePO', ['AuthMiddleware']);
    Router::post('/purchases/delete/{id}', 'InventoryController@deletePO', ['AuthMiddleware']);
    Router::get('/suppliers', 'InventoryController@suppliers', ['AuthMiddleware']);
    Router::post('/suppliers/store', 'InventoryController@storeSupplier', ['AuthMiddleware']);
    Router::post('/suppliers/update/{id}', 'InventoryController@updateSupplier', ['AuthMiddleware']);
    Router::post('/suppliers/delete/{id}', 'InventoryController@deleteSupplier', ['AuthMiddleware']);
});

// ── Resellers ─────────────────────────────────────────────────────
Router::prefix('/resellers', function() {
    Router::get('', 'ResellerController@index', ['AuthMiddleware']);
    Router::get('/create', 'ResellerController@create', ['AuthMiddleware']);
    Router::post('/store', 'ResellerController@store', ['AuthMiddleware']);
    Router::get('/view/{id}', 'ResellerController@view', ['AuthMiddleware']);
    Router::get('/edit/{id}', 'ResellerController@edit', ['AuthMiddleware']);
    Router::post('/update/{id}', 'ResellerController@update', ['AuthMiddleware']);
    Router::post('/delete/{id}', 'ResellerController@delete', ['AuthMiddleware']);
    Router::post('/topup/{id}', 'ResellerController@topup', ['AuthMiddleware']);
});

// ── MAC Resellers ─────────────────────────────────────────────────
Router::prefix('/mac-resellers', function() {
    Router::get('', 'MacResellerController@index', ['AuthMiddleware']);
    Router::get('/create', 'MacResellerController@create', ['AuthMiddleware']);
    Router::post('/store', 'MacResellerController@store', ['AuthMiddleware']);
    Router::get('/view/{id}', 'MacResellerController@view', ['AuthMiddleware']);
    Router::get('/edit/{id}', 'MacResellerController@edit', ['AuthMiddleware']);
    Router::post('/update/{id}', 'MacResellerController@update', ['AuthMiddleware']);
    Router::post('/delete/{id}', 'MacResellerController@delete', ['AuthMiddleware']);
    Router::post('/topup/{id}', 'MacResellerController@topup', ['AuthMiddleware']);
    // Tariff plans
    Router::get('/{id}/tariffs', 'MacResellerController@tariffs', ['AuthMiddleware']);
    Router::post('/{id}/tariffs/store', 'MacResellerController@storeTariff', ['AuthMiddleware']);
    Router::post('/tariffs/update/{tid}', 'MacResellerController@updateTariff', ['AuthMiddleware']);
    Router::post('/tariffs/delete/{tid}', 'MacResellerController@deleteTariff', ['AuthMiddleware']);
    // Clients
    Router::get('/{id}/clients', 'MacResellerController@clients', ['AuthMiddleware']);
    Router::post('/{id}/clients/store', 'MacResellerController@storeClient', ['AuthMiddleware']);
    Router::post('/clients/update/{cid}', 'MacResellerController@updateClient', ['AuthMiddleware']);
    Router::post('/clients/delete/{cid}', 'MacResellerController@deleteClient', ['AuthMiddleware']);
    Router::post('/clients/suspend/{cid}', 'MacResellerController@suspendClient', ['AuthMiddleware']);
    // Billing
    Router::get('/{id}/billing', 'MacResellerController@billing', ['AuthMiddleware']);
    Router::post('/{id}/billing/generate', 'MacResellerController@generateBilling', ['AuthMiddleware']);
    Router::post('/billing/pay/{bid}', 'MacResellerController@payBilling', ['AuthMiddleware']);
});

// ── Work Orders ───────────────────────────────────────────────────
Router::prefix('/workorders', function() {
    Router::get('', 'WorkOrderController@index', ['AuthMiddleware']);
    Router::get('/create', 'WorkOrderController@create', ['AuthMiddleware']);
    Router::post('/store', 'WorkOrderController@store', ['AuthMiddleware']);
    Router::get('/view/{id}', 'WorkOrderController@view', ['AuthMiddleware']);
    Router::post('/status/{id}', 'WorkOrderController@updateStatus', ['AuthMiddleware']);
    Router::post('/delete/{id}', 'WorkOrderController@delete', ['AuthMiddleware']);
});

// ── Reports ───────────────────────────────────────────────────────
Router::prefix('/reports', function() {
    Router::get('', 'ReportController@index', ['AuthMiddleware']);
    Router::get('/income', 'ReportController@income', ['AuthMiddleware']);
    Router::get('/due', 'ReportController@due', ['AuthMiddleware']);
    Router::get('/collection', 'ReportController@collection', ['AuthMiddleware']);
    Router::get('/customers', 'ReportController@customers', ['AuthMiddleware']);
});

// ── Finance ───────────────────────────────────────────────────────
Router::prefix('/finance', function() {
    Router::get('', 'FinanceController@index', ['AuthMiddleware']);
    Router::get('/cashbook', 'FinanceController@cashbook', ['AuthMiddleware']);
    Router::get('/expenses', 'FinanceController@expenses', ['AuthMiddleware']);
    Router::post('/expenses/store', 'FinanceController@storeExpense', ['AuthMiddleware']);
    Router::post('/expenses/delete/{id}', 'FinanceController@deleteExpense', ['AuthMiddleware']);
    Router::post('/daily-close', 'FinanceController@dailyClose', ['AuthMiddleware']);
});

// ── Automation ────────────────────────────────────────────────────
Router::prefix('/automation', function() {
    Router::get('', 'AutomationController@index', ['AuthMiddleware']);
    Router::get('/logs', 'AutomationController@logs', ['AuthMiddleware']);
    Router::post('/run/{job}', 'AutomationController@run', ['AuthMiddleware']);
    Router::post('/settings', 'AutomationController@saveSettings', ['AuthMiddleware']);
});

// ── Communication Hub ─────────────────────────────────────────────
Router::prefix('/comms', function() {
    Router::get('', 'CommunicationController@index', ['AuthMiddleware']);
    Router::get('/bulk', 'CommunicationController@bulk', ['AuthMiddleware']);
    Router::post('/bulk/send', 'CommunicationController@sendBulk', ['AuthMiddleware']);
    Router::get('/preview-recipients', 'CommunicationController@previewRecipients', ['AuthMiddleware']);
    Router::get('/logs', 'CommunicationController@logs', ['AuthMiddleware']);
    Router::get('/templates', 'CommunicationController@templates', ['AuthMiddleware']);
    Router::post('/templates/store', 'CommunicationController@storeTemplate', ['AuthMiddleware']);
    Router::post('/templates/update', 'CommunicationController@updateTemplate', ['AuthMiddleware']);
    Router::post('/templates/delete/{id}', 'CommunicationController@deleteTemplate', ['AuthMiddleware']);
    Router::get('/campaigns', 'CommunicationController@campaigns', ['AuthMiddleware']);
    Router::post('/due-reminders', 'CommunicationController@sendDueReminders', ['AuthMiddleware']);
});

// ── PipraPay Payment Callbacks ────────────────────────────────────
Router::prefix('/payment/piprapay', function() {
    Router::get('/success', 'PipraPayController@success', ['AuthMiddleware']);
    Router::get('/cancel', 'PipraPayController@cancel', ['AuthMiddleware']);
    Router::post('/callback', 'PipraPayController@callback');
    Router::post('/initiate/{invoice_id}', 'PipraPayController@initiate', ['AuthMiddleware']);
});

// ── Self-Hosted PipraPay Payment System ───────────────────────────
Router::prefix('/payment/selfhosted', function() {
    Router::post('/initiate/{invoice_id}', 'SelfHostedPipraPayController@initiate', ['AuthMiddleware']);
    Router::get('/success', 'SelfHostedPipraPayController@success', ['AuthMiddleware']);
    Router::get('/cancel', 'SelfHostedPipraPayController@cancel', ['AuthMiddleware']);
    Router::get('/checkout/{session_id}', 'SelfHostedPipraPayController@checkout');
    Router::post('/process/{session_id}', 'SelfHostedPipraPayController@process');
    Router::post('/webhook', 'SelfHostedPipraPayController@webhook');
    Router::post('/queue/{invoice_id}', 'SelfHostedPipraPayController@queueAutomatedPayment', ['AuthMiddleware']);
    Router::get('/status/{customer_id}', 'SelfHostedPipraPayController@getBillingStatus', ['AuthMiddleware']);
    Router::post('/process-automated', 'SelfHostedPipraPayController@processAutomatedBilling', ['AuthMiddleware']);
});

// ── HR & Payroll ──────────────────────────────────────────────────
Router::prefix('/hr', function() {
    Router::get('', 'HrController@index', ['AuthMiddleware']);
    // Employees
    Router::get('/employees', 'HrController@employees', ['AuthMiddleware']);
    Router::get('/employees/create', 'HrController@createEmployee', ['AuthMiddleware']);
    Router::post('/employees/store', 'HrController@storeEmployee', ['AuthMiddleware']);
    Router::get('/employees/view/{id}', 'HrController@viewEmployee', ['AuthMiddleware']);
    Router::get('/employees/edit/{id}', 'HrController@editEmployee', ['AuthMiddleware']);
    Router::post('/employees/update/{id}', 'HrController@updateEmployee', ['AuthMiddleware']);
    Router::post('/employees/leave/{id}', 'HrController@updateLeaveBalance', ['AuthMiddleware']);
    Router::post('/employees/appraisal/{id}', 'HrController@storeAppraisal', ['AuthMiddleware']);
    // Attendance
    Router::get('/attendance', 'HrController@attendance', ['AuthMiddleware']);
    Router::post('/attendance/store', 'HrController@storeAttendance', ['AuthMiddleware']);
    // Payroll
    Router::get('/payroll', 'HrController@payroll', ['AuthMiddleware']);
    Router::post('/payroll/generate', 'HrController@generatePayroll', ['AuthMiddleware']);
    Router::get('/payroll/slip/{id}', 'HrController@viewSalarySlip', ['AuthMiddleware']);
    // Departments
    Router::get('/departments', 'HrController@departments', ['AuthMiddleware']);
    Router::post('/departments/store', 'HrController@storeDepartment', ['AuthMiddleware']);
    Router::post('/departments/update/{id}', 'HrController@updateDepartment', ['AuthMiddleware']);
    Router::post('/departments/delete/{id}', 'HrController@deleteDepartment', ['AuthMiddleware']);
});

// ── Support & Ticketing ───────────────────────────────────────────
Router::prefix('/support', function() {
    Router::get('', 'SupportController@index', ['AuthMiddleware']);
    // Tickets
    Router::get('/tickets', 'SupportController@tickets', ['AuthMiddleware']);
    Router::get('/tickets/create', 'SupportController@create', ['AuthMiddleware']);
    Router::post('/tickets/store', 'SupportController@store', ['AuthMiddleware']);
    Router::get('/tickets/view/{id}', 'SupportController@view', ['AuthMiddleware']);
    Router::get('/tickets/edit/{id}', 'SupportController@edit', ['AuthMiddleware']);
    Router::post('/tickets/update/{id}', 'SupportController@update', ['AuthMiddleware']);
    Router::post('/tickets/assign/{id}', 'SupportController@assign', ['AuthMiddleware']);
    Router::post('/tickets/comment/{id}', 'SupportController@comment', ['AuthMiddleware']);
    Router::post('/tickets/resolve/{id}', 'SupportController@resolve', ['AuthMiddleware']);
    Router::post('/tickets/close/{id}', 'SupportController@close', ['AuthMiddleware']);
    // Dashboard & SLA
    Router::get('/dashboard', 'SupportController@dashboard', ['AuthMiddleware']);
    Router::post('/check-sla', 'SupportController@checkSla', ['AuthMiddleware']);
});

// ── Roles & Permissions ───────────────────────────────────────────
Router::prefix('/roles', function() {
    Router::get('', 'RoleController@index', ['AuthMiddleware']);
    Router::get('/create', 'RoleController@create', ['AuthMiddleware']);
    Router::post('/store', 'RoleController@store', ['AuthMiddleware']);
    Router::get('/edit/{id}', 'RoleController@edit', ['AuthMiddleware']);
    Router::post('/update/{id}', 'RoleController@update', ['AuthMiddleware']);
    Router::post('/delete/{id}', 'RoleController@delete', ['AuthMiddleware']);
    Router::post('/permissions/{id}', 'RoleController@savePermissions', ['AuthMiddleware']);
    Router::get('/users/{id}', 'RoleController@users', ['AuthMiddleware']);
    Router::post('/assign-user', 'RoleController@assignUser', ['AuthMiddleware']);
    Router::post('/seed', 'RoleController@seed', ['AuthMiddleware']);
});

// ── Settings ──────────────────────────────────────────────────────
Router::prefix('/settings', function() {
    Router::get('', 'SettingsController@index', ['AuthMiddleware']);
    Router::post('/general', 'SettingsController@saveGeneral', ['AuthMiddleware']);
    Router::post('/app', 'SettingsController@saveApp', ['AuthMiddleware']);
    Router::post('/ai', 'SettingsController@saveAi', ['AuthMiddleware']);
    Router::post('/reseller', 'SettingsController@saveReseller', ['AuthMiddleware']);
    Router::post('/packages/store', 'SettingsController@storePackage', ['AuthMiddleware']);
    Router::post('/packages/update', 'SettingsController@updatePackage', ['AuthMiddleware']);
    Router::post('/packages/delete', 'SettingsController@deletePackage', ['AuthMiddleware']);
    Router::post('/branches/store', 'SettingsController@storeBranch', ['AuthMiddleware']);
    Router::post('/branches/update', 'SettingsController@updateBranch', ['AuthMiddleware']);
    Router::post('/branches/delete', 'SettingsController@deleteBranch', ['AuthMiddleware']);
    Router::post('/zones/store', 'SettingsController@storeZone', ['AuthMiddleware']);
    Router::post('/zones/update', 'SettingsController@updateZone', ['AuthMiddleware']);
    Router::post('/zones/delete', 'SettingsController@deleteZone', ['AuthMiddleware']);
    Router::post('/users/store', 'SettingsController@storeUser', ['AuthMiddleware']);
    Router::post('/users/update', 'SettingsController@updateUser', ['AuthMiddleware']);
    Router::post('/users/delete', 'SettingsController@deleteUser', ['AuthMiddleware']);
    Router::post('/payments/save', 'SettingsController@savePaymentSettings', ['AuthMiddleware']);
    Router::get('/api/mikrotik-profiles', 'SettingsController@apiGetMikrotikProfiles', ['AuthMiddleware']);
    Router::post('/config/store', 'SettingsController@storeConfigItem', ['AuthMiddleware']);
    Router::post('/config/update', 'SettingsController@updateConfigItem', ['AuthMiddleware']);
    Router::post('/config/delete/{id}', 'SettingsController@deleteConfigItem', ['AuthMiddleware']);
    // ── PPPoE Profiles ──
    Router::get('/profiles', 'SettingsController@profiles', ['AuthMiddleware']);
    Router::post('/profiles/store', 'SettingsController@storeProfile', ['AuthMiddleware']);
    Router::post('/profiles/update', 'SettingsController@updateProfile', ['AuthMiddleware']);
    Router::post('/profiles/delete/{id}', 'SettingsController@deleteProfile', ['AuthMiddleware']);
    Router::get('/{type}', 'SettingsController@configPage', ['AuthMiddleware']);
});

// Dispatch
Router::dispatch($_SERVER['REQUEST_METHOD'], $path);
