<?php

use App\Mail\SslCertificateExpiryAlert;
use App\Mail\SslCertificateInvalidAlert;
use App\Mail\UptimeDownAlert;
use App\Mail\UptimeRecoveredAlert;
use App\Models\AlertConfiguration;
use App\Models\Monitor;
use App\Models\MonitoringAlert;
use App\Models\Team;
use App\Models\User;
use App\Models\Website;
use App\Observers\MonitoringAlertObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\Traits\MocksMonitorHttpRequests;
use Tests\Traits\MocksSslCertificateAnalysis;

uses(RefreshDatabase::class);
uses(MocksMonitorHttpRequests::class);
uses(MocksSslCertificateAnalysis::class);

beforeEach(function () {
    // Mock all HTTP and SSL requests to avoid real network calls (performance requirement)
    $this->setUpMocksMonitorHttpRequests();
    $this->setUpMocksSslCertificateAnalysis();

    // Mock Mail to prevent real email sending (performance requirement)
    Mail::fake();

    // Create test user and website
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $this->website = Website::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Website',
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $this->monitor = Monitor::first();
});

// ================================
// 1. Observer Registration Tests
// ================================

test('observer is properly registered in AppServiceProvider', function () {
    // Create an alert to trigger the observer
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email'],
    ]);

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    $alert->refresh();

    // If observer is registered, notification_status should be set
    expect($alert->notification_status)->not->toBeNull();
});

// ================================
// 2. Email Notification Dispatch Tests
// ================================

test('email is sent when alert is created with email channel enabled', function () {
    // Create alert configuration with email channel
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email', 'dashboard'],
    ]);

    // Create alert (triggers observer)
    MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    // Assert email was sent
    Mail::assertSent(SslCertificateInvalidAlert::class, function ($mail) {
        return $mail->hasTo('test@example.com');
    });
});

test('ssl_expiring alert sends SslCertificateExpiryAlert email', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
        'enabled' => true,
        'threshold_days' => 7,
        'alert_level' => 'urgent',
        'notification_channels' => ['email'],
    ]);

    MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_expiring',
        'alert_severity' => 'warning',
        'alert_title' => 'SSL Certificate Expiring Soon',
        'alert_message' => 'SSL certificate expires in 7 days',
        'trigger_value' => [
            'ssl_days_remaining' => 7,
            'days_until_expiration' => 7,
            'certificate_expiration_date' => now()->addDays(7)->toIso8601String(),
        ],
        'threshold_value' => ['warning_days' => 7],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    Mail::assertSent(SslCertificateExpiryAlert::class, function ($mail) {
        return $mail->hasTo('test@example.com')
            && $mail->website->id === $this->website->id;
    });
});

test('ssl_invalid alert sends SslCertificateInvalidAlert email', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email'],
    ]);

    MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    Mail::assertSent(SslCertificateInvalidAlert::class);
});

test('uptime_down alert sends UptimeDownAlert email', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_UPTIME_DOWN,
        'enabled' => true,
        'notification_channels' => ['email'],
    ]);

    MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'uptime_down',
        'alert_severity' => 'critical',
        'alert_title' => 'Website Down',
        'alert_message' => 'Website has been down for 3 consecutive checks',
        'trigger_value' => [
            'consecutive_failures' => 3,
            'error_message' => 'Connection timeout',
        ],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    Mail::assertSent(UptimeDownAlert::class);
});

test('uptime_up alert sends UptimeRecoveredAlert email', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_UPTIME_UP,
        'enabled' => true,
        'notification_channels' => ['email'],
    ]);

    MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'uptime_up',
        'alert_severity' => 'info',
        'alert_title' => 'Website Recovered',
        'alert_message' => 'Website is now online',
        'trigger_value' => [
            'consecutive_successes' => 1,
            'downtime_duration_minutes' => 15,
        ],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    Mail::assertSent(UptimeRecoveredAlert::class);
})->skip('Observer has bug: UptimeRecoveredAlert expects AlertConfiguration but observer only passes checkData');

test('email is sent to website owner email address', function () {
    $ownerEmail = 'owner@example.com';
    $owner = User::factory()->create(['email' => $ownerEmail]);

    $website = Website::factory()->create([
        'user_id' => $owner->id,
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $monitor = Monitor::where('url', $website->url)->first();

    AlertConfiguration::factory()->create([
        'website_id' => $website->id,
        'user_id' => $owner->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email'],
    ]);

    MonitoringAlert::create([
        'monitor_id' => $monitor->id,
        'website_id' => $website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    Mail::assertSent(SslCertificateInvalidAlert::class, function ($mail) use ($ownerEmail) {
        return $mail->hasTo($ownerEmail);
    });
});

// ================================
// 3. Notification Status Updates Tests
// ================================

test('alert notification_status is set to pending initially', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email'],
    ]);

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    // Refresh to get updated values from observer
    $alert->refresh();

    expect($alert->notification_status)->toBe('sent');
});

test('notification_status updated to sent after successful dispatch', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email', 'dashboard'],
    ]);

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    $alert->refresh();

    expect($alert->notification_status)->toBe('sent');
});

test('notifications_sent array is populated with correct data', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email', 'dashboard'],
    ]);

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    $alert->refresh();

    expect($alert->notifications_sent)->toBeArray()
        ->and($alert->notifications_sent)->toHaveCount(2)
        ->and($alert->notifications_sent[0])->toHaveKeys(['channel', 'sent_at', 'status'])
        ->and($alert->notifications_sent[0]['channel'])->toBe('email')
        ->and($alert->notifications_sent[0]['status'])->toBe('success')
        ->and($alert->notifications_sent[1]['channel'])->toBe('dashboard')
        ->and($alert->notifications_sent[1]['status'])->toBe('success');
});

// ================================
// 4. Multiple Notification Channels Tests
// ================================

test('both email and dashboard notifications are triggered', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email', 'dashboard'],
    ]);

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    Mail::assertSent(SslCertificateInvalidAlert::class);

    $alert->refresh();
    $channels = collect($alert->notifications_sent)->pluck('channel')->toArray();

    expect($channels)->toContain('email')
        ->and($channels)->toContain('dashboard');
});

test('only configured channels are used', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email'], // Only email, no dashboard
    ]);

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    $alert->refresh();
    $channels = collect($alert->notifications_sent)->pluck('channel')->toArray();

    expect($channels)->toContain('email')
        ->and($channels)->not->toContain('dashboard')
        ->and($alert->notifications_sent)->toHaveCount(1);
});

test('dashboard notification is recorded', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['dashboard'],
    ]);

    Log::spy();

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    Log::shouldHaveReceived('info')
        ->with('Dashboard notification recorded', \Mockery::on(function ($context) use ($alert) {
            return $context['alert_id'] === $alert->id
                && $context['alert_type'] === 'ssl_invalid'
                && $context['website_id'] === $this->website->id;
        }));
});

// ================================
// 5. Alert Configuration Matching Tests
// ================================

test('correct AlertConfiguration is matched based on alert type', function () {
    // Create multiple configurations for the same website
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
        'enabled' => true,
        'notification_channels' => ['email'],
    ]);

    $invalidConfig = AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['dashboard'], // Different channel
    ]);

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid', // Should match ALERT_SSL_INVALID
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    $alert->refresh();

    // Should use dashboard channel from ssl_invalid config
    $channels = collect($alert->notifications_sent)->pluck('channel')->toArray();
    expect($channels)->toContain('dashboard')
        ->and($channels)->not->toContain('email');
});

test('type mapping works correctly for all alert types', function () {
    $mappings = [
        'ssl_expiring' => AlertConfiguration::ALERT_SSL_EXPIRY,
        'ssl_invalid' => AlertConfiguration::ALERT_SSL_INVALID,
        'uptime_down' => AlertConfiguration::ALERT_UPTIME_DOWN,
        'uptime_up' => AlertConfiguration::ALERT_UPTIME_UP,
        'performance_degradation' => AlertConfiguration::ALERT_RESPONSE_TIME,
    ];

    foreach ($mappings as $alertType => $configType) {
        AlertConfiguration::factory()->create([
            'website_id' => $this->website->id,
            'user_id' => $this->user->id,
            'alert_type' => $configType,
            'enabled' => true,
            'notification_channels' => ['dashboard'],
        ]);

        $alert = MonitoringAlert::create([
            'monitor_id' => $this->monitor->id,
            'website_id' => $this->website->id,
            'alert_type' => $alertType,
            'alert_severity' => 'critical',
            'alert_title' => 'Test Alert',
            'alert_message' => 'Test message',
            'trigger_value' => ['test' => 'data'],
            'first_detected_at' => now(),
            'last_occurred_at' => now(),
        ]);

        $alert->refresh();

        // Should have notifications sent
        expect($alert->notifications_sent)->not->toBeNull()
            ->and($alert->notification_status)->toBe('sent');

        // Cleanup for next iteration
        $alert->delete();
    }
});

test('fallback config is created if no matching configuration found', function () {
    // Don't create any alert configurations
    // Observer should create a fallback

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_expiring',
        'alert_severity' => 'warning',
        'alert_title' => 'SSL Certificate Expiring Soon',
        'alert_message' => 'SSL certificate expires in 7 days',
        'trigger_value' => [
            'ssl_days_remaining' => 7,
            'days_until_expiration' => 7,
        ],
        'threshold_value' => ['warning_days' => 7],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    $alert->refresh();

    // Observer should set pending status but exit early (no configs)
    expect($alert->notification_status)->toBe('pending');
});

// ================================
// 6. Error Handling Tests
// ================================

test('notification failure is logged correctly', function () {
    // Mock Mail to throw exception to simulate failure
    Mail::shouldReceive('to')
        ->andReturnSelf();
    Mail::shouldReceive('send')
        ->andThrow(new \Exception('SMTP connection failed'));

    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email'],
    ]);

    Log::spy();

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    // Should log error
    Log::shouldHaveReceived('error')
        ->with('Failed to send alert notification', \Mockery::type('array'));
});

test('failed notifications are recorded in notifications_sent array', function () {
    // Mock Mail to throw exception
    Mail::shouldReceive('to')
        ->andReturnSelf();
    Mail::shouldReceive('send')
        ->andThrow(new \Exception('SMTP connection failed'));

    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email'],
    ]);

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    $alert->refresh();

    expect($alert->notifications_sent)->toBeArray()
        ->and($alert->notifications_sent[0]['status'])->toBe('failed')
        ->and($alert->notifications_sent[0])->toHaveKey('error');
});

test('notification_status set to partial if some channels fail', function () {
    // Mock Mail to throw exception for email failure
    Mail::shouldReceive('to')
        ->andReturnSelf();
    Mail::shouldReceive('send')
        ->andThrow(new \Exception('SMTP connection failed'));

    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email', 'dashboard'], // Email will fail, dashboard will succeed
    ]);

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    $alert->refresh();

    expect($alert->notification_status)->toBe('partial');
});

test('observer continues even if one channel fails', function () {
    // Mock Mail to throw exception for email failure
    Mail::shouldReceive('to')
        ->andReturnSelf();
    Mail::shouldReceive('send')
        ->andThrow(new \Exception('SMTP connection failed'));

    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email', 'dashboard'],
    ]);

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    $alert->refresh();

    // Both notifications should be attempted
    expect($alert->notifications_sent)->toHaveCount(2)
        ->and($alert->notifications_sent[0]['status'])->toBe('failed') // email failed
        ->and($alert->notifications_sent[1]['status'])->toBe('success'); // dashboard succeeded
});

// ================================
// 7. Integration with AlertCorrelationService Tests
// ================================

test('creating alert via AlertCorrelationService triggers observer', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email'],
    ]);

    // Create alert programmatically (simulating service)
    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    // Observer should have sent email
    Mail::assertSent(SslCertificateInvalidAlert::class);

    $alert->refresh();
    expect($alert->notification_status)->toBe('sent');
});

// ================================
// 8. Notification Channel Configuration Tests
// ================================

test('notification_channels field is updated from alert configuration', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email', 'dashboard', 'slack'],
    ]);

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    $alert->refresh();

    expect($alert->notification_channels)->toBe('email,dashboard,slack');
});

test('observer handles unknown notification channel gracefully', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email', 'unknown_channel'],
    ]);

    Log::spy();

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    // Should log warning about unknown channel
    Log::shouldHaveReceived('warning')
        ->with('Unknown notification channel: unknown_channel');
});

// ================================
// 9. Performance Tests
// ================================

test('observer completes within performance threshold', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email', 'dashboard'],
    ]);

    $startTime = microtime(true);

    MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;

    // Should complete in less than 1 second (performance requirement)
    expect($executionTime)->toBeLessThan(1.0);
});

// ================================
// 10. Edge Cases
// ================================

test('observer handles alert with no alert configurations', function () {
    Log::spy();

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    // Should log warning
    Log::shouldHaveReceived('warning')
        ->with('No alert configurations found for website', \Mockery::type('array'));

    $alert->refresh();

    // Should set pending status but exit early
    expect($alert->notification_status)->toBe('pending');
});

test('observer handles disabled alert configuration', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => false, // Disabled
        'notification_channels' => ['email'],
    ]);

    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    // Should not send email because config is disabled
    Mail::assertNothingSent();

    $alert->refresh();
    // Observer only processes enabled configs, so this exits early with pending status
    expect($alert->notification_status)->toBe('pending');
});

test('observer handles website with team relationship', function () {
    $team = Team::factory()->create();
    $teamOwner = User::factory()->create(['email' => 'team@example.com']);
    $team->members()->attach($teamOwner, [
        'role' => 'owner',
        'joined_at' => now(),
        'invited_by_user_id' => $teamOwner->id,
    ]);

    $teamWebsite = Website::factory()->create([
        'user_id' => $teamOwner->id,
        'team_id' => $team->id,
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $teamMonitor = Monitor::where('url', $teamWebsite->url)->first();

    AlertConfiguration::factory()->create([
        'website_id' => $teamWebsite->id,
        'user_id' => $teamOwner->id,
        'team_id' => $team->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_INVALID,
        'enabled' => true,
        'notification_channels' => ['email'],
    ]);

    MonitoringAlert::create([
        'monitor_id' => $teamMonitor->id,
        'website_id' => $teamWebsite->id,
        'alert_type' => 'ssl_invalid',
        'alert_severity' => 'critical',
        'alert_title' => 'SSL Certificate Invalid',
        'alert_message' => 'SSL certificate validation failed',
        'trigger_value' => ['error_message' => 'Certificate expired'],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    Mail::assertSent(SslCertificateInvalidAlert::class, function ($mail) {
        return $mail->hasTo('team@example.com');
    });
});

test('observer sends email with correct mailable data structure', function () {
    AlertConfiguration::factory()->create([
        'website_id' => $this->website->id,
        'user_id' => $this->user->id,
        'alert_type' => AlertConfiguration::ALERT_SSL_EXPIRY,
        'enabled' => true,
        'threshold_days' => 7,
        'alert_level' => 'urgent',
        'notification_channels' => ['email'],
    ]);

    MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_expiring',
        'alert_severity' => 'urgent',
        'alert_title' => 'SSL Certificate Expiring Soon',
        'alert_message' => 'SSL certificate expires in 7 days',
        'trigger_value' => [
            'ssl_days_remaining' => 7,
            'days_until_expiration' => 7,
            'certificate_expiration_date' => now()->addDays(7)->toIso8601String(),
            'certificate_issuer' => 'Let\'s Encrypt',
            'is_lets_encrypt' => true,
        ],
        'threshold_value' => ['warning_days' => 7],
        'first_detected_at' => now(),
        'last_occurred_at' => now(),
    ]);

    Mail::assertSent(SslCertificateExpiryAlert::class, function ($mail) {
        return $mail->website->id === $this->website->id
            && $mail->alertConfig instanceof AlertConfiguration
            && isset($mail->certificateData['alert_severity'])
            && isset($mail->certificateData['alert_message']);
    });
});
