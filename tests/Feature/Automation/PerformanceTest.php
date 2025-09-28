<?php

use App\Models\Website;
use App\Models\User;
use App\Jobs\ImmediateWebsiteCheckJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'performance@example.com',
    ]);
});

test('automation system handles multiple concurrent immediate checks', function () {
    // Create multiple websites for concurrent testing
    $websites = Website::factory()->count(5)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $startTime = microtime(true);
    $results = [];

    // Process multiple jobs concurrently (simulating queue processing)
    foreach ($websites as $website) {
        $job = new ImmediateWebsiteCheckJob($website);
        $results[] = $job->handle();
    }

    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;

    // Verify all jobs completed successfully
    expect($results)->toHaveCount(5);

    foreach ($results as $result) {
        expect($result)->toBeArray()
            ->and($result)->toHaveKey('website_id')
            ->and($result)->toHaveKey('uptime')
            ->and($result)->toHaveKey('ssl');
    }

    // Verify total execution time is reasonable (under 60 seconds for 5 websites)
    expect($totalTime)->toBeLessThan(60.0);

    // Log performance metrics
    $avgTimePerWebsite = $totalTime / 5;
    expect($avgTimePerWebsite)->toBeLessThan(12.0); // Average under 12 seconds per website
});

test('immediate check API endpoint handles concurrent requests', function () {
    Queue::fake();

    $websites = Website::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $startTime = microtime(true);

    // Simulate multiple concurrent API requests
    $responses = [];
    foreach ($websites as $website) {
        $response = $this->actingAs($this->user)
            ->postJson(route('ssl.websites.immediate-check', $website));

        $responses[] = $response;
    }

    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;

    // Verify all requests succeeded
    foreach ($responses as $response) {
        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    // Verify all jobs were queued
    Queue::assertPushed(ImmediateWebsiteCheckJob::class, 3);

    // API should respond quickly (under 5 seconds for 3 requests)
    expect($totalTime)->toBeLessThan(5.0);
});

test('status polling endpoints perform efficiently', function () {
    $websites = Website::factory()->count(10)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $startTime = microtime(true);

    // Simulate frontend polling multiple websites
    foreach ($websites as $website) {
        $response = $this->actingAs($this->user)
            ->getJson(route('ssl.websites.check-status', $website));

        $response->assertOk();
    }

    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;

    // Status endpoints should be very fast (under 3 seconds for 10 requests)
    expect($totalTime)->toBeLessThan(3.0);

    $avgTimePerRequest = $totalTime / 10;
    expect($avgTimePerRequest)->toBeLessThan(0.3); // Under 300ms per request
});

test('memory usage remains reasonable during bulk operations', function () {
    $initialMemory = memory_get_usage();

    // Create websites but use a smaller subset for actual processing
    $websites = Website::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $jobs = [];
    foreach ($websites as $website) {
        $jobs[] = new ImmediateWebsiteCheckJob($website);
    }

    $beforeProcessing = memory_get_usage();

    // Process fewer jobs to reduce test time while still testing memory usage
    foreach ($jobs as $job) {
        $job->handle();
    }

    $afterProcessing = memory_get_usage();
    $peakMemory = memory_get_peak_usage();

    // Memory usage should not exceed reasonable limits
    $memoryIncrease = $afterProcessing - $initialMemory;
    $memoryIncreaseInMB = $memoryIncrease / 1024 / 1024;

    // Should not use more than 30MB additional memory for 3 website checks
    expect($memoryIncreaseInMB)->toBeLessThan(30);

    // Peak memory should be reasonable
    $peakMemoryInMB = $peakMemory / 1024 / 1024;
    expect($peakMemoryInMB)->toBeLessThan(100);
});

test('database queries remain efficient during automation', function () {
    $websites = Website::factory()->count(5)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    // Enable query logging
    \DB::enableQueryLog();

    foreach ($websites as $website) {
        $job = new ImmediateWebsiteCheckJob($website);
        $job->handle();
    }

    $queries = \DB::getQueryLog();
    \DB::disableQueryLog();

    // Verify reasonable number of queries per website
    $queriesPerWebsite = count($queries) / 5;
    expect($queriesPerWebsite)->toBeLessThan(20); // Under 20 queries per website check

    // Check for potential N+1 queries
    $selectQueries = collect($queries)->filter(function ($query) {
        return stripos($query['query'], 'select') === 0;
    });

    // Should not have excessive SELECT queries
    expect($selectQueries->count())->toBeLessThan(count($queries) * 0.8);
});

test('automation system handles error scenarios without performance degradation', function () {
    // Mix of valid and invalid websites
    $validWebsites = collect(range(1, 3))->map(function ($i) {
        return Website::factory()->create([
            'user_id' => $this->user->id,
            'url' => "https://example{$i}.com",
            'uptime_monitoring_enabled' => true,
            'ssl_monitoring_enabled' => true,
        ]);
    });

    $invalidWebsites = collect(range(1, 2))->map(function ($i) {
        return Website::factory()->create([
            'user_id' => $this->user->id,
            'url' => "https://invalid-domain-{$i}-12345.test",
            'uptime_monitoring_enabled' => true,
            'ssl_monitoring_enabled' => true,
        ]);
    });

    $allWebsites = $validWebsites->concat($invalidWebsites);

    $startTime = microtime(true);
    $results = [];

    foreach ($allWebsites as $website) {
        $job = new ImmediateWebsiteCheckJob($website);
        $results[] = $job->handle();
    }

    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;

    // All jobs should complete (even with errors)
    expect($results)->toHaveCount(5);

    // Should not take significantly longer due to errors
    expect($totalTime)->toBeLessThan(45.0);

    // Verify error handling doesn't cause failures
    foreach ($results as $result) {
        expect($result)->toBeArray()
            ->and($result)->toHaveKey('website_id');
    }
});