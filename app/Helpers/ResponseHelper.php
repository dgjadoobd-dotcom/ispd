<?php

/**
 * ResponseHelper — static utility class for consistent HTTP responses.
 *
 * Provides helpers for:
 *  - JSON API responses (success, error, raw)
 *  - HTTP redirects
 *  - View rendering
 *  - Structured error responses with module-specific error codes
 *
 * Usage:
 *   ResponseHelper::success('Customer created.', ['id' => 42]);
 *   ResponseHelper::error('Validation failed.', 422, $errors);
 *   ResponseHelper::errorResponse(409, 'Branch code already exists.', 'BRANCH_DUPLICATE_CODE');
 *   ResponseHelper::redirect(base_url('customers'));
 *   ResponseHelper::view('customers/index', compact('customers'));
 */
class ResponseHelper
{
    // ── Standard error codes for all modules ─────────────────────

    // Branch Management
    public const ERR_BRANCH_DUPLICATE_CODE    = 'BRANCH_DUPLICATE_CODE';
    public const ERR_BRANCH_NOT_FOUND         = 'BRANCH_NOT_FOUND';
    public const ERR_BRANCH_HAS_USERS         = 'BRANCH_HAS_USERS';
    public const ERR_BRANCH_INACTIVE          = 'BRANCH_INACTIVE';

    // HR & Payroll
    public const ERR_SALARY_DUPLICATE         = 'SALARY_DUPLICATE';
    public const ERR_EMPLOYEE_NOT_FOUND       = 'EMPLOYEE_NOT_FOUND';
    public const ERR_ATTENDANCE_DUPLICATE     = 'ATTENDANCE_DUPLICATE';
    public const ERR_LEAVE_INSUFFICIENT       = 'LEAVE_INSUFFICIENT';
    public const ERR_PAYROLL_ALREADY_PAID     = 'PAYROLL_ALREADY_PAID';

    // Support & Ticketing
    public const ERR_TICKET_DUPLICATE         = 'TICKET_DUPLICATE';
    public const ERR_TICKET_NOT_FOUND         = 'TICKET_NOT_FOUND';
    public const ERR_TICKET_ALREADY_CLOSED    = 'TICKET_ALREADY_CLOSED';
    public const ERR_SLA_VIOLATION            = 'SLA_VIOLATION';

    // Task Management
    public const ERR_TASK_NOT_FOUND           = 'TASK_NOT_FOUND';
    public const ERR_TASK_INVALID_TRANSITION  = 'TASK_INVALID_TRANSITION';
    public const ERR_TASK_ALREADY_COMPLETED   = 'TASK_ALREADY_COMPLETED';

    // Sales & Invoicing
    public const ERR_INVOICE_DUPLICATE        = 'INVOICE_DUPLICATE';
    public const ERR_INVOICE_NOT_FOUND        = 'INVOICE_NOT_FOUND';
    public const ERR_INVOICE_ALREADY_PAID     = 'INVOICE_ALREADY_PAID';
    public const ERR_INVOICE_CANCELLED        = 'INVOICE_CANCELLED';
    public const ERR_PAYMENT_EXCEEDS_BALANCE  = 'PAYMENT_EXCEEDS_BALANCE';

    // Purchase Management
    public const ERR_VENDOR_NOT_FOUND         = 'VENDOR_NOT_FOUND';
    public const ERR_PURCHASE_NOT_FOUND       = 'PURCHASE_NOT_FOUND';
    public const ERR_PURCHASE_ALREADY_APPROVED = 'PURCHASE_ALREADY_APPROVED';

    // Inventory Management
    public const ERR_STOCK_INSUFFICIENT       = 'STOCK_INSUFFICIENT';
    public const ERR_ITEM_NOT_FOUND           = 'ITEM_NOT_FOUND';
    public const ERR_WAREHOUSE_NOT_FOUND      = 'WAREHOUSE_NOT_FOUND';
    public const ERR_TRANSFER_SAME_WAREHOUSE  = 'TRANSFER_SAME_WAREHOUSE';

    // Network Diagram
    public const ERR_NODE_NOT_FOUND           = 'NODE_NOT_FOUND';
    public const ERR_CONNECTION_DUPLICATE     = 'CONNECTION_DUPLICATE';
    public const ERR_INVALID_COORDINATES      = 'INVALID_COORDINATES';

    // Accounts Management
    public const ERR_ACCOUNT_NOT_FOUND        = 'ACCOUNT_NOT_FOUND';
    public const ERR_EXPENSE_NOT_FOUND        = 'EXPENSE_NOT_FOUND';
    public const ERR_BANK_BALANCE_INSUFFICIENT = 'BANK_BALANCE_INSUFFICIENT';

    // Asset Management
    public const ERR_ASSET_NOT_FOUND          = 'ASSET_NOT_FOUND';
    public const ERR_ASSET_SERIAL_DUPLICATE   = 'ASSET_SERIAL_DUPLICATE';
    public const ERR_ASSET_ALREADY_DISPOSED   = 'ASSET_ALREADY_DISPOSED';

    // Bandwidth
    public const ERR_BANDWIDTH_CREDIT_EXCEEDED = 'BANDWIDTH_CREDIT_EXCEEDED';
    public const ERR_RESELLER_NOT_FOUND        = 'RESELLER_NOT_FOUND';
    public const ERR_RESELLER_SUSPENDED        = 'RESELLER_SUSPENDED';

    // MAC Reseller
    public const ERR_MAC_RESELLER_NOT_FOUND    = 'MAC_RESELLER_NOT_FOUND';
    public const ERR_MAC_RESELLER_SUSPENDED    = 'MAC_RESELLER_SUSPENDED';
    public const ERR_MAC_ADDRESS_DUPLICATE     = 'MAC_ADDRESS_DUPLICATE';
    public const ERR_MAC_ADDRESS_INVALID       = 'MAC_ADDRESS_INVALID';

    // Employee Portal
    public const ERR_EMPLOYEE_PORTAL_NOT_FOUND = 'EMPLOYEE_PORTAL_NOT_FOUND';
    public const ERR_EMPLOYEE_ACCESS_DENIED    = 'EMPLOYEE_ACCESS_DENIED';

    // BTRC Reports
    public const ERR_BTRC_REPORT_NOT_FOUND     = 'BTRC_REPORT_NOT_FOUND';
    public const ERR_BTRC_PERIOD_DUPLICATE     = 'BTRC_PERIOD_DUPLICATE';

    // OTT Subscriptions
    public const ERR_OTT_PROVIDER_NOT_FOUND    = 'OTT_PROVIDER_NOT_FOUND';
    public const ERR_OTT_SUBSCRIPTION_ACTIVE   = 'OTT_SUBSCRIPTION_ACTIVE';
    public const ERR_OTT_RENEWAL_FAILED        = 'OTT_RENEWAL_FAILED';

    // Configuration
    public const ERR_CONFIG_PACKAGE_DUPLICATE  = 'CONFIG_PACKAGE_DUPLICATE';
    public const ERR_CONFIG_ZONE_NOT_FOUND     = 'CONFIG_ZONE_NOT_FOUND';
    public const ERR_CONFIG_TEMPLATE_INVALID   = 'CONFIG_TEMPLATE_INVALID';

    // Campaigns
    public const ERR_CAMPAIGN_NOT_FOUND        = 'CAMPAIGN_NOT_FOUND';
    public const ERR_CAMPAIGN_ALREADY_SENT     = 'CAMPAIGN_ALREADY_SENT';
    public const ERR_CAMPAIGN_NO_RECIPIENTS    = 'CAMPAIGN_NO_RECIPIENTS';

    // API / Android
    public const ERR_API_TOKEN_INVALID         = 'API_TOKEN_INVALID';
    public const ERR_API_TOKEN_EXPIRED         = 'API_TOKEN_EXPIRED';
    public const ERR_API_RATE_LIMIT_EXCEEDED   = 'API_RATE_LIMIT_EXCEEDED';

    // Generic
    public const ERR_PERMISSION_DENIED         = 'PERMISSION_DENIED';
    public const ERR_VALIDATION_FAILED         = 'VALIDATION_FAILED';
    public const ERR_NOT_FOUND                 = 'NOT_FOUND';
    public const ERR_INTERNAL_ERROR            = 'INTERNAL_ERROR';
    public const ERR_DUPLICATE_ENTRY           = 'DUPLICATE_ENTRY';
    // ── JSON responses ────────────────────────────────────────────

    /**
     * Send a raw JSON response with the given HTTP status code.
     *
     * Terminates script execution after sending.
     *
     * @param  mixed $data        Data to JSON-encode
     * @param  int   $statusCode  HTTP status code (default: 200)
     * @return never
     */
    public static function json(mixed $data, int $statusCode = 200): never
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send a standardised error JSON response.
     *
     * Response body:
     * {
     *   "success": false,
     *   "error": {
     *     "message": "...",
     *     "details": ...,
     *     "timestamp": "..."
     *   }
     * }
     *
     * @param  string     $message     Human-readable error description
     * @param  int        $code        HTTP status code (default: 400)
     * @param  mixed|null $details     Additional error details (validation errors, etc.)
     * @return never
     */
    public static function error(string $message, int $code = 400, mixed $details = null): never
    {
        $body = [
            'success' => false,
            'error'   => [
                'message'   => $message,
                'timestamp' => date('c'),
            ],
        ];

        if ($details !== null) {
            $body['error']['details'] = $details;
        }

        self::json($body, $code);
    }

    /**
     * Send a structured error response with a module-specific error code.
     *
     * This is the preferred method for module-level errors as it includes
     * a machine-readable error code alongside the human-readable message.
     *
     * For API requests (URI contains /api/), always returns JSON.
     * For web requests, returns JSON if the request accepts JSON, otherwise
     * renders the appropriate HTML error view.
     *
     * Response body (JSON):
     * {
     *   "success": false,
     *   "error": {
     *     "code": "STOCK_INSUFFICIENT",
     *     "message": "Insufficient stock for this operation.",
     *     "timestamp": "..."
     *   }
     * }
     *
     * @param  int        $httpCode   HTTP status code (e.g. 400, 403, 404, 409, 422, 500)
     * @param  string     $message    Human-readable error description
     * @param  string     $errorCode  Machine-readable error code constant (e.g. self::ERR_STOCK_INSUFFICIENT)
     * @param  mixed|null $details    Optional additional details
     * @return never
     */
    public static function errorResponse(
        int $httpCode,
        string $message,
        string $errorCode = '',
        mixed $details = null
    ): never {
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // Always return JSON for API requests or JSON-accepting clients
        $wantsJson = str_contains($uri, '/api/')
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        if ($wantsJson) {
            $body = [
                'success' => false,
                'error'   => [
                    'code'      => $errorCode ?: self::ERR_INTERNAL_ERROR,
                    'message'   => $message,
                    'timestamp' => date('c'),
                ],
            ];

            if ($details !== null) {
                $body['error']['details'] = $details;
            }

            self::json($body, $httpCode);
        }

        // Web request — set flash and render HTML error view
        http_response_code($httpCode);

        if (isset($_SESSION)) {
            $_SESSION['error'] = $message;
        }

        $viewMap = [
            403 => '403',
            404 => '404',
            500 => '500',
        ];

        $viewName = $viewMap[$httpCode] ?? null;

        if ($viewName !== null) {
            $viewFile = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2))
                . '/views/errors/'
                . $viewName
                . '.php';

            if (file_exists($viewFile)) {
                $errorMessage = $message;
                $errorCode    = $errorCode;
                require $viewFile;
                exit;
            }
        }

        // Fallback plain-text error
        echo "Error {$httpCode}: " . htmlspecialchars($message, ENT_QUOTES);
        exit;
    }

    /**
     * Render an HTML error page for web requests.
     *
     * Renders the appropriate view from views/errors/{code}.php.
     * Falls back to a plain-text response if the view does not exist.
     *
     * @param  int    $httpCode     HTTP status code (403, 404, 500, etc.)
     * @param  string $message      Human-readable error message
     * @param  string $errorCode    Optional machine-readable error code
     * @return never
     */
    public static function htmlError(
        int $httpCode,
        string $message = '',
        string $errorCode = ''
    ): never {
        http_response_code($httpCode);

        $viewFile = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2))
            . '/views/errors/'
            . $httpCode
            . '.php';

        if (file_exists($viewFile)) {
            $errorMessage = $message;
            require $viewFile;
            exit;
        }

        // Fallback
        echo "<!DOCTYPE html><html><head><title>Error {$httpCode}</title></head><body>";
        echo "<h1>Error {$httpCode}</h1>";
        if ($message !== '') {
            echo '<p>' . htmlspecialchars($message, ENT_QUOTES) . '</p>';
        }
        echo '</body></html>';
        exit;
    }

    /**
     * Send a standardised success JSON response.
     *
     * Response body:
     * {
     *   "success": true,
     *   "message": "...",
     *   "data": ...
     * }
     *
     * @param  string     $message  Human-readable success description
     * @param  mixed|null $data     Optional payload to include
     * @return never
     */
    public static function success(string $message, mixed $data = null): never
    {
        $body = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $body['data'] = $data;
        }

        self::json($body, 200);
    }

    // ── Redirects ─────────────────────────────────────────────────

    /**
     * Redirect the browser to the given URL and terminate.
     *
     * Uses a 302 Found redirect by default.
     *
     * @param  string $url         Target URL (absolute or relative)
     * @param  int    $statusCode  HTTP redirect code (301, 302, 303, 307, 308)
     * @return never
     */
    public static function redirect(string $url, int $statusCode = 302): never
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header("Location: {$url}");
        } else {
            // Fallback for when headers are already sent (e.g. during output buffering)
            echo '<script>window.location.href=' . json_encode($url) . ';</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES) . '"></noscript>';
        }

        exit;
    }

    // ── View rendering ────────────────────────────────────────────

    /**
     * Render a PHP view template with the given data.
     *
     * The template is located at:
     *   BASE_PATH . '/views/' . $template . '.php'
     *
     * All keys in $data are extracted as local variables inside the template.
     *
     * @param  string $template  Template path relative to /views/ (without .php)
     * @param  array  $data      Variables to make available in the template
     * @return void
     * @throws \RuntimeException if the template file does not exist
     */
    public static function view(string $template, array $data = []): void
    {
        $viewFile = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2))
            . '/views/'
            . ltrim($template, '/')
            . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View template not found: {$viewFile}");
        }

        // Extract data into local scope for the template
        extract($data, EXTR_SKIP);

        require $viewFile;
    }

    // ── Convenience wrappers ──────────────────────────────────────

    /**
     * Send a 404 Not Found JSON error response.
     *
     * @param  string $message  Optional custom message
     * @return never
     */
    public static function notFound(string $message = 'Resource not found.'): never
    {
        self::error($message, 404);
    }

    /**
     * Send a 403 Forbidden JSON error response.
     *
     * @param  string $message  Optional custom message
     * @return never
     */
    public static function forbidden(string $message = 'Access denied.'): never
    {
        self::error($message, 403);
    }

    /**
     * Send a 401 Unauthorized JSON error response.
     *
     * @param  string $message  Optional custom message
     * @return never
     */
    public static function unauthorized(string $message = 'Authentication required.'): never
    {
        self::error($message, 401);
    }

    /**
     * Send a 422 Unprocessable Entity JSON error response for validation failures.
     *
     * @param  array  $errors   Associative array of field => error message
     * @param  string $message  Optional top-level message
     * @return never
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed.'
    ): never {
        self::error($message, 422, $errors);
    }

    /**
     * Send a 500 Internal Server Error JSON response.
     *
     * In production, the raw exception message is hidden.
     *
     * @param  string          $message    Public-facing error message
     * @param  \Throwable|null $exception  Optional exception for debug environments
     * @return never
     */
    public static function serverError(
        string $message = 'An unexpected error occurred.',
        ?\Throwable $exception = null
    ): never {
        $details = null;

        if ($exception !== null && defined('APP_DEBUG') && APP_DEBUG === true) {
            $details = [
                'exception' => get_class($exception),
                'message'   => $exception->getMessage(),
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
            ];
        }

        self::error($message, 500, $details);
    }
}
