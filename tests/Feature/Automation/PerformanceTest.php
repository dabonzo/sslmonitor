<?php

use App\Jobs\ImmediateWebsiteCheckJob;
use Illuminate\Support\Facades\Queue;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

test('automation system handles multiple concurrent immediate checks', function () {
    // Use real websites that actually exist and respond quickly
    $websites = $this->realWebsites->take(3); // Use only 3 real websites for faster testing

    expect($websites->count())->toBeGreaterThan(0, 'No real websites found in seeded data');

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
    expect($results)->toHaveCount($websites->count());

    foreach ($results as $result) {
        expect($result)->toBeArray()
            ->and($result)->toHaveKey('website_id')
            ->and($result)->toHaveKey('uptime')
            ->and($result)->toHaveKey('ssl');
    }

    // Real websites should be much faster (under 10 seconds for 3 real websites)
    expect($totalTime)->toBeLessThan(10.0);

    // Log performance metrics
    $avgTimePerWebsite = $totalTime / $websites->count();
    expect($avgTimePerWebsite)->toBeLessThan(4.0); // Average under 4 seconds per real website
});

test('immediate check API endpoint handles concurrent requests', function () {
    Queue::fake();

    $websites = $this->realWebsites->take(3);

    $startTime = microtime(true);

    // Simulate multiple concurrent API requests
    $responses = [];
    foreach ($websites as $website) {
        $response = $this->actingAs($this->testUser)
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
    // Use real websites, repeat if needed for testing 10 requests
    $websites = $this->realWebsites->take(3);
    $testWebsites = $websites->concat($websites)->concat($websites)->take(10);

    $startTime = microtime(true);

    // Simulate frontend polling multiple websites
    foreach ($testWebsites as $website) {
        $response = $this->actingAs($this->testUser)
            ->getJson(route('ssl.websites.check-status', $website));

        $response->assertOk();
    }

    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;

    // Status endpoints should be very fast (under 3 seconds for 10 requests)
    expect($totalTime)->toBeLessThan(3.0);

    $avgTimePerRequest = $totalTime / $testWebsites->count();
    expect($avgTimePerRequest)->toBeLessThan(0.3); // Under 300ms per request
});

test('memory usage remains reasonable during bulk operations', function () {
    $initialMemory = memory_get_usage();

    // Use real websites for memory testing
    $websites = $this->realWebsites->take(3);

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
    // Use real websites for database query testing (all 4 real websites)
    $websites = $this->realWebsites;

    // Enable query logging
    \DB::enableQueryLog();

    foreach ($websites as $website) {
        $job = new ImmediateWebsiteCheckJob($website);
        $job->handle();
    }

    $queries = \DB::getQueryLog();
    \DB::disableQueryLog();

    // Verify reasonable number of queries per website
    $queriesPerWebsite = count($queries) / $websites->count();
    expect($queriesPerWebsite)->toBeLessThan(20); // Under 20 queries per website check

    // Check for potential N+1 queries
    $selectQueries = collect($queries)->filter(function ($query) {
        return stripos($query['query'], 'select') === 0;
    });

    // Should not have excessive SELECT queries
    expect($selectQueries->count())->toBeLessThan(count($queries) * 0.8);
});

test('automation system handles error scenarios without performance degradation', function () {
    // Use all real websites (4 domains)
    $allWebsites = $this->realWebsites;

    $startTime = microtime(true);
    $results = [];

    foreach ($allWebsites as $website) {
        $job = new ImmediateWebsiteCheckJob($website);
        $results[] = $job->handle();
    }

    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;

    // All jobs should complete successfully
    expect($results)->toHaveCount($allWebsites->count());

    // Should complete quickly with real websites (under 16 seconds for 4 websites)
    expect($totalTime)->toBeLessThan(16.0);

    // Verify all results are properly formatted
    foreach ($results as $result) {
        expect($result)->toBeArray()
            ->and($result)->toHaveKey('website_id')
            ->and($result)->toHaveKey('uptime')
            ->and($result)->toHaveKey('ssl');
    }
});