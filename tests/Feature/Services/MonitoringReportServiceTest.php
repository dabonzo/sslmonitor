<?php

use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Models\Website;
use App\Services\MonitoringReportService;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->service = app(MonitoringReportService::class);
    $this->monitor = Monitor::first();
    $this->website = Website::first();
});

test('generates CSV export with correct format', function () {
    // Arrange: Create test data
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now(),
    ]);

    // Act: Generate CSV export
    $csv = $this->service->generateCsvExport($this->monitor, now()->subDay(), now());

    // Assert: Verify CSV structure
    expect($csv)->toContain('Timestamp,Status,Uptime Status');
    expect(substr_count($csv, "\n"))->toBe(6); // Header + 5 rows
});

test('summary report calculates statistics correctly', function () {
    // Arrange: Create successful test data
    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'status' => 'success',
        'uptime_status' => 'up',
        'started_at' => now(),
    ]);

    // Act: Get summary report
    $report = $this->service->getSummaryReport($this->monitor, '30d');

    // Assert: Verify all statistics are calculated correctly
    expect($report['total_checks'])->toBe(10)
        ->and($report['success_count'])->toBe(10)
        ->and($report['failure_count'])->toBe(0)
        ->and($report['uptime_percentage'])->toBe(100.0);
});
