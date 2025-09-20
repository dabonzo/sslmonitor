<?php

use App\Models\User;

it('shows professional dark theme style', function () {
    // Just take a simple screenshot without login complexity
    $page = visit('/')
        ->screenshot(filename: 'homepage-current-theme');
});

it('demonstrates the boxed layout fix for horizontal navigation', function () {
    $user = User::factory()->create();

    $page = visit('/login');

    $page->fill('email', $user->email)
        ->fill('password', 'password')
        ->submit()
        ->assertSee('SSL Monitor Dashboard')
        ->screenshot(filename: '01-dashboard-default')

        // Open theme customizer
        ->click('[data-test="theme-customizer-toggle"]')
        ->wait(1)
        ->screenshot(filename: '02-customizer-opened')

        // Set horizontal navigation first
        ->click('[data-test="navigation-horizontal"]')
        ->wait(1)
        ->screenshot(filename: '03-horizontal-mode')

        // Then add boxed layout - this should NOT show sidebar space
        ->click('[data-test="layout-boxed-layout"]')
        ->wait(2)
        ->screenshot(filename: '04-horizontal-boxed-FIXED')

        // Compare with vertical boxed (which should show sidebar)
        ->click('[data-test="navigation-vertical"]')
        ->wait(2)
        ->screenshot(filename: '05-vertical-boxed-correct')

        ->assertNoJavascriptErrors();
});