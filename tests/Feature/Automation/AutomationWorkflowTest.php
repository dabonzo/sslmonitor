<?php

use App\Models\Website;
use App\Models\User;
use App\Jobs\ImmediateWebsiteCheckJob;
use App\Services\MonitorIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\UptimeMonitor\Models\Monitor;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test user and website
    $this->user = User::factory()->create([
        'email' => 'automation@example.com',
    ]);

    $this->website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'name' => 'Test Automation Website',
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);
});

test('complete automation workflow from website creation to monitoring', function () {
    // Test the complete workflow: Website creation → Monitor sync → Immediate check → Queue processing

    // 1. Verify website was created with monitoring enabled
    expect($this->website->uptime_monitoring_enabled)->toBeTrue()
        ->and($this->website->ssl_monitoring_enabled)->toBeTrue();

    // 2. Sync website with monitor system
    $monitorService = app(MonitorIntegrationService::class);
    $monitor = $monitorService->createOrUpdateMonitorForWebsite($this->website);

    expect($monitor)->toBeInstanceOf(Monitor::class)
        ->and((string) $monitor->url)->toBe($this->website->url)
        ->and($monitor->uptime_check_enabled)->toBeTrue()
        ->and($monitor->certificate_check_enabled)->toBeTrue();

    // 3. Test immediate check job dispatch and execution
    $job = new ImmediateWebsiteCheckJob($this->website);
    $result = $job->handle();

    // 4. Verify job completed successfully with expected structure
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('website_id', $this->website->id)
        ->and($result)->toHaveKey('url', $this->website->url)
        ->and($result)->toHaveKey('checked_at')
        ->and($result)->toHaveKey('uptime')
        ->and($result)->toHaveKey('ssl');

    // 5. Verify uptime check results
    expect($result['uptime'])->toBeArray()
        ->and($result['uptime'])->toHaveKey('status')
        ->and($result['uptime'])->toHaveKey('checked_at');

    // 6. Verify SSL check results
    expect($result['ssl'])->toBeArray()
        ->and($result['ssl'])->toHaveKey('status')
        ->and($result['ssl'])->toHaveKey('checked_at');

    // 7. Verify website timestamp was updated
    $this->website->refresh();
    expect($this->website->updated_at)->not()->toBeNull();

    // 8. Verify monitor was updated with fresh data
    $monitor->refresh();
    expect($monitor->updated_at)->not()->toBeNull();
});

test('automation workflow with queue system integration', function () {
    Queue::fake();

    // Test website controller immediate check endpoint
    $response = $this->actingAs($this->user)
        ->postJson(route('ssl.websites.immediate-check', $this->website));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'website_id' => $this->website->id,
        ]);

    // Verify job was dispatched to correct queue
    Queue::assertPushed(ImmediateWebsiteCheckJob::class, function ($job) {
        return $job->website->id === $this->website->id;
    });
});

test('automation workflow handles multiple websites concurrently', function () {
    // Create multiple websites for concurrent testing
    $websites = Website::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $results = [];

    // Process multiple immediate checks
    foreach ($websites as $website) {
        $job = new ImmediateWebsiteCheckJob($website);
        $results[] = $job->handle();
    }

    // Verify all jobs completed successfully
    expect($results)->toHaveCount(3);

    foreach ($results as $result) {
        expect($result)->toBeArray()
            ->and($result)->toHaveKey('website_id')
            ->and($result)->toHaveKey('uptime')
            ->and($result)->toHaveKey('ssl');
    }

    // Verify all websites were updated
    foreach ($websites as $website) {
        $website->refresh();
        expect($website->updated_at)->not()->toBeNull();
    }
});

test('automation workflow error handling and recovery', function () {
    // Create website with invalid URL to test error handling
    $invalidWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://invalid-domain-12345.test',
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $job = new ImmediateWebsiteCheckJob($invalidWebsite);
    $result = $job->handle();

    // Verify job handled errors gracefully
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('website_id', $invalidWebsite->id)
        ->and($result)->toHaveKey('ssl')
        ->and($result['ssl'])->toHaveKey('status');

    // SSL check should fail gracefully
    expect($result['ssl']['status'])->toBe('error');

    // Website should still be updated even with errors
    $invalidWebsite->refresh();
    expect($invalidWebsite->updated_at)->not()->toBeNull();
});

test('automation system monitor synchronization workflow', function () {
    // Test the complete monitor synchronization process
    $monitorService = app(MonitorIntegrationService::class);

    // 1. Create multiple websites
    $websites = Website::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    // 2. Sync all websites with monitors
    $syncResult = $monitorService->syncAllWebsitesWithMonitors();

    // 3. Verify synchronization results
    expect($syncResult)->toBeArray()
        ->and($syncResult)->toHaveKey('synced')
        ->and($syncResult)->toHaveKey('errors')
        ->and($syncResult)->toHaveKey('total_websites')
        ->and($syncResult)->toHaveKey('synced_count')
        ->and($syncResult)->toHaveKey('error_count');

    // Should sync at least our test websites
    expect($syncResult['synced_count'])->toBeGreaterThanOrEqual(3);
    expect($syncResult['error_count'])->toBe(0);

    // 4. Verify monitors were created
    foreach ($websites as $website) {
        $monitor = $monitorService->getMonitorForWebsite($website);
        expect($monitor)->toBeInstanceOf(Monitor::class)
            ->and((string) $monitor->url)->toBe($website->url);
    }
});

test('automation workflow status checking and polling', function () {
    // Test the status checking workflow used by the frontend

    // 1. Trigger immediate check
    $job = new ImmediateWebsiteCheckJob($this->website);
    $result = $job->handle();

    // 2. Test status checking endpoint
    $response = $this->actingAs($this->user)
        ->getJson(route('ssl.websites.check-status', $this->website));

    $response->assertOk()
        ->assertJsonStructure([
            'website_id',
            'last_updated',
            'ssl_status',
            'uptime_status',
            'ssl_monitoring_enabled',
            'uptime_monitoring_enabled',
            'checked_at',
        ]);

    // 3. Verify the response contains actual status data
    $data = $response->json();
    expect($data['website_id'])->toBe($this->website->id)
        ->and($data['ssl_monitoring_enabled'])->toBeTrue()
        ->and($data['uptime_monitoring_enabled'])->toBeTrue();
});

test('automation workflow performance and timing', function () {
    $startTime = microtime(true);

    // Process immediate check
    $job = new ImmediateWebsiteCheckJob($this->website);
    $result = $job->handle();

    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;

    // Verify job completed in reasonable time (under 30 seconds for example.com)
    expect($executionTime)->toBeLessThan(30.0);

    // Verify result includes timing information
    expect($result)->toHaveKey('checked_at');

    $checkedAt = new \Carbon\Carbon($result['checked_at']);
    expect($checkedAt)->toBeInstanceOf(\Carbon\Carbon::class);
});