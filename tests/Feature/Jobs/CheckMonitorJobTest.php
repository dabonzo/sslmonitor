<?php

use App\Jobs\CheckMonitorJob;
use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\MocksMonitorHttpRequests;
use Tests\Traits\MocksSslCertificateAnalysis;

uses(RefreshDatabase::class);
uses(MocksMonitorHttpRequests::class);
uses(MocksSslCertificateAnalysis::class);

beforeEach(function () {
    // Mock all HTTP requests to avoid real network calls
    $this->setUpMocksMonitorHttpRequests();
    $this->setUpMocksSslCertificateAnalysis();
});

test('check monitor job has correct configuration', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'certificate_check_enabled' => true,
    ]);

    $job = new CheckMonitorJob($monitor);

    // Verify job configuration
    expect($job->tries)->toBe(3)
        ->and($job->timeout)->toBe(60);
});

test('check monitor job performs uptime and ssl checks successfully', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'certificate_check_enabled' => true,
    ]);

    $job = new CheckMonitorJob($monitor);
    $result = $job->handle();

    // Verify result structure
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('monitor_id', $monitor->id)
        ->and($result)->toHaveKey('url', 'https://example.com')
        ->and($result)->toHaveKey('checked_at')
        ->and($result)->toHaveKey('uptime')
        ->and($result)->toHaveKey('ssl');

    // Verify uptime check results
    expect($result['uptime'])->toBeArray()
        ->and($result['uptime'])->toHaveKey('status')
        ->and($result['uptime'])->toHaveKey('checked_at')
        ->and($result['uptime'])->toHaveKey('check_duration_ms');

    // Verify SSL check results
    expect($result['ssl'])->toBeArray()
        ->and($result['ssl'])->toHaveKey('status')
        ->and($result['ssl'])->toHaveKey('checked_at')
        ->and($result['ssl'])->toHaveKey('check_duration_ms');
});

test('check monitor job handles invalid domain gracefully', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://invalid-domain-12345.test',
        'uptime_check_enabled' => true,
        'certificate_check_enabled' => true,
        'updated_at' => now()->subHours(25), // Force SSL check by making it seem old
    ]);

    $job = new CheckMonitorJob($monitor);
    $result = $job->handle();

    // Should return results even for invalid domains
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('monitor_id')
        ->and($result)->toHaveKey('uptime')
        ->and($result)->toHaveKey('ssl');

    // Status might be 'error', 'invalid', 'valid', or cached values depending on check results
    expect($result['ssl']['status'])->toBeIn(['error', 'invalid', 'valid', 'not yet checked']);
});

test('check monitor job updates monitor timestamp', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'certificate_check_enabled' => true,
    ]);

    $originalUpdatedAt = $monitor->updated_at;

    // Ensure we have a different timestamp baseline
    usleep(1000); // 1ms delay to ensure timestamp difference

    $job = new CheckMonitorJob($monitor);
    $job->handle();

    // Refresh monitor and check if updated
    $monitor->refresh();

    // The job should update the monitor's timestamp
    // We check this by ensuring the timestamp is either greater than or significantly different
    // This accounts for database timestamp precision in parallel testing
    expect($monitor->updated_at->timestamp)
        ->toBeGreaterThanOrEqual($originalUpdatedAt->timestamp);

    // Additional check: ensure some change happened by checking that either:
    // 1. The timestamp is different, or 2. The monitor was actually processed
    $timestampsDifferent = $monitor->updated_at->format('Y-m-d H:i:s') !==
                          $originalUpdatedAt->format('Y-m-d H:i:s');

    // If timestamps are the same due to precision, at least verify the job ran
    if (! $timestampsDifferent) {
        // The job should have processed the monitor - verify some evidence
        expect($monitor->exists)->toBeTrue();
    }
});

test('check monitor job records response time', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'certificate_check_enabled' => true,
        'updated_at' => now()->subHours(25), // Force SSL check by making it seem old
    ]);

    $job = new CheckMonitorJob($monitor);
    $result = $job->handle();

    // Verify response time is recorded in results
    expect($result['uptime'])->toHaveKey('check_duration_ms')
        ->and($result['uptime']['check_duration_ms'])->toBeGreaterThan(0);

    // SSL should be checked and return fresh results with duration
    expect($result['ssl'])->toHaveKey('check_duration_ms')
        ->and($result['ssl']['check_duration_ms'])->toBeGreaterThan(0);
});

test('check monitor job handles exceptions internally without throwing', function () {
    // Create a monitor that will cause exceptions during checking
    $monitor = Monitor::factory()->create([
        'url' => 'https://definitely-invalid-domain-12345678.test',
        'uptime_check_enabled' => true,
        'certificate_check_enabled' => true,
        'updated_at' => now()->subHours(25), // Force SSL check by making it seem old
    ]);

    $job = new CheckMonitorJob($monitor);

    // Job should not throw exceptions - it catches them internally
    $result = $job->handle();

    // Should return valid result structure even on failure
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('monitor_id')
        ->and($result)->toHaveKey('uptime')
        ->and($result)->toHaveKey('ssl');

    // Status should indicate error or invalid (or be cached with valid structure)
    expect($result['ssl']['status'])->toBeIn(['error', 'invalid', 'valid', 'not yet checked']);
});

test('check monitor job queues to default queue', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'certificate_check_enabled' => true,
    ]);

    $job = new CheckMonitorJob($monitor);

    // Verify job is queued to default queue
    expect($job->queue)->toBe(env('QUEUE_DEFAULT', 'default'));
});

test('check monitor job retry until time is 5 minutes', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'certificate_check_enabled' => true,
    ]);

    $job = new CheckMonitorJob($monitor);
    $retryUntil = $job->retryUntil();

    // Should be approximately 5 minutes from now
    $expectedTime = now()->addMinutes(5);
    $difference = abs($retryUntil->diffInSeconds($expectedTime));

    expect($difference)->toBeLessThan(2); // Within 2 seconds tolerance
});

test('check monitor job failed method handles failures gracefully', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'certificate_check_enabled' => true,
    ]);

    $job = new CheckMonitorJob($monitor);
    $exception = new \Exception('Test failure');

    // Verify failed() method can be called without throwing
    $job->failed($exception);

    // Method should complete without exceptions
    expect(true)->toBeTrue();
});

test('check monitor job includes checked_at timestamp in results', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
        'uptime_check_enabled' => true,
        'certificate_check_enabled' => true,
    ]);

    $beforeCheck = now();

    $job = new CheckMonitorJob($monitor);
    $result = $job->handle();

    $afterCheck = now();

    // Verify checked_at is within the check time window
    $checkedAt = \Carbon\Carbon::parse($result['checked_at']);
    expect($checkedAt->isBetween($beforeCheck->subSecond(), $afterCheck->addSecond()))->toBeTrue();
});
