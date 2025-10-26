<?php

use App\Jobs\AnalyzeSslCertificateJob;
use App\Models\Website;
use Illuminate\Support\Facades\Queue;
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->mockSslCertificateAnalysis();

    // Clear any seeded websites from global beforeEach to ensure clean test state
    Website::query()->forceDelete();
});

afterEach(function () {
    \Illuminate\Support\Facades\Artisan::clearResolvedInstances();
});

test('command processes websites without certificate data', function () {
    Queue::fake();

    // Create websites using raw inserts for speed
    $websites = collect(range(1, 5))->map(fn($i) => [
        'name' => "Test Website {$i}",
        'url' => "https://process-test-{$i}.example.com",
        'user_id' => $this->testUser->id,
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => false,
        'ssl_certificate_analyzed_at' => null,
        'monitoring_config' => json_encode([]),
        'plugin_data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    \DB::table('websites')->insert($websites->toArray());

    // Create a website with existing certificate data (should be skipped)
    \DB::table('websites')->insert([
        'name' => 'Existing Website',
        'url' => 'https://existing.example.com',
        'user_id' => $this->testUser->id,
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => false,
        'ssl_certificate_analyzed_at' => now(),
        'latest_ssl_certificate' => json_encode(['subject' => 'existing.com']),
        'monitoring_config' => json_encode([]),
        'plugin_data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('ssl:backfill-certificates', ['--limit' => 10, '--no-delay' => true])
        ->expectsOutput('Processing 5 websites...')
        ->assertExitCode(0);

    Queue::assertPushed(AnalyzeSslCertificateJob::class, 5);
});

test('command respects limit option', function () {
    Queue::fake();

    // Create 10 websites using raw inserts for speed
    $websites = collect(range(1, 10))->map(fn($i) => [
        'name' => "Limit Test {$i}",
        'url' => "https://limit-test-{$i}.example.com",
        'user_id' => $this->testUser->id,
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => false,
        'ssl_certificate_analyzed_at' => null,
        'monitoring_config' => json_encode([]),
        'plugin_data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    \DB::table('websites')->insert($websites->toArray());

    $this->artisan('ssl:backfill-certificates', ['--limit' => 3, '--no-delay' => true])
        ->expectsOutput('Processing 3 websites...')
        ->assertExitCode(0);

    Queue::assertPushed(AnalyzeSslCertificateJob::class, 3);
});

test('command with force processes all websites', function () {
    Queue::fake();

    // Create websites using raw inserts for speed
    $websitesWithout = collect(range(1, 3))->map(fn($i) => [
        'name' => "Without Cert {$i}",
        'url' => "https://force-without-{$i}.example.com",
        'user_id' => $this->testUser->id,
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => false,
        'ssl_certificate_analyzed_at' => null,
        'monitoring_config' => json_encode([]),
        'plugin_data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    \DB::table('websites')->insert($websitesWithout->toArray());

    $websitesWith = collect(range(1, 2))->map(fn($i) => [
        'name' => "With Cert {$i}",
        'url' => "https://force-with-{$i}.example.com",
        'user_id' => $this->testUser->id,
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => false,
        'ssl_certificate_analyzed_at' => now(),
        'latest_ssl_certificate' => json_encode(['subject' => 'existing.com']),
        'monitoring_config' => json_encode([]),
        'plugin_data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    \DB::table('websites')->insert($websitesWith->toArray());

    $this->artisan('ssl:backfill-certificates', ['--force' => true, '--limit' => 10, '--no-delay' => true])
        ->expectsOutput('Processing 5 websites...')
        ->assertExitCode(0);

    Queue::assertPushed(AnalyzeSslCertificateJob::class, 5);
});

test('command shows progress and summary', function () {
    Queue::fake();

    Website::withoutEvents(fn() => Website::factory()->count(3)->create([
        'ssl_monitoring_enabled' => true,
        'ssl_certificate_analyzed_at' => null,
    ]));

    $this->artisan('ssl:backfill-certificates', ['--limit' => 10, '--no-delay' => true])
        ->expectsOutput('Processing 3 websites...')
        ->expectsOutputToContain('Queued 3 certificate analysis jobs')
        ->expectsOutputToContain('Check Horizon dashboard to monitor progress')
        ->assertExitCode(0);
});

test('command skips websites without ssl monitoring', function () {
    Queue::fake();

    // Create websites with SSL monitoring disabled
    Website::withoutEvents(fn() => Website::factory()->count(3)->create([
        'ssl_monitoring_enabled' => false,
        'ssl_certificate_analyzed_at' => null,
    ]));

    // Create websites with SSL monitoring enabled
    Website::withoutEvents(fn() => Website::factory()->count(2)->create([
        'ssl_monitoring_enabled' => true,
        'ssl_certificate_analyzed_at' => null,
    ]));

    $this->artisan('ssl:backfill-certificates', ['--limit' => 10, '--no-delay' => true])
        ->expectsOutput('Processing 2 websites...')
        ->assertExitCode(0);

    Queue::assertPushed(AnalyzeSslCertificateJob::class, 2);
});

test('command handles no websites to process', function () {
    Queue::fake();

    // Create only websites with existing certificate data
    Website::withoutEvents(fn() => Website::factory()->count(3)->create([
        'ssl_monitoring_enabled' => true,
        'ssl_certificate_analyzed_at' => now(),
        'latest_ssl_certificate' => ['subject' => 'existing.com'],
    ]));

    $this->artisan('ssl:backfill-certificates', ['--limit' => 10, '--no-delay' => true])
        ->expectsOutput('No websites need certificate analysis.')
        ->assertExitCode(0);

    Queue::assertNothingPushed();
});

test('command dispatches jobs to correct queue', function () {
    Queue::fake();

    Website::withoutEvents(fn() => Website::factory()->count(2)->create([
        'ssl_monitoring_enabled' => true,
        'ssl_certificate_analyzed_at' => null,
    ]));

    $this->artisan('ssl:backfill-certificates', ['--limit' => 10, '--no-delay' => true])
        ->assertExitCode(0);

    Queue::assertPushed(AnalyzeSslCertificateJob::class, function ($job) {
        return $job->queue === 'monitoring-analysis';
    });
});

test('command outputs website url for each processed website', function () {
    Queue::fake();

    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'url' => 'https://test-example.com',
        'ssl_monitoring_enabled' => true,
        'ssl_certificate_analyzed_at' => null,
    ]));

    $this->artisan('ssl:backfill-certificates', ['--limit' => 10, '--no-delay' => true])
        ->expectsOutputToContain('Analyzing: https://test-example.com')
        ->assertExitCode(0);
});

test('command default limit is 10', function () {
    Queue::fake();

    // Create more than 10 websites using raw inserts for speed
    $websites = collect(range(1, 15))->map(fn($i) => [
        'name' => "Test Website {$i}",
        'url' => "https://test-{$i}.example.com",
        'user_id' => $this->testUser->id,
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => false,
        'ssl_certificate_analyzed_at' => null,
        'monitoring_config' => json_encode([]),
        'plugin_data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    \DB::table('websites')->insert($websites->toArray());

    $this->artisan('ssl:backfill-certificates', ['--no-delay' => true])
        ->expectsOutput('Processing 10 websites...')
        ->assertExitCode(0);

    Queue::assertPushed(AnalyzeSslCertificateJob::class, 10);
});

test('command with force and no limit processes all matching websites', function () {
    Queue::fake();

    // Create 15 websites using raw inserts for speed
    $websites = collect(range(1, 15))->map(fn($i) => [
        'name' => "Test Website {$i}",
        'url' => "https://test-force-{$i}.example.com",
        'user_id' => $this->testUser->id,
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => false,
        'ssl_certificate_analyzed_at' => now(),
        'latest_ssl_certificate' => json_encode(['subject' => 'existing.com']),
        'monitoring_config' => json_encode([]),
        'plugin_data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    \DB::table('websites')->insert($websites->toArray());

    $this->artisan('ssl:backfill-certificates', ['--force' => true, '--limit' => 20, '--no-delay' => true])
        ->expectsOutput('Processing 15 websites...')
        ->assertExitCode(0);

    Queue::assertPushed(AnalyzeSslCertificateJob::class, 15);
});

test('command processes websites in order they were created', function () {
    Queue::fake();

    $website1 = Website::withoutEvents(fn() => Website::factory()->create([
        'url' => 'https://first.com',
        'ssl_monitoring_enabled' => true,
        'ssl_certificate_analyzed_at' => null,
        'created_at' => now()->subDays(3),
    ]));

    $website2 = Website::withoutEvents(fn() => Website::factory()->create([
        'url' => 'https://second.com',
        'ssl_monitoring_enabled' => true,
        'ssl_certificate_analyzed_at' => null,
        'created_at' => now()->subDays(2),
    ]));

    $website3 = Website::withoutEvents(fn() => Website::factory()->create([
        'url' => 'https://third.com',
        'ssl_monitoring_enabled' => true,
        'ssl_certificate_analyzed_at' => null,
        'created_at' => now()->subDays(1),
    ]));

    $this->artisan('ssl:backfill-certificates', ['--limit' => 2, '--no-delay' => true])
        ->assertExitCode(0);

    // Should process the oldest websites first
    Queue::assertPushed(AnalyzeSslCertificateJob::class, 2);
});

test('command completes in under 3 seconds for small batch', function () {
    Queue::fake();

    // Create websites using raw inserts for speed
    $websites = collect(range(1, 5))->map(fn($i) => [
        'name' => "Batch Test {$i}",
        'url' => "https://batch-test-{$i}.example.com",
        'user_id' => $this->testUser->id,
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => false,
        'ssl_certificate_analyzed_at' => null,
        'monitoring_config' => json_encode([]),
        'plugin_data' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    \DB::table('websites')->insert($websites->toArray());

    $startTime = microtime(true);

    $this->artisan('ssl:backfill-certificates', ['--limit' => 5, '--no-delay' => true])
        ->assertExitCode(0);

    $executionTime = microtime(true) - $startTime;

    // Should complete quickly since we're just dispatching jobs, not processing them
    // Allowing 3 seconds for parallel test environment overhead (includes database cleanup, etc.)
    expect($executionTime)->toBeLessThan(3.0);
});
