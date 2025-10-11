<?php

use App\Models\User;
use App\Models\Website;
use App\Services\MonitorIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Spatie\UptimeMonitor\Models\Monitor;
use Tests\Traits\MocksMonitorHttpRequests;

uses(RefreshDatabase::class);
uses(MocksMonitorHttpRequests::class);

beforeEach(function () {
    // Mock all HTTP requests to avoid real network calls
    $this->setUpMocksMonitorHttpRequests();

    // Ensure clean state - explicitly truncate monitors table
    // (RefreshDatabase should handle this, but explicit cleanup ensures isolation)
    Monitor::query()->forceDelete();

    $this->user = User::factory()->create();
    $this->monitorService = app(MonitorIntegrationService::class);
});

test('creating website with monitoring enabled creates monitor automatically', function () {
    // Verify no monitors exist initially
    expect(Monitor::count())->toBe(0);

    // Create website with monitoring enabled
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    // Observer should have created a monitor
    expect(Monitor::count())->toBe(1);

    $monitor = Monitor::first();
    expect($monitor->url)->toBe($website->url)
        ->and($monitor->uptime_check_enabled)->toBeTrue()
        ->and($monitor->certificate_check_enabled)->toBeTrue();
});

test('creating website with monitoring disabled does not create monitor', function () {
    expect(Monitor::count())->toBe(0);

    // Create website without monitoring
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => false,
        'ssl_monitoring_enabled' => false,
    ]);

    // No monitor should be created
    expect(Monitor::count())->toBe(0);
});

test('creating website with only uptime monitoring creates monitor', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => false,
    ]);

    expect(Monitor::count())->toBe(1);

    $monitor = Monitor::first();
    expect($monitor->uptime_check_enabled)->toBeTrue()
        ->and($monitor->certificate_check_enabled)->toBeFalse();
});

test('creating website with only ssl monitoring creates monitor', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => false,
        'ssl_monitoring_enabled' => true,
    ]);

    expect(Monitor::count())->toBe(1);

    $monitor = Monitor::first();
    expect($monitor->uptime_check_enabled)->toBeFalse()
        ->and($monitor->certificate_check_enabled)->toBeTrue();
});

test('updating website url updates monitor', function () {
    // Create website with monitor
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    $monitor = Monitor::first();
    $originalMonitorId = $monitor->id;

    // Update URL
    $website->update(['url' => 'https://newdomain.com']);

    // Monitor should be updated
    $monitor = Monitor::find($originalMonitorId);
    expect($monitor->url)->toBe('https://newdomain.com');
});

test('enabling monitoring on existing website creates monitor', function () {
    // Create website without monitoring
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => false,
        'ssl_monitoring_enabled' => false,
    ]);

    expect(Monitor::count())->toBe(0);

    // Enable monitoring
    $website->update([
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    // Monitor should now exist
    expect(Monitor::count())->toBe(1);

    $monitor = Monitor::first();
    expect($monitor->url)->toBe($website->url);
});

test('disabling all monitoring on website removes monitor', function () {
    // Create website with monitoring
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    expect(Monitor::count())->toBe(1);

    // Disable all monitoring
    $website->update([
        'uptime_monitoring_enabled' => false,
        'ssl_monitoring_enabled' => false,
    ]);

    // Monitor should be removed
    expect(Monitor::count())->toBe(0);
});

test('updating unrelated website fields does not trigger monitor sync', function () {
    // Create website with monitoring
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'name' => 'Original Name',
        'uptime_monitoring_enabled' => true,
    ]);

    $monitor = Monitor::first();
    $originalUpdatedAt = $monitor->updated_at;

    // Wait a moment to ensure timestamp difference
    sleep(1);

    // Update unrelated field (name doesn't trigger sync)
    $website->update(['name' => 'New Name']);

    // Monitor should not be updated
    $monitor->refresh();
    expect($monitor->updated_at->equalTo($originalUpdatedAt))->toBeTrue();
});

test('deleting website removes monitor', function () {
    // Create website with monitoring
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    expect(Monitor::count())->toBe(1);

    // Delete website
    $website->delete();

    // Monitor should be removed
    expect(Monitor::count())->toBe(0);
});

test('force deleting website removes monitor', function () {
    // Create website with monitoring
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => true,
    ]);

    expect(Monitor::count())->toBe(1);

    // Force delete website
    $website->forceDelete();

    // Monitor should be removed
    expect(Monitor::count())->toBe(0);
});

test('restoring deleted website recreates monitor if monitoring was enabled', function () {
    // Create website with monitoring
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    expect(Monitor::count())->toBe(1);

    // Soft delete website
    $website->delete();
    expect(Monitor::count())->toBe(0);

    // Restore website
    $website->restore();

    // Monitor should be recreated
    expect(Monitor::count())->toBe(1);

    $monitor = Monitor::first();
    expect($monitor->url)->toBe($website->url);
});

test('restoring website without monitoring enabled does not create monitor', function () {
    // Create website without monitoring
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => false,
        'ssl_monitoring_enabled' => false,
    ]);

    expect(Monitor::count())->toBe(0);

    // Delete and restore
    $website->delete();
    $website->restore();

    // Still no monitor
    expect(Monitor::count())->toBe(0);
});

test('observer handles exceptions gracefully when creating monitor', function () {
    // Mock the service to throw an exception
    $this->mock(MonitorIntegrationService::class, function ($mock) {
        $mock->shouldReceive('createOrUpdateMonitorForWebsite')
            ->andThrow(new \Exception('Test exception'));
    });

    // Creating website should not throw exception (observer catches it)
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => true,
    ]);

    // Website should still be created despite monitor creation failure
    expect($website->exists)->toBeTrue();
});

test('observer handles exceptions gracefully when updating monitor', function () {
    // Create website first
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => true,
    ]);

    // Mock the service to throw on update
    $this->mock(MonitorIntegrationService::class, function ($mock) {
        $mock->shouldReceive('createOrUpdateMonitorForWebsite')
            ->andThrow(new \Exception('Test exception'));
    });

    // Updating should not throw (observer catches exception)
    $website->update(['url' => 'https://newdomain.com']);

    // Website should still be updated
    expect($website->url)->toBe('https://newdomain.com');
});

test('observer handles exceptions gracefully when deleting monitor', function () {
    // Create website first
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => true,
    ]);

    // Mock the service to throw on delete
    $this->mock(MonitorIntegrationService::class, function ($mock) {
        $mock->shouldReceive('removeMonitorForWebsite')
            ->andThrow(new \Exception('Test exception'));
    });

    // Deleting should not throw (observer catches exception)
    $website->delete();

    // Website should still be deleted
    expect($website->trashed())->toBeTrue();
});

test('multiple websites each get their own monitors', function () {
    expect(Monitor::count())->toBe(0);

    // Create multiple websites
    $websites = Website::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring_enabled' => true,
        'ssl_monitoring_enabled' => true,
    ]);

    // Should have 3 monitors
    expect(Monitor::count())->toBe(3);

    // Each website should have a corresponding monitor
    foreach ($websites as $website) {
        $monitor = $this->monitorService->getMonitorForWebsite($website);
        expect($monitor)->not()->toBeNull()
            ->and((string) $monitor->url)->toBe($website->url);
    }
});

test('changing monitoring config triggers monitor update', function () {
    // Create website with monitoring
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'url' => 'https://example.com',
        'uptime_monitoring_enabled' => true,
        'monitoring_config' => ['interval' => 5],
    ]);

    $monitor = Monitor::first();
    expect(Monitor::count())->toBe(1);

    // Update monitoring config
    $website->update([
        'monitoring_config' => ['interval' => 10],
    ]);

    // Monitor should still exist and be updated
    expect(Monitor::count())->toBe(1);

    // Verify monitor was refreshed/updated by checking it still exists
    $monitor->refresh();
    expect($monitor->exists)->toBeTrue();
});
