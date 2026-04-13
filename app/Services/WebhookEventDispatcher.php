<?php

/**
 * Central event dispatcher that routes RADIUS events to WebhookService
 * and SlackNotificationService.
 *
 * Built-in events: session.start, session.stop, session.timeout,
 *                  alert.critical, alert.warning,
 *                  user.created, user.deleted
 */
class WebhookEventDispatcher
{
    public function __construct(
        private WebhookService $webhookService,
        private SlackNotificationService $slackService
    ) {}

    /**
     * Dispatch an event to all registered handlers.
     *
     * Routing rules:
     *  - session.*        → WebhookService::sendSessionEvent()
     *  - session.timeout  → SlackNotificationService::sendSessionAlert()
     *  - alert.*          → WebhookService::sendAlertEvent()
     *  - alert.critical   → SlackNotificationService::sendAlert()
     *  - user.*           → WebhookService::send() (all configured URLs)
     */
    public function dispatch(string $event, array $data): void
    {
        if (str_starts_with($event, 'session.')) {
            $this->webhookService->sendSessionEvent($event, $data);

            if ($event === 'session.timeout') {
                $username = $data['username'] ?? 'unknown';
                $this->slackService->sendSessionAlert($username, 'session_timeout', $data);
            }
            return;
        }

        if (str_starts_with($event, 'alert.')) {
            $this->webhookService->sendAlertEvent($data);

            if ($event === 'alert.critical') {
                $message  = $data['message'] ?? $event;
                $severity = $data['severity'] ?? 'critical';
                $context  = array_diff_key($data, array_flip(['message', 'severity']));
                $this->slackService->sendAlert($message, $severity, $context);
            }
            return;
        }

        if (str_starts_with($event, 'user.')) {
            foreach ($this->webhookService->getConfiguredUrls() as $url) {
                $this->webhookService->send($url, $event, [
                    'event'     => $event,
                    'timestamp' => date('c'),
                    'data'      => $data,
                ]);
            }
        }
    }

    /**
     * Fire the session.start event.
     */
    public function onSessionStart(array $sessionData): void
    {
        $this->dispatch('session.start', $sessionData);
    }

    /**
     * Fire the session.stop event.
     */
    public function onSessionStop(array $sessionData): void
    {
        $this->dispatch('session.stop', $sessionData);
    }

    /**
     * Fire alert.critical or alert.warning based on $alertData['severity'].
     */
    public function onAlert(array $alertData): void
    {
        $severity = strtolower($alertData['severity'] ?? 'warning');
        $event    = in_array($severity, ['critical', 'warning'], true)
            ? "alert.{$severity}"
            : 'alert.warning';

        $this->dispatch($event, $alertData);
    }

    /**
     * Fire the user.created event.
     */
    public function onUserCreated(string $username, array $data = []): void
    {
        $this->dispatch('user.created', array_merge(['username' => $username], $data));
    }

    /**
     * Fire the user.deleted event.
     */
    public function onUserDeleted(string $username): void
    {
        $this->dispatch('user.deleted', ['username' => $username]);
    }
}
