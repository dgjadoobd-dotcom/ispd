<?php

/**
 * Structured JSON logging service.
 * Writes log entries to storage/logs/app.log.
 */
class LoggingService
{
    private string $logFile;
    private string $requestId;

    public function __construct(?string $logFile = null)
    {
        $this->logFile = $logFile ?? dirname(__DIR__, 2) . '/storage/logs/app.log';
        $this->requestId = $this->resolveRequestId();

        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    /**
     * Audit log for security-sensitive actions (user, action, resource).
     */
    public function audit(string $message, array $context = []): void
    {
        $context['_audit'] = true;
        $this->write('AUDIT', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $entry = json_encode([
            'timestamp'  => date('c'),
            'level'      => $level,
            'message'    => $message,
            'context'    => $context,
            'request_id' => $this->requestId,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        file_put_contents($this->logFile, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function resolveRequestId(): string
    {
        // Reuse an existing request ID header if present (e.g. from a proxy)
        if (!empty($_SERVER['HTTP_X_REQUEST_ID'])) {
            return $_SERVER['HTTP_X_REQUEST_ID'];
        }
        return bin2hex(random_bytes(8));
    }
}
