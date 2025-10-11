<?php

use App\Jobs\CheckMonitorJob;
use App\Jobs\ImmediateWebsiteCheckJob;
use App\Models\Monitor;
use App\Models\User;
use App\Models\Website;
use App\Services\MonitorIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Traits\MocksMonitorHttpRequests;

uses(RefreshDatabase::class);
uses(MocksMonitorHttpRequests::class);

beforeEach(function () {
    // Mock all HTTP requests to avoid real network calls
    $this->setUpMocksMonitorHttpRequests();
});

test('check monitor job records response time in results', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
    ]);

    $job = new CheckMonitorJob($monitor);
    $result = $job->handle();

    // Verify uptime check duration is recorded
    expect($result['uptime'])->toHaveKey('check_duration_ms')
        ->and($result['uptime']['check_duration_ms'])->toBeNumeric()
        ->and($result['uptime']['check_duration_ms'])->toBeGreaterThan(0);

    // Verify SSL check duration is recorded
    expect($result['ssl'])->toHaveKey('check_duration_ms')
        ->and($result['ssl']['check_duration_ms'])->toBeNumeric()
        ->and($result['ssl']['check_duration_ms'])->toBeGreaterThan(0);
});

test('monitor stores uptime response time after check', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'uptime_check_response_time_in_ms' => null,
    ]);

    expect($monitor->uptime_check_response_time_in_ms)->toBeNull();

    // Perform check
    $job = new CheckMonitorJob($monitor);
    $job->handle();

    // Refresh monitor
    $monitor->refresh();

    // Response time should now be recorded by Spatie Monitor
    // Note: uptime_check_response_time_in_ms is updated by Spatie's MonitorCollection
    expect($monitor->uptime_check_response_time_in_ms)->not()->toBeNull();
});

test('monitor uptime_check_response_time_in_ms is updated after check', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'uptime_check_response_time_in_ms' => null,
    ]);

    // Perform check
    $job = new CheckMonitorJob($monitor);
    $job->handle();

    // Refresh monitor
    $monitor->refresh();

    // Response time field should be updated by Spatie's uptime check
    expect($monitor->uptime_check_response_time_in_ms)->not()->toBeNull();
});

test('response time tracking works through immediate website check', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => true,
    ]);

    // Sync website to create monitor
    $monitorService = app(MonitorIntegrationService::class);
    $monitor = $monitorService->createOrUpdateMonitorForWebsite($website);

    // Perform immediate check
    $job = new ImmediateWebsiteCheckJob($website);
    $result = app()->call([$job, 'handle']);

    // Verify response time is in results
    expect($result['uptime'])->toHaveKey('response_time')
        ->and($result['uptime']['response_time'])->toBeNumeric();
});

test('response time is reasonable for real websites', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
    ]);

    $job = new CheckMonitorJob($monitor);
    $result = $job->handle();

    // Response time should be under 10 seconds for example.com
    $checkDuration = $result['uptime']['check_duration_ms'];
    expect($checkDuration)->toBeLessThan(10000); // 10 seconds in milliseconds
});

test('response time tracking handles slow websites', function () {
    // Use a slow-responding test URL if available
    $monitor = Monitor::factory()->create([
        'url' => 'https://httpbin.org/delay/2', // 2 second delay
        'uptime_check_enabled' => true,
    ]);

    $startTime = microtime(true);

    $job = new CheckMonitorJob($monitor);
    $result = $job->handle();

    $totalTime = (microtime(true) - $startTime) * 1000; // Convert to ms

    // Check duration should reflect the delay
    expect($result['uptime']['check_duration_ms'])->toBeGreaterThan(1000) // At least 1 second
        ->and($result['uptime']['check_duration_ms'])->toBeLessThan($totalTime + 1000); // Reasonable overhead
})->skip('Requires external HTTP delay service');

test('response time tracking handles failed requests', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://invalid-domain-12345.test',
        'uptime_check_enabled' => true,
    ]);

    $job = new CheckMonitorJob($monitor);
    $result = $job->handle();

    // Even failed checks should record duration
    expect($result['uptime'])->toHaveKey('check_duration_ms')
        ->and($result['uptime']['check_duration_ms'])->toBeGreaterThan(0);
});

test('multiple checks update response time history', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
    ]);

    // First check
    $job1 = new CheckMonitorJob($monitor);
    $result1 = $job1->handle();
    $firstResponseTime = $result1['uptime']['check_duration_ms'];

    $monitor->refresh();
    $firstStoredTime = $monitor->uptime_check_response_time_in_ms;

    // Wait a moment
    sleep(1);

    // Second check
    $job2 = new CheckMonitorJob($monitor);
    $result2 = $job2->handle();
    $secondResponseTime = $result2['uptime']['check_duration_ms'];

    $monitor->refresh();
    $secondStoredTime = $monitor->uptime_check_response_time_in_ms;

    // Both checks should record times
    expect($firstResponseTime)->toBeGreaterThan(0)
        ->and($secondResponseTime)->toBeGreaterThan(0);

    // Stored times should be updated
    expect($secondStoredTime)->not()->toBe($firstStoredTime);
});

test('response time includes SSL check duration', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'certificate_check_enabled' => true,
    ]);

    $job = new CheckMonitorJob($monitor);
    $result = $job->handle();

    // Both checks should report duration
    expect($result['uptime'])->toHaveKey('check_duration_ms')
        ->and($result['uptime']['check_duration_ms'])->toBeGreaterThan(0);

    expect($result['ssl'])->toHaveKey('check_duration_ms')
        ->and($result['ssl']['check_duration_ms'])->toBeGreaterThan(0);
});

test('response time persists across job executions', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
    ]);

    // First execution
    $job1 = new CheckMonitorJob($monitor);
    $job1->handle();

    $monitor->refresh();
    $responseTime1 = $monitor->uptime_check_response_time_in_ms;

    // Create new job instance (simulating queue worker behavior)
    $monitorFresh = Monitor::find($monitor->id);
    $job2 = new CheckMonitorJob($monitorFresh);
    $job2->handle();

    $monitorFresh->refresh();
    $responseTime2 = $monitorFresh->uptime_check_response_time_in_ms;

    // Both should have response times recorded
    expect($responseTime1)->not()->toBeNull()
        ->and($responseTime2)->not()->toBeNull();
});

test('average response time can be calculated from monitors', function () {
    // Create multiple monitors with checks
    $monitors = Monitor::factory()->count(3)->create([
        'uptime_check_enabled' => true,
    ]);

    foreach ($monitors as $monitor) {
        $job = new CheckMonitorJob($monitor);
        $job->handle();
    }

    // Calculate average response time
    $avgResponseTime = Monitor::whereNotNull('uptime_check_response_time_in_ms')
        ->avg('uptime_check_response_time_in_ms');

    expect($avgResponseTime)->toBeNumeric()
        ->and($avgResponseTime)->toBeGreaterThan(0);
});
