<?php

use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Models\Website;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->monitor = Monitor::first();
    $this->website = Website::first();
});

test('prunes data older than specified days', function () {
    // Arrange: Create old data (100 days ago)
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now()->subDays(100),
    ]);

    // Create recent data (10 days ago)
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now()->subDays(10),
    ]);

    expect(MonitoringResult::count())->toBe(10);

    // Act: Run prune command
    $this->artisan('monitoring:prune-old-data', ['--days' => 90])
        ->expectsConfirmation('Delete 5 monitoring result records?', 'yes')
        ->assertSuccessful();

    // Assert: Should have deleted old data only
    expect(MonitoringResult::count())->toBe(5);
});

test('dry run does not delete data', function () {
    // Arrange: Create old data
    MonitoringResult::factory()->count(5)->create([
        'monitor_id' => $this->monitor->id,
        'website_id' => $this->website->id,
        'started_at' => now()->subDays(100),
    ]);

    // Act: Run prune command with dry-run flag
    $this->artisan('monitoring:prune-old-data', ['--days' => 90, '--dry-run' => true])
        ->assertSuccessful();

    // Assert: All data should still exist
    expect(MonitoringResult::count())->toBe(5);
});
