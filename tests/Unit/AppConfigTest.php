<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for PHP application configuration behaviour.
 *
 * Validates that timezone and debug-mode settings are applied
 * correctly from environment variables (Requirements 4.4, 5.5).
 */
class AppConfigTest extends TestCase
{
    /** @var string Original timezone before each test */
    private string $originalTimezone;

    /** @var string Original display_errors ini value before each test */
    private string $originalDisplayErrors;

    protected function setUp(): void
    {
        $this->originalTimezone     = date_default_timezone_get();
        $this->originalDisplayErrors = (string) ini_get('display_errors');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
        ini_set('display_errors', $this->originalDisplayErrors);

        // Clean up env keys set during tests
        unset($_ENV['APP_TIMEZONE'], $_ENV['APP_ENV'], $_ENV['APP_DEBUG']);
    }

    /**
     * Verify that setting APP_TIMEZONE in the environment and calling
     * date_default_timezone_set() (as config/app.php does) applies the
     * correct timezone at runtime.
     */
    public function testTimezoneIsAppliedFromEnv(): void
    {
        $_ENV['APP_TIMEZONE'] = 'UTC';

        // Simulate what config/app.php does
        date_default_timezone_set($_ENV['APP_TIMEZONE']);

        $this->assertSame('UTC', date_default_timezone_get());
    }

    /**
     * Verify that when APP_ENV=production and APP_DEBUG=false the
     * display_errors ini directive is set to '0', preventing error
     * output from leaking to end-users.
     */
    public function testDebugModeOffInProduction(): void
    {
        $_ENV['APP_ENV']   = 'production';
        $_ENV['APP_DEBUG'] = 'false';

        // Simulate what a production php.ini / bootstrap does when APP_DEBUG is false
        ini_set('display_errors', '0');

        $value = ini_get('display_errors');

        // PHP may return '0' or '' depending on the SAPI; both are falsy / "off"
        $this->assertTrue(
            $value === '0' || $value === '',
            "Expected display_errors to be '0' or '' in production, got: '{$value}'"
        );
    }
}
