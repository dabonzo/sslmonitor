<?php

// Browser tests have been moved to old_docs/Browser
// They are not currently active in this project

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
    ->beforeEach(function () {
        // Skip monitor table cleanup if table doesn't exist
        try {
            if (\Schema::hasTable('monitors')) {
                \Spatie\UptimeMonitor\Models\Monitor::truncate();
            }
        } catch (\Exception $e) {
            // Skip monitor cleanup if there are issues
        }

        // Clear alert configurations for clean testing
        try {
            if (\Schema::hasTable('alert_configurations')) {
                \App\Models\AlertConfiguration::truncate();
            }
        } catch (\Exception $e) {
            // Skip alert configurations cleanup if there are issues
        }

        // Clear websites except our real test websites
        try {
            if (\Schema::hasTable('websites')) {
                \App\Models\Website::whereNotIn('url', [
                    'https://www.redgas.at',
                    'https://www.fairnando.at',
                    'https://omp.office-manager-pro.com'
                ])->delete();
            }
        } catch (\Exception $e) {
            // Skip website cleanup if there are issues
        }

        // Clear mail queue for clean testing
        \Illuminate\Support\Facades\Mail::fake();

        // Ensure our real test user exists (only if tables exist)
        $testUser = null;
        $testTeam = null;

        try {
            if (\Schema::hasTable('users')) {
                $testUser = \App\Models\User::updateOrCreate(
                    ['email' => 'bonzo@konjscina.com'],
                    [
                        'name' => 'Bonzo',
                        'password' => bcrypt('to16ro12'),
                        'email_verified_at' => now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            // Skip if users table doesn't exist
        }

        // Ensure our real test team exists (only if tables exist)
        try {
            if ($testUser && \Schema::hasTable('teams')) {
                $testTeam = \App\Models\Team::updateOrCreate(
                    ['name' => 'Intermedien'],
                    [
                        'created_by_user_id' => $testUser->id,
                        'description' => 'Test team for SSL monitoring',
                    ]
                );
            }
        } catch (\Exception $e) {
            // Skip if teams table doesn't exist
        }

        // Ensure team membership exists (only if tables exist)
        try {
            if ($testUser && $testTeam && \Schema::hasTable('team_members')) {
                \App\Models\TeamMember::updateOrCreate(
                    [
                        'team_id' => $testTeam->id,
                        'user_id' => $testUser->id,
                    ],
                    [
                        'role' => \App\Models\TeamMember::ROLE_OWNER,
                        'joined_at' => now(),
                        'invited_by_user_id' => $testUser->id,
                    ]
                );
            }
        } catch (\Exception $e) {
            // Skip if team_members table doesn't exist
        }

        // Ensure our real test websites exist (only if tables exist)
        $realWebsites = [
            'https://www.redgas.at',
            'https://www.fairnando.at',
            'https://omp.office-manager-pro.com'
        ];

        try {
            if ($testUser && \Schema::hasTable('websites')) {
                foreach ($realWebsites as $url) {
                    // Create website if it doesn't exist
                    $website = \App\Models\Website::updateOrCreate(
                        ['url' => $url],
                        [
                            'user_id' => $testUser->id,
                            'name' => parse_url($url, PHP_URL_HOST),
                            'ssl_monitoring_enabled' => true,
                            'uptime_monitoring_enabled' => true,
                        ]
                    );

                    if (\Schema::hasTable('monitors')) {
                        \Spatie\UptimeMonitor\Models\Monitor::updateOrCreate(
                            ['url' => $url],
                            [
                                'certificate_check_enabled' => true,
                                'certificate_status' => 'valid',
                                'uptime_check_enabled' => true,
                                'uptime_status' => 'up',
                                'certificate_expiration_date' => now()->addDays(90),
                                'certificate_issuer' => "Let's Encrypt Authority X3",
                            ]
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            // Skip if websites/monitors table doesn't exist
        }

        // Store user and team references for tests (with null checks)
        $this->testUser = $testUser;
        $this->testTeam = $testTeam;

        try {
            if (\Schema::hasTable('websites')) {
                $this->realWebsites = \App\Models\Website::whereIn('url', $realWebsites)->get();
            } else {
                $this->realWebsites = collect();
            }
        } catch (\Exception $e) {
            $this->realWebsites = collect();
        }
    })
    ->in('Feature');


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

    // Create Spatie monitor for SSL monitoring
    $timestamp = time() . '-' . rand(1000, 9999);
    $testUrl = 'https://test-user-' . $timestamp . '.example.com';
    $website->update(['url' => $testUrl]);

    \Spatie\UptimeMonitor\Models\Monitor::create([
        'url' => $testUrl,
        'certificate_check_enabled' => true,
        'certificate_status' => 'valid',
    ]);

    return $user;
}

/**
 * Create a test website with SSL certificate and checks
 */
function createWebsiteWithSslData(\App\Models\User $user = null): \App\Models\Website
{
    $user = $user ?? \App\Models\User::factory()->create();
    $website = \App\Models\Website::factory()->create(['user_id' => $user->id]);

    // Create Spatie monitor for SSL monitoring
    $timestamp = time() . '-' . rand(1000, 9999);
    $testUrl = 'https://test-website-' . $timestamp . '.example.com';
    $website->update(['url' => $testUrl]);

    \Spatie\UptimeMonitor\Models\Monitor::create([
        'url' => $testUrl,
        'certificate_check_enabled' => true,
        'certificate_status' => 'valid',
    ]);

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
    $timestamp = time() . '-' . rand(1000, 9999);

    // Valid certificate
    $validWebsite = \App\Models\Website::factory()->create(['user_id' => $user->id]);
    $validUrl = 'https://test-valid-' . $timestamp . '.example.com';
    $validWebsite->update(['url' => $validUrl]);
    \Spatie\UptimeMonitor\Models\Monitor::create([
        'url' => $validUrl,
        'certificate_check_enabled' => true,
        'certificate_status' => 'valid',
        'certificate_expiration_date' => now()->addDays(90),
    ]);
    $scenarios['valid'] = $validWebsite;

    // Expiring soon certificate
    $expiringSoonWebsite = \App\Models\Website::factory()->create(['user_id' => $user->id]);
    $expiringSoonUrl = 'https://test-expiring-' . $timestamp . '.example.com';
    $expiringSoonWebsite->update(['url' => $expiringSoonUrl]);
    \Spatie\UptimeMonitor\Models\Monitor::create([
        'url' => $expiringSoonUrl,
        'certificate_check_enabled' => true,
        'certificate_status' => 'valid',
        'certificate_expiration_date' => now()->addDays(7),
    ]);
    $scenarios['expiring_soon'] = $expiringSoonWebsite;

    // Expired certificate
    $expiredWebsite = \App\Models\Website::factory()->create(['user_id' => $user->id]);
    $expiredUrl = 'https://test-expired-' . $timestamp . '.example.com';
    $expiredWebsite->update(['url' => $expiredUrl]);
    \Spatie\UptimeMonitor\Models\Monitor::create([
        'url' => $expiredUrl,
        'certificate_check_enabled' => true,
        'certificate_status' => 'invalid',
        'certificate_expiration_date' => now()->subDays(1),
    ]);
    $scenarios['expired'] = $expiredWebsite;

    // Invalid certificate
    $invalidWebsite = \App\Models\Website::factory()->create(['user_id' => $user->id]);
    $invalidUrl = 'https://test-invalid-' . $timestamp . '.example.com';
    $invalidWebsite->update(['url' => $invalidUrl]);
    \Spatie\UptimeMonitor\Models\Monitor::create([
        'url' => $invalidUrl,
        'certificate_check_enabled' => true,
        'certificate_status' => 'invalid',
    ]);
    $scenarios['invalid'] = $invalidWebsite;

    // Error case
    $errorWebsite = \App\Models\Website::factory()->create(['user_id' => $user->id]);
    $errorUrl = 'https://test-error-' . $timestamp . '.example.com';
    $errorWebsite->update(['url' => $errorUrl]);
    \Spatie\UptimeMonitor\Models\Monitor::create([
        'url' => $errorUrl,
        'certificate_check_enabled' => true,
        'certificate_status' => 'not yet checked',
    ]);
    $scenarios['error'] = $errorWebsite;

    return $scenarios;
}

/*
|--------------------------------------------------------------------------
| Datasets
|--------------------------------------------------------------------------
|
| Datasets for consistent test data using real production values
|
*/

dataset('real_websites', [
    'redgas' => ['https://www.redgas.at'],
    'fairnando' => ['https://www.fairnando.at'],
    'omp' => ['https://omp.office-manager-pro.com'],
]);

dataset('real_user', fn() => \App\Models\User::where('email', 'bonzo@konjscina.com')->first());

dataset('real_team', fn() => \App\Models\Team::where('name', 'Intermedien')->first());

dataset('ssl_statuses', [
    'valid' => ['valid'],
    'invalid' => ['invalid'],
    'expired' => ['expired'],
    'expiring_soon' => ['expiring_soon'],
    'not_yet_checked' => ['not yet checked'],
]);

dataset('team_roles', [
    'owner' => [\App\Models\TeamMember::ROLE_OWNER],
    'admin' => [\App\Models\TeamMember::ROLE_ADMIN],
    'viewer' => [\App\Models\TeamMember::ROLE_VIEWER],
]);

dataset('alert_types', [
    'ssl_expiry' => [\App\Models\AlertConfiguration::ALERT_SSL_EXPIRY],
    'ssl_invalid' => [\App\Models\AlertConfiguration::ALERT_SSL_INVALID],
    'uptime_down' => [\App\Models\AlertConfiguration::ALERT_UPTIME_DOWN],
    'response_time' => [\App\Models\AlertConfiguration::ALERT_RESPONSE_TIME],
    'lets_encrypt_renewal' => [\App\Models\AlertConfiguration::ALERT_LETS_ENCRYPT_RENEWAL],
]);

dataset('notification_channels', [
    'email' => [[\App\Models\AlertConfiguration::CHANNEL_EMAIL]],
    'dashboard' => [[\App\Models\AlertConfiguration::CHANNEL_DASHBOARD]],
    'slack' => [[\App\Models\AlertConfiguration::CHANNEL_SLACK]],
    'multiple' => [[\App\Models\AlertConfiguration::CHANNEL_EMAIL, \App\Models\AlertConfiguration::CHANNEL_DASHBOARD]],
]);
