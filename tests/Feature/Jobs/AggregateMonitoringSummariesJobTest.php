<?php

use App\Jobs\AggregateMonitoringSummariesJob;
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

test('aggregates daily statistics correctly', function () {
    // Arrange: Create test data for today
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now(),
        'status' => 'success',
        'uptime_status' => 'up',
    ]);

    // Act: Run aggregation
    $job = new AggregateMonitoringSummariesJob('daily');
    $job->handle();

    // Assert: Verify summary was created
    $summary = MonitoringCheckSummary::where('monitor_id', $this->monitor->id)
        ->where('summary_period', 'daily')
        ->first();

    expect($summary)->not->toBeNull()
        ->and($summary->total_checks)->toBe(5)
        ->and($summary->successful_uptime_checks)->toBe(5)
        ->and($summary->website_id)->toBe($this->website->id);
});

test('handles multiple periods correctly', function () {
    // Arrange: Create test data
    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now(),
    ]);

    // Act & Assert: Test each period
    foreach (['hourly', 'daily', 'weekly', 'monthly'] as $period) {
        $job = new AggregateMonitoringSummariesJob($period);
        $job->handle();

        $summary = MonitoringCheckSummary::where('monitor_id', $this->monitor->id)
            ->where('summary_period', $period)
            ->first();

        expect($summary)->not->toBeNull()
            ->and($summary->total_checks)->toBe(10)
            ->and($summary->summary_period)->toBe($period);
    }
});
