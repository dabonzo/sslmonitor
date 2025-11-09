<?php

use App\Events\MonitoringCheckCompleted;
use App\Models\Monitor;
use App\Models\MonitoringCheckSummary;
use App\Models\MonitoringResult;
use App\Models\Website;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->monitor = Monitor::first();
    $this->website = Website::first();
});

test('creates hourly summary on monitoring check completed', function () {
    // Create a test monitor to avoid interference
    $testMonitor = Monitor::create([
        'url' => 'https://hourly-test-'.time().'.example.com',
        'certificate_check_enabled' => true,
    ]);

    // Create monitoring result
    $result = MonitoringResult::factory()->create([
        'monitor_id' => $testMonitor->id,
        'website_id' => $this->website->id,
        'started_at' => now(),
        'status' => 'success',
        'uptime_status' => 'up',
        'response_time_ms' => 150,
        'check_type' => 'both',
    ]);

    // Create event
    $event = new MonitoringCheckCompleted(
        monitor: $testMonitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $result->started_at,
        completedAt: now(),
        checkResults: [
            'check_type' => 'both',
            'uptime_status' => 'up',
            'response_time_ms' => 150,
        ]
    );

    // Manually invoke listener (because ShouldQueue listeners don't execute automatically in sync tests)
    $listener = app(\App\Listeners\UpdateMonitoringSummaries::class);
    $listener->handle($event);

    // Verify summary created
    $summary = MonitoringCheckSummary::where('monitor_id', $testMonitor->id)
        ->where('summary_period', 'hourly')
        ->first();

    expect($summary)->not->toBeNull();
    expect($summary->website_id)->toBe($this->website->id);
    expect($summary->total_checks)->toBe(1);

    // Cleanup
    $testMonitor->delete();
});

test('updates existing summary when check in same period', function () {
    // Create a test monitor to avoid interference
    $testMonitor = Monitor::create([
        'url' => 'https://update-test-'.time().'.example.com',
        'certificate_check_enabled' => true,
    ]);

    $listener = app(\App\Listeners\UpdateMonitoringSummaries::class);

    // Create first result
    MonitoringResult::factory()->create([
        'monitor_id' => $testMonitor->id,
        'website_id' => $this->website->id,
        'started_at' => now(),
        'response_time_ms' => 100,
        'check_type' => 'both',
    ]);

    // Fire first event
    $event1 = new MonitoringCheckCompleted(
        monitor: $testMonitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: now()->subMinute(),
        completedAt: now(),
        checkResults: ['response_time_ms' => 100]
    );
    $listener->handle($event1);

    // Create second result in same hour
    MonitoringResult::factory()->create([
        'monitor_id' => $testMonitor->id,
        'website_id' => $this->website->id,
        'started_at' => now(),
        'response_time_ms' => 200,
        'check_type' => 'both',
    ]);

    $event2 = new MonitoringCheckCompleted(
        monitor: $testMonitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: now(),
        completedAt: now(),
        checkResults: ['response_time_ms' => 200]
    );
    $listener->handle($event2);

    // Should have ONE summary with updated stats
    $summaries = MonitoringCheckSummary::where('monitor_id', $testMonitor->id)
        ->where('summary_period', 'hourly')
        ->get();

    expect($summaries->count())->toBe(1);
    expect($summaries->first()->total_checks)->toBe(2);

    // Cleanup
    $testMonitor->delete();
});

test('calculates percentiles correctly', function () {
    // Create a test monitor to avoid interference
    $testMonitor = Monitor::create([
        'url' => 'https://percentiles-test-'.time().'.example.com',
        'certificate_check_enabled' => true,
    ]);

    // Create results with varying response times (wider range to ensure p99 > p95)
    foreach ([100, 200, 300, 400, 500, 600, 700, 800, 900, 1000, 1100, 1200, 1300, 1400, 1500] as $time) {
        MonitoringResult::factory()->create([
            'monitor_id' => $testMonitor->id,
            'website_id' => $this->website->id,
            'started_at' => now(),
            'response_time_ms' => $time,
            'check_type' => 'both',
        ]);
    }

    $event = new MonitoringCheckCompleted(
        monitor: $testMonitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: now(),
        completedAt: now(),
        checkResults: []
    );

    $listener = app(\App\Listeners\UpdateMonitoringSummaries::class);
    $listener->handle($event);

    $summary = MonitoringCheckSummary::where('monitor_id', $testMonitor->id)->first();

    expect($summary->p95_response_time_ms)->toBeGreaterThan($summary->average_response_time_ms);
    expect($summary->p99_response_time_ms)->toBeGreaterThanOrEqual($summary->p95_response_time_ms);

    // Cleanup
    $testMonitor->delete();
});

test('handles empty result set gracefully', function () {
    // Create a new monitor just for this test to avoid interference
    $testMonitor = Monitor::create([
        'url' => 'https://empty-test-'.time().'.example.com',
        'certificate_check_enabled' => true,
    ]);

    // Fire event without creating results first
    $event = new MonitoringCheckCompleted(
        monitor: $testMonitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: now(),
        completedAt: now(),
        checkResults: []
    );

    $listener = app(\App\Listeners\UpdateMonitoringSummaries::class);
    $listener->handle($event);

    // No summary should be created when there are no results
    $summaries = MonitoringCheckSummary::where('monitor_id', $testMonitor->id)->get();
    expect($summaries->count())->toBe(0);

    // Cleanup
    $testMonitor->delete();
});

test('aggregates SSL statistics correctly', function () {
    // Create a test monitor to avoid interference
    $testMonitor = Monitor::create([
        'url' => 'https://ssl-test-'.time().'.example.com',
        'certificate_check_enabled' => true,
    ]);

    // Create results with SSL data
    MonitoringResult::factory()->create([
        'monitor_id' => $testMonitor->id,
        'website_id' => $this->website->id,
        'started_at' => now(),
        'check_type' => 'ssl',
        'ssl_status' => 'valid',
        'days_until_expiration' => 90,
    ]);

    MonitoringResult::factory()->create([
        'monitor_id' => $testMonitor->id,
        'website_id' => $this->website->id,
        'started_at' => now(),
        'check_type' => 'ssl',
        'ssl_status' => 'expires_soon',
        'days_until_expiration' => 5,
    ]);

    $event = new MonitoringCheckCompleted(
        monitor: $testMonitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: now(),
        completedAt: now(),
        checkResults: []
    );

    $listener = app(\App\Listeners\UpdateMonitoringSummaries::class);
    $listener->handle($event);

    $summary = MonitoringCheckSummary::where('monitor_id', $testMonitor->id)->first();

    expect($summary->total_ssl_checks)->toBe(2);
    expect($summary->certificates_expiring)->toBe(1);

    // Cleanup
    $testMonitor->delete();
});

test('calculates uptime percentage correctly', function () {
    // Create a new monitor for this specific test to avoid interference
    $testMonitor = Monitor::create([
        'url' => 'https://uptime-test-'.time().'.example.com',
        'certificate_check_enabled' => true,
    ]);

    // Create exactly 7 successful and 3 failed uptime checks
    for ($i = 0; $i < 7; $i++) {
        MonitoringResult::factory()->create([
            'monitor_id' => $testMonitor->id,
            'website_id' => $this->website->id,
            'started_at' => now(),
            'check_type' => 'uptime',
            'uptime_status' => 'up',
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        MonitoringResult::factory()->create([
            'monitor_id' => $testMonitor->id,
            'website_id' => $this->website->id,
            'started_at' => now(),
            'check_type' => 'uptime',
            'uptime_status' => 'down',
        ]);
    }

    $event = new MonitoringCheckCompleted(
        monitor: $testMonitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: now(),
        completedAt: now(),
        checkResults: []
    );

    $listener = app(\App\Listeners\UpdateMonitoringSummaries::class);
    $listener->handle($event);

    $summary = MonitoringCheckSummary::where('monitor_id', $testMonitor->id)->first();

    expect($summary->total_uptime_checks)->toBe(10);
    expect($summary->successful_uptime_checks)->toBe(7);
    expect($summary->failed_uptime_checks)->toBe(3);
    expect((float) $summary->uptime_percentage)->toBe(70.0);

    // Cleanup
    $testMonitor->delete();
});
