<?php

/**
 * Sends webhook notifications to external URLs.
 */
class WebhookService
{
    private ?LoggingService $logger;

    public function __construct()
    {
        try {
            $this->logger = new LoggingService();
        } catch (\Throwable $e) {
            $this->logger = null;
        }
    }

    /**
     * Send a POST request to $url with a JSON payload.
     * If $secret is provided, adds an HMAC-SHA256 signature header.
     *
     * @return bool true on HTTP 2xx, false otherwise
     */
    public function send(string $url, string $event, array $payload, ?string $secret = null): bool
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $headers = [
            'Content-Type: application/json',
            'User-Agent: DigitalISP-Webhook/1.0',
        ];

        if ($secret !== null) {
            $signature = 'sha256=' . hash_hmac('sha256', $body, $secret);
            $headers[] = 'X-Webhook-Signature: ' . $signature;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $success = $httpCode >= 200 && $httpCode < 300;

        if (!$success) {
            try {
                if ($this->logger) {
                    $this->logger->warning('Webhook delivery failed', [
                        'url'        => $url,
                        'event'      => $event,
                        'http_code'  => $httpCode,
                        'curl_error' => $curlError ?: null,
                    ]);
                }
            } catch (\Throwable $e) {
                // Logging is optional — swallow silently
            }
        }

        return $success;
    }

    /**
     * Send a session event (session_start, session_stop, session_timeout)
     * to all configured webhook URLs.
     */
    public function sendSessionEvent(string $event, array $sessionData): void
    {
        $payload = [
            'event'     => $event,
            'timestamp' => date('c'),
            'data'      => $sessionData,
        ];

        foreach ($this->getConfiguredUrls() as $url) {
            $this->send($url, $event, $payload);
        }
    }

    /**
     * Send an alert event to all configured webhook URLs.
     */
    public function sendAlertEvent(array $alertData): void
    {
        $payload = [
            'event'     => 'alert',
            'timestamp' => date('c'),
            'data'      => $alertData,
        ];

        foreach ($this->getConfiguredUrls() as $url) {
            $this->send($url, 'alert', $payload);
        }
    }

    /**
     * Returns webhook URLs from the WEBHOOK_URLS environment variable
     * (comma-separated), or an empty array if not configured.
     *
     * @return string[]
     */
    public function getConfiguredUrls(): array
    {
        $raw = $_ENV['WEBHOOK_URLS'] ?? getenv('WEBHOOK_URLS') ?: '';
        if (empty(trim($raw))) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
