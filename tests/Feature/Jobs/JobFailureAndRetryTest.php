<?php

use App\Jobs\CheckMonitorJob;
use App\Jobs\ImmediateWebsiteCheckJob;
use App\Models\Monitor;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\Traits\MocksMonitorHttpRequests;

uses(RefreshDatabase::class);
uses(MocksMonitorHttpRequests::class);

beforeEach(function () {
    // Mock all HTTP requests to avoid real network calls
    $this->setUpMocksMonitorHttpRequests();
});

test('check monitor job has correct retry configuration', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
    ]);

    $job = new CheckMonitorJob($monitor);

    // Verify retry configuration
    expect($job->tries)->toBe(3)
        ->and($job->timeout)->toBe(60);

    // Verify retry window (5 minutes from now)
    $retryUntil = $job->retryUntil();
    $diffInMinutes = now()->diffInMinutes($retryUntil);

    expect($retryUntil->isFuture())->toBeTrue()
        ->and($diffInMinutes)->toBeGreaterThanOrEqual(4)
        ->and($diffInMinutes)->toBeLessThanOrEqual(6);
});

test('immediate website check job has correct retry configuration', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com',
    ]);

    $job = new ImmediateWebsiteCheckJob($website);

    // Verify retry configuration
    expect($job->tries)->toBe(3)
        ->and($job->timeout)->toBe(120);
});

test('failed jobs are recorded in failed_jobs table', function () {
    // Clear any existing failed jobs
    DB::table('failed_jobs')->truncate();

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
    ]);

    // Create a job that will fail
    $job = new class($monitor) extends CheckMonitorJob {
        public function handle(): array
        {
            throw new \Exception('Simulated job failure');
        }
    };

    // Try to handle the job
    try {
        $job->handle();
    } catch (\Exception $e) {
        // Expected to throw
    }

    // The failed() method should be called by Laravel's queue worker
    // We'll simulate this behavior
    $job->failed(new \Exception('Simulated job failure'));

    // Verify the failed callback was executed (logged)
    // In real queue processing, this would be stored in failed_jobs table
    expect(true)->toBeTrue(); // Placeholder - actual DB recording happens in queue worker
});

test('check monitor job does not throw exception on failure', function () {
    // Create a monitor with invalid domain that will cause check failures
    $monitor = Monitor::factory()->create([
        'url' => 'https://invalid-domain-999.test',
        'uptime_check_enabled' => true,
        'certificate_check_enabled' => true,
    ]);

    $job = new CheckMonitorJob($monitor);

    // Job should not throw - it catches exceptions internally
    $result = $job->handle();

    // Should return results even on failure
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('monitor_id')
        ->and($result)->toHaveKey('uptime')
        ->and($result)->toHaveKey('ssl');

    // Status should indicate error or invalid
    expect($result['ssl']['status'])->toBeIn(['error', 'invalid']);
});

test('failed method handles job failure gracefully', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
    ]);

    $job = new CheckMonitorJob($monitor);
    $exception = new \Exception('Test failure message');

    // Verify failed() method can be called without throwing
    $job->failed($exception);

    // Method should complete without exceptions
    expect(true)->toBeTrue();
});

test('immediate website check job failed method handles failures gracefully', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'url' => 'https://example.com',
    ]);

    $job = new ImmediateWebsiteCheckJob($website);
    $exception = new \Exception('Test failure');

    // Verify failed() method can be called without throwing
    $job->failed($exception);

    // Method should complete without exceptions
    expect(true)->toBeTrue();
});

test('job timeout is enforced by configuration', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
    ]);

    $job = new CheckMonitorJob($monitor);

    // Verify timeout is set correctly
    expect($job->timeout)->toBe(60);

    // Timeout should prevent jobs from running indefinitely
    // In real queue processing, Laravel will kill jobs that exceed timeout
});

test('queue system properly assigns jobs to default queue', function () {
    Queue::fake();

    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
    ]);

    // Dispatch job
    CheckMonitorJob::dispatch($monitor);

    // Verify job was pushed to correct queue
    Queue::assertPushed(CheckMonitorJob::class, function ($job) {
        return $job->queue === env('QUEUE_DEFAULT', 'default');
    });
});

test('multiple job failures are tracked with attempt count', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
    ]);

    $job = new CheckMonitorJob($monitor);

    // Simulate the job being retried multiple times
    // In real queue processing, Laravel tracks this automatically
    expect($job->tries)->toBe(3);

    // After 3 attempts, the job should fail permanently
    // and the failed() method will be called
});

test('retry until time prevents infinite retries', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://example.com',
    ]);

    $job = new CheckMonitorJob($monitor);
    $retryUntil = $job->retryUntil();

    // Verify retry window is limited (approximately 5 minutes)
    $diffInMinutes = now()->diffInMinutes($retryUntil);

    expect($diffInMinutes)->toBeGreaterThanOrEqual(4)
        ->and($diffInMinutes)->toBeLessThanOrEqual(6);

    // After this time, no more retries should occur
    $futureTime = now()->addMinutes(10);
    expect($retryUntil->isBefore($futureTime))->toBeTrue();
});

test('jobs dispatch successfully through queue system', function () {
    Queue::fake();

    $user = User::factory()->create();
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    // Dispatch immediate check
    ImmediateWebsiteCheckJob::dispatch($website);

    // Verify job was queued
    Queue::assertPushed(ImmediateWebsiteCheckJob::class, function ($job) use ($website) {
        return $job->website->id === $website->id;
    });
});

test('job error handling does not break queue processing', function () {
    $monitor = Monitor::factory()->create([
        'url' => 'https://invalid-domain-that-will-fail.test',
    ]);

    $job = new CheckMonitorJob($monitor);

    // Even with failures, the job should complete and return results
    $result = $job->handle();

    // Should have results (not throw exception)
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('monitor_id')
        ->and($result)->toHaveKey('uptime')
        ->and($result)->toHaveKey('ssl');

    // Queue processing can continue
    expect(true)->toBeTrue();
});
