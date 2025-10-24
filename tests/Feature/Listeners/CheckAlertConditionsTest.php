<?php

use App\Events\MonitoringCheckCompleted;
use App\Models\Monitor;
use App\Models\MonitoringAlert;
use App\Models\MonitoringResult;
use App\Models\Website;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->monitor = Monitor::first();
    $this->website = Website::first();
});

test('creates SSL expiration alert via event', function () {
    $result = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'ssl_status' => 'expires_soon',
        'days_until_expiration' => 5,
        'started_at' => now(),
    ]);

    $event = new MonitoringCheckCompleted(
        monitor: $this->monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $result->started_at,
        completedAt: now(),
        checkResults: [
            'ssl_status' => 'expires_soon',
            'days_until_expiration' => 5,
        ]
    );

    $listener = new \App\Listeners\CheckAlertConditions(app(\App\Services\AlertCorrelationService::class));
    $listener->handle($event);

    $alert = MonitoringAlert::where('monitor_id', $this->monitor->id)
        ->where('alert_type', 'ssl_expiring')
        ->first();

    expect($alert)->not->toBeNull();
    expect($alert->website_id)->toBe($this->website->id);
    expect($alert->alert_severity)->toBe('warning');
    expect($alert->alert_title)->toBe('SSL Certificate Expiring Soon');
});

test('handles race condition when result not yet persisted', function () {
    // Fire event without creating result first (simulates race condition)
    $event = new MonitoringCheckCompleted(
        monitor: $this->monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: now(),
        completedAt: now(),
        checkResults: []
    );

    $listener = new \App\Listeners\CheckAlertConditions(app(\App\Services\AlertCorrelationService::class));
    $listener->handle($event);

    // No alert should be created
    expect(MonitoringAlert::count())->toBe(0);
});

test('creates critical SSL alert for imminent expiration', function () {
    $result = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'ssl_status' => 'expires_soon',
        'days_until_expiration' => 2,
        'started_at' => now(),
    ]);

    $event = new MonitoringCheckCompleted(
        monitor: $this->monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $result->started_at,
        completedAt: now(),
        checkResults: [
            'ssl_status' => 'expires_soon',
            'days_until_expiration' => 2,
        ]
    );

    $listener = new \App\Listeners\CheckAlertConditions(app(\App\Services\AlertCorrelationService::class));
    $listener->handle($event);

    $alert = MonitoringAlert::where('monitor_id', $this->monitor->id)
        ->where('alert_type', 'ssl_expiring')
        ->first();

    expect($alert)->not->toBeNull();
    expect($alert->alert_severity)->toBe('critical');
});

test('creates uptime alert after consecutive failures', function () {
    // Create 3 consecutive down results
    for ($i = 0; $i < 3; $i++) {
        MonitoringResult::factory()->create([
            'monitor_id' => $this->monitor->id,
            'website_id' => $this->website->id,
            'uptime_status' => 'down',
            'started_at' => now()->subMinutes($i),
        ]);
    }

    $latestResult = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'uptime_status' => 'down',
        'started_at' => now(),
    ]);

    $event = new MonitoringCheckCompleted(
        monitor: $this->monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $latestResult->started_at,
        completedAt: now(),
        checkResults: [
            'uptime_status' => 'down',
        ]
    );

    $listener = new \App\Listeners\CheckAlertConditions(app(\App\Services\AlertCorrelationService::class));
    $listener->handle($event);

    $alert = MonitoringAlert::where('monitor_id', $this->monitor->id)
        ->where('alert_type', 'uptime_down')
        ->first();

    expect($alert)->not->toBeNull();
    expect($alert->alert_severity)->toBe('critical');
    expect($alert->website_id)->toBe($this->website->id);
});

test('does not create duplicate alerts for same condition', function () {
    $listener = new \App\Listeners\CheckAlertConditions(app(\App\Services\AlertCorrelationService::class));

    // Create first SSL expiring result and alert
    $result1 = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'ssl_status' => 'expires_soon',
        'days_until_expiration' => 5,
        'started_at' => now()->subHour(),
    ]);

    $event1 = new MonitoringCheckCompleted(
        monitor: $this->monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $result1->started_at,
        completedAt: now()->subHour(),
        checkResults: ['ssl_status' => 'expires_soon']
    );
    $listener->handle($event1);

    // Create second result with same condition
    $result2 = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'ssl_status' => 'expires_soon',
        'days_until_expiration' => 5,
        'started_at' => now(),
    ]);

    $event2 = new MonitoringCheckCompleted(
        monitor: $this->monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $result2->started_at,
        completedAt: now(),
        checkResults: ['ssl_status' => 'expires_soon']
    );
    $listener->handle($event2);

    // Should only have ONE alert
    $alerts = MonitoringAlert::where('monitor_id', $this->monitor->id)
        ->where('alert_type', 'ssl_expiring')
        ->get();

    expect($alerts->count())->toBe(1);
});

test('creates response time alert for slow responses', function () {
    $result = MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 6000,
        'started_at' => now(),
    ]);

    $event = new MonitoringCheckCompleted(
        monitor: $this->monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $result->started_at,
        completedAt: now(),
        checkResults: [
            'response_time_ms' => 6000,
        ]
    );

    $listener = new \App\Listeners\CheckAlertConditions(app(\App\Services\AlertCorrelationService::class));
    $listener->handle($event);

    $alert = MonitoringAlert::where('monitor_id', $this->monitor->id)
        ->where('alert_type', 'performance_degradation')
        ->first();

    expect($alert)->not->toBeNull();
    expect($alert->website_id)->toBe($this->website->id);
    expect($alert->alert_title)->toBe('Slow Response Time');
});
