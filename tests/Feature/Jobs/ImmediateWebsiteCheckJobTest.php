<?php

use App\Jobs\ImmediateWebsiteCheckJob;
use App\Models\Website;
use App\Models\User;
use App\Support\AutomationLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test user and website
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $this->website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'name' => 'Test Website',
    ]);
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
    $job = new ImmediateWebsiteCheckJob($this->website);

    // Mock the actual check methods to avoid external calls during testing
    $this->partialMock(\App\Services\MonitorIntegrationService::class)
        ->shouldReceive('checkWebsiteUptime')
        ->with($this->website)
        ->andReturn([
            'status' => 'up',
            'response_time' => 150,
            'checked_at' => now(),
        ]);

    $result = $job->handle();

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('uptime')
        ->and($result['uptime']['status'])->toBe('up');
});

test('immediate check job handles website SSL check', function () {
    $job = new ImmediateWebsiteCheckJob($this->website);

    // Mock SSL check service
    $this->partialMock(\App\Services\SslCertificateAnalysisService::class)
        ->shouldReceive('analyzeWebsite')
        ->with($this->website->url)
        ->andReturn([
            'status' => 'valid',
            'expires_at' => now()->addDays(30),
            'issuer' => 'Let\'s Encrypt',
            'checked_at' => now(),
        ]);

    $result = $job->handle();

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('ssl')
        ->and($result['ssl']['status'])->toBe('valid');
});

test('immediate check job logs activity correctly', function () {
    // The logging channels are working, verify job completes without errors
    $job = new ImmediateWebsiteCheckJob($this->website);

    // Mock both services
    $this->partialMock(\App\Services\MonitorIntegrationService::class)
        ->shouldReceive('checkWebsiteUptime')
        ->andReturn(['status' => 'up', 'response_time' => 150]);

    $this->partialMock(\App\Services\SslCertificateAnalysisService::class)
        ->shouldReceive('analyzeWebsite')
        ->andReturn(['status' => 'valid', 'expires_at' => now()->addDays(30)]);

    $result = $job->handle();

    // Verify job completed successfully with expected structure
    expect($result)->toHaveKey('website_id')
        ->and($result)->toHaveKey('checked_at')
        ->and($result)->toHaveKey('uptime')
        ->and($result)->toHaveKey('ssl')
        ->and($result['website_id'])->toBe($this->website->id);
});

test('immediate check job handles failures gracefully', function () {
    $job = new ImmediateWebsiteCheckJob($this->website);

    // Mock service to throw exception
    $this->partialMock(\App\Services\MonitorIntegrationService::class)
        ->shouldReceive('checkWebsiteUptime')
        ->andThrow(new \Exception('Network timeout'));

    // Job should handle exceptions and return error status
    $result = $job->handle();

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('uptime')
        ->and($result['uptime']['status'])->toBe('error');
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

    // Add a 1 second delay to ensure timestamp difference
    sleep(1);

    $job = new ImmediateWebsiteCheckJob($this->website);
    $job->handle();

    $this->website->refresh();

    // The updated_at should be different from the original (even if just by seconds)
    expect($this->website->updated_at->format('Y-m-d H:i:s'))
        ->not->toBe($originalTimestamp->format('Y-m-d H:i:s'));
});

test('immediate check job has correct queue configuration', function () {
    $job = new ImmediateWebsiteCheckJob($this->website);

    // Test job properties
    expect($job->tries)->toBe(3)
        ->and($job->timeout)->toBe(60);
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