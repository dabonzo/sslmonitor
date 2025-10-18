<?php

/*
|--------------------------------------------------------------------------
| Debug Integration Test Suite
|--------------------------------------------------------------------------
|
| This test suite is for real data testing with the actual MariaDB database.
| These tests use real websites, SSL certificates, and Mailpit for email testing.
|
| IMPORTANT: These tests use REAL data, not mocks!
| - Real MariaDB database (not SQLite)
| - Real SSL certificate data
| - Real email sending to Mailpit
| - Real user: bonzo@konjscina.com
|
*/

use Illuminate\Foundation\Testing\RefreshDatabase;

tests()->extend(Tests\TestCase::class)
    // Don't use RefreshDatabase - we want REAL MariaDB data
    ->beforeEach(function () {
        // Clear mail queue for clean testing
        \Illuminate\Support\Facades\Mail::fake();

        // Use REAL MariaDB database for debug tests (no refresh - real data)

        // Ensure debug user exists in real database
        $this->debugUser = \App\Models\User::firstOrCreate(
            ['email' => 'bonzo@konjscina.com'],
            [
                'name' => 'Debug User',
                'password' => \Hash::make('to16ro12'),
                'email_verified_at' => now(),
            ]
        );

        // Clean up debug overrides from previous runs
        if (\Schema::hasTable('debug_overrides')) {
            \App\Models\DebugOverride::where('user_id', $this->debugUser->id)->delete();
        }

        // Set up debug environment
        config(['debug.menu_enabled' => true]);
        config(['debug.menu_users' => 'bonzo@konjscina.com']);
        config(['debug.menu_roles' => 'OWNER,ADMIN']);
        config(['debug.menu_audit' => true]);

        // Enable real mail configuration for Mailpit testing
        config(['mail.default' => 'smtp']);
        config(['mail.mailers.smtp.host' => 'mailpit']);
        config(['mail.mailers.smtp.port' => 1025]);
        config(['mail.mailers.smtp.encryption' => null]);
    })
    ->in('Integration/Debug');

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
    $timestamp = time().'-'.rand(1000, 9999);
    $testUrl = 'https://debug-website-'.$timestamp.'.example.com';

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
        $response = \Http::get('http://localhost:8025');

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
        $response = \Http::get('http://localhost:8025/api/v1/messages');
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
        \Http::delete('http://localhost:8025/api/v1/messages');
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
        $recipientMatch = ! empty(array_intersect($emailRecipients, array_map('strtolower', $recipients)));

        return $subjectMatch && $recipientMatch;
    });

    expect($matchingEmails)->toHaveCountGreaterThan(0,
        "Expected email with subject '{$subject}' to be sent to Mailpit, but found none. ".
        'Available emails: '.json_encode(array_column($emails, 'Subject'))
    );
}

/*
|--------------------------------------------------------------------------
| Debug Test Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeEffectiveSslOverride', function () {
    return $this->toBeInstanceOf(\App\Models\DebugOverride::class)
        ->and($this->value->is_active)->toBeTrue()
        ->and($this->value->module_type)->toBe('ssl_expiry')
        ->and($this->value->isEffective())->toBeTrue();
});

expect()->extend('toHaveEffectiveExpiryDate', function (\Carbon\Carbon $expectedDate, ?int $userId = null) {
    $website = $this->value;
    $effectiveDate = $website->getEffectiveSslExpiryDate($userId);

    return expect($effectiveDate)->not->toBeNull()
        ->and($effectiveDate->format('Y-m-d H:i:s'))->toBe($expectedDate->format('Y-m-d H:i:s'));
});

expect()->extend('toDaysRemaining', function (int $expectedDays, ?int $userId = null) {
    $website = $this->value;
    $daysRemaining = $website->getDaysRemaining($userId);

    return expect($daysRemaining)->toBe($expectedDays);
});
