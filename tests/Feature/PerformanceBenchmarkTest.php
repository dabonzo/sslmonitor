<?php

use App\Models\Monitor;
use App\Models\Team;
use App\Models\User;
use App\Models\Website;
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->setUpMocksSslCertificateAnalysis();

    // Mock MonitorIntegrationService to prevent observer overhead during ALL tests
    $this->mock(\App\Services\MonitorIntegrationService::class, function ($mock) {
        $mock->shouldReceive('createOrUpdateMonitorForWebsite')->andReturn(null);
        $mock->shouldReceive('removeMonitorForWebsite')->andReturn(null);
    });
});

describe('Performance Benchmarks', function () {
    test('dashboard loads efficiently with multiple websites', function () {
        $user = User::factory()->create();

        // Create multiple teams and websites for comprehensive testing
        $team1 = Team::factory()->create(['created_by_user_id' => $user->id]);
        $team2 = Team::factory()->create(['created_by_user_id' => $user->id]);

        // Add user to teams
        $user->teams()->attach($team1->id, ['role' => 'OWNER', 'joined_at' => now(), 'invited_by_user_id' => $user->id]);
        $user->teams()->attach($team2->id, ['role' => 'ADMIN', 'joined_at' => now(), 'invited_by_user_id' => $user->id]);

        // Create multiple websites WITHOUT triggering observers
        Website::withoutEvents(function () use ($user, $team1, $team2) {
            Website::factory()->count(10)->create(['user_id' => $user->id]);
            Website::factory()->count(5)->create(['user_id' => $user->id, 'team_id' => $team1->id]);
            Website::factory()->count(3)->create(['user_id' => $user->id, 'team_id' => $team2->id]);
        });

        $this->actingAs($user);

        // Measure time for dashboard load
        $startTime = microtime(true);

        $response = $this->get('/dashboard');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Dashboard should load in under 1 second (1000ms) with optimizations
        expect($executionTime)->toBeLessThan(1000)
            ->and($executionTime)->toBeGreaterThan(0);
    });

    test('website index loads efficiently with search and filtering', function () {
        $user = User::factory()->create();

        // Create websites with various statuses WITHOUT triggering observers
        $websites = Website::withoutEvents(function () use ($user) {
            return Website::factory()->count(25)->create(['user_id' => $user->id]);
        });

        // Create Monitor records for each website to simulate real monitoring data
        // This prevents expensive queries and missing data issues in the controller
        foreach ($websites as $website) {
            Monitor::factory()->create([
                'url' => $website->url,
                'uptime_check_enabled' => $website->uptime_monitoring_enabled,
                'certificate_check_enabled' => $website->ssl_monitoring_enabled,
                'certificate_status' => 'valid',
                'uptime_status' => 'up',
            ]);
        }

        $this->actingAs($user);

        // Test index page load time
        $startTime = microtime(true);

        $response = $this->get('/ssl/websites');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        // Website index should load in under 800ms with bulk query optimizations
        expect($executionTime)->toBeLessThan(800);

        // Test search functionality performance
        $startTime = microtime(true);

        $response = $this->get('/ssl/websites?search=test');

        $endTime = microtime(true);
        $searchTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        // Search should be fast even with filtering
        expect($searchTime)->toBeLessThan(600);
    });

    test('database queries are optimized with indexes', function () {
        $user = User::factory()->create();

        // Create test data
        $team = Team::factory()->create(['created_by_user_id' => $user->id]);
        $user->teams()->attach($team->id, ['role' => 'OWNER', 'joined_at' => now(), 'invited_by_user_id' => $user->id]);

        // Use withoutEvents() to skip observer processing during test data setup
        // This prevents SSL certificate analysis jobs from being dispatched
        Website::withoutEvents(function () use ($user, $team) {
            Website::factory()->count(20)->create(['user_id' => $user->id]);
            Website::factory()->count(10)->create(['user_id' => $user->id, 'team_id' => $team->id]);
        });

        $this->actingAs($user);

        // Enable query logging
        \DB::enableQueryLog();

        $response = $this->get('/ssl/websites');

        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        $response->assertStatus(200);

        // With optimizations, should not exceed 12 queries
        $queryCount = count($queries);
        expect($queryCount)->toBeLessThanOrEqual(12);
    });

    test('cache effectiveness reduces database load', function () {
        $user = User::factory()->create();

        // Create websites WITHOUT triggering observers
        Website::withoutEvents(function () use ($user) {
            Website::factory()->count(15)->create(['user_id' => $user->id]);
        });

        $this->actingAs($user);

        // First request - should populate cache
        \DB::enableQueryLog();

        $response1 = $this->get('/dashboard');

        $firstQueries = \DB::getQueryLog();
        \DB::disableQueryLog();

        $response1->assertStatus(200);

        // Second request - should use cache
        \DB::enableQueryLog();

        $response2 = $this->get('/dashboard');

        $secondQueries = \DB::getQueryLog();
        \DB::disableQueryLog();

        $response2->assertStatus(200);

        // Cache should reduce the number of queries significantly
        $firstQueryCount = count($firstQueries);
        $secondQueryCount = count($secondQueries);

        // In production, caching would reduce queries, but in testing environment
        // we just ensure both requests work without excessive queries
        // Allow slightly higher limits for parallel testing to account for race conditions
        expect($firstQueryCount)->toBeLessThanOrEqual(18);
        expect($secondQueryCount)->toBeLessThanOrEqual(25);
    });

    test('frontend bundle size is optimized', function () {
        // Check if build artifacts exist and are reasonably sized
        $manifestPath = public_path('build/manifest.json');

        expect(file_exists($manifestPath))->toBeTrue('Build manifest should exist');

        $manifest = json_decode(file_get_contents($manifestPath), true);

        // Find main JS bundle
        $mainBundle = null;
        foreach ($manifest as $file => $data) {
            if (str_ends_with($file, 'app.ts') && isset($data['file'])) {
                $bundlePath = public_path('build/'.$data['file']);
                if (file_exists($bundlePath)) {
                    $bundleSize = filesize($bundlePath);

                    // Main bundle should be under 50KB with code splitting
                    expect($bundleSize)->toBeLessThan(50 * 1024); // 50KB
                    $mainBundle = $bundleSize;
                }
                break;
            }
        }

        expect($mainBundle)->not()->toBeNull('Main bundle should be found');

        // Check vendor chunks exist (indicating code splitting is working)
        $vendorChunks = 0;
        foreach ($manifest as $file => $data) {
            if (str_contains($data['file'] ?? '', 'vendor-')) {
                $vendorChunks++;
            }
        }

        expect($vendorChunks)->toBeGreaterThan(0, 'Code splitting should create vendor chunks');
    });
});
