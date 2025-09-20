<?php

declare(strict_types=1);

use App\Models\User;

test('authenticated users can access layout preferences page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/layout');

    $response->assertOk();
    $response->assertSee('Layout Style');
    $response->assertSee('Navigation Style');
    $response->assertSee('Navbar Type');
});

test('guest users cannot access layout preferences page', function () {
    $response = $this->get('/settings/layout');

    $response->assertRedirect('/login');
});

test('users can update layout preferences', function () {
    $user = User::factory()->create();

    // Directly test the updateLayoutPreferences method
    $user->updateLayoutPreferences([
        'layout' => 'boxed',
        'navigation' => 'sidebar',
        'navbar' => 'floating',
        'semidark' => true,
    ]);

    $user->refresh();
    $preferences = $user->getLayoutPreferences();

    expect($preferences['layout'])->toBe('boxed');
    expect($preferences['navigation'])->toBe('sidebar');
    expect($preferences['navbar'])->toBe('floating');
    expect($preferences['semidark'])->toBeTrue();
});

test('layout preferences have default values', function () {
    $user = User::factory()->create();

    $preferences = $user->getLayoutPreferences();

    expect($preferences['layout'])->toBe('full');
    expect($preferences['navigation'])->toBe('header');
    expect($preferences['navbar'])->toBe('sticky');
    expect($preferences['theme'])->toBe('system');
    expect($preferences['semidark'])->toBeFalse();
});

test('middleware applies layout classes correctly', function () {
    $user = User::factory()->create();
    $user->updateLayoutPreferences([
        'layout' => 'boxed',
        'navigation' => 'sidebar',
        'navbar' => 'floating',
        'semidark' => true,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('boxed-layout');
    $response->assertSee('sidebar-nav');
    $response->assertSee('navbar-floating');
    $response->assertSee('semi-dark');
});

test('layout preferences persist across sessions', function () {
    $user = User::factory()->create();

    // Set preferences
    $user->updateLayoutPreferences([
        'layout' => 'boxed',
        'navigation' => 'sidebar',
    ]);

    // Verify preferences persist in new session
    $newUserInstance = User::find($user->id);
    $preferences = $newUserInstance->getLayoutPreferences();

    expect($preferences['layout'])->toBe('boxed');
    expect($preferences['navigation'])->toBe('sidebar');
});
