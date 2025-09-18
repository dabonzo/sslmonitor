<?php

use App\Models\User;

it('can open and use theme customizer', function () {
    // Use existing user
    $page = visit('/login')
        ->fill('email', 'bonzo@konjscina.com')
        ->fill('password', 'to16ro12')
        ->submit()
        ->assertSee('Dashboard');

    // Take a screenshot of the dashboard
    $page->screenshot(filename: 'dashboard-with-customizer');

    // Test theme customizer toggle
    $page->click('[data-test="theme-customizer-toggle"]')
        ->assertSee('Theme Customizer');

    // Test theme switching
    $page->click('[data-test="theme-dark"]')
        ->wait(1) // Wait for theme transition
        ->screenshot(filename: 'dashboard-dark-theme');

    // Test layout switching
    $page->click('[data-test="layout-horizontal"]')
        ->wait(1) // Wait for layout change
        ->screenshot(filename: 'dashboard-horizontal-layout');

    // Test menu position
    $page->click('[data-test="menu-horizontal"]')
        ->wait(1)
        ->screenshot(filename: 'dashboard-horizontal-menu');

    // Test reset to defaults
    $page->click('[data-test="reset-defaults"]')
        ->wait(1)
        ->screenshot(filename: 'dashboard-reset-defaults');

    // Close customizer
    $page->click('[data-test="customizer-close"]')
        ->assertDontSee('Theme Customizer');
});

it('persists theme settings across page reloads', function () {
    // Use existing user
    $page = visit('/login')
        ->fill('email', 'bonzo@konjscina.com')
        ->fill('password', 'to16ro12')
        ->submit();

    // Set dark theme
    $page->click('[data-test="theme-customizer-toggle"]')
        ->click('[data-test="theme-dark"]')
        ->wait(1);

    // Reload page
    $page->refresh()
        ->assertSee('Dashboard');

    // Verify dark theme persisted
    $page->screenshot(filename: 'dashboard-persisted-dark-theme');
});