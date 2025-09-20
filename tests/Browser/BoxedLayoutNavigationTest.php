<?php

use App\Models\User;

it('correctly handles boxed layout with horizontal navigation', function () {
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

    // Open theme customizer
    $page->click('[data-test="theme-customizer-toggle"]')
        ->wait(1);

    // Set to horizontal navigation mode first
    $page->click('[data-test="navigation-horizontal"]')
        ->wait(1);

    // Then set to boxed layout
    $page->click('[data-test="layout-boxed-layout"]')
        ->wait(2);

    // Verify the body has both classes applied
    expect($page->script('document.body.classList.contains("horizontal")')[0])
        ->toBe(true, 'Body should have horizontal class');

    expect($page->script('document.body.classList.contains("boxed-layout")')[0])
        ->toBe(true, 'Body should have boxed-layout class');

    // Verify sidebar is positioned off-screen (hidden in horizontal mode)
    $sidebarLeft = $page->script('
        const sidebar = document.querySelector(".sidebar");
        const computedStyle = window.getComputedStyle(sidebar);
        computedStyle.left;
    ')[0];

    expect($sidebarLeft)->toBe('-260px', 'Sidebar should be hidden off-screen in horizontal mode');

    // Verify main content has no left margin (no sidebar space)
    $mainContentMargin = $page->script('
        const mainContent = document.querySelector(".main-content");
        const computedStyle = window.getComputedStyle(mainContent);
        computedStyle.marginLeft;
    ')[0];

    expect($mainContentMargin)->toBe('0px', 'Main content should have no left margin in horizontal mode');

    // Take screenshot for verification
    $page->screenshot('boxed-horizontal-layout-fixed');

    // Verify no JavaScript errors
    $page->assertNoJavascriptErrors();
});

it('correctly handles boxed layout with vertical navigation', function () {
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

    // Open theme customizer
    $page->click('[data-test="theme-customizer-toggle"]')
        ->wait(1);

    // Set to vertical navigation mode (default, but explicit)
    $page->click('[data-test="navigation-vertical"]')
        ->wait(1);

    // Then set to boxed layout
    $page->click('[data-test="layout-boxed-layout"]')
        ->wait(2);

    // Verify the body has both classes
    expect($page->script('document.body.classList.contains("vertical")')[0])
        ->toBe(true, 'Body should have vertical class');

    expect($page->script('document.body.classList.contains("boxed-layout")')[0])
        ->toBe(true, 'Body should have boxed-layout class');

    // Verify sidebar is visible and positioned correctly (not off-screen)
    $sidebarLeft = $page->script('
        const sidebar = document.querySelector(".sidebar");
        const computedStyle = window.getComputedStyle(sidebar);
        computedStyle.left;
    ')[0];

    // In vertical mode, sidebar should be visible (left: 0 or similar)
    expect($sidebarLeft)->not()->toBe('-260px', 'Sidebar should be visible in vertical mode');

    // Take screenshot for verification
    $page->screenshot('boxed-vertical-layout-correct');

    // Verify no JavaScript errors
    $page->assertNoJavascriptErrors();
});