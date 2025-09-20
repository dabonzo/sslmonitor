<?php

declare(strict_types=1);

use App\Models\User;

test('vristo header layout contains all expected elements', function () {
    $user = User::factory()->create([
        'name' => 'Bonzo',
        'email' => 'bonzo@konjscina.com',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk()
        // Check for main header structure
        ->assertSee('SSL Monitor') // Logo and title
        ->assertSee('Dashboard') // Navigation item
        ->assertSee('Websites') // Navigation item
        ->assertSee('Search') // Search tooltip
        ->assertSee('Toggle Theme') // Dark mode toggle tooltip
        ->assertSee($user->name) // User profile with name
        ->assertSee($user->email) // User email in dropdown
        ->assertSee('Profile') // Profile menu item
        ->assertSee('Appearance') // Appearance menu item
        ->assertSee('Email Settings') // Email settings menu item
        ->assertSee('Team') // Team menu item
        ->assertSee('Log Out'); // Logout menu item
});

test('websites page uses vristo header layout', function () {
    $user = User::factory()->create([
        'name' => 'Bonzo',
        'email' => 'bonzo@konjscina.com',
    ]);

    $response = $this->actingAs($user)->get('/websites');

    $response->assertOk()
        ->assertSee('SSL Monitor') // Header logo
        ->assertSee('Dashboard') // Navigation
        ->assertSee('Websites') // Current page
        ->assertSee($user->name); // User profile
});

test('header navigation links are functional', function () {
    $user = User::factory()->create([
        'name' => 'Bonzo',
        'email' => 'bonzo@konjscina.com',
    ]);

    // Test dashboard navigation
    $this->actingAs($user)->get('/dashboard')->assertOk();

    // Test websites navigation
    $this->actingAs($user)->get('/websites')->assertOk();
});
