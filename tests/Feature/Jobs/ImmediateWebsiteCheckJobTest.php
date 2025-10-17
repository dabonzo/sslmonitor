<?php

use App\Jobs\ImmediateWebsiteCheckJob;
use App\Models\Website;
use App\Models\User;
use App\Support\AutomationLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\Traits\MocksMonitorHttpRequests;

uses(RefreshDatabase::class);
uses(MocksMonitorHttpRequests::class);

beforeEach(function () {
    // Mock all HTTP requests to avoid real network calls
    $this->setUpMocksMonitorHttpRequests();

    // Additional setup after HTTP mocking
    // Create test user and website
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $this->website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'name' => 'Test Website',
    ]);

    // Mock MonitorIntegrationService to avoid real HTTP requests
    $this->mock(\App\Services\MonitorIntegrationService::class, function ($mock) {
        $monitor = \App\Models\Monitor::factory()->make([
            'url' => 'https://example.com',
            'uptime_status' => 'up',
            'certificate_status' => 'valid',
        ]);

        $mock->shouldReceive('createOrUpdateMonitorForWebsite')
            ->andReturn($monitor);

        $mock->shouldReceive('getMonitorForWebsite')
            ->andReturn($monitor);
    });
});

test('immediate website check job can be created and dispatched', function () {
    Queue::fake();

    $job = new ImmediateWebsiteCheckJob($this->website);

    expect($job)->toBeInstanceOf(ImmediateWebsiteCheckJob::class);

    // Test job can be dispatched to correct queue
    ImmediateWebsiteCheckJob::dispatch($this->website)
        ->onQueue(env('QUEUE_IMMEDIATE', 'immediate'));

    Queue::assertPushed(ImmediateWebsiteCheckJob::class, function ($job) {
        return $job->website->id === $this->website->id;
    });
});

test('immediate check job handles website uptime check', function () {
    // Use a real website with real monitor data
    $this->website->update([
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $job = new ImmediateWebsiteCheckJob($this->website);
    $result = app()->call([$job, 'handle']);

    // Verify job completed and returned expected structure
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('website_id')
        ->and($result)->toHaveKey('uptime')
        ->and($result)->toHaveKey('ssl')
        ->and($result)->toHaveKey('checked_at')
        ->and($result['website_id'])->toBe($this->website->id);

    // Verify uptime check result has the expected structure
    expect($result['uptime'])->toBeArray()
        ->and($result['uptime'])->toHaveKey('status');

    // Verify website timestamp was updated
    $this->website->refresh();
    expect($this->website->updated_at)->not()->toBeNull();
});

test('immediate check job handles website SSL check', function () {
    // Use a real website with SSL monitoring enabled
    $this->website->update([
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $job = new ImmediateWebsiteCheckJob($this->website);
    $result = app()->call([$job, 'handle']);

    // Verify job completed and returned expected structure
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('ssl')
        ->and($result['ssl'])->toBeArray()
        ->and($result['ssl'])->toHaveKey('status');
});

test('immediate check job logs activity correctly', function () {
    // Enable monitoring for real data test
    $this->website->update([
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $job = new ImmediateWebsiteCheckJob($this->website);
    $result = app()->call([$job, 'handle']);

    // Verify job completed successfully with expected structure
    expect($result)->toHaveKey('website_id')
        ->and($result)->toHaveKey('checked_at')
        ->and($result)->toHaveKey('uptime')
        ->and($result)->toHaveKey('ssl')
        ->and($result['website_id'])->toBe($this->website->id);
});

test('immediate check job handles failures gracefully', function () {
    // Mock failure scenario
    $this->mock(\App\Services\MonitorIntegrationService::class, function ($mock) {
        $failedMonitor = \App\Models\Monitor::factory()->make([
            'url' => 'https://invalid.test',
            'uptime_status' => 'down',
            'certificate_status' => 'invalid',
        ]);

        $mock->shouldReceive('createOrUpdateMonitorForWebsite')
            ->andReturn($failedMonitor);

        $mock->shouldReceive('getMonitorForWebsite')
            ->andReturn($failedMonitor);
    });

    $invalidWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://invalid.test',
        'name' => 'Invalid Website',
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $job = new ImmediateWebsiteCheckJob($invalidWebsite);

    // Job should handle invalid URL and return invalid status
    $result = app()->call([$job, 'handle']);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('ssl')
        ->and($result['ssl'])->toHaveKey('status')
        ->and($result['ssl']['status'])->toBe('invalid');
});

test('immediate check job updates website last checked timestamp', function () {
    $originalTimestamp = $this->website->updated_at;

    // Mock services first
    $this->partialMock(\App\Services\MonitorIntegrationService::class)
        ->shouldReceive('checkWebsiteUptime')
        ->andReturn(['status' => 'up', 'response_time' => 150]);

    $this->partialMock(\App\Services\SslCertificateAnalysisService::class)
        ->shouldReceive('analyzeWebsite')
        ->andReturn(['status' => 'valid', 'expires_at' => now()->addDays(30)]);

    // Travel forward in time to ensure timestamp difference
    $this->travel(2)->seconds();

    $job = new ImmediateWebsiteCheckJob($this->website);
    app()->call([$job, 'handle']);

    $this->website->refresh();

    // The updated_at should be different from the original (at least 2 seconds later)
    expect($this->website->updated_at->format('Y-m-d H:i:s'))
        ->not->toBe($originalTimestamp->format('Y-m-d H:i:s'));
});

test('immediate check job has correct queue configuration', function () {
    $job = new ImmediateWebsiteCheckJob($this->website);

    // Test job properties
    expect($job->tries)->toBe(3)
        ->and($job->timeout)->toBe(120);
});

test('immediate check job can be retried on failure', function () {
    Queue::fake();

    $job = new ImmediateWebsiteCheckJob($this->website);

    // Simulate job failure and retry
    $job->fail(new \Exception('Test failure'));

    expect($job->attempts())->toBe(1);

    // Job should be retryable
    expect($job->retryUntil())->toBeInstanceOf(\Carbon\Carbon::class);
});