<?php

use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Models\User;
use App\Models\Website;
use Illuminate\Support\Facades\Cache;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    Cache::flush(); // Clear cache to prevent test interference
    $this->setUpCleanDatabase();
    $this->user = User::first();
    $this->monitor = Monitor::first();
    $this->website = Website::first();
});

test('GET /api/monitors/{monitor}/history returns monitoring results', function () {
    // Arrange: Create test data
    MonitoringResult::factory()->count(15)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now()->subDays(5),
    ]);

    // Act: Make authenticated request
    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/history");

    // Assert: Verify response structure and data
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'uuid',
                    'monitor_id',
                    'website_id',
                    'check_type',
                    'status',
                    'started_at',
                    'completed_at',
                    'duration_ms',
                    'uptime_status',
                    'response_time_ms',
                    'ssl_status',
                ],
            ],
            'meta' => [
                'total',
                'per_page',
                'current_page',
            ],
        ]);

    expect($response->json('data'))->toHaveCount(15);
});

test('GET /api/monitors/{monitor}/history respects limit parameter', function () {
    // Arrange: Create test data
    MonitoringResult::factory()->count(50)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
    ]);

    // Act: Make request with limit parameter
    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/history?limit=10");

    // Assert: Verify limit is respected
    $response->assertOk()
        ->assertJsonCount(10, 'data');

    // meta.per_page can be returned as string '10' or integer 10 depending on JSON encoding
    expect((int) $response->json('meta.per_page'))->toBe(10);
});

test('GET /api/monitors/{monitor}/history filters by check_type parameter', function () {
    // Arrange: Create mixed check types
    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'check_type' => 'ssl_certificate',
    ]);

    MonitoringResult::factory()->count(15)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'check_type' => 'uptime',
    ]);

    // Act: Filter by SSL check type
    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/history?check_type=ssl_certificate");

    // Assert: Only SSL checks should be returned
    $response->assertOk()
        ->assertJsonCount(10, 'data');

    foreach ($response->json('data') as $item) {
        expect($item['check_type'])->toBe('ssl_certificate');
    }
});

test('GET /api/monitors/{monitor}/history filters by status parameter', function () {
    // Arrange: Create mixed status results
    MonitoringResult::factory()->count(12)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'status' => 'success',
    ]);

    MonitoringResult::factory()->count(8)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'status' => 'failed',
    ]);

    // Act: Filter by failed status
    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/history?status=failed");

    // Assert: Only failed results should be returned
    $response->assertOk()
        ->assertJsonCount(8, 'data');

    foreach ($response->json('data') as $item) {
        expect($item['status'])->toBe('failed');
    }
});

test('GET /api/monitors/{monitor}/history filters by days parameter', function () {
    // Arrange: Create data across different time periods
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now()->subDays(3), // Recent
    ]);

    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now()->subDays(15), // Older
    ]);

    // Act: Filter by recent days
    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/history?days=7");

    // Assert: Only recent results should be returned
    $response->assertOk()
        ->assertJsonCount(5, 'data');
});

test('GET /api/monitors/{monitor}/history requires authentication', function () {
    // Arrange: Create test data
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
    ]);

    // Act: Make unauthenticated request
    $response = $this->getJson("/api/monitors/{$this->monitor->id}/history");

    // Assert: Should return 401 Unauthorized
    $response->assertUnauthorized();
});

test('GET /api/monitors/{monitor}/history validates monitor ownership', function () {
    // Arrange: Create different user with a team and website
    $otherUser = User::factory()->create();
    $otherTeam = \App\Models\Team::factory()->create(['created_by_user_id' => $otherUser->id]);
    $uniqueUrl = 'https://other-history-'.uniqid().'.example.com';
    $otherWebsite = Website::factory()->create([
        'team_id' => $otherTeam->id,
        'url' => $uniqueUrl,
    ]);

    // Get the monitor created automatically by WebsiteObserver
    $otherMonitor = $otherWebsite->getSpatieMonitor();

    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $otherMonitor->id,
        'website_id' => $otherWebsite->id,
    ]);

    // Act: Try to access other user's monitor history
    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$otherMonitor->id}/history");

    // Assert: Should return 403 Forbidden
    $response->assertForbidden();
});

test('GET /api/monitors/{monitor}/trends returns trend data', function () {
    // Arrange: Create test data for trends
    MonitoringResult::factory()->count(20)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'status' => 'success',
        'response_time_ms' => 250,
        'started_at' => now()->subDays(5),
    ]);

    // Act: Make authenticated request
    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/trends");

    // Assert: Verify response structure matches Vue component expectations
    $response->assertOk()
        ->assertJsonStructure([
            'labels',
            'data',
            'avg',
        ]);

    expect($response->json('labels'))->toBeArray()
        ->and($response->json('data'))->toBeArray()
        ->and($response->json('avg'))->toBeNumeric();
});

test('GET /api/monitors/{monitor}/trends accepts period parameter', function () {
    // Arrange: Create test data across multiple periods
    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 200,
        'started_at' => now()->subDays(5),
    ]);

    MonitoringResult::factory()->count(15)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'response_time_ms' => 300,
        'started_at' => now()->subDays(25),
    ]);

    // Act: Test different periods
    $response7d = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/trends?period=7d");

    $response30d = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/trends?period=30d");

    // Assert: Both periods should work
    $response7d->assertOk()
        ->assertJsonStructure(['labels', 'data', 'avg']);

    $response30d->assertOk()
        ->assertJsonStructure(['labels', 'data', 'avg']);

    // 30d period should include more data points
    expect(count($response30d->json('labels')))->toBeGreaterThanOrEqual(count($response7d->json('labels')));
});

test('GET /api/monitors/{monitor}/trends requires authentication', function () {
    // Act: Make unauthenticated request
    $response = $this->getJson("/api/monitors/{$this->monitor->id}/trends");

    // Assert: Should return 401 Unauthorized
    $response->assertUnauthorized();
});

test('GET /api/monitors/{monitor}/trends validates monitor ownership', function () {
    // Arrange: Create different user with a team and website
    $otherUser = User::factory()->create();
    $otherTeam = \App\Models\Team::factory()->create(['created_by_user_id' => $otherUser->id]);
    $uniqueUrl = 'https://other-trends-'.uniqid().'.example.com';
    $otherWebsite = Website::factory()->create([
        'team_id' => $otherTeam->id,
        'url' => $uniqueUrl,
    ]);

    // Get the monitor created automatically by WebsiteObserver
    $otherMonitor = $otherWebsite->getSpatieMonitor();

    // Act: Try to access other user's monitor trends
    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$otherMonitor->id}/trends");

    // Assert: Should return 403 Forbidden
    $response->assertForbidden();
});

test('GET /api/monitors/{monitor}/summary returns summary statistics', function () {
    // Arrange: Create test data with known values
    MonitoringResult::factory()->count(15)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'status' => 'success',
        'response_time_ms' => 200,
        'started_at' => now()->subDays(5),
    ]);

    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'status' => 'failed',
        'response_time_ms' => 500,
        'started_at' => now()->subDays(3),
    ]);

    // Act: Make authenticated request
    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/summary");

    // Assert: Verify response structure and calculations
    $response->assertOk()
        ->assertJsonStructure([
            'total_checks',
            'successful_checks',
            'failed_checks',
            'uptime_percentage',
            'avg_response_time',
            'min_response_time',
            'max_response_time',
            'period_start',
            'period_end',
        ]);

    expect($response->json('total_checks'))->toBe(20)
        ->and($response->json('successful_checks'))->toBe(15)
        ->and($response->json('failed_checks'))->toBe(5);

    // Uptime percentage can be integer 75 or float 75.0 depending on calculation
    $uptimePercentage = $response->json('uptime_percentage');
    expect($uptimePercentage)->toBeNumeric();
    expect((float) $uptimePercentage)->toBe(75.0);
});

test('GET /api/monitors/{monitor}/summary accepts period parameter', function () {
    // Arrange: Create test data across different periods
    MonitoringResult::factory()->count(10)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now()->subDays(5), // Recent
    ]);

    MonitoringResult::factory()->count(20)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now()->subDays(25), // Older
    ]);

    // Act: Test different periods
    $response7d = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/summary?period=7d");

    $response30d = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/summary?period=30d");

    // Assert: Different periods should return different totals
    expect($response7d->json('total_checks'))->toBe(10)
        ->and($response30d->json('total_checks'))->toBe(30);
});

test('GET /api/monitors/{monitor}/summary handles empty data gracefully', function () {
    // Arrange: Create a new website and monitor with no results
    $uniqueUrl = 'https://empty-data-'.uniqid().'.example.com';
    $newWebsite = Website::factory()->create([
        'team_id' => $this->website->team_id, // Use test user's team
        'url' => $uniqueUrl,
    ]);

    // Get the monitor created automatically by WebsiteObserver
    $newMonitor = $newWebsite->getSpatieMonitor();

    // Act: Get summary for monitor with no data
    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$newMonitor->id}/summary");

    // Assert: Should return zero values
    $response->assertOk()
        ->assertJson([
            'total_checks' => 0,
            'successful_checks' => 0,
            'failed_checks' => 0,
        ]);

    // Check uptime_percentage is numeric zero (can be int 0 or float 0.0)
    $uptimePercentage = $response->json('uptime_percentage');
    expect($uptimePercentage)->toBeNumeric();
    expect((float) $uptimePercentage)->toBe(0.0);
});

test('GET /api/monitors/{monitor}/summary requires authentication', function () {
    // Act: Make unauthenticated request
    $response = $this->getJson("/api/monitors/{$this->monitor->id}/summary");

    // Assert: Should return 401 Unauthorized
    $response->assertUnauthorized();
});

test('GET /api/monitors/{monitor}/summary validates monitor ownership', function () {
    // Arrange: Create different user with a team and website
    $otherUser = User::factory()->create();
    $otherTeam = \App\Models\Team::factory()->create(['created_by_user_id' => $otherUser->id]);
    $uniqueUrl = 'https://other-summary-'.uniqid().'.example.com';
    $otherWebsite = Website::factory()->create([
        'team_id' => $otherTeam->id,
        'url' => $uniqueUrl,
    ]);

    // Get the monitor created automatically by WebsiteObserver
    $otherMonitor = $otherWebsite->getSpatieMonitor();

    // Act: Try to access other user's monitor summary
    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$otherMonitor->id}/summary");

    // Assert: Should return 403 Forbidden
    $response->assertForbidden();
});

test('API endpoints handle invalid parameters gracefully', function () {
    // Arrange: Create test data
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
    ]);

    // Act & Assert: Test invalid limit parameter
    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/history?limit=invalid");

    $response->assertStatus(422); // Validation error

    // Act & Assert: Test invalid period parameter
    $response = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/trends?period=invalid");

    $response->assertStatus(422); // Validation error
});

test('API endpoints return consistent JSON structure', function () {
    // Arrange: Create minimal test data
    MonitoringResult::factory()->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
    ]);

    // Act: Make requests to all endpoints
    $historyResponse = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/history");

    $trendsResponse = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/trends");

    $summaryResponse = $this->actingAs($this->user)
        ->getJson("/api/monitors/{$this->monitor->id}/summary");

    // Assert: All should return valid JSON
    $historyResponse->assertOk();
    $trendsResponse->assertOk();
    $summaryResponse->assertOk();

    // Assert: Content-Type header should be application/json
    expect($historyResponse->headers->get('content-type'))->toContain('application/json')
        ->and($trendsResponse->headers->get('content-type'))->toContain('application/json')
        ->and($summaryResponse->headers->get('content-type'))->toContain('application/json');
});
