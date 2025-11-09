<?php

use App\Models\Monitor;
use App\Models\MonitoringCheckSummary;
use App\Services\MonitoringCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('summary stats are cached for 1 hour', function () {
    $monitor = Monitor::factory()->create();
    MonitoringCheckSummary::factory()->create([
        'monitor_id' => $monitor->id,
        'summary_period' => 'daily',
        'uptime_percentage' => 95.00,
        'period_start' => now()->subDays(15),
    ]);

    $service = new MonitoringCacheService();

    // First call - should cache
    $result1 = $service->getSummaryStats($monitor, '30d');

    // Verify cache exists
    expect(Cache::has("monitor:{$monitor->id}:summary:30d"))->toBeTrue();

    // Second call - should use cache
    $result2 = $service->getSummaryStats($monitor, '30d');

    expect($result1)->toBe($result2);
    expect($result1['uptime_percentage'])->toBe(95.00);
});

test('cache is invalidated when summaries are updated', function () {
    $monitor = Monitor::factory()->create();
    $service = new MonitoringCacheService();

    // Create initial cache
    MonitoringCheckSummary::factory()->create([
        'monitor_id' => $monitor->id,
        'summary_period' => 'daily',
        'period_start' => now()->subDays(15),
    ]);

    $service->getSummaryStats($monitor, '30d');
    expect(Cache::has("monitor:{$monitor->id}:summary:30d"))->toBeTrue();

    // Invalidate cache
    $service->invalidateMonitorCaches($monitor->id);

    // Verify cache is cleared
    expect(Cache::has("monitor:{$monitor->id}:summary:30d"))->toBeFalse();
});

test('response time trend is cached for 10 minutes', function () {
    $monitor = Monitor::factory()->create();
    MonitoringCheckSummary::factory()->hourly()->create([
        'monitor_id' => $monitor->id,
        'summary_period' => 'hourly',
        'average_response_time_ms' => 150,
        'period_start' => now()->subHours(3),
    ]);

    $service = new MonitoringCacheService();
    $trend = $service->getResponseTimeTrend($monitor, '7d');

    expect(Cache::has("monitor:{$monitor->id}:response_trend:7d"))->toBeTrue();
    expect($trend)->toBeArray();
});

test('uptime percentage is cached for 5 minutes', function () {
    $monitor = Monitor::factory()->create();
    $periodStart = now()->subDays(5)->startOfDay();

    MonitoringCheckSummary::factory()->create([
        'monitor_id' => $monitor->id,
        'summary_period' => 'daily',
        'uptime_percentage' => 98.50,
        'period_start' => $periodStart,
        'period_end' => $periodStart->copy()->endOfDay(),
    ]);

    $service = new MonitoringCacheService();

    // First call - should cache
    $uptime1 = $service->getUptimePercentage($monitor, '7d');

    // Verify cache exists
    expect(Cache::has("monitor:{$monitor->id}:uptime:7d"))->toBeTrue();

    // Second call - should use cache
    $uptime2 = $service->getUptimePercentage($monitor, '7d');

    expect($uptime1)->toBe($uptime2);
    expect($uptime1)->toBe(98.50);
});

test('cache invalidation clears all period caches', function () {
    $monitor = Monitor::factory()->create();
    $service = new MonitoringCacheService();

    MonitoringCheckSummary::factory()->create([
        'monitor_id' => $monitor->id,
        'summary_period' => 'daily',
        'period_start' => now()->subDays(5),
    ]);

    // Create caches for multiple periods
    $service->getSummaryStats($monitor, '7d');
    $service->getSummaryStats($monitor, '30d');
    $service->getUptimePercentage($monitor, '7d');

    // Verify caches exist
    expect(Cache::has("monitor:{$monitor->id}:summary:7d"))->toBeTrue();
    expect(Cache::has("monitor:{$monitor->id}:summary:30d"))->toBeTrue();
    expect(Cache::has("monitor:{$monitor->id}:uptime:7d"))->toBeTrue();

    // Invalidate all caches
    $service->invalidateMonitorCaches($monitor->id);

    // Verify all caches are cleared
    expect(Cache::has("monitor:{$monitor->id}:summary:7d"))->toBeFalse();
    expect(Cache::has("monitor:{$monitor->id}:summary:30d"))->toBeFalse();
    expect(Cache::has("monitor:{$monitor->id}:uptime:7d"))->toBeFalse();
});

test('summary stats return correct data structure', function () {
    $monitor = Monitor::factory()->create();
    MonitoringCheckSummary::factory()->create([
        'monitor_id' => $monitor->id,
        'summary_period' => 'daily',
        'uptime_percentage' => 95.00,
        'average_response_time_ms' => 200,
        'total_checks' => 100,
        'successful_uptime_checks' => 95,
        'failed_uptime_checks' => 5,
        'period_start' => now()->subDays(10),
    ]);

    $service = new MonitoringCacheService();
    $stats = $service->getSummaryStats($monitor, '30d');

    expect($stats)->toHaveKeys([
        'uptime_percentage',
        'average_response_time',
        'total_checks',
        'successful_checks',
        'failed_checks',
    ]);

    expect($stats['uptime_percentage'])->toBe(95.00);
    expect($stats['average_response_time'])->toBe(200.00);
    expect($stats['total_checks'])->toBe(100);
});

test('response time trend returns correct format', function () {
    $monitor = Monitor::factory()->create();

    // Create hourly summaries
    MonitoringCheckSummary::factory()->hourly()->create([
        'monitor_id' => $monitor->id,
        'average_response_time_ms' => 150,
        'period_start' => now()->subHours(2),
    ]);

    MonitoringCheckSummary::factory()->hourly()->create([
        'monitor_id' => $monitor->id,
        'average_response_time_ms' => 200,
        'period_start' => now()->subHour(),
    ]);

    $service = new MonitoringCacheService();
    $trend = $service->getResponseTimeTrend($monitor, '7d');

    expect($trend)->toBeArray();
    expect($trend)->not()->toBeEmpty();

    foreach ($trend as $point) {
        expect($point)->toHaveKeys(['timestamp', 'avg_response_time']);
    }
});

test('different periods use different cache keys', function () {
    $monitor = Monitor::factory()->create();
    $service = new MonitoringCacheService();

    // Create summaries within 7 days (will be included in 7d period)
    MonitoringCheckSummary::factory()->create([
        'monitor_id' => $monitor->id,
        'summary_period' => 'daily',
        'uptime_percentage' => 95.00,
        'total_checks' => 100,
        'period_start' => now()->subDays(5),
    ]);

    // Create summary outside 7 days but within 30 days (only in 30d period)
    MonitoringCheckSummary::factory()->create([
        'monitor_id' => $monitor->id,
        'summary_period' => 'daily',
        'uptime_percentage' => 88.00,
        'total_checks' => 50,
        'period_start' => now()->subDays(20),
    ]);

    // Cache for different periods
    $stats7d = $service->getSummaryStats($monitor, '7d');
    $stats30d = $service->getSummaryStats($monitor, '30d');

    // Verify different cache keys exist
    expect(Cache::has("monitor:{$monitor->id}:summary:7d"))->toBeTrue();
    expect(Cache::has("monitor:{$monitor->id}:summary:30d"))->toBeTrue();

    // Verify they have different values (30d includes more data)
    expect($stats7d['total_checks'])->toBe(100);
    expect($stats30d['total_checks'])->toBe(150); // 100 + 50
});
