<?php

/**
 * TestableRoleService — thin subclass of RoleService for unit testing.
 *
 * Overrides the constructor to accept an injected Database mock,
 * bypassing the Database::getInstance() singleton call in BaseService.
 * Also stubs out LoggingService to avoid filesystem/output side-effects.
 */
class TestableRoleService extends RoleService
{
    public function __construct(\Database $db)
    {
        // Skip parent constructor entirely; inject dependencies directly.
        $this->db     = $db;
        $this->logger = new NullLoggingService();
    }
}
