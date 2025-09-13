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

test('dashboard shows uptime monitoring section when websites have uptime monitoring', function () {
    Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
    ]);

    Livewire::test(SslDashboard::class)
        ->assertSee('Uptime Monitoring')
        ->assertSee('Availability');
});

test('dashboard shows uptime status cards with correct counts', function () {
    Website::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
    ]);

    Website::factory()->count(1)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'down',
    ]);

    Website::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => false, // SSL only - should not appear in uptime stats
    ]);

    Livewire::test(SslDashboard::class)
        ->assertSee('3') // Up count
        ->assertSee('1') // Down count
        ->assertSee('75.0%'); // Availability (3 up / 4 monitored)
});

test('dashboard hides uptime monitoring section when no uptime monitoring enabled', function () {
    Website::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => false,
    ]);

    Livewire::test(SslDashboard::class)
        ->assertDontSee('Uptime Monitoring')
        ->assertSee('SSL Certificate Status'); // SSL section should still be visible
});

test('dashboard shows uptime critical issues', function () {
    $downWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Critical Site',
        'uptime_monitoring' => true,
        'uptime_status' => 'down',
        'last_uptime_check_at' => now(),
    ]);

    Livewire::test(SslDashboard::class)
        ->assertSee('Uptime Issues')
        ->assertSee('Critical Site')
        ->assertSee('Website is down');
});

test('dashboard shows mixed ssl and uptime stats correctly', function () {
    // SSL + Uptime monitoring
    Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
    ]);

    // SSL only
    Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => false,
    ]);

    Livewire::test(SslDashboard::class)
        ->assertSee('SSL Certificate Status') // SSL section
        ->assertSee('Uptime Monitoring') // Uptime section
        ->assertSee('1 monitored'); // Should show monitored count
});
