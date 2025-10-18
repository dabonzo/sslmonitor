<?php

use App\Models\AlertConfiguration;
use App\Models\DebugOverride;
use App\Models\Website;
use Illuminate\Support\Facades\Mail;
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->setUpMocksSslCertificateAnalysis();
});

describe('SSL Alert Debug Testing - Disabled Configurations', function () {
    it('triggers 30-day SSL alert even when disabled in production', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        // Create a 30-day SSL alert configuration (disabled by default)
        $alertConfig = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'threshold_days' => 30,
            'enabled' => false, // Disabled in production
            'alert_level' => AlertConfiguration::LEVEL_INFO,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $checkData = [
            'ssl_status' => 'valid',
            'ssl_days_remaining' => 30,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        // In production mode: disabled alert should NOT trigger
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: false, bypassEnabledCheck: false))
            ->toBeFalse();

        // In debug mode: disabled alert SHOULD trigger (bypassEnabledCheck = true)
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: true, bypassEnabledCheck: true))
            ->toBeTrue();
    });

    it('triggers 14-day SSL alert even when disabled in production', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        // Create a 14-day SSL alert configuration (disabled by default)
        $alertConfig = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'threshold_days' => 14,
            'enabled' => false, // Disabled in production
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $checkData = [
            'ssl_status' => 'valid',
            'ssl_days_remaining' => 14,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        // In production mode: disabled alert should NOT trigger
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: false, bypassEnabledCheck: false))
            ->toBeFalse();

        // In debug mode: disabled alert SHOULD trigger (bypassEnabledCheck = true)
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: true, bypassEnabledCheck: true))
            ->toBeTrue();
    });

    it('triggers enabled SSL alerts in debug mode (7-day, 3-day, 0-day)', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        // Create enabled SSL alert configurations
        $alertConfigs = [
            AlertConfiguration::factory()->create([
                'user_id' => $user->id,
                'website_id' => $website->id,
                'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
                'threshold_days' => 7,
                'enabled' => true, // Enabled
                'alert_level' => AlertConfiguration::LEVEL_URGENT,
                'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
            ]),
            AlertConfiguration::factory()->create([
                'user_id' => $user->id,
                'website_id' => $website->id,
                'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
                'threshold_days' => 3,
                'enabled' => true, // Enabled
                'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
                'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
            ]),
            AlertConfiguration::factory()->create([
                'user_id' => $user->id,
                'website_id' => $website->id,
                'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
                'threshold_days' => 0,
                'enabled' => true, // Enabled
                'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
                'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
            ]),
        ];

        // Test 7-day alert
        $checkData7Days = [
            'ssl_status' => 'valid',
            'ssl_days_remaining' => 7,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        expect($alertConfigs[0]->shouldTrigger($checkData7Days, bypassCooldown: true, bypassEnabledCheck: true))
            ->toBeTrue();

        // Test 3-day alert
        $checkData3Days = [
            'ssl_status' => 'valid',
            'ssl_days_remaining' => 3,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        expect($alertConfigs[1]->shouldTrigger($checkData3Days, bypassCooldown: true, bypassEnabledCheck: true))
            ->toBeTrue();

        // Test 0-day (expired) alert
        $checkData0Days = [
            'ssl_status' => 'expired',
            'ssl_days_remaining' => 0,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        expect($alertConfigs[2]->shouldTrigger($checkData0Days, bypassCooldown: true, bypassEnabledCheck: true))
            ->toBeTrue();
    });

    it('sends all 5 SSL alerts when testing all levels via debug controller', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        // Create all 5 SSL alert configurations
        $sslLevels = [30, 14, 7, 3, 0];
        foreach ($sslLevels as $days) {
            AlertConfiguration::factory()->create([
                'user_id' => $user->id,
                'website_id' => $website->id,
                'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
                'threshold_days' => $days,
                'enabled' => $days >= 7, // 30 and 14 day alerts disabled by default
                'alert_level' => match ($days) {
                    30 => AlertConfiguration::LEVEL_INFO,
                    14 => AlertConfiguration::LEVEL_WARNING,
                    7 => AlertConfiguration::LEVEL_URGENT,
                    3, 0 => AlertConfiguration::LEVEL_CRITICAL,
                },
                'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
            ]);
        }

        // Test the SSL alerts endpoint
        $response = $this->actingAs($user)
            ->postJson('/debug/alerts/test-ssl', [
                'website_id' => $website->id,
                'days' => $sslLevels,
            ]);

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'message',
            'total_sent',
            'results',
        ]);

        // Verify 5 SSL expiry alerts were triggered
        expect($response->json('total_sent'))->toBe(5);
    });
});

describe('Response Time Alert Threshold Logic', function () {
    it('triggers when response time equals threshold (5000ms == 5000ms)', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        $alertConfig = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_RESPONSE_TIME,
            'threshold_response_time' => 5000,
            'enabled' => true,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $checkData = [
            'response_time' => 5000, // Exactly equals threshold
            'threshold_exceeded' => true,
            'checked_at' => now(),
        ];

        // Response time exactly equal to threshold should trigger
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeTrue();
    });

    it('triggers when response time exceeds threshold (6000ms > 5000ms)', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        $alertConfig = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_RESPONSE_TIME,
            'threshold_response_time' => 5000,
            'enabled' => true,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $checkData = [
            'response_time' => 6000, // Exceeds threshold
            'threshold_exceeded' => true,
            'checked_at' => now(),
        ];

        // Response time exceeding threshold should trigger
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeTrue();
    });

    it('does not trigger when response time is below threshold (4000ms < 5000ms)', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        $alertConfig = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_RESPONSE_TIME,
            'threshold_response_time' => 5000,
            'enabled' => true,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $checkData = [
            'response_time' => 4000, // Below threshold
            'threshold_exceeded' => false,
            'checked_at' => now(),
        ];

        // Response time below threshold should NOT trigger
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeFalse();
    });

    it('tests both 5000ms and 10000ms thresholds correctly', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        // Create 5000ms threshold alert
        $alertConfig5000 = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_RESPONSE_TIME,
            'threshold_response_time' => 5000,
            'enabled' => true,
            'alert_level' => AlertConfiguration::LEVEL_WARNING,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        // Create 10000ms threshold alert
        $alertConfig10000 = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_RESPONSE_TIME,
            'threshold_response_time' => 10000,
            'enabled' => true,
            'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        // Test 5000ms threshold with 5000ms response time
        $checkData5000 = ['response_time' => 5000, 'checked_at' => now()];
        expect($alertConfig5000->shouldTrigger($checkData5000, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeTrue();

        // Test 10000ms threshold with 10000ms response time
        $checkData10000 = ['response_time' => 10000, 'checked_at' => now()];
        expect($alertConfig10000->shouldTrigger($checkData10000, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeTrue();

        // Test 5000ms threshold with 4999ms response time (should not trigger)
        $checkData4999 = ['response_time' => 4999, 'checked_at' => now()];
        expect($alertConfig5000->shouldTrigger($checkData4999, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeFalse();

        // Test 10000ms threshold with 9999ms response time (should not trigger)
        $checkData9999 = ['response_time' => 9999, 'checked_at' => now()];
        expect($alertConfig10000->shouldTrigger($checkData9999, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeFalse();
    });
});

describe('SSL Invalid vs SSL Expiry Separation', function () {
    it('triggers SSL Expiry alert for expired certificates (days_remaining <= 0)', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        $alertConfig = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'threshold_days' => 0,
            'enabled' => true,
            'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $checkData = [
            'ssl_status' => 'expired',
            'ssl_days_remaining' => 0,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        // SSL Expiry alert should trigger for expired certificates
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeTrue();

        // Test with negative days remaining (past expiration)
        $checkDataNegative = [
            'ssl_status' => 'expired',
            'ssl_days_remaining' => -5,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        expect($alertConfig->shouldTrigger($checkDataNegative, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeTrue();
    });

    it('triggers SSL Invalid alert for invalid certificates', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        $alertConfig = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
            'enabled' => true,
            'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $checkDataInvalid = [
            'ssl_status' => 'invalid',
            'ssl_days_remaining' => null,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        // SSL Invalid alert should trigger for invalid status
        expect($alertConfig->shouldTrigger($checkDataInvalid, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeTrue();
    });

    it('triggers SSL Invalid alert for failed certificates', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        $alertConfig = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
            'enabled' => true,
            'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $checkDataFailed = [
            'ssl_status' => 'failed',
            'ssl_days_remaining' => null,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        // SSL Invalid alert should trigger for failed status
        expect($alertConfig->shouldTrigger($checkDataFailed, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeTrue();
    });

    it('does not trigger SSL Invalid alert for expired certificates (no duplication)', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        $alertConfig = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
            'enabled' => true,
            'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $checkDataExpired = [
            'ssl_status' => 'expired',
            'ssl_days_remaining' => 0,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        // SSL Invalid alert should NOT trigger for expired status (handled by SSL Expiry)
        expect($alertConfig->shouldTrigger($checkDataExpired, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeFalse();
    });

    it('only SSL Expiry alert triggers for expired certificates, not both', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        // Create both SSL Expiry and SSL Invalid alerts
        $sslExpiryAlert = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'threshold_days' => 0,
            'enabled' => true,
            'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $sslInvalidAlert = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
            'enabled' => true,
            'alert_level' => AlertConfiguration::LEVEL_CRITICAL,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $checkDataExpired = [
            'ssl_status' => 'expired',
            'ssl_days_remaining' => 0,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        // SSL Expiry alert should trigger
        expect($sslExpiryAlert->shouldTrigger($checkDataExpired, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeTrue();

        // SSL Invalid alert should NOT trigger (preventing duplication)
        expect($sslInvalidAlert->shouldTrigger($checkDataExpired, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeFalse();
    });
});

describe('AlertConfiguration::shouldTrigger() Bypass Parameters', function () {
    it('tests all parameter combinations of shouldTrigger()', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        $alertConfig = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'threshold_days' => 7,
            'enabled' => false, // Disabled
            'alert_level' => AlertConfiguration::LEVEL_URGENT,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
            'last_triggered_at' => now()->subHours(1), // Recently triggered
        ]);

        $checkData = [
            'ssl_status' => 'valid',
            'ssl_days_remaining' => 5,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        // Test 1: Both bypasses FALSE (production mode)
        // Should not trigger: alert is disabled AND has cooldown
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: false, bypassEnabledCheck: false))
            ->toBeFalse();

        // Test 2: bypassCooldown = true, bypassEnabledCheck = false
        // Should not trigger: alert is still disabled
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeFalse();

        // Test 3: bypassCooldown = false, bypassEnabledCheck = true
        // Should not trigger: cooldown is active (recently triggered)
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: false, bypassEnabledCheck: true))
            ->toBeFalse();

        // Test 4: Both bypasses TRUE (debug mode)
        // Should trigger: both cooldown and enabled check bypassed
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: true, bypassEnabledCheck: true))
            ->toBeTrue();
    });

    it('disabled alerts do not trigger in production (both bypasses false)', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        $alertConfig = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'threshold_days' => 7,
            'enabled' => false, // Disabled
            'alert_level' => AlertConfiguration::LEVEL_URGENT,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $checkData = [
            'ssl_status' => 'valid',
            'ssl_days_remaining' => 5,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        // In production mode: disabled alert should NOT trigger
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: false, bypassEnabledCheck: false))
            ->toBeFalse();
    });

    it('disabled alerts do trigger when bypassEnabledCheck is true', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        $alertConfig = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'threshold_days' => 7,
            'enabled' => false, // Disabled
            'alert_level' => AlertConfiguration::LEVEL_URGENT,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $checkData = [
            'ssl_status' => 'valid',
            'ssl_days_remaining' => 5,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        // With bypassEnabledCheck = true: disabled alert SHOULD trigger
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: true, bypassEnabledCheck: true))
            ->toBeTrue();
    });

    it('cooldown bypass functionality works correctly', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        $alertConfig = AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'threshold_days' => 7,
            'enabled' => true, // Enabled
            'alert_level' => AlertConfiguration::LEVEL_URGENT,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
            'last_triggered_at' => now()->subHours(1), // Recently triggered (within 24 hours)
        ]);

        $checkData = [
            'ssl_status' => 'valid',
            'ssl_days_remaining' => 5,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];

        // Without bypass: cooldown should prevent triggering
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: false, bypassEnabledCheck: false))
            ->toBeFalse();

        // With cooldown bypass: should trigger
        expect($alertConfig->shouldTrigger($checkData, bypassCooldown: true, bypassEnabledCheck: false))
            ->toBeTrue();
    });
});

describe('AlertTestingController Endpoints', function () {
    it('tests POST /debug/alerts/test-ssl endpoint with all 5 SSL levels', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        // Create all 5 SSL alert configurations
        $sslLevels = [30, 14, 7, 3, 0];
        foreach ($sslLevels as $days) {
            AlertConfiguration::factory()->create([
                'user_id' => $user->id,
                'website_id' => $website->id,
                'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
                'threshold_days' => $days,
                'enabled' => $days >= 7, // 30 and 14 day alerts disabled by default
                'alert_level' => match ($days) {
                    30 => AlertConfiguration::LEVEL_INFO,
                    14 => AlertConfiguration::LEVEL_WARNING,
                    7 => AlertConfiguration::LEVEL_URGENT,
                    3, 0 => AlertConfiguration::LEVEL_CRITICAL,
                },
                'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
            ]);
        }

        $response = $this->actingAs($user)
            ->postJson('/debug/alerts/test-ssl', [
                'website_id' => $website->id,
                'days' => $sslLevels,
            ]);

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'total_sent' => 5,
        ]);

        // Verify 5 debug overrides were created
        $overrides = DebugOverride::where('user_id', $user->id)
            ->where('targetable_type', Website::class)
            ->where('targetable_id', $website->id)
            ->where('module_type', 'ssl_expiry')
            ->get();

        expect($overrides)->toHaveCount(5);
    });

    it('tests POST /debug/alerts/test-response-time endpoint with 5000ms and 10000ms', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        // Create response time alert configurations
        $responseTimes = [5000, 10000];
        foreach ($responseTimes as $responseTime) {
            AlertConfiguration::factory()->create([
                'user_id' => $user->id,
                'website_id' => $website->id,
                'alert_type' => AlertConfiguration::ALERT_RESPONSE_TIME,
                'threshold_response_time' => $responseTime,
                'enabled' => false, // Disabled by default
                'alert_level' => $responseTime === 5000
                    ? AlertConfiguration::LEVEL_WARNING
                    : AlertConfiguration::LEVEL_CRITICAL,
                'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
            ]);
        }

        $response = $this->actingAs($user)
            ->postJson('/debug/alerts/test-response-time', [
                'website_id' => $website->id,
                'response_times' => $responseTimes,
            ]);

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'total_sent' => 2,
        ]);

        // Verify 2 debug overrides were created
        $overrides = DebugOverride::where('user_id', $user->id)
            ->where('targetable_type', Website::class)
            ->where('targetable_id', $website->id)
            ->where('module_type', 'response_time')
            ->get();

        expect($overrides)->toHaveCount(2);
    });

    it('tests POST /debug/alerts/test-all endpoint', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        // Create all alert configurations
        $sslLevels = [30, 14, 7, 3, 0];
        foreach ($sslLevels as $days) {
            AlertConfiguration::factory()->create([
                'user_id' => $user->id,
                'website_id' => $website->id,
                'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
                'threshold_days' => $days,
                'enabled' => $days >= 7,
                'alert_level' => match ($days) {
                    30 => AlertConfiguration::LEVEL_INFO,
                    14 => AlertConfiguration::LEVEL_WARNING,
                    7 => AlertConfiguration::LEVEL_URGENT,
                    3, 0 => AlertConfiguration::LEVEL_CRITICAL,
                },
                'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
            ]);
        }

        $responseTimes = [5000, 10000];
        foreach ($responseTimes as $responseTime) {
            AlertConfiguration::factory()->create([
                'user_id' => $user->id,
                'website_id' => $website->id,
                'alert_type' => AlertConfiguration::ALERT_RESPONSE_TIME,
                'threshold_response_time' => $responseTime,
                'enabled' => false,
                'alert_level' => $responseTime === 5000
                    ? AlertConfiguration::LEVEL_WARNING
                    : AlertConfiguration::LEVEL_CRITICAL,
                'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
            ]);
        }

        $response = $this->actingAs($user)
            ->postJson('/debug/alerts/test-all', [
                'website_id' => $website->id,
            ]);

        $response->assertSuccessful();

        // Should send: 5 SSL + 1 SSL Invalid + 1 Uptime Down + 1 Uptime Recovered + 2 Response Time = 10 alerts
        expect($response->json('total_sent'))->toBeGreaterThanOrEqual(5);
    });

    it('verifies correct number of emails sent for each test', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        // Create SSL alert configurations
        $sslLevels = [7, 3, 0];
        foreach ($sslLevels as $days) {
            AlertConfiguration::factory()->create([
                'user_id' => $user->id,
                'website_id' => $website->id,
                'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
                'threshold_days' => $days,
                'enabled' => true,
                'alert_level' => $days === 7
                    ? AlertConfiguration::LEVEL_URGENT
                    : AlertConfiguration::LEVEL_CRITICAL,
                'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
            ]);
        }

        $response = $this->actingAs($user)
            ->postJson('/debug/alerts/test-ssl', [
                'website_id' => $website->id,
                'days' => $sslLevels,
            ]);

        $response->assertSuccessful();

        // Verify the response indicates 3 alerts were sent
        expect($response->json('total_sent'))->toBe(3);

        // Verify the results array has 3 entries
        expect($response->json('results'))->toHaveCount(3);
    });

    it('verifies toast messages show correct counts', function () {
        Mail::fake();

        $user = $this->testUser;
        $website = $this->realWebsites->first();

        // Create SSL alert configurations
        AlertConfiguration::factory()->create([
            'user_id' => $user->id,
            'website_id' => $website->id,
            'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
            'threshold_days' => 7,
            'enabled' => true,
            'alert_level' => AlertConfiguration::LEVEL_URGENT,
            'notification_channels' => [AlertConfiguration::CHANNEL_EMAIL],
        ]);

        $response = $this->actingAs($user)
            ->postJson('/debug/alerts/test-ssl', [
                'website_id' => $website->id,
                'days' => [7],
            ]);

        $response->assertSuccessful();

        // Verify the message contains the count
        expect($response->json('message'))->toContain('1');
        expect($response->json('message'))->toContain($website->name);

        // Verify the total_sent matches
        expect($response->json('total_sent'))->toBe(1);
    });
});
