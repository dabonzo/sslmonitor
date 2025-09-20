<?php

use App\Livewire\Settings\EmailSettings;
use App\Models\EmailSettings as EmailSettingsModel;
use App\Models\User;
use Livewire\Livewire;

test('authenticated user can access email settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/email')
        ->assertStatus(200)
        ->assertSeeLivewire(EmailSettings::class);
});

test('guest cannot access email settings page', function () {
    $this->get('/settings/email')
        ->assertRedirect('/login');
});

test('user can save email settings', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EmailSettings::class)
        ->set('host', 'smtp.example.com')
        ->set('port', 587)
        ->set('encryption', 'tls')
        ->set('username', 'user@example.com')
        ->set('password', 'password123')
        ->set('from_address', 'noreply@example.com')
        ->set('from_name', 'SSL Monitor')
        ->set('timeout', 30)
        ->set('verify_peer', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('isEditing', false);

    expect(EmailSettingsModel::active())->not->toBeNull();
    expect(EmailSettingsModel::active()->host)->toBe('smtp.example.com');
});

test('email settings form validates required fields', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(EmailSettings::class)
        ->set('host', '')
        ->set('from_address', '')
        ->set('from_name', '')
        ->call('save')
        ->assertHasErrors(['host', 'from_address', 'from_name']);
});

test('user can test email configuration', function () {
    $user = User::factory()->create();
    
    $settings = EmailSettingsModel::create([
        'host' => 'smtp.example.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'user@example.com',
        'password' => 'password123',
        'from_address' => 'noreply@example.com',
        'from_name' => 'SSL Monitor',
        'timeout' => 30,
        'verify_peer' => true,
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(EmailSettings::class)
        ->call('testEmail')
        ->assertSet('isTesting', false);

    // Verify the test was attempted (should have a result)
    $settings->refresh();
    expect($settings->last_tested_at)->not->toBeNull();
});
