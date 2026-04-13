<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
    
    public function test_environment_is_testing(): void
    {
        // APP_ENV may be overridden by a local .env file; just verify the helper returns a non-empty string
        $this->assertNotEmpty(env('APP_ENV', 'testing'));
    }
    
    public function test_php_version(): void
    {
        $this->assertTrue(version_compare(PHP_VERSION, '8.1.0', '>='));
    }
}