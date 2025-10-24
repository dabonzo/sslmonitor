<?php

use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Models\User;
use App\Models\Website;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();

    $this->user = User::factory()->create();
    $this->website = Website::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Website',
    ]);

    // Get or create the monitor for the website
    $this->monitor = $this->website->getSpatieMonitor() ?? Monitor::factory()->create([
        'url' => $this->website->url,
    ]);
});

test('website has monitoringResults relationship', function () {
    MonitoringResult::factory()->count(5)->create([
        'website_id' => $this->website->id,
        'monitor_id' => $this->monitor->id,
    ]);

    expect($this->website->monitoringResults()->count())->toBe(5);
});

test('history endpoint returns monitoring results for authenticated user', function () {
    MonitoringResult::factory()->count(10)->create([
        'website_id' => $this->website->id,
        'monitor_id' => $this->monitor->id,
        'started_at' => now()->subDays(5),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('ssl.websites.history', $this->website));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'uuid',
                    'check_type',
                    'status',
                    'started_at',
                    'completed_at',
                ],
            ],
            'meta' => [
                'current_page',
                'total',
                'per_page',
            ],
        ]);

    expect($response->json('data'))->toHaveCount(10);
});

test('history endpoint filters by check type', function () {
    MonitoringResult::factory()->count(5)->create([
        'website_id' => $this->website->id,
        'monitor_id' => $this->monitor->id,
        'check_type' => 'ssl',
    ]);

    MonitoringResult::factory()->count(3)->create([
        'website_id' => $this->website->id,
        'monitor_id' => $this->monitor->id,
        'check_type' => 'uptime',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('ssl.websites.history', ['website' => $this->website, 'check_type' => 'ssl']));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
});

test('history endpoint filters by status', function () {
    MonitoringResult::factory()->count(7)->create([
        'website_id' => $this->website->id,
        'monitor_id' => $this->monitor->id,
        'status' => 'success',
    ]);

    MonitoringResult::factory()->count(3)->create([
        'website_id' => $this->website->id,
        'monitor_id' => $this->monitor->id,
        'status' => 'failed',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('ssl.websites.history', ['website' => $this->website, 'status' => 'success']));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(7);
});

test('history endpoint filters by days', function () {
    MonitoringResult::factory()->count(5)->create([
        'website_id' => $this->website->id,
        'monitor_id' => $this->monitor->id,
        'started_at' => now()->subDays(5),
    ]);

    MonitoringResult::factory()->count(3)->create([
        'website_id' => $this->website->id,
        'monitor_id' => $this->monitor->id,
        'started_at' => now()->subDays(15),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('ssl.websites.history', ['website' => $this->website, 'days' => 7]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
});

test('statistics endpoint returns monitoring statistics', function () {
    MonitoringResult::factory()->count(10)->create([
        'website_id' => $this->website->id,
        'monitor_id' => $this->monitor->id,
        'status' => 'success',
        'check_type' => 'uptime',
        'response_time_ms' => 250,
    ]);

    MonitoringResult::factory()->count(2)->create([
        'website_id' => $this->website->id,
        'monitor_id' => $this->monitor->id,
        'status' => 'failed',
        'check_type' => 'uptime',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('ssl.websites.statistics', $this->website));

    $response->assertOk()
        ->assertJsonStructure([
            'website_id',
            'website_name',
            'website_url',
            'period_days',
            'statistics' => [
                'total_checks',
                'successful_checks',
                'failed_checks',
                'success_rate',
                'avg_response_time_ms',
            ],
        ]);

    expect($response->json('statistics.total_checks'))->toBe(12)
        ->and($response->json('statistics.successful_checks'))->toBe(10)
        ->and($response->json('statistics.failed_checks'))->toBe(2)
        ->and($response->json('statistics.success_rate'))->toBe(83.33);
});

test('statistics endpoint filters by days parameter', function () {
    MonitoringResult::factory()->count(5)->create([
        'website_id' => $this->website->id,
        'monitor_id' => $this->monitor->id,
        'started_at' => now()->subDays(5),
    ]);

    MonitoringResult::factory()->count(3)->create([
        'website_id' => $this->website->id,
        'monitor_id' => $this->monitor->id,
        'started_at' => now()->subDays(40),
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('ssl.websites.statistics', ['website' => $this->website, 'days' => 7]));

    $response->assertOk();
    expect($response->json('statistics.total_checks'))->toBe(5);
});

test('history endpoint requires authentication', function () {
    $response = $this->getJson(route('ssl.websites.history', $this->website));

    $response->assertUnauthorized();
});

test('statistics endpoint requires authentication', function () {
    $response = $this->getJson(route('ssl.websites.statistics', $this->website));

    $response->assertUnauthorized();
});

test('history endpoint requires authorization to view website', function () {
    $otherUser = User::factory()->create();

    $response = $this->actingAs($otherUser)
        ->getJson(route('ssl.websites.history', $this->website));

    $response->assertForbidden();
});

test('show method includes monitoring history and statistics', function () {
    MonitoringResult::factory()->count(5)->create([
        'website_id' => $this->website->id,
        'monitor_id' => $this->monitor->id,
        'status' => 'success',
        'check_type' => 'uptime',
        'response_time_ms' => 200,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('ssl.websites.show', $this->website));

    $response->assertOk();

    // Verify Inertia props include monitoring data
    $props = $response->viewData('page')['props'];

    expect($props)->toHaveKey('website')
        ->and($props['website'])->toHaveKey('monitoring_history')
        ->and($props['website'])->toHaveKey('monitoring_stats')
        ->and($props['website']['monitoring_stats']['total_checks'])->toBe(5)
        ->and($props['website']['monitoring_stats']['success_rate'])->toBe(100.0);
});
