<?php

/**
 * TestableHrService — thin subclass of HrService for unit testing.
 *
 * Overrides the constructor to accept an injected Database mock,
 * bypassing the Database::getInstance() singleton call in BaseService.
 * Also stubs out LoggingService to avoid filesystem/output side-effects.
 */
class TestableHrService extends HrService
{
    public function __construct(\Database $db)
    {
        // Skip parent constructor entirely; inject dependencies directly.
        $this->db     = $db;
        $this->logger = new NullLoggingService();
    }
}

/**
 * NullLoggingService — silences all log output during unit tests.
 * Extends LoggingService but overrides the constructor to skip file setup.
 */
class NullLoggingService extends LoggingService
{
    public function __construct()
    {
        // Skip parent constructor (no log file, no directory creation)
    }

    public function info(string $message, array $context = []): void {}
    public function warning(string $message, array $context = []): void {}
    public function error(string $message, array $context = []): void {}
    public function audit(string $message, array $context = []): void {}
}
