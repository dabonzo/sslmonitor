<?php

use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Models\Website;
use App\Services\MonitoringHistoryService;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->service = app(MonitoringHistoryService::class);
    $this->monitor = Monitor::first();
    $this->website = Website::first();
});

test('getTrendData returns chart data for specified period', function () {
    // Arrange: Create test data across different days
    MonitoringResult::factory()->count(20)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'status' => 'success',
        'response_time_ms' => 150,
        'started_at' => now()->subDays(3),
    ]);

    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'status' => 'failed',
        'response_time_ms' => 300,
        'started_at' => now()->subDays(2),
    ]);

    // Act: Get trend data for 7 days
    $trendData = $this->service->getTrendData($this->monitor, '7d');

    // Assert: Verify structure and data (returns array with labels and datasets)
    expect($trendData)->toBeArray()
        ->toHaveKeys(['labels', 'datasets'])
        ->and($trendData['labels'])->toBeArray()
        ->and($trendData['datasets'])->toBeArray()
        ->and(count($trendData['datasets']))->toBe(2) // Success checks and response time
        ->and($trendData['datasets'][0])->toHaveKey('name', 'Successful Checks')
        ->and($trendData['datasets'][1])->toHaveKey('name', 'Response Time (ms)');
});

test('getTrendData handles different periods correctly', function () {
    // Arrange: Create recent data for hourly aggregation
    MonitoringResult::factory()->count(15)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 200,
        'started_at' => now()->subHours(2),
    ]);

    // Act: Test different periods
    $trendData7d = $this->service->getTrendData($this->monitor, '7d');
    $trendData30d = $this->service->getTrendData($this->monitor, '30d');

    // Assert: Different periods should work
    expect($trendData7d)->toBeArray()
        ->toHaveKeys(['labels', 'datasets'])
        ->and($trendData30d)->toBeArray()
        ->toHaveKeys(['labels', 'datasets']);
});

test('getRecentHistory returns limited results ordered by date', function () {
    // Arrange: Create test data
    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now()->subMinutes(5),
    ]);

    // Create older results
    MonitoringResult::factory()->count(50)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now()->subDays(10),
    ]);

    // Act: Get recent history with limit
    $recentHistory = $this->service->getRecentHistory($this->monitor, 20);

    // Assert: Verify results are limited and ordered correctly
    expect($recentHistory)->toHaveCount(20)
        ->and($recentHistory->first()->started_at->greaterThan($recentHistory->last()->started_at))->toBeTrue();
});

test('getRecentHistory respects custom limit', function () {
    // Arrange: Create test data
    MonitoringResult::factory()->count(100)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
    ]);

    // Act: Get with different limits
    $limited5 = $this->service->getRecentHistory($this->monitor, 5);
    $limited50 = $this->service->getRecentHistory($this->monitor, 50);

    // Assert: Verify limits are respected
    expect($limited5)->toHaveCount(5)
        ->and($limited50)->toHaveCount(50);
});

test('getSummaryStats calculates correct statistics', function () {
    // Arrange: Create test data with known values
    MonitoringResult::factory()->count(15)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'status' => 'success',
        'uptime_status' => 'up',
        'response_time_ms' => 200,
        'started_at' => now()->subDays(3),
    ]);

    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'status' => 'failed',
        'uptime_status' => 'down',
        'response_time_ms' => 1000,
        'started_at' => now()->subDays(2),
    ]);

    // Act: Get summary statistics
    $stats = $this->service->getSummaryStats($this->monitor, '7d');

    // Assert: Verify calculations with new field names
    expect($stats['total_checks'])->toBe(20)
        ->and($stats['successful_checks'])->toBe(15)
        ->and($stats['failed_checks'])->toBe(5)
        ->and($stats['uptime_percentage'])->toBe(75.0)
        ->and($stats['avg_response_time'])->toBeGreaterThanOrEqual(0)
        ->and($stats)->toHaveKeys(['period_start', 'period_end']);
});

test('getSummaryStats handles empty data gracefully', function () {
    // Arrange: No monitoring results for this monitor
    $newMonitor = Monitor::factory()->create();

    // Act: Get summary statistics
    $stats = $this->service->getSummaryStats($newMonitor, '7d');

    // Assert: Verify graceful handling of empty data with new field names
    expect($stats['total_checks'])->toBe(0)
        ->and($stats['successful_checks'])->toBe(0)
        ->and($stats['failed_checks'])->toBe(0)
        ->and($stats['uptime_percentage'])->toBe(0.0)
        ->and($stats['avg_response_time'])->toBe(0.0);
});

test('getUptimePercentage calculates correctly', function () {
    // Arrange: Create test data with mixed status (uptime_status doesn't matter, it uses status field)
    MonitoringResult::factory()->count(18)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'status' => 'success', // This is what uptimePercentage checks
        'started_at' => now()->subDays(5),
    ]);

    MonitoringResult::factory()->count(2)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'status' => 'failed', // This is what uptimePercentage checks
        'started_at' => now()->subDays(3),
    ]);

    // Act: Calculate uptime percentage
    $uptime = $this->service->getUptimePercentage($this->monitor, '7d');

    // Assert: Verify calculation
    expect($uptime)->toBe(90.0);
});

test('getUptimePercentage handles no data', function () {
    // Arrange: No monitoring results
    $newMonitor = Monitor::factory()->create();

    // Act: Calculate uptime percentage
    $uptime = $this->service->getUptimePercentage($newMonitor, '7d');

    // Assert: Should return 0 for no data
    expect($uptime)->toBe(0.0);
});

test('getResponseTimeTrend returns time series data', function () {
    // Arrange: Create test data with varying response times
    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 150,
        'started_at' => now()->subDays(1),
    ]);

    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 300,
        'started_at' => now()->subDays(2),
    ]);

    // Act: Get response time trend
    $responseTimeTrend = $this->service->getResponseTimeTrend($this->monitor, '7d');

    // Assert: Verify structure and data (returns array, not collection)
    expect($responseTimeTrend)->toBeArray()
        ->toHaveKeys(['labels', 'data', 'avg'])
        ->and($responseTimeTrend['labels'])->toBeArray()
        ->and($responseTimeTrend['data'])->toBeArray()
        ->and($responseTimeTrend['avg'])->toBeGreaterThan(0);
});

test('getResponseTimeTrend excludes null response times', function () {
    // Arrange: Create mixed data with and without response times
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 200,
        'started_at' => now()->subDays(1),
    ]);

    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => null, // These should be excluded
        'started_at' => now()->subDays(1),
    ]);

    // Act: Get response time trend
    $responseTimeTrend = $this->service->getResponseTimeTrend($this->monitor, '7d');

    // Assert: Should only include results with response times
    expect($responseTimeTrend)->toBeArray()
        ->toHaveKeys(['labels', 'data', 'avg'])
        ->and($responseTimeTrend['avg'])->toBe(200.0); // Average of the 5 results with 200ms
});

test('getSslExpirationTrend returns expiry data', function () {
    // Arrange: Create test data with SSL information
    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'certificate_expiration_date' => now()->addDays(60),
        'days_until_expiration' => 60,
        'started_at' => now()->subDays(1),
    ]);

    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'certificate_expiration_date' => now()->addDays(30),
        'days_until_expiration' => 30,
        'started_at' => now()->subDays(2),
    ]);

    // Act: Get SSL expiration trend
    $sslTrend = $this->service->getSslExpirationTrend($this->monitor, '7d');

    // Assert: Verify structure and data (returns array with specific structure)
    expect($sslTrend)->toBeArray()
        ->toHaveKeys([
            'current_expiration', 'days_until_expiration', 'issuer',
            'subject', 'historical_data'
        ])
        ->and($sslTrend['historical_data'])->toBeArray()
        ->and(count($sslTrend['historical_data']))->toBeGreaterThan(0);
});

test('getSslExpirationTrend excludes results without SSL data', function () {
    // Arrange: Create mixed SSL data
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'certificate_expiration_date' => now()->addDays(90),
        'days_until_expiration' => 90,
        'started_at' => now()->subDays(1),
    ]);

    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'certificate_expiration_date' => null,
        'days_until_expiration' => null,
        'started_at' => now()->subDays(1),
    ]);

    // Act: Get SSL expiration trend
    $sslTrend = $this->service->getSslExpirationTrend($this->monitor, '7d');

    // Assert: Should only include results with SSL data
    expect($sslTrend)->toBeArray()
        ->and($sslTrend['days_until_expiration'])->toBe(90) // Latest result should have 90 days
        ->and($sslTrend['historical_data'])->toHaveCount(5) // 5 SSL check results
        ->and($sslTrend['historical_data'][0]['days_until'])->toBe(90);
});

test('all service methods handle different time periods correctly', function () {
    // Arrange: Create test data across multiple periods
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 200,
        'started_at' => now()->subHours(12),
    ]);

    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 300,
        'started_at' => now()->subDays(5),
    ]);

    MonitoringResult::factory()->count(20)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 400,
        'started_at' => now()->subDays(20),
    ]);

    // Act: Test supported periods (7d, 30d)
    $stats7d = $this->service->getSummaryStats($this->monitor, '7d');
    $stats30d = $this->service->getSummaryStats($this->monitor, '30d');

    // Assert: Different periods should return different result counts
    expect($stats7d['total_checks'])->toBe(15)
        ->and($stats30d['total_checks'])->toBe(35);
});

test('service methods filter by correct date ranges', function () {
    // Arrange: Create test data with specific dates
    MonitoringResult::factory()->count(3)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 250,
        'started_at' => now()->subDays(3), // Within 7d period
    ]);

    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 350,
        'started_at' => now()->subDays(10), // Outside 7d period
    ]);

    // Act: Test that filtering works correctly
    $stats7d = $this->service->getSummaryStats($this->monitor, '7d');
    $stats30d = $this->service->getSummaryStats($this->monitor, '30d');

    // Assert: Different date ranges should include different amounts of data
    expect($stats7d['total_checks'])->toBe(3) // Only recent data
        ->and($stats30d['total_checks'])->toBe(8) // All data
        ->and($stats7d['successful_checks'] + $stats7d['failed_checks'])->toBe(3);
});