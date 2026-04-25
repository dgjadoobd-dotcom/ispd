<?php

/**
 * TestableOttService — thin subclass of OttService for unit testing.
 *
 * Overrides the constructor to accept an injected Database mock and
 * an optional SmsService mock, bypassing singleton calls in BaseService.
 */
class TestableOttService extends OttService
{
    public function __construct(\Database $db, ?\SmsService $sms = null)
    {
        // Skip parent constructor entirely; inject dependencies directly.
        $this->db     = $db;
        $this->logger = new NullLoggingService();

        // Inject a real or mock SmsService; default to a null stub.
        $this->sms = $sms ?? new NullSmsService();
    }
}

/**
 * NullSmsService — silences all SMS sends during unit tests.
 */
class NullSmsService extends SmsService
{
    public function __construct()
    {
        // Skip parent constructor (no DB, no gateway lookup)
    }

    public function send(string $phone, string $message, ?int $customerId = null, ?int $templateId = null): bool
    {
        return true; // pretend every SMS was sent
    }

    public function sendTemplate(string $eventType, string $phone, array $vars = [], ?int $customerId = null): bool
    {
        return true;
    }
}
