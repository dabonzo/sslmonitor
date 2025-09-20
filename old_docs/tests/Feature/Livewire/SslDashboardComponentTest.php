<?php

declare(strict_types=1);

use App\Livewire\SslDashboard;
use App\Models\User;
use App\Models\Website;
use App\Models\SslCheck;
use App\Services\SslStatusCalculator;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('authenticated users can view ssl dashboard', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertSeeLivewire('ssl-dashboard')
        ->assertStatus(200);
});

test('guests cannot access ssl dashboard', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('dashboard shows ssl status summary cards', function () {
    // Create websites with different SSL statuses
    $website1 = Website::factory()->for($this->user)->create();
    $website2 = Website::factory()->for($this->user)->create();
    $website3 = Website::factory()->for($this->user)->create();
    $website4 = Website::factory()->for($this->user)->create();

    // Create SSL checks with different statuses
    SslCheck::factory()->for($website1)->valid()->create();
    SslCheck::factory()->for($website2)->expiringSoon()->create();
    SslCheck::factory()->for($website3)->expired()->create();
    SslCheck::factory()->for($website4)->error()->create();

    Livewire::actingAs($this->user)
        ->test('ssl-dashboard')
        ->assertSee('SSL Certificate Status')
        ->assertSee('Valid')
        ->assertSee('Expiring Soon')
        ->assertSee('Expired')
        ->assertSee('Errors')
        ->assertSet('statusCounts.valid', 1)
        ->assertSet('statusCounts.expiring_soon', 1)
        ->assertSet('statusCounts.expired', 1)
        ->assertSet('statusCounts.error', 1);
});

test('dashboard only shows current user websites', function () {
    $otherUser = User::factory()->create();
    
    // Create websites for both users
    $userWebsite = Website::factory()->for($this->user)->create();
    $otherWebsite = Website::factory()->for($otherUser)->create();
    
    SslCheck::factory()->for($userWebsite)->valid()->create();
    SslCheck::factory()->for($otherWebsite)->valid()->create();

    Livewire::actingAs($this->user)
        ->test('ssl-dashboard')
        ->assertSet('statusCounts.valid', 1)
        ->assertSet('statusCounts.total', 1);
});

test('dashboard shows recent ssl checks list', function () {
    $website1 = Website::factory()->for($this->user)->create(['name' => 'Example Site']);
    $website2 = Website::factory()->for($this->user)->create(['name' => 'Test Site']);
    
    $check1 = SslCheck::factory()->for($website1)->valid()->create();
    $check2 = SslCheck::factory()->for($website2)->expiringSoon()->create();

    Livewire::actingAs($this->user)
        ->test('ssl-dashboard')
        ->assertSee('Recent SSL Checks')
        ->assertSee('Example Site')
        ->assertSee('Test Site')
        ->assertViewHas('recentChecks', function ($checks) use ($check1, $check2) {
            return $checks->contains($check1) && $checks->contains($check2);
        });
});

test('dashboard shows empty state when no websites exist', function () {
    Livewire::actingAs($this->user)
        ->test('ssl-dashboard')
        ->assertSee('No SSL certificates to monitor yet')
        ->assertSee('Add your first website')
        ->assertSet('statusCounts.total', 0);
});

test('dashboard shows websites without ssl checks as pending', function () {
    Website::factory()->for($this->user)->create();

    Livewire::actingAs($this->user)
        ->test('ssl-dashboard')
        ->assertSet('statusCounts.pending', 1)
        ->assertSet('statusCounts.total', 1);
});

test('dashboard calculates status counts correctly', function () {
    // Use separate user to avoid URL conflicts with other tests
    $testUser = User::factory()->create();
    $websites = Website::factory()->for($testUser)->count(5)->create();
    
    // Create different SSL check statuses
    SslCheck::factory()->for($websites[0])->valid()->create();
    SslCheck::factory()->for($websites[1])->valid()->create();
    SslCheck::factory()->for($websites[2])->expiringSoon()->create();
    SslCheck::factory()->for($websites[3])->expired()->create();
    // websites[4] has no SSL checks (pending)

    Livewire::actingAs($testUser)
        ->test('ssl-dashboard')
        ->assertSet('statusCounts.valid', 2)
        ->assertSet('statusCounts.expiring_soon', 1)
        ->assertSet('statusCounts.expired', 1)
        ->assertSet('statusCounts.error', 0)
        ->assertSet('statusCounts.pending', 1)
        ->assertSet('statusCounts.total', 5);
});

test('dashboard can refresh ssl status data', function () {
    $website = Website::factory()->for($this->user)->create();
    SslCheck::factory()->for($website)->valid()->create();

    Livewire::actingAs($this->user)
        ->test('ssl-dashboard')
        ->assertSet('statusCounts.valid', 1)
        ->call('refresh')
        ->assertSet('statusCounts.valid', 1)
        ->assertDispatched('ssl-status-refreshed');
});

test('dashboard shows status percentages correctly', function () {
    $websites = Website::factory()->for($this->user)->count(4)->create();
    
    SslCheck::factory()->for($websites[0])->valid()->create();
    SslCheck::factory()->for($websites[1])->valid()->create();
    SslCheck::factory()->for($websites[2])->expiringSoon()->create();
    SslCheck::factory()->for($websites[3])->expired()->create();

    $component = Livewire::actingAs($this->user)
        ->test('ssl-dashboard');

    // Test computed percentages
    $percentages = $component->instance()->statusPercentages;
    
    expect($percentages['valid'])->toBe(50.0); // 2/4 = 50%
    expect($percentages['expiring_soon'])->toBe(25.0); // 1/4 = 25%
    expect($percentages['expired'])->toBe(25.0); // 1/4 = 25%
    expect($percentages['error'])->toBe(0.0); // 0/4 = 0%
});

test('dashboard handles pagination for recent checks', function () {
    $websites = Website::factory()->for($this->user)->count(15)->create();
    
    foreach ($websites as $website) {
        SslCheck::factory()->for($website)->valid()->create();
    }

    Livewire::actingAs($this->user)
        ->test('ssl-dashboard')
        ->assertSet('recentChecksLimit', 10)
        ->assertViewHas('recentChecks', function ($checks) {
            return $checks->count() === 10;
        });
});

test('dashboard shows critical issues prominently', function () {
    $criticalUser = User::factory()->create();
    $website1 = Website::factory()->for($criticalUser)->create(['name' => 'Critical Site']);
    $website2 = Website::factory()->for($criticalUser)->create(['name' => 'Error Site']);
    
    SslCheck::factory()->for($website1)->expired()->create();
    SslCheck::factory()->for($website2)->error()->create(['error_message' => 'Connection failed']);

    Livewire::actingAs($criticalUser)
        ->test('ssl-dashboard')
        ->assertSee('Critical Issues')
        ->assertSee('Critical Site')
        ->assertSee('Error Site')
        ->assertSee('SSL certificate expired')
        ->assertSee('SSL check failed');
});