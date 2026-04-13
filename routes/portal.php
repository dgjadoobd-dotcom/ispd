<?php

// Customer Portal Routes
// Accessible at /portal/*

// Load portal controllers
require_once BASE_PATH . '/app/Controllers/CustomerPortal/PortalController.php';
require_once BASE_PATH . '/app/Controllers/CustomerPortal/AuthController.php';
require_once BASE_PATH . '/app/Controllers/CustomerPortal/DashboardController.php';
require_once BASE_PATH . '/app/Controllers/CustomerPortal/BillingController.php';
require_once BASE_PATH . '/app/Controllers/CustomerPortal/SupportController.php';
require_once BASE_PATH . '/app/Controllers/CustomerPortal/ProfileController.php';
require_once BASE_PATH . '/app/Controllers/CustomerPortal/UsageController.php';
require_once BASE_PATH . '/app/Controllers/CustomerPortal/AiChatController.php';

Router::prefix('/portal', function() {

    // ── Auth Routes ──────────────────────────────────────────────
    Router::get('/login', 'PortalAuthController@showLogin');
    Router::post('/login', 'PortalAuthController@login');
    Router::get('/logout', 'PortalAuthController@logout');
    
    Router::get('/forgot-password', 'PortalAuthController@showForgotPassword');
    Router::post('/forgot-password', 'PortalAuthController@sendOtp');
    Router::get('/secret-question', 'PortalAuthController@showSecretQuestion');
    Router::post('/secret-question', 'PortalAuthController@verifySecretQuestion');
    Router::get('/reset-password', 'PortalAuthController@showResetPassword');
    Router::post('/reset-password', 'PortalAuthController@resetPassword');

    // ── Dashboard ─────────────────────────────────────────────────
    Router::get('/dashboard', 'PortalDashboardController@index');
    Router::get('/api/dashboard/stats', 'PortalDashboardController@getLiveStats');
    Router::get('/api/live', 'PortalUsageController@getLiveData');

    // ── Billing ──────────────────────────────────────────────────
    Router::prefix('/billing', function() {
        Router::get('/invoices', 'PortalBillingController@invoices');
        Router::get('/invoice/{id}', 'PortalBillingController@viewInvoice');
        Router::get('/payments', 'PortalBillingController@payments');
        Router::get('/pay-form/{invoiceId}', 'PortalBillingController@payForm');
        Router::post('/initiate-payment', 'PortalBillingController@initiatePayment');
        Router::post('/confirm-payment', 'PortalBillingController@confirmPayment');
        Router::get('/payment-success', 'PortalBillingController@paymentSuccess');
        Router::get('/receipt/{receiptNumber}', 'PortalBillingController@printReceipt');
        Router::get('/api/unpaid', 'PortalBillingController@getUnpaidInvoices');
    });

    // ── Usage ─────────────────────────────────────────────────────
    Router::prefix('/usage', function() {
        Router::get('', 'PortalUsageController@index');
        Router::get('/connection', 'PortalUsageController@connectionStatus');
        Router::get('/api/live', 'PortalUsageController@getLiveData');
        Router::get('/api/daily', 'PortalUsageController@getDailyUsage');
    });

    // ── Support ───────────────────────────────────────────────────
    Router::prefix('/support', function() {
        Router::get('', 'PortalSupportController@index');
        Router::get('/create', 'PortalSupportController@create');
        Router::post('/create', 'PortalSupportController@store');
        Router::get('/view/{id}', 'PortalSupportController@view');
        Router::post('/reply', 'PortalSupportController@reply');
        Router::post('/close', 'PortalSupportController@close');
        Router::get('/api/categories', 'PortalSupportController@getCategories');
    });

    // ── Profile ───────────────────────────────────────────────────
    Router::prefix('/profile', function() {
        Router::get('', 'PortalProfileController@index');
        Router::post('/update', 'PortalProfileController@updateProfile');
        Router::get('/change-password', 'PortalProfileController@showChangePassword');
        Router::post('/change-password', 'PortalProfileController@changePassword');
        Router::get('/secret-question', 'PortalProfileController@showSecretQuestion');
        Router::post('/secret-question', 'PortalProfileController@setSecretQuestion');
        Router::get('/mac-devices', 'PortalProfileController@macDevices');
        Router::post('/mac-devices/add', 'PortalProfileController@addMacDevice');
        Router::post('/mac-devices/remove', 'PortalProfileController@removeMacDevice');
        Router::get('/notifications', 'PortalProfileController@notifications');
        Router::post('/notifications/read', 'PortalProfileController@markNotificationRead');
        Router::get('/login-history', 'PortalProfileController@getLoginHistory');
    });

    // ── AI Chat ───────────────────────────────────────────────────
    Router::post('/ai/chat', 'AiChatController@chat');

    // ── API for Mobile App ─────────────────────────────────────────
    Router::prefix('/api', function() {
        Router::post('/auth/login', function() {
            $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $identifier = sanitize($body['identifier'] ?? '');
            $password = $body['password'] ?? '';

            if (empty($identifier) || empty($password)) {
                jsonResponse(['error' => 'Credentials required'], 400);
            }

            $db = Database::getInstance();
            $customer = null;

            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $customer = $db->fetchOne("SELECT * FROM customers WHERE email = ? AND status = 'active' AND portal_active = 1", [$identifier]);
            } elseif (preg_match('/^[0-9]{10,15}$/', $identifier)) {
                $customer = $db->fetchOne("SELECT * FROM customers WHERE phone = ? AND status = 'active' AND portal_active = 1", [$identifier]);
            } else {
                $customer = $db->fetchOne("SELECT * FROM customers WHERE pppoe_username = ? AND status = 'active' AND portal_active = 1", [$identifier]);
            }

            if (!$customer) {
                jsonResponse(['error' => 'Invalid credentials'], 401);
            }

            $passwordValid = false;
            if (!empty($customer['portal_password'])) {
                $passwordValid = password_verify($password, $customer['portal_password']);
            } elseif (!empty($customer['pppoe_password'])) {
                $passwordValid = ($password === $customer['pppoe_password']);
            }

            if (!$passwordValid) {
                jsonResponse(['error' => 'Invalid credentials'], 401);
            }

            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 86400);

            $db->insert('customer_portal_sessions', [
                'customer_id' => $customer['id'],
                'session_token' => $token,
                'device_info' => $_SERVER['HTTP_USER_AGENT'] ?? 'Mobile App',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'expires_at' => $expiresAt,
            ]);

            $db->update('customers', ['portal_last_login' => date('Y-m-d H:i:s')], 'id=?', [$customer['id']]);

            jsonResponse([
                'success' => true,
                'token' => $token,
                'expires' => $expiresAt,
                'customer' => [
                    'id' => $customer['id'],
                    'code' => $customer['customer_code'],
                    'name' => $customer['full_name'],
                    'phone' => $customer['phone'],
                    'email' => $customer['email'],
                    'status' => $customer['status'],
                    'package' => $customer['package_name'] ?? null,
                    'due_amount' => (float)$customer['due_amount'],
                ]
            ]);
        });

        Router::post('/auth/logout', function() {
            $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
            $db = Database::getInstance();
            $db->update('customer_portal_sessions', 
                ['is_active' => 0, 'logout_at' => date('Y-m-d H:i:s')],
                'session_token = ?', [$token]
            );
            jsonResponse(['success' => true]);
        });

        Router::get('/invoices', function() {
            $customerId = \PortalController::getCustomerIdFromToken();
            if (!$customerId) jsonResponse(['error' => 'Unauthorized'], 401);
            
            $db = Database::getInstance();
            $invoices = $db->fetchAll(
                "SELECT id, invoice_number, billing_month, total, paid_amount, due_amount, status, due_date 
                 FROM invoices WHERE customer_id = ? ORDER BY billing_month DESC",
                [$customerId]
            );
            jsonResponse(['invoices' => $invoices]);
        });

        Router::get('/usage', function() {
            $customerId = \PortalController::getCustomerIdFromToken();
            if (!$customerId) jsonResponse(['error' => 'Unauthorized'], 401);
            
            $db = Database::getInstance();
            $customer = $db->fetchOne("SELECT * FROM customers WHERE id = ?", [$customerId]);
            jsonResponse(['usage' => ['customer' => $customer]]);
        });

        Router::post('/tickets', function() {
            $customerId = \PortalController::getCustomerIdFromToken();
            if (!$customerId) jsonResponse(['error' => 'Unauthorized'], 401);
            
            $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $db = Database::getInstance();
            
            $ticketNumber = 'TKT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $id = $db->insert('support_tickets', [
                'ticket_number' => $ticketNumber,
                'customer_id' => $customerId,
                'branch_id' => 1,
                'category' => sanitize($body['category'] ?? 'general'),
                'priority' => sanitize($body['priority'] ?? 'normal'),
                'subject' => sanitize($body['subject'] ?? ''),
                'description' => sanitize($body['description'] ?? ''),
                'status' => 'open',
            ]);
            
            jsonResponse(['success' => true, 'ticket_number' => $ticketNumber, 'id' => $id]);
        });
    });

});
