<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base test case for browser tests using Playwright
 *
 * This class provides common functionality for all browser tests including
 * setup/teardown and common browser interactions.
 */
abstract class BrowserTestCase extends BaseTestCase
{
    protected bool $seed = true;

    protected string $seeder = \Database\Seeders\TestUserSeeder::class;

    /**
     * Test user credentials
     */
    protected string $testEmail = 'bonzo@konjscina.com';
    protected string $testPassword = 'to16ro12';

    /**
     * Get the base URL for the application
     */
    protected function baseUrl(): string
    {
        return config('app.url');
    }

    /**
     * Get absolute URL for a path
     */
    protected function url(string $path): string
    {
        return rtrim($this->baseUrl(), '/') . '/' . ltrim($path, '/');
    }
}
