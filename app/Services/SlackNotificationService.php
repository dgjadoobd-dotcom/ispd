<?php

/**
 * Sends RADIUS alert notifications to Slack via incoming webhooks.
 */
class SlackNotificationService
{
    private const SEVERITY_EMOJI = [
        'critical' => '🔴',
        'warning'  => '🟡',
        'info'     => '🔵',
    ];

    private const SESSION_EVENTS = [
        'session_start'   => 'Session Started',
        'session_stop'    => 'Session Stopped',
        'session_timeout' => 'Session Timed Out',
    ];

    /**
     * Returns true if SLACK_WEBHOOK_URL is configured.
     */
    public function isConfigured(): bool
    {
        $url = $_ENV['SLACK_WEBHOOK_URL'] ?? getenv('SLACK_WEBHOOK_URL') ?: '';
        return !empty(trim($url));
    }

    /**
     * Send a formatted alert message to Slack.
     *
     * @param string $message  Alert message text
     * @param string $severity One of: critical, warning, info
     * @param array  $context  Optional key-value pairs shown as attachment fields
     * @return bool true on success, false if not configured or request fails
     */
    public function sendAlert(string $message, string $severity = 'info', array $context = []): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $severity = strtolower($severity);
        $emoji    = self::SEVERITY_EMOJI[$severity] ?? self::SEVERITY_EMOJI['info'];
        $label    = strtoupper($severity);
        $text     = "{$emoji} *[{$label}]* {$message}";

        $payload = ['text' => $text];

        if (!empty($context)) {
            $fields = [];
            foreach ($context as $key => $value) {
                $fields[] = [
                    'title' => (string) $key,
                    'value' => (string) $value,
                    'short' => true,
                ];
            }
            $payload['attachments'] = [
                [
                    'color'  => $this->severityColor($severity),
                    'fields' => $fields,
                ],
            ];
        }

        return $this->post($payload);
    }

    /**
     * Send a session event notification to Slack.
     *
     * @param string $username    RADIUS username
     * @param string $event       One of: session_start, session_stop, session_timeout
     * @param array  $sessionData Optional session details
     * @return bool true on success, false if not configured or request fails
     */
    public function sendSessionAlert(string $username, string $event, array $sessionData = []): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $label    = self::SESSION_EVENTS[$event] ?? ucwords(str_replace('_', ' ', $event));
        $severity = ($event === 'session_timeout') ? 'warning' : 'info';
        $message  = "User *{$username}*: {$label}";

        return $this->sendAlert($message, $severity, $sessionData);
    }

    /**
     * POST a JSON payload to the configured Slack webhook URL.
     */
    private function post(array $payload): bool
    {
        $url  = $_ENV['SLACK_WEBHOOK_URL'] ?? getenv('SLACK_WEBHOOK_URL') ?: '';
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * Map severity to a Slack attachment color.
     */
    private function severityColor(string $severity): string
    {
        return match ($severity) {
            'critical' => 'danger',
            'warning'  => 'warning',
            default    => 'good',
        };
    }
}
