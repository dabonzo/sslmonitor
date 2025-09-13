<?php

declare(strict_types=1);

use App\Livewire\WebsiteManagement;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('component can be rendered', function () {
    Livewire::test(WebsiteManagement::class)
        ->assertStatus(200)
        ->assertSee('Websites')
        ->assertSee('Add your first website');
});

test('component can create website with uptime monitoring enabled', function () {
    Livewire::test(WebsiteManagement::class)
        ->set('name', 'Test Website')
        ->set('url', 'https://example.com')
        ->set('uptime_monitoring', true)
        ->set('expected_status_code', 200)
        ->set('expected_content', 'Welcome')
        ->set('forbidden_content', 'Error')
        ->set('max_response_time', 5000)
        ->call('save')
        ->assertHasNoErrors();

    expect(Website::count())->toBe(1);

    $website = Website::first();
    expect($website->name)->toBe('Test Website');
    expect($website->url)->toBe('https://example.com');
    expect($website->uptime_monitoring)->toBeTrue();
    expect($website->expected_status_code)->toBe(200);
    expect($website->expected_content)->toBe('Welcome');
    expect($website->forbidden_content)->toBe('Error');
    expect($website->max_response_time)->toBe(5000);
    expect($website->follow_redirects)->toBeTrue(); // Default
    expect($website->max_redirects)->toBe(3); // Default
});

test('component can create website with uptime monitoring disabled', function () {
    Livewire::test(WebsiteManagement::class)
        ->set('name', 'SSL Only Website')
        ->set('url', 'https://example.com')
        ->set('uptime_monitoring', false)
        ->call('save')
        ->assertHasNoErrors();

    $website = Website::first();
    expect($website->uptime_monitoring)->toBeFalse();
    expect($website->expected_status_code)->toBe(200); // Default
});

test('component validates uptime monitoring settings when enabled', function () {
    Livewire::test(WebsiteManagement::class)
        ->set('name', 'Test Website')
        ->set('url', 'https://example.com')
        ->set('uptime_monitoring', true)
        ->set('expected_status_code', 600) // Invalid status code
        ->set('max_response_time', -100) // Invalid response time
        ->call('save')
        ->assertHasErrors(['expected_status_code', 'max_response_time']);
});

test('component shows uptime monitoring form fields when enabled', function () {
    Livewire::test(WebsiteManagement::class)
        ->set('uptime_monitoring', true)
        ->assertSee('Expected Status Code')
        ->assertSee('Expected Content')
        ->assertSee('Forbidden Content')
        ->assertSee('Max Response Time')
        ->assertSee('Follow Redirects');
});

test('component hides uptime monitoring form fields when disabled', function () {
    Livewire::test(WebsiteManagement::class)
        ->set('uptime_monitoring', false)
        ->assertDontSee('Expected Status Code')
        ->assertDontSee('Expected Content')
        ->assertDontSee('Max Response Time');
});

test('component can update website with uptime monitoring settings', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Original Name',
        'uptime_monitoring' => false,
    ]);

    Livewire::test(WebsiteManagement::class)
        ->call('edit', $website->id)
        ->set('name', 'Updated Name')
        ->set('uptime_monitoring', true)
        ->set('expected_status_code', 201)
        ->set('expected_content', 'Success')
        ->set('max_response_time', 10000)
        ->call('save')
        ->assertHasNoErrors();

    $website->refresh();
    expect($website->name)->toBe('Updated Name');
    expect($website->uptime_monitoring)->toBeTrue();
    expect($website->expected_status_code)->toBe(201);
    expect($website->expected_content)->toBe('Success');
    expect($website->max_response_time)->toBe(10000);
});

test('component can disable uptime monitoring for existing website', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'expected_status_code' => 201,
        'expected_content' => 'Test content',
    ]);

    Livewire::test(WebsiteManagement::class)
        ->call('edit', $website->id)
        ->set('uptime_monitoring', false)
        ->call('save')
        ->assertHasNoErrors();

    $website->refresh();
    expect($website->uptime_monitoring)->toBeFalse();
    // Settings should be preserved
    expect($website->expected_status_code)->toBe(201);
    expect($website->expected_content)->toBe('Test content');
});

test('component validates redirect settings when follow redirects is enabled', function () {
    Livewire::test(WebsiteManagement::class)
        ->set('name', 'Test Website')
        ->set('url', 'https://example.com')
        ->set('uptime_monitoring', true)
        ->set('follow_redirects', true)
        ->set('max_redirects', 0) // Invalid when follow_redirects is true
        ->call('save')
        ->assertHasErrors(['max_redirects']);
});

test('component shows uptime monitoring toggle', function () {
    Livewire::test(WebsiteManagement::class)
        ->assertSee('Enable Uptime Monitoring')
        ->assertSeeHtml('wire:model.live="uptime_monitoring"');
});

test('component shows redirect settings when follow redirects is enabled', function () {
    Livewire::test(WebsiteManagement::class)
        ->set('uptime_monitoring', true)
        ->set('follow_redirects', true)
        ->assertSee('Max Redirects');
});

test('component hides max redirects when follow redirects is disabled', function () {
    Livewire::test(WebsiteManagement::class)
        ->set('uptime_monitoring', true)
        ->set('follow_redirects', false)
        ->assertDontSee('Max Redirects');
});

test('component populates form with existing uptime monitoring settings on edit', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Site',
        'url' => 'https://test.com',
        'uptime_monitoring' => true,
        'expected_status_code' => 201,
        'expected_content' => 'Test Content',
        'forbidden_content' => 'Error Page',
        'max_response_time' => 8000,
        'follow_redirects' => false,
        'max_redirects' => 5,
    ]);

    $component = Livewire::test(WebsiteManagement::class)
        ->call('edit', $website->id);

    expect($component->get('name'))->toBe('Test Site');
    expect($component->get('url'))->toBe('https://test.com');
    expect($component->get('uptime_monitoring'))->toBeTrue();
    expect($component->get('expected_status_code'))->toBe(201);
    expect($component->get('expected_content'))->toBe('Test Content');
    expect($component->get('forbidden_content'))->toBe('Error Page');
    expect($component->get('max_response_time'))->toBe(8000);
    expect($component->get('follow_redirects'))->toBeFalse();
    expect($component->get('max_redirects'))->toBe(5);
});

test('component shows uptime status badges when uptime monitoring is enabled', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
        'last_uptime_check_at' => now(),
    ]);

    Livewire::test(WebsiteManagement::class)
        ->assertSee('Up')
        ->assertSee($website->name);
});

test('component shows down status with red badge', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'down',
        'last_uptime_check_at' => now(),
    ]);

    Livewire::test(WebsiteManagement::class)
        ->assertSee('Down')
        ->assertSee($website->name);
});

test('component shows slow status with yellow badge', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'slow',
        'last_uptime_check_at' => now(),
    ]);

    Livewire::test(WebsiteManagement::class)
        ->assertSee('Slow')
        ->assertSee($website->name);
});

test('component shows content mismatch status with orange badge', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'content_mismatch',
        'last_uptime_check_at' => now(),
    ]);

    Livewire::test(WebsiteManagement::class)
        ->assertSee('Content Issue')
        ->assertSee($website->name);
});

test('component shows unknown status when no check has been performed', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'unknown',
        'last_uptime_check_at' => null,
    ]);

    Livewire::test(WebsiteManagement::class)
        ->assertSee('Unknown')
        ->assertSee($website->name);
});

test('component hides uptime status when monitoring is disabled', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => false,
        'uptime_status' => 'up',
    ]);

    Livewire::test(WebsiteManagement::class)
        ->assertSee('SSL Only')
        ->assertSee($website->name);
});

test('component shows last check time for uptime monitoring', function () {
    $checkTime = now()->subMinutes(5);
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
        'last_uptime_check_at' => $checkTime,
    ]);

    Livewire::test(WebsiteManagement::class)
        ->assertSee('Last checked:')
        ->assertSee($checkTime->diffForHumans());
});

test('component shows never checked message when no uptime check performed', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'unknown',
        'last_uptime_check_at' => null,
    ]);

    Livewire::test(WebsiteManagement::class)
        ->assertSee('Never checked');
});

test('component shows both ssl and uptime status when both enabled', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'uptime_status' => 'up',
        'last_uptime_check_at' => now(),
    ]);

    Livewire::test(WebsiteManagement::class)
        ->assertSee('Up') // Uptime status
        ->assertDontSee('SSL Only'); // Should not show SSL only badge
});

test('component shows manual check uptime button for enabled websites', function () {
    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
    ]);

    Livewire::test(WebsiteManagement::class)
        ->assertSee('Check Uptime')
        ->assertSeeHtml('wire:click="checkUptime('.$website->id.')"');
});

test('component can manually trigger uptime check', function () {
    Queue::fake();

    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
    ]);

    Livewire::test(WebsiteManagement::class)
        ->call('checkUptime', $website->id)
        ->assertHasNoErrors()
        ->assertDispatched('uptime-check-queued');

    Queue::assertPushed(\App\Jobs\CheckWebsiteUptimeJob::class);
});
