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

test('dashboard shows uptime monitoring summary when websites exist', function () {
    // Create a mix of SSL-only and uptime-monitored websites
    Website::factory()->count(5)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => false, // SSL only
    ]);

    Website::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
    ]);

    Livewire::test(SslDashboard::class)
        ->assertSee('8 websites') // Total websites
        ->assertSee('3 monitored'); // Uptime monitored websites
});

test('dashboard shows correct uptime monitoring overview statistics', function () {
    // Create websites with different uptime statuses
    Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
        'last_uptime_check_at' => now(),
    ]);

    Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'down',
        'last_uptime_check_at' => now(),
    ]);

    Website::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'unknown', // Not checked yet
        'last_uptime_check_at' => null,
    ]);

    Website::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => false, // SSL only
    ]);

    $component = Livewire::test(SslDashboard::class);

    // Should show total websites and monitored count
    $component->assertSee('7 websites'); // Total
    $component->assertSee('4 monitored'); // Uptime monitored

    // Should show overall health status
    $component->assertSee('25.0%'); // 1 up out of 4 monitored = 25% availability
});

test('dashboard shows uptime monitoring overview section only when websites exist', function () {
    // No websites at all
    Livewire::test(SslDashboard::class)
        ->assertDontSee('Website Overview') // Should not show overview section
        ->assertSee('No SSL certificates to monitor yet'); // Should show empty state
});

test('dashboard shows different overview when only SSL monitoring exists', function () {
    // Only SSL monitoring websites, no uptime monitoring
    Website::factory()->count(4)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => false,
    ]);

    Livewire::test(SslDashboard::class)
        ->assertSee('4 websites') // Should show total
        ->assertSee('SSL only'); // Should indicate no uptime monitoring
});

test('dashboard shows uptime monitoring trends and insights', function () {
    // Create websites with different check times
    Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
        'last_uptime_check_at' => now()->subMinutes(5), // Recent check
    ]);

    Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
        'last_uptime_check_at' => now()->subHours(2), // Older check
    ]);

    Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'unknown',
        'last_uptime_check_at' => null, // Never checked
    ]);

    Livewire::test(SslDashboard::class)
        ->assertSee('3 monitored') // Total monitored
        ->assertSee('66.7%'); // 2 up out of 3 = 66.7% availability
});
