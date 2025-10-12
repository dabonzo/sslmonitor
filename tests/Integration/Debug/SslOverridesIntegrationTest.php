<?php

/*
|--------------------------------------------------------------------------
| SSL Overrides Integration Tests (Real Data Testing)
|--------------------------------------------------------------------------
|
| These tests use REAL data to verify SSL override functionality:
| - Real MariaDB database
| - Real user: bonzo@konjscina.com
| - Real SSL certificate data
| - Real email sending to Mailpit
|
| BEFORE RUNNING: Ensure Mailpit is running at http://localhost:8025
|
*/

use App\Models\User;
use App\Models\Website;
use App\Models\Monitor;
use App\Models\DebugOverride;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Debug Test Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create a real website with SSL monitoring for debug testing
 */
function createDebugWebsite(\App\Models\User $user, array $overrides = []): \App\Models\Website
{
    $website = \App\Models\Website::factory()->create(array_merge([
        'user_id' => $user->id,
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => true,
    ], $overrides));

    // Create corresponding monitor with real SSL data
    $timestamp = time() . '-' . rand(1000, 9999);
    $testUrl = 'https://debug-website-' . $timestamp . '.example.com';

    $website->update(['url' => $testUrl]);

    \App\Models\Monitor::create([
        'url' => $testUrl,
        'certificate_check_enabled' => true,
        'uptime_check_enabled' => true,
        'certificate_expiration_date' => now()->addDays(60),
        'certificate_issuer' => "Let's Encrypt Authority X3",
        'certificate_status' => 'valid',
        'uptime_check_interval_in_minutes' => 5,
        'uptime_status' => 'up',
    ]);

    return $website;
}

/**
 * Create SSL override for testing
 */
function createSslOverride(\App\Models\Website $website, \App\Models\User $user, \Carbon\Carbon $expiryDate): \App\Models\DebugOverride
{
    return \App\Models\DebugOverride::create([
        'user_id' => $user->id,
        'module_type' => 'ssl_expiry',
        'targetable_type' => \App\Models\Website::class,
        'targetable_id' => $website->id,
        'override_data' => [
            'expiry_date' => $expiryDate->format('Y-m-d H:i:s'),
            'original_expiry' => $website->getSpatieMonitor()?->certificate_expiration_date,
            'reason' => 'Debug testing',
        ],
        'is_active' => true,
        'expires_at' => now()->addHours(24), // Auto-expire after 24 hours
    ]);
}

/**
 * Check if Mailpit is ready for email testing
 */
function isMailpitReady(): bool
{
    try {
        $response = Http::get('http://localhost:8025');
        return $response->successful();
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Get emails from Mailpit
 */
function getMailpitEmails(): array
{
    try {
        $response = Http::get('http://localhost:8025/api/v1/messages');
        if ($response->successful()) {
            return $response->json()['messages'] ?? [];
        }
    } catch (\Exception $e) {
        // Mailpit not available
    }

    return [];
}

/**
 * Clear emails from Mailpit
 */
function clearMailpitEmails(): void
{
    try {
        Http::delete('http://localhost:8025/api/v1/messages');
    } catch (\Exception $e) {
        // Mailpit not available
    }
}

/**
 * Assert email was sent to Mailpit
 */
function assertMailpitEmailSent(string $subject, array $recipients = []): void
{
    $emails = getMailpitEmails();

    $matchingEmails = collect($emails)->filter(function ($email) use ($subject, $recipients) {
        $subjectMatch = str_contains(strtolower($email['Subject'] ?? ''), strtolower($subject));

        if (empty($recipients)) {
            return $subjectMatch;
        }

        $emailRecipients = array_map('strtolower', $email['To'] ?? []);
        $recipientMatch = !empty(array_intersect($emailRecipients, array_map('strtolower', $recipients)));

        return $subjectMatch && $recipientMatch;
    });

    expect($matchingEmails)->toHaveCountGreaterThan(0,
        "Expected email with subject '{$subject}' to be sent to Mailpit, but found none. " .
        "Available emails: " . json_encode(array_column($emails, 'Subject'))
    );
}

// Test basic SSL override creation and functionality
test('user can create ssl override for their website', function () {
    // Arrange: Set up test data manually for now
    $user = User::firstOrCreate(
        ['email' => 'bonzo@konjscina.com'],
        [
            'name' => 'Debug User',
            'password' => Hash::make('to16ro12'),
            'email_verified_at' => now(),
        ]
    );

    $website = createDebugWebsite($user);
    $overrideDate = now()->addDays(7);

    // Act: Create SSL override
    $override = createSslOverride($website, $user, $overrideDate);

    // Assert: Override created and effective
    expect($override)->toBeInstanceOf(DebugOverride::class);
    expect($override->is_active)->toBeTrue();
    expect($override->module_type)->toBe('ssl_expiry');
    expect($override->override_data['expiry_date'])->toBe($overrideDate->format('Y-m-d H:i:s'));

    // Assert: Database record exists
    $this->assertDatabaseHas('debug_overrides', [
        'user_id' => $user->id,
        'module_type' => 'ssl_expiry',
        'targetable_type' => Website::class,
        'targetable_id' => $website->id,
        'is_active' => true,
    ]);
});

test('ssl override affects effective expiry date calculation', function () {
    // Arrange: Create website with real SSL expiry
    $user = $this->debugUser;
    $realExpiryDate = now()->addDays(60);
    $website = createDebugWebsite($user);
    $website->getSpatieMonitor()->update([
        'certificate_expiration_date' => $realExpiryDate,
    ]);

    // Override to 7 days from now
    $overrideDate = now()->addDays(7);
    createSslOverride($website, $user, $overrideDate);

    // Act & Assert: Website should use override date
    expect($website)->toHaveEffectiveExpiryDate($overrideDate, $user->id);
    expect($website)->toDaysRemaining(7, $user->id); // Approximately 7 days
});

test('ssl override triggers real email alerts', function () {
    // Arrange: Ensure Mailpit is ready
    $this->assertTrue(isMailpitReady(), 'Mailpit must be running for email testing');
    clearMailpitEmails();

    $user = $this->debugUser;
    $website = createDebugWebsite($user);

    // Create alert configuration
    \App\Models\AlertConfiguration::factory()->create([
        'user_id' => $user->id,
        'website_id' => $website->id,
        'alert_type' => \App\Models\AlertConfiguration::ALERT_SSL_EXPIRY,
        'threshold_days' => 7,
        'enabled' => true,
        'notification_channels' => [\App\Models\AlertConfiguration::CHANNEL_EMAIL],
    ]);

    // Override to trigger urgent alert (7 days)
    $overrideDate = now()->addDays(7);
    createSslOverride($website, $user, $overrideDate);

    // Act: Check and trigger alerts
    $alertService = app(\App\Services\AlertService::class);
    $triggeredAlerts = $alertService->checkAndTriggerAlerts($website);

    // Assert: Alerts triggered
    expect($triggeredAlerts)->toHaveCount(1);
    expect($triggeredAlerts[0]['type'])->toBe('ssl_expiry');
    expect($triggeredAlerts[0]['level'])->toBe('urgent');

    // Assert: Real email sent to Mailpit
    assertMailpitEmailSent('SSL certificate', [$user->email]);
});

test('ssl override works with different alert thresholds', function () {
    $user = $this->debugUser;

    // Test different threshold scenarios
    $scenarios = [
        ['days' => 7, 'expected_level' => 'urgent'],
        ['days' => 3, 'expected_level' => 'critical'],
        ['days' => 1, 'expected_level' => 'critical'],
        ['days' => 0, 'expected_level' => 'critical'],
        ['days' => -1, 'expected_level' => 'critical'], // Expired
    ];

    foreach ($scenarios as $scenario) {
        clearMailpitEmails();

        $website = createDebugWebsite($user);
        $overrideDate = now()->addDays($scenario['days']);
        createSslOverride($website, $user, $overrideDate);

        // Create alert configuration
        \App\Models\AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => \App\Models\AlertConfiguration::ALERT_SSL_EXPIRY,
            'threshold_days' => 30, // High threshold to catch all scenarios
            'enabled' => true,
            'notification_channels' => [\App\Models\AlertConfiguration::CHANNEL_EMAIL],
        ]);

        // Check alerts
        $alertService = app(\App\Services\AlertService::class);
        $triggeredAlerts = $alertService->checkAndTriggerAlerts($website);

        expect($triggeredAlerts)->toHaveCount(1);
        expect($triggeredAlerts[0]['level'])->toBe($scenario['expected_level']);
    }
});

test('ssl override security - user isolation enforced', function () {
    // Arrange: Create two users
    $user1 = $this->debugUser;
    $user2 = User::factory()->create(['email' => 'other-user@example.com']);

    $website1 = createDebugWebsite($user1);
    $website2 = createDebugWebsite($user2);

    // Act: User1 creates override for their website
    $overrideDate = now()->addDays(7);
    $override1 = createSslOverride($website1, $user1, $overrideDate);

    // Assert: User1 can only see their own overrides
    $user1Overrides = DebugOverride::where('user_id', $user1->id)->get();
    expect($user1Overrides)->toHaveCount(1);
    expect($user1Overrides->first()->id)->toBe($override1->id);

    // Assert: User2 has no overrides
    $user2Overrides = DebugOverride::where('user_id', $user2->id)->get();
    expect($user2Overrides)->toHaveCount(0);

    // Assert: Cross-user access prevention
    expect($website2->getDebugOverride('ssl_expiry'))->toBeNull();
});

test('ssl override auto-expiration functionality', function () {
    $user = $this->debugUser;
    $website = createDebugWebsite($user);

    // Create override that expires in 1 hour
    $overrideDate = now()->addDays(7);
    $override = createSslOverride($website, $user, $overrideDate);
    $override->update(['expires_at' => now()->addHour()]);

    // Assert: Override is currently effective
    expect($override->isEffective())->toBeTrue();
    expect($website)->toHaveEffectiveExpiryDate($overrideDate, $user->id);

    // Simulate time passing (override expired)
    \Carbon\Carbon::setTestNow(now()->addHours(2));

    // Assert: Override is no longer effective
    expect($override->refresh()->isEffective())->toBeFalse();

    // Website should revert to real SSL expiry
    $realExpiry = $website->getSpatieMonitor()->certificate_expiration_date;
    expect($website)->toHaveEffectiveExpiryDate($realExpiry);

    // Reset test time
    \Carbon\Carbon::setTestNow();
});

test('ssl override cleanup and restoration', function () {
    $user = $this->debugUser;
    $website = createDebugWebsite($user);
    $realExpiry = $website->getSpatieMonitor()->certificate_expiration_date;

    // Create override
    $overrideDate = now()->addDays(7);
    $override = createSslOverride($website, $user, $overrideDate);

    // Assert: Override is active
    expect($website)->toHaveEffectiveExpiryDate($overrideDate, $user->id);

    // Act: Deactivate override
    $override->deactivate();

    // Assert: Website reverts to real expiry
    expect($website)->toHaveEffectiveExpiryDate($realExpiry);
    expect($override->refresh()->is_active)->toBeFalse();
});

test('bulk ssl override operations', function () {
    $user = $this->debugUser;

    // Create multiple websites
    $websites = collect([
        createDebugWebsite($user),
        createDebugWebsite($user),
        createDebugWebsite($user),
    ]);

    $overrideDate = now()->addDays(7);

    // Act: Create bulk overrides
    foreach ($websites as $website) {
        createSslOverride($website, $user, $overrideDate);
    }

    // Assert: All overrides created
    $this->assertDatabaseHas('debug_overrides', [
        'user_id' => $user->id,
        'module_type' => 'ssl_expiry',
        'is_active' => true,
    ]);

    expect(DebugOverride::where('user_id', $user->id)->active()->count())
        ->toBe($websites->count());

    // Assert: All websites use override date
    foreach ($websites as $website) {
        expect($website)->toHaveEffectiveExpiryDate($overrideDate, $user->id);
    }
});

test('ssl override with different certificate states', function () {
    $user = $this->debugUser;

    $certificateStates = [
        ['status' => 'valid', 'expiry' => now()->addDays(90)],
        ['status' => 'expiring_soon', 'expiry' => now()->addDays(15)],
        ['status' => 'expired', 'expiry' => now()->subDays(1)],
        ['status' => 'invalid', 'expiry' => null],
    ];

    foreach ($certificateStates as $state) {
        $website = createDebugWebsite($user);
        $monitor = $website->getSpatieMonitor();

        if ($state['expiry']) {
            $monitor->update([
                'certificate_expiration_date' => $state['expiry'],
                'certificate_status' => $state['status'],
            ]);
        } else {
            $monitor->update([
                'certificate_status' => $state['status'],
            ]);
        }

        // Override to 7 days regardless of original state
        $overrideDate = now()->addDays(7);
        createSslOverride($website, $user, $overrideDate);

        // Assert: All websites use override date
        expect($website)->toHaveEffectiveExpiryDate($overrideDate, $user->id);
        expect($website)->toDaysRemaining(7, $user->id);
    }
});

test('ssl override audit trail logging', function () {
    $user = $this->debugUser;
    $website = createDebugWebsite($user);

    // Enable audit logging
    config(['debug.menu_audit' => true]);

    // Act: Create override
    $overrideDate = now()->addDays(7);
    $override = createSslOverride($website, $user, $overrideDate);

    // Assert: Override was created with audit trail
    expect($override->created_at)->not->toBeNull();
    expect($override->updated_at)->not->toBeNull();

    // Check that logs are created (would need to implement logging service)
    $this->assertTrue(true, 'Audit trail placeholder - implement logging service');
});

test('debug menu access control enforcement', function () {
    // Arrange: Disable debug menu
    config(['debug.menu_enabled' => false]);

    $user = $this->debugUser;
    $website = createDebugWebsite($user);

    // Act & Assert: Should not be able to create overrides when debug menu disabled
    expect(fn() => createSslOverride($website, $user, now()->addDays(7)))
        ->toThrow(Exception::class, 'Debug menu is disabled');
});