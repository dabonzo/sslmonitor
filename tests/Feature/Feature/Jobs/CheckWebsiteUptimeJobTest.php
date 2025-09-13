<?php

declare(strict_types=1);

use App\Jobs\CheckWebsiteUptimeJob;
use App\Models\DowntimeIncident;
use App\Models\UptimeCheck;
use App\Models\User;
use App\Models\Website;
use App\Services\UptimeChecker;
use App\Services\UptimeCheckResult;
use App\Services\UptimeStatusCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'expected_status_code' => 200,
        'expected_content' => 'Welcome to our site',
        'forbidden_content' => 'Error 500',
        'max_response_time' => 5000,
        'follow_redirects' => true,
        'max_redirects' => 3,
    ]);
});

test('job can be created and queued', function () {
    Queue::fake();

    CheckWebsiteUptimeJob::dispatch($this->website);

    Queue::assertPushed(CheckWebsiteUptimeJob::class, function ($job) {
        return $job->website->id === $this->website->id;
    });
});

test('job processes successfully with up status', function () {
    $mockChecker = $this->mock(UptimeChecker::class);
    $mockCalculator = $this->mock(UptimeStatusCalculator::class);

    $checkResult = new UptimeCheckResult(
        status: 'up',
        httpStatusCode: 200,
        responseTime: 350,
        responseSize: 2048,
        contentCheckPassed: true
    );

    $mockChecker->shouldReceive('checkWebsite')
        ->with($this->website)
        ->once()
        ->andReturn($checkResult);

    $mockCalculator->shouldReceive('detectDowntimeIncident')
        ->with($this->website)
        ->once()
        ->andReturnNull(); // No incident for up status

    $mockCalculator->shouldReceive('calculateStatus')
        ->with($this->website)
        ->once()
        ->andReturn('up');

    $job = new CheckWebsiteUptimeJob($this->website);
    $job->handle($mockChecker, $mockCalculator);

    // Verify uptime check was stored
    expect(UptimeCheck::count())->toBe(1);

    $uptimeCheck = UptimeCheck::first();
    expect($uptimeCheck->website_id)->toBe($this->website->id);
    expect($uptimeCheck->status)->toBe('up');
    expect($uptimeCheck->http_status_code)->toBe(200);
    expect($uptimeCheck->response_time_ms)->toBe(350);
    expect($uptimeCheck->response_size_bytes)->toBe(2048);
    expect($uptimeCheck->content_check_passed)->toBeTrue();
    expect($uptimeCheck->content_check_error)->toBeNull();
    expect($uptimeCheck->error_message)->toBeNull();
    expect($uptimeCheck->checked_at)->not->toBeNull();
});

test('job processes successfully with down status', function () {
    $mockChecker = $this->mock(UptimeChecker::class);
    $mockCalculator = $this->mock(UptimeStatusCalculator::class);

    $checkResult = new UptimeCheckResult(
        status: 'down',
        httpStatusCode: 500,
        responseTime: 30000,
        errorMessage: 'Internal Server Error'
    );

    $mockChecker->shouldReceive('checkWebsite')
        ->with($this->website)
        ->once()
        ->andReturn($checkResult);

    // Mock incident creation for down status
    $incident = DowntimeIncident::factory()->make([
        'website_id' => $this->website->id,
        'incident_type' => 'http_error',
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $mockCalculator->shouldReceive('detectDowntimeIncident')
        ->with($this->website)
        ->once()
        ->andReturn($incident);

    $mockCalculator->shouldReceive('calculateStatus')
        ->with($this->website)
        ->once()
        ->andReturn('down');

    $job = new CheckWebsiteUptimeJob($this->website);
    $job->handle($mockChecker, $mockCalculator);

    // Verify uptime check was stored with error details
    expect(UptimeCheck::count())->toBe(1);

    $uptimeCheck = UptimeCheck::first();
    expect($uptimeCheck->website_id)->toBe($this->website->id);
    expect($uptimeCheck->status)->toBe('down');
    expect($uptimeCheck->http_status_code)->toBe(500);
    expect($uptimeCheck->response_time_ms)->toBe(30000);
    expect($uptimeCheck->error_message)->toBe('Internal Server Error');
    expect($uptimeCheck->content_check_passed)->toBeNull();
});

test('job processes successfully with slow status', function () {
    $mockChecker = $this->mock(UptimeChecker::class);
    $mockCalculator = $this->mock(UptimeStatusCalculator::class);

    $checkResult = new UptimeCheckResult(
        status: 'slow',
        httpStatusCode: 200,
        responseTime: 8500, // Exceeds 5000ms threshold
        responseSize: 1024,
        contentCheckPassed: true
    );

    $mockChecker->shouldReceive('checkWebsite')
        ->with($this->website)
        ->once()
        ->andReturn($checkResult);

    $mockCalculator->shouldReceive('detectDowntimeIncident')
        ->with($this->website)
        ->once()
        ->andReturnNull(); // Slow status might not create incident

    $job = new CheckWebsiteUptimeJob($this->website);
    $job->handle($mockChecker, $mockCalculator);

    // Verify slow status was recorded
    $uptimeCheck = UptimeCheck::first();
    expect($uptimeCheck->status)->toBe('slow');
    expect($uptimeCheck->response_time_ms)->toBe(8500);
    expect($uptimeCheck->content_check_passed)->toBeTrue();
});

test('job processes successfully with content mismatch status', function () {
    $mockChecker = $this->mock(UptimeChecker::class);
    $mockCalculator = $this->mock(UptimeStatusCalculator::class);

    $checkResult = new UptimeCheckResult(
        status: 'content_mismatch',
        httpStatusCode: 200,
        responseTime: 1200,
        responseSize: 2048,
        contentCheckPassed: false,
        contentCheckError: 'Expected content "Welcome to our site" not found. Forbidden content "Error 500" detected.'
    );

    $mockChecker->shouldReceive('checkWebsite')
        ->with($this->website)
        ->once()
        ->andReturn($checkResult);

    // Content mismatch should create incident
    $incident = DowntimeIncident::factory()->make([
        'website_id' => $this->website->id,
        'incident_type' => 'content_mismatch',
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $mockCalculator->shouldReceive('detectDowntimeIncident')
        ->with($this->website)
        ->once()
        ->andReturn($incident);

    $job = new CheckWebsiteUptimeJob($this->website);
    $job->handle($mockChecker, $mockCalculator);

    // Verify content mismatch was recorded
    $uptimeCheck = UptimeCheck::first();
    expect($uptimeCheck->status)->toBe('content_mismatch');
    expect($uptimeCheck->http_status_code)->toBe(200);
    expect($uptimeCheck->content_check_passed)->toBeFalse();
    expect($uptimeCheck->content_check_error)->toBe('Expected content "Welcome to our site" not found. Forbidden content "Error 500" detected.');
});

test('job handles website without uptime monitoring enabled', function () {
    $websiteNoUptime = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => false,
    ]);

    $mockChecker = $this->mock(UptimeChecker::class);
    $mockCalculator = $this->mock(UptimeStatusCalculator::class);

    // Should not call services if uptime monitoring is disabled
    $mockChecker->shouldNotReceive('checkWebsite');
    $mockCalculator->shouldNotReceive('detectDowntimeIncident');

    $job = new CheckWebsiteUptimeJob($websiteNoUptime);
    $job->handle($mockChecker, $mockCalculator);

    // No uptime check should be created
    expect(UptimeCheck::count())->toBe(0);
});

test('job handles service exceptions gracefully', function () {
    $mockChecker = $this->mock(UptimeChecker::class);
    $mockCalculator = $this->mock(UptimeStatusCalculator::class);

    // Simulate service throwing exception
    $mockChecker->shouldReceive('checkWebsite')
        ->with($this->website)
        ->once()
        ->andThrow(new \Exception('Network connection failed'));

    $mockCalculator->shouldNotReceive('detectDowntimeIncident');

    $job = new CheckWebsiteUptimeJob($this->website);

    // Job should handle exception and create error check record
    $job->handle($mockChecker, $mockCalculator);

    // Verify error check was recorded
    expect(UptimeCheck::count())->toBe(1);

    $uptimeCheck = UptimeCheck::first();
    expect($uptimeCheck->status)->toBe('down');
    expect($uptimeCheck->error_message)->toBe('Network connection failed');
    expect($uptimeCheck->http_status_code)->toBeNull();
    expect($uptimeCheck->response_time_ms)->toBeNull();
});

test('job updates website uptime status after check', function () {
    $mockChecker = $this->mock(UptimeChecker::class);
    $mockCalculator = $this->mock(UptimeStatusCalculator::class);

    $checkResult = new UptimeCheckResult(
        status: 'up',
        httpStatusCode: 200,
        responseTime: 450,
        contentCheckPassed: true
    );

    $mockChecker->shouldReceive('checkWebsite')
        ->with($this->website)
        ->once()
        ->andReturn($checkResult);

    // Mock calculator to return calculated status
    $mockCalculator->shouldReceive('detectDowntimeIncident')
        ->with($this->website)
        ->once()
        ->andReturnNull();

    $mockCalculator->shouldReceive('calculateStatus')
        ->with($this->website)
        ->once()
        ->andReturn('up');

    $job = new CheckWebsiteUptimeJob($this->website);
    $job->handle($mockChecker, $mockCalculator);

    // Verify website uptime status was updated
    $this->website->refresh();
    expect($this->website->uptime_status)->toBe('up');
    expect($this->website->last_uptime_check_at)->not->toBeNull();
});

test('job can handle incident creation and resolution', function () {
    $mockChecker = $this->mock(UptimeChecker::class);
    $mockCalculator = $this->mock(UptimeStatusCalculator::class);

    // First check - site goes down
    $downResult = new UptimeCheckResult(
        status: 'down',
        httpStatusCode: 500,
        errorMessage: 'Internal Server Error'
    );

    $newIncident = DowntimeIncident::factory()->create([
        'website_id' => $this->website->id,
        'incident_type' => 'http_error',
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $mockChecker->shouldReceive('checkWebsite')
        ->with($this->website)
        ->once()
        ->andReturn($downResult);

    $mockCalculator->shouldReceive('detectDowntimeIncident')
        ->with($this->website)
        ->once()
        ->andReturn($newIncident);

    $mockCalculator->shouldReceive('calculateStatus')
        ->with($this->website)
        ->once()
        ->andReturn('down');

    $job = new CheckWebsiteUptimeJob($this->website);
    $job->handle($mockChecker, $mockCalculator);

    // Verify incident was associated with check
    expect(UptimeCheck::count())->toBe(1);
    expect(DowntimeIncident::count())->toBe(1);

    $uptimeCheck = UptimeCheck::first();
    expect($uptimeCheck->status)->toBe('down');

    $incident = DowntimeIncident::first();
    expect($incident->website_id)->toBe($this->website->id);
    expect($incident->ended_at)->toBeNull();
});

test('job processes retries on transient failures', function () {
    $this->website->update(['uptime_monitoring' => true]);

    $job = new CheckWebsiteUptimeJob($this->website);

    // Verify job has retry configuration
    expect($job->tries)->toBe(3);
    expect($job->backoff())->toBe([30, 60, 120]); // Exponential backoff
});

test('job has proper queue configuration', function () {
    $job = new CheckWebsiteUptimeJob($this->website);

    // Test that job is configured properly
    expect($job->timeout)->toBe(120); // 2 minutes timeout
    expect($job->tries)->toBe(3);

    // Verify queue name via reflection since it's set in constructor
    $reflection = new ReflectionClass($job);
    $queueProperty = $reflection->getProperty('queue');
    $queueProperty->setAccessible(true);
    expect($queueProperty->getValue($job))->toBe('uptime-monitoring');
});
