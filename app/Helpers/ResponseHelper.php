<?php

/**
 * ResponseHelper — static utility class for consistent HTTP responses.
 *
 * Provides helpers for:
 *  - JSON API responses (success, error, raw)
 *  - HTTP redirects
 *  - View rendering
 *
 * Usage:
 *   ResponseHelper::success('Customer created.', ['id' => 42]);
 *   ResponseHelper::error('Validation failed.', 422, $errors);
 *   ResponseHelper::redirect(base_url('customers'));
 *   ResponseHelper::view('customers/index', compact('customers'));
 */
class ResponseHelper
{
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
