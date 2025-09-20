<?php

use App\Models\User;

it('can switch between all navigation and layout modes', function () {
    // Use existing test user
    $user = User::where('email', 'bonzo@konjscina.com')->first();

    if (!$user) {
        $user = User::factory()->create([
            'email' => 'bonzo@konjscina.com',
            'password' => bcrypt('to16ro12')
        ]);
    }

    // Visit the login page and authenticate
    $page = visit('/login')
        ->fill('email', 'bonzo@konjscina.com')
        ->fill('password', 'to16ro12')
        ->submit();

    // Should be redirected to dashboard
    $page->assertSee('SSL Monitor Dashboard')
        ->assertNoJavascriptErrors();

    // Take screenshot of default state (vertical + full)
    $page->screenshot(filename: 'navigation-vertical-full');

    // Open theme customizer
    $page->click('[data-test="theme-customizer-toggle"]')
        ->wait(1);

    // Test Layout Mode - Switch to Boxed
    $page->click('[data-test="layout-boxed-layout"]')
        ->wait(2)
        ->screenshot(filename: 'navigation-vertical-boxed');

    // Switch back to Full Screen
    $page->click('[data-test="layout-full"]')
        ->wait(2)
        ->screenshot(filename: 'navigation-vertical-full-switched');

    // Test Navigation Mode - Switch to Horizontal
    $page->click('[data-test="navigation-horizontal"]')
        ->wait(2)
        ->screenshot(filename: 'navigation-horizontal-full');

    // Test Horizontal + Boxed combination
    $page->click('[data-test="layout-boxed-layout"]')
        ->wait(2)
        ->screenshot(filename: 'navigation-horizontal-boxed');

    // Switch back to Vertical mode
    $page->click('[data-test="navigation-vertical"]')
        ->wait(2)
        ->screenshot(filename: 'navigation-vertical-final');

    // Verify all combinations work without errors
    $page->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    // Close theme customizer
    $page->click('[data-test="customizer-close"]');
});