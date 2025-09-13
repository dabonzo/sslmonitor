<?php

declare(strict_types=1);

use App\Livewire\SslDashboard;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('dashboard calculates uptime statistics for websites with uptime monitoring', function () {
    // Create websites with different uptime statuses
    $upWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
        'last_uptime_check_at' => now(),
    ]);

    $downWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'down',
        'last_uptime_check_at' => now(),
    ]);

    $slowWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'slow',
        'last_uptime_check_at' => now(),
    ]);

    $sslOnlyWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => false,
    ]);

    $component = Livewire::test(SslDashboard::class);

    // Test uptime status counts
    $uptimeStats = $component->get('uptimeStatusCounts');
    expect($uptimeStats['up'])->toBe(1);
    expect($uptimeStats['down'])->toBe(1);
    expect($uptimeStats['slow'])->toBe(1);
    expect($uptimeStats['content_mismatch'])->toBe(0);
    expect($uptimeStats['unknown'])->toBe(0);
    expect($uptimeStats['total_monitored'])->toBe(3);
    expect($uptimeStats['total_websites'])->toBe(4);
});

test('dashboard calculates uptime percentages correctly', function () {
    // Create 10 websites with different statuses for easy percentage calculation
    Website::factory()->count(6)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
        'last_uptime_check_at' => now(),
    ]);

    Website::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'down',
        'last_uptime_check_at' => now(),
    ]);

    Website::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'slow',
        'last_uptime_check_at' => now(),
    ]);

    $component = Livewire::test(SslDashboard::class);

    $uptimePercentages = $component->get('uptimeStatusPercentages');
    expect($uptimePercentages['up'])->toBe(60.0); // 6/10 * 100
    expect($uptimePercentages['down'])->toBe(20.0); // 2/10 * 100
    expect($uptimePercentages['slow'])->toBe(20.0); // 2/10 * 100
});

test('dashboard shows uptime availability percentage', function () {
    // Create websites with mixed statuses
    Website::factory()->count(7)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
        'last_uptime_check_at' => now(),
    ]);

    Website::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'slow',
        'last_uptime_check_at' => now(),
    ]);

    Website::factory()->count(1)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'down',
        'last_uptime_check_at' => now(),
    ]);

    $component = Livewire::test(SslDashboard::class);

    // Up + slow = available, down = unavailable
    // (7 + 2) / 10 = 90% availability
    $availability = $component->get('uptimeAvailability');
    expect($availability)->toBe(90.0);
});

test('dashboard handles websites without uptime checks gracefully', function () {
    // Create websites with uptime monitoring but no checks yet
    Website::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'unknown',
        'last_uptime_check_at' => null,
    ]);

    $component = Livewire::test(SslDashboard::class);

    $uptimeStats = $component->get('uptimeStatusCounts');
    expect($uptimeStats['unknown'])->toBe(3);
    expect($uptimeStats['total_monitored'])->toBe(3);

    // Availability should be 0% if all are unknown
    $availability = $component->get('uptimeAvailability');
    expect($availability)->toBe(0.0);
});

test('dashboard returns empty uptime stats when no websites exist', function () {
    $component = Livewire::test(SslDashboard::class);

    $uptimeStats = $component->get('uptimeStatusCounts');
    expect($uptimeStats['total_monitored'])->toBe(0);
    expect($uptimeStats['total_websites'])->toBe(0);

    $availability = $component->get('uptimeAvailability');
    expect($availability)->toBe(0.0);
});

test('dashboard only includes user accessible websites in uptime stats', function () {
    $otherUser = User::factory()->create();

    // User's websites
    Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
    ]);

    // Other user's websites (should not be included)
    Website::factory()->count(5)->create([
        'user_id' => $otherUser->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
    ]);

    $component = Livewire::test(SslDashboard::class);

    $uptimeStats = $component->get('uptimeStatusCounts');
    expect($uptimeStats['up'])->toBe(1);
    expect($uptimeStats['total_monitored'])->toBe(1);
});
