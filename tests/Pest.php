<?php

pest()->extend(Tests\DuskTestCase::class)
//  ->use(Illuminate\Foundation\Testing\DatabaseMigrations::class)
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

// SSL Monitor specific expectations
expect()->extend('toBeValidSslStatus', function () {
    return $this->toBeIn(['valid', 'invalid', 'expired', 'expiring_soon', 'error', 'unknown']);
});

expect()->extend('toBeValidPluginType', function () {
    return $this->toBeIn(['agent', 'webhook', 'external_service']);
});

expect()->extend('toBeValidPluginStatus', function () {
    return $this->toBeIn(['active', 'inactive', 'error', 'pending']);
});

expect()->extend('toBeValidUrl', function () {
    $url = $this->value;
    return expect(filter_var($url, FILTER_VALIDATE_URL))->not->toBeFalse()
        ->and(parse_url($url, PHP_URL_SCHEME))->toBeIn(['http', 'https']);
});

expect()->extend('toHaveValidSslCertificateStructure', function () {
    return $this->toBeArray()
        ->toHaveKeys(['issuer', 'expires_at', 'subject', 'is_valid']);
});

expect()->extend('toHaveValidPluginConfiguration', function () {
    return $this->toBeArray()
        ->toHaveKey('plugin_type')
        ->toHaveKey('plugin_name')
        ->toHaveKey('configuration');
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a test user with SSL monitoring data
 */
function createUserWithSslData(): \App\Models\User
{
    $user = \App\Models\User::factory()->create();

    $website = \App\Models\Website::factory()->create(['user_id' => $user->id]);
    \App\Models\SslCertificate::factory()->create(['website_id' => $website->id]);
    \App\Models\SslCheck::factory()->valid()->create(['website_id' => $website->id]);

    return $user;
}

/**
 * Create a test website with SSL certificate and checks
 */
function createWebsiteWithSslData(\App\Models\User $user = null): \App\Models\Website
{
    $user = $user ?? \App\Models\User::factory()->create();
    $website = \App\Models\Website::factory()->create(['user_id' => $user->id]);

    \App\Models\SslCertificate::factory()->create(['website_id' => $website->id]);
    \App\Models\SslCheck::factory()->valid()->create(['website_id' => $website->id]);

    return $website;
}

/**
 * Create test plugin configuration
 */
function createTestPlugin(\App\Models\User $user = null, string $type = 'agent'): \App\Models\PluginConfiguration
{
    $user = $user ?? \App\Models\User::factory()->create();

    return match ($type) {
        'agent' => \App\Models\PluginConfiguration::factory()->agent()->active()->create(['user_id' => $user->id]),
        'webhook' => \App\Models\PluginConfiguration::factory()->webhook()->active()->create(['user_id' => $user->id]),
        'external_service' => \App\Models\PluginConfiguration::factory()->externalService()->active()->create(['user_id' => $user->id]),
        default => \App\Models\PluginConfiguration::factory()->create(['user_id' => $user->id]),
    };
}

/**
 * Assert SSL check has expected structure
 */
function assertValidSslCheck(array $sslCheck): void
{
    expect($sslCheck)->toHaveKeys([
        'status', 'checked_at', 'expires_at', 'issuer', 'subject',
        'is_valid', 'days_until_expiry'
    ])
    ->and($sslCheck['status'])->toBeValidSslStatus()
    ->and($sslCheck['checked_at'])->toBeInstanceOf(\Carbon\Carbon::class)
    ->and($sslCheck['is_valid'])->toBeBoolean();
}

/**
 * Assert plugin configuration has expected structure
 */
function assertValidPluginConfiguration(\App\Models\PluginConfiguration $plugin): void
{
    expect($plugin->plugin_type)->toBeValidPluginType()
        ->and($plugin->plugin_name)->toBeString()
        ->and($plugin->status)->toBeValidPluginStatus()
        ->and($plugin->configuration)->toBeArray()
        ->and($plugin->is_enabled)->toBeBoolean();
}

/**
 * Simulate SSL certificate check result
 */
function mockSslCheckResult(string $status = 'valid', array $overrides = []): array
{
    $baseResult = [
        'status' => $status,
        'checked_at' => now(),
        'expires_at' => now()->addDays(60),
        'issuer' => 'Let\'s Encrypt Authority X3',
        'subject' => 'example.com',
        'serial_number' => 'ABC123DEF456',
        'signature_algorithm' => 'SHA256withRSA',
        'is_valid' => in_array($status, ['valid', 'expiring_soon']),
        'days_until_expiry' => 60,
        'error_message' => $status === 'error' ? 'Connection failed' : null,
        'response_time' => 0.5,
        'check_source' => 'manual',
    ];

    return array_merge($baseResult, $overrides);
}

/**
 * Create SSL test scenarios for comprehensive testing
 */
function createSslTestScenarios(\App\Models\User $user): array
{
    $scenarios = [];

    // Valid certificate
    $validWebsite = \App\Models\Website::factory()->create(['user_id' => $user->id]);
    \App\Models\SslCertificate::factory()->create(['website_id' => $validWebsite->id]);
    \App\Models\SslCheck::factory()->valid()->create(['website_id' => $validWebsite->id]);
    $scenarios['valid'] = $validWebsite;

    // Expiring soon certificate
    $expiringSoonWebsite = \App\Models\Website::factory()->create(['user_id' => $user->id]);
    \App\Models\SslCertificate::factory()->expiringSoon()->create(['website_id' => $expiringSoonWebsite->id]);
    \App\Models\SslCheck::factory()->expiringSoon()->create(['website_id' => $expiringSoonWebsite->id]);
    $scenarios['expiring_soon'] = $expiringSoonWebsite;

    // Expired certificate
    $expiredWebsite = \App\Models\Website::factory()->create(['user_id' => $user->id]);
    \App\Models\SslCertificate::factory()->expired()->create(['website_id' => $expiredWebsite->id]);
    \App\Models\SslCheck::factory()->expired()->create(['website_id' => $expiredWebsite->id]);
    $scenarios['expired'] = $expiredWebsite;

    // Invalid certificate
    $invalidWebsite = \App\Models\Website::factory()->create(['user_id' => $user->id]);
    \App\Models\SslCertificate::factory()->invalid()->create(['website_id' => $invalidWebsite->id]);
    \App\Models\SslCheck::factory()->invalid()->create(['website_id' => $invalidWebsite->id]);
    $scenarios['invalid'] = $invalidWebsite;

    // Error case
    $errorWebsite = \App\Models\Website::factory()->create(['user_id' => $user->id]);
    \App\Models\SslCheck::factory()->error()->create(['website_id' => $errorWebsite->id]);
    $scenarios['error'] = $errorWebsite;

    return $scenarios;
}
