<?php

use App\Models\User;
use App\Models\Website;
use App\Models\AlertConfiguration;
use App\Services\AlertService;
use App\Mail\SslCertificateExpiryAlert;
use Illuminate\Support\Facades\Mail;
use Spatie\UptimeMonitor\Models\Monitor;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
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

    expect($alertConfigs)->toHaveCount(count(AlertConfiguration::getDefaultConfigurations()));

    // Check that SSL expiry alert exists
    expect($alertConfigs->where('alert_type', AlertConfiguration::ALERT_SSL_EXPIRY))->toHaveCount(1);

    // Check that Let's Encrypt renewal alert exists
    expect($alertConfigs->where('alert_type', AlertConfiguration::ALERT_LETS_ENCRYPT_RENEWAL))->toHaveCount(1);
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

test('alert service handles lets encrypt renewal alerts', function () {
    Mail::fake();

    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    $alertConfig = AlertConfiguration::factory()->create([
        'user_id' => $user->id,
        'website_id' => $website->id,
        'alert_type' => AlertConfiguration::ALERT_LETS_ENCRYPT_RENEWAL,
        'threshold_days' => 3,
        'enabled' => true,
        'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
    ]);

    // Create monitor with Let's Encrypt certificate expiring in 2 days
    Monitor::updateOrCreate(
        ['url' => $website->url],
        [
            'certificate_check_enabled' => true,
            'certificate_status' => 'valid',
            'certificate_expiration_date' => now()->addDays(2),
            'certificate_issuer' => "Let's Encrypt Authority X3",
        ]
    );

    $alertService = new AlertService(app(\App\Services\SslCertificateAnalysisService::class));
    $triggeredAlerts = $alertService->checkAndTriggerAlerts($website);

    expect($triggeredAlerts)->toHaveCount(1);
    expect($triggeredAlerts[0]['type'])->toBe(AlertConfiguration::ALERT_LETS_ENCRYPT_RENEWAL);

    Mail::assertSent(SslCertificateExpiryAlert::class);
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
        AlertConfiguration::ALERT_LETS_ENCRYPT_RENEWAL,
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
    $sslExpiryAlert = collect($defaults)->firstWhere('alert_type', AlertConfiguration::ALERT_SSL_EXPIRY);
    expect($sslExpiryAlert)->not->toBeNull();
    expect($sslExpiryAlert['threshold_days'])->toBe(7);

    // Check that Let's Encrypt renewal alert is included
    $letsEncryptAlert = collect($defaults)->firstWhere('alert_type', AlertConfiguration::ALERT_LETS_ENCRYPT_RENEWAL);
    expect($letsEncryptAlert)->not->toBeNull();
    expect($letsEncryptAlert['threshold_days'])->toBe(3);
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

// Global Alert Configuration Tests
test('global alert configurations apply to all websites', function () {
    $user = User::factory()->create();
    $website1 = Website::factory()->create(['user_id' => $user->id]);
    $website2 = Website::factory()->create(['user_id' => $user->id]);

    // Create global alert (no website_id)
    $globalAlert = AlertConfiguration::factory()->create([
        'user_id' => $user->id,
        'website_id' => null,
        'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
    ]);

    $alertService = new AlertService(app(\App\Services\SslCertificateAnalysisService::class));

    // Check that global alert applies to both websites
    $alerts1 = AlertConfiguration::where('user_id', $user->id)
        ->where(function ($query) use ($website1) {
            $query->where('website_id', $website1->id)
                  ->orWhereNull('website_id');
        })
        ->get();

    $alerts2 = AlertConfiguration::where('user_id', $user->id)
        ->where(function ($query) use ($website2) {
            $query->where('website_id', $website2->id)
                  ->orWhereNull('website_id');
        })
        ->get();

    expect($alerts1)->toHaveCount(1);
    expect($alerts2)->toHaveCount(1);
    expect($alerts1->first()->id)->toBe($globalAlert->id);
    expect($alerts2->first()->id)->toBe($globalAlert->id);
});
