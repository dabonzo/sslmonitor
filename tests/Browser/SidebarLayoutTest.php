<?php

use App\Models\User;

it('can view dashboard and analyze sidebar layout', function () {
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

    // Take screenshot for analysis
    $page->screenshot(filename: 'sidebar-layout-analysis');

    // Wait a moment for layout to settle
    $page->wait(2);

    // Take another screenshot after layout settles
    $page->screenshot(filename: 'sidebar-layout-settled');

    // Verify sidebar elements are visible
    $page->assertSee('SSL Monitor') // Logo in sidebar
        ->assertSee('Dashboard') // Menu item
        ->assertSee('SSL Certificates') // Menu item
        ->assertSee('Monitoring'); // Menu item

    // Verify main content is visible
    $page->assertSee('Active Certificates')
        ->assertSee('Expiring Soon')
        ->assertSee('Certificate Status Overview');
});