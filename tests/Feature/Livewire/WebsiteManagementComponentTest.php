<?php

use App\Models\User;
use App\Models\Website;
use Livewire\Livewire;

test('authenticated users can view website management page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/websites')
        ->assertStatus(200)
        ->assertSeeLivewire('website-management');
});

test('guests cannot access website management page', function () {
    $this->get('/websites')
        ->assertRedirect('/login');
});

test('users can add a new website', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('website-management')
        ->set('name', 'My Website')
        ->set('url', 'https://example.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('website-added');

    expect(Website::where('name', 'My Website')->where('user_id', $user->id)->exists())->toBeTrue();
});

test('website form validates required fields', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('website-management')
        ->set('name', '')
        ->set('url', '')
        ->call('save')
        ->assertHasErrors(['name', 'url']);
});

test('website form validates url format', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('website-management')
        ->set('name', 'Test Site')
        ->set('url', 'not-a-valid-url')
        ->call('save')
        ->assertHasErrors(['url']);
});

test('users can edit existing websites', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original Name',
        'url' => 'https://original.com',
    ]);

    Livewire::actingAs($user)
        ->test('website-management')
        ->call('edit', $website->id)
        ->set('name', 'Updated Name')
        ->set('url', 'https://updated.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('website-updated');

    $website->refresh();
    expect($website->name)->toBe('Updated Name')
        ->and($website->url)->toBe('https://updated.com');
});

test('users can delete websites', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test('website-management')
        ->call('delete', $website->id)
        ->assertDispatched('website-deleted');

    expect(Website::find($website->id))->toBeNull();
});

test('users cannot edit websites belonging to other users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user2->id]);

    Livewire::actingAs($user1)
        ->test('website-management')
        ->call('edit', $website->id)
        ->assertForbidden();
});

test('users cannot delete websites belonging to other users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user2->id]);

    Livewire::actingAs($user1)
        ->test('website-management')
        ->call('delete', $website->id)
        ->assertForbidden();
});

test('website management shows user websites list', function () {
    $user = User::factory()->create();
    $userWebsite = Website::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Site',
        'url' => 'https://mysite.com',
    ]);
    
    // Create another user's website to ensure it's not shown
    $otherUser = User::factory()->create();
    $otherWebsite = Website::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Site',
    ]);

    Livewire::actingAs($user)
        ->test('website-management')
        ->assertSee('My Site')
        ->assertSee('https://mysite.com')
        ->assertDontSee('Other Site');
});

test('check before adding workflow shows ssl certificate preview', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('website-management')
        ->set('url', 'https://github.com')
        ->call('checkCertificate')
        ->assertSet('certificatePreview.status', function ($status) {
            return in_array($status, ['valid', 'expiring_soon', 'expired', 'invalid', 'error']);
        })
        ->assertNotSet('certificatePreview', null);
});

test('check certificate handles invalid urls gracefully', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('website-management')
        ->set('url', 'https://non-existent-domain-12345.com')
        ->call('checkCertificate')
        ->assertSet('certificatePreview.status', 'error')
        ->assertSet('certificatePreview.error_message', fn($message) => !empty($message));
});

test('user can add website after previewing certificate', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('website-management')
        ->set('name', 'GitHub')
        ->set('url', 'https://github.com')
        ->call('checkCertificate')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('website-added');

    expect(Website::where('name', 'GitHub')->where('user_id', $user->id)->exists())->toBeTrue();
});

test('website management component resets form after saving', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('website-management')
        ->set('name', 'Test Site')
        ->set('url', 'https://test.com')
        ->call('save')
        ->assertSet('name', '')
        ->assertSet('url', '')
        ->assertSet('certificatePreview', null);
});

test('website management component shows loading states', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test('website-management')
        ->set('url', 'https://github.com');

    // Should not be checking initially
    $component->assertSet('isCheckingCertificate', false);
    
    // Should show loading during check (we can't easily test the intermediate state)
    $component->call('checkCertificate');
    
    // Should not be checking after completion
    $component->assertSet('isCheckingCertificate', false);
});