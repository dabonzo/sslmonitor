<?php

use App\Mail\SslCertificateExpiryAlert;
use App\Models\AlertConfiguration;
use App\Models\Monitor;
use App\Models\User;
use App\Models\Website;
use App\Services\AlertService;
use Illuminate\Support\Facades\Mail;
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->setUpMocksSslCertificateAnalysis();
});

// Alert Configuration Tests
test('user can view alert configurations', function () {
    $user = $this->testUser;
    $website = $this->realWebsites->first();

    // Create some alert configurations
    AlertConfiguration::factory()->count(3)->create([
        'user_id' => $user->id,
        'website_id' => $website->id,
    ]);

    $response = $this->actingAs($user)->get('/alerts');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Alerts/Index')
        ->has('alertConfigurations', 5) // 3 factory + 2 seeded = 5 total
    );
});

test('alert configuration shows default configurations', function () {
    $user = $this->testUser;

    $response = $this->actingAs($user)->get('/alerts');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('defaultConfigurations')
        ->has('alertTypes')
        ->has('alertLevels')
        ->has('notificationChannels')
    );
});

test('user can update alert configuration', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    $alertConfig = AlertConfiguration::factory()->create([
        'user_id' => $user->id,
        'website_id' => $website->id,
        'threshold_days' => 7,
        'enabled' => true,
    ]);

    $response = $this->actingAs($user)
        ->put("/alerts/{$alertConfig->id}", [
            'threshold_days' => 14,
            'enabled' => false,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $alertConfig->refresh();
    expect($alertConfig->threshold_days)->toBe(14);
    expect($alertConfig->enabled)->toBeFalse();
});

test('user cannot update other users alert configurations', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user2->id]);

    $alertConfig = AlertConfiguration::factory()->create([
        'user_id' => $user2->id,
        'website_id' => $website->id,
    ]);

    $response = $this->actingAs($user1)
        ->put("/alerts/{$alertConfig->id}", [
            'threshold_days' => 14,
            'enabled' => false,
        ]);

    $response->assertForbidden();
});

test('alert configuration validation works correctly', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    $alertConfig = AlertConfiguration::factory()->create([
        'user_id' => $user->id,
        'website_id' => $website->id,
    ]);

    $response = $this->actingAs($user)
        ->put("/alerts/{$alertConfig->id}", [
            'threshold_days' => 'invalid',
            'enabled' => 'not_boolean',
        ]);

    $response->assertSessionHasErrors(['threshold_days', 'enabled']);
});

// Alert Service Tests
test('alert service creates default alerts for new websites', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    $alertService = new AlertService(app(\App\Services\SslCertificateAnalysisService::class));
    $alertService->createDefaultAlerts($website);

    $alertConfigs = AlertConfiguration::where('user_id', $user->id)
        ->where('website_id', $website->id)
        ->get();

    // Get the actual default configurations to check against
    $defaultConfigs = AlertConfiguration::getDefaultConfigurations();

    // Count how many default configs should be created for a website
    $expectedCount = 0;
    $sslExpiryCount = 0;

    foreach ($defaultConfigs as $config) {
        if (in_array($config['alert_type'], [
            AlertConfiguration::ALERT_SSL_EXPIRY,
            AlertConfiguration::ALERT_SSL_INVALID,
            AlertConfiguration::ALERT_UPTIME_DOWN,
            AlertConfiguration::ALERT_UPTIME_UP,
            AlertConfiguration::ALERT_RESPONSE_TIME,
        ])) {
            $expectedCount++;

            if ($config['alert_type'] === AlertConfiguration::ALERT_SSL_EXPIRY) {
                $sslExpiryCount++;
            }
        }
    }

    expect($alertConfigs)->toHaveCount($expectedCount);

    // Check that SSL expiry alerts exist (there are multiple with different thresholds)
    expect($alertConfigs->where('alert_type', AlertConfiguration::ALERT_SSL_EXPIRY))->toHaveCount($sslExpiryCount);
});

test('alert service checks and triggers alerts correctly', function () {
    Mail::fake();

    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    // Create alert configuration for SSL expiry
    $alertConfig = AlertConfiguration::factory()->create([
        'user_id' => $user->id,
        'website_id' => $website->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
        'threshold_days' => 7,
        'enabled' => true,
        'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
    ]);

    // Create or update Spatie monitor with certificate expiring in 5 days
    Monitor::updateOrCreate(
        ['url' => $website->url],
        [
            'certificate_check_enabled' => true,
            'certificate_status' => 'valid',
            'certificate_expiration_date' => now()->addDays(5),
            'certificate_issuer' => "Let's Encrypt Authority X3",
        ]
    );

    $alertService = new AlertService(app(\App\Services\SslCertificateAnalysisService::class));
    $triggeredAlerts = $alertService->checkAndTriggerAlerts($website);

    expect($triggeredAlerts)->toHaveCount(1);
    expect($triggeredAlerts[0]['type'])->toBe(AlertConfiguration::ALERT_SSL_EXPIRY);

    Mail::assertSent(SslCertificateExpiryAlert::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

test('alert service does not trigger disabled alerts', function () {
    Mail::fake();

    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    $alertConfig = AlertConfiguration::factory()->create([
        'user_id' => $user->id,
        'website_id' => $website->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
        'threshold_days' => 7,
        'enabled' => false, // Disabled
        'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
    ]);

    Monitor::updateOrCreate(
        ['url' => $website->url],
        [
            'certificate_check_enabled' => true,
            'certificate_status' => 'valid',
            'certificate_expiration_date' => now()->addDays(5),
        ]
    );

    $alertService = new AlertService(app(\App\Services\SslCertificateAnalysisService::class));
    $triggeredAlerts = $alertService->checkAndTriggerAlerts($website);

    expect($triggeredAlerts)->toHaveCount(0);
    Mail::assertNotSent(SslCertificateExpiryAlert::class);
});

test('alert service respects threshold days', function () {
    Mail::fake();

    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    $alertConfig = AlertConfiguration::factory()->create([
        'user_id' => $user->id,
        'website_id' => $website->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
        'threshold_days' => 3, // Only alert if expiring in 3 days or less
        'enabled' => true,
        'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
    ]);

    // Certificate expires in 5 days (should not trigger)
    Monitor::updateOrCreate(
        ['url' => $website->url],
        [
            'certificate_check_enabled' => true,
            'certificate_status' => 'valid',
            'certificate_expiration_date' => now()->addDays(5),
        ]
    );

    $alertService = new AlertService(app(\App\Services\SslCertificateAnalysisService::class));
    $triggeredAlerts = $alertService->checkAndTriggerAlerts($website);

    expect($triggeredAlerts)->toHaveCount(0);
    Mail::assertNotSent(SslCertificateExpiryAlert::class);
});

test('alert service can test alerts', function () {
    Mail::fake();

    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    $alertService = new AlertService(app(\App\Services\SslCertificateAnalysisService::class));
    $result = $alertService->testAlert($website);

    expect($result)->toBeTrue();
    Mail::assertSent(SslCertificateExpiryAlert::class);
});

test('user can test alert from controller', function () {
    Mail::fake();

    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    $alertConfig = AlertConfiguration::factory()->create([
        'user_id' => $user->id,
        'website_id' => $website->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
    ]);

    $response = $this->actingAs($user)
        ->post("/alerts/{$alertConfig->id}/test");

    $response->assertRedirect();
    $response->assertSessionHas('success');

    Mail::assertSent(SslCertificateExpiryAlert::class);
});

// Alert Level Tests
test('alert levels work correctly', function () {
    $levels = [
        AlertConfiguration::LEVEL_INFO,
        AlertConfiguration::LEVEL_WARNING,
        AlertConfiguration::LEVEL_URGENT,
        AlertConfiguration::LEVEL_CRITICAL,
    ];

    foreach ($levels as $level) {
        $alertConfig = AlertConfiguration::factory()->create([
            'alert_level' => $level,
        ]);

        expect($alertConfig->alert_level)->toBe($level);
    }
});

test('alert types are defined correctly', function () {
    $types = [
        AlertConfiguration::ALERT_SSL_EXPIRY,
        AlertConfiguration::ALERT_SSL_INVALID,
        AlertConfiguration::ALERT_UPTIME_DOWN,
        AlertConfiguration::ALERT_RESPONSE_TIME,
    ];

    foreach ($types as $type) {
        $alertConfig = AlertConfiguration::factory()->create([
            'alert_type' => $type,
        ]);

        expect($alertConfig->alert_type)->toBe($type);
    }
});

test('notification channels work correctly', function () {
    $channels = [
        [AlertConfiguration::CHANNEL_EMAIL],
        [AlertConfiguration::CHANNEL_DASHBOARD],
        [AlertConfiguration::CHANNEL_SLACK],
        [AlertConfiguration::CHANNEL_EMAIL, AlertConfiguration::CHANNEL_DASHBOARD],
    ];

    foreach ($channels as $channelSet) {
        $alertConfig = AlertConfiguration::factory()->create([
            'notification_channels' => $channelSet,
        ]);

        expect($alertConfig->notification_channels)->toBe($channelSet);
    }
});

// Default Alert Configuration Tests
test('default alert configurations are properly defined', function () {
    $defaults = AlertConfiguration::getDefaultConfigurations();

    expect($defaults)->not->toBeEmpty();

    foreach ($defaults as $default) {
        expect($default)->toHaveKeys([
            'alert_type',
            'enabled',
            'threshold_days',
            'alert_level',
            'notification_channels',
        ]);
    }

    // Check that SSL expiry alert is included
    // Find the SSL expiry alert with 7-day threshold (urgent level)
    $sslExpiryAlert = collect($defaults)->firstWhere('alert_type', AlertConfiguration::ALERT_SSL_EXPIRY);
    expect($sslExpiryAlert)->not->toBeNull();

    // Since there are multiple SSL expiry alerts, find the one with 7-day threshold
    $sevenDaySslAlert = collect($defaults)->first(function ($config) {
        return $config['alert_type'] === AlertConfiguration::ALERT_SSL_EXPIRY
               && $config['threshold_days'] === 7;
    });
    expect($sevenDaySslAlert)->not->toBeNull();
    expect($sevenDaySslAlert['threshold_days'])->toBe(7);
});

// Alert Triggering Logic Tests
test('alert shouldTrigger method works correctly', function () {
    $alertConfig = AlertConfiguration::factory()->make([
        'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
        'threshold_days' => 7,
        'enabled' => true,
    ]);

    // Should trigger when days remaining is less than threshold
    $checkData = ['ssl_days_remaining' => 5];
    expect($alertConfig->shouldTrigger($checkData))->toBeTrue();

    // Should not trigger when days remaining is more than threshold
    $checkData = ['ssl_days_remaining' => 10];
    expect($alertConfig->shouldTrigger($checkData))->toBeFalse();

    // Should not trigger when disabled
    $alertConfig->enabled = false;
    $checkData = ['ssl_days_remaining' => 5];
    expect($alertConfig->shouldTrigger($checkData))->toBeFalse();
});

test('alert cooldown prevents spam', function () {
    $alertConfig = AlertConfiguration::factory()->create([
        'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
        'threshold_days' => 7,
        'enabled' => true,
        'last_triggered_at' => now()->subHours(23), // Recently triggered
    ]);

    $checkData = ['ssl_days_remaining' => 5];

    // Should not trigger due to cooldown
    expect($alertConfig->shouldTrigger($checkData))->toBeFalse();

    // Update to trigger more than 24 hours ago
    $alertConfig->update(['last_triggered_at' => now()->subHours(25)]);

    // Should trigger now
    expect($alertConfig->shouldTrigger($checkData))->toBeTrue();
});

// Website-Specific Alert Configuration Tests
test('alert configurations are website-specific', function () {
    $user = User::factory()->create();
    $website1 = Website::factory()->create(['user_id' => $user->id]);
    $website2 = Website::factory()->create(['user_id' => $user->id]);

    // Create alert for website1 only
    $website1Alert = AlertConfiguration::factory()->create([
        'user_id' => $user->id,
        'website_id' => $website1->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
    ]);

    $alertService = new AlertService(app(\App\Services\SslCertificateAnalysisService::class));

    // Check that alert only applies to website1
    $alerts1 = AlertConfiguration::where('user_id', $user->id)
        ->where('website_id', $website1->id)
        ->get();

    $alerts2 = AlertConfiguration::where('user_id', $user->id)
        ->where('website_id', $website2->id)
        ->get();

    expect($alerts1)->toHaveCount(1);
    expect($alerts2)->toHaveCount(0);
    expect($alerts1->first()->id)->toBe($website1Alert->id);
});
