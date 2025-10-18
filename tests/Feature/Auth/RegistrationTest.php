<?php

use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    $email = 'test-'.time().'@example.com';

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest(); // User should not be logged in automatically
    $response->assertRedirect(route('registration.success', ['email' => $email], absolute: false));
});

test('unverified users cannot access dashboard', function () {
    $user = \App\Models\User::factory()->create([
        'email_verified_at' => null, // Unverified user
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('verification.notice'));
});
