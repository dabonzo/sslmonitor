<?php

use App\Models\Monitor;
use App\Models\MonitoringAlert;
use App\Models\MonitoringResult;
use App\Models\User;
use App\Models\Website;
use App\Services\AlertCorrelationService;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->service = app(AlertCorrelationService::class);
    $this->monitor = Monitor::first();
    $this->testUser = User::first();
    $this->website = Website::first();
});

test('creates SSL expiration alert when certificate expires soon', function () {
    // Arrange: Create result with expiring SSL certificate
    $result = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'ssl_status' => 'expires_soon',
        'days_until_expiration' => 5,
    ]);

    // Act: Check and create alerts
    $this->service->checkAndCreateAlerts($result);

    // Assert: Verify alert was created with correct data
    $alert = MonitoringAlert::where('monitor_id', $this->monitor->id)
        ->where('alert_type', 'ssl_expiring')
        ->first();

    expect($alert)->not->toBeNull()
        ->and($alert->alert_severity)->toBe('warning')
        ->and($alert->affected_check_result_id)->toBe($result->id)
        ->and($alert->website_id)->toBe($this->website->id);
});

test('creates uptime alert after 3 consecutive failures', function () {
    // Arrange: Create 3 consecutive down results
    for ($i = 0; $i < 3; $i++) {
        MonitoringResult::factory()->create([
            'monitor_id' => $this->monitor->id,
            'website_id' => $this->website->id,
            'uptime_status' => 'down',
            'started_at' => now()->subMinutes(30 - ($i * 5)),
        ]);
    }

    // Create latest result that should trigger alert
    $latestResult = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'uptime_status' => 'down',
        'started_at' => now(),
    ]);

    // Act: Check and create alerts
    $this->service->checkAndCreateAlerts($latestResult);

    // Assert: Verify critical alert was created
    $alert = MonitoringAlert::where('monitor_id', $this->monitor->id)
        ->where('alert_type', 'uptime_down')
        ->first();

    expect($alert)->not->toBeNull()
        ->and($alert->alert_severity)->toBe('critical');
});

test('creates performance alert for slow response time', function () {
    // Arrange: Create result with slow response time
    $result = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 5500,
    ]);

    // Act: Check and create alerts
    $this->service->checkAndCreateAlerts($result);

    // Assert: Verify warning alert was created
    $alert = MonitoringAlert::where('monitor_id', $this->monitor->id)
        ->where('alert_type', 'performance_degradation')
        ->first();

    expect($alert)->not->toBeNull()
        ->and($alert->alert_severity)->toBe('warning');
});

test('auto-resolves SSL alerts when certificate renewed', function () {
    // Arrange: Create active SSL alert
    $alert = MonitoringAlert::create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'alert_type' => 'ssl_expiring',
        'alert_severity' => 'warning',
        'alert_title' => 'SSL Expiring',
        'alert_message' => 'Certificate expires in 7 days',
        'first_detected_at' => now()->subDays(1),
    ]);

    // Create new result showing certificate renewed
    $result = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'ssl_status' => 'valid',
        'days_until_expiration' => 60,
    ]);

    // Act: Auto-resolve alerts
    $this->service->autoResolveAlerts($result);

    // Assert: Verify alert was resolved
    $alert->refresh();
    expect($alert->resolved_at)->not->toBeNull();
});
