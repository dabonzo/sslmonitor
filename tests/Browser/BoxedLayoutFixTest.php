<?php

use App\Models\User;

it('fixes boxed layout horizontal navigation sidebar issue', function () {
    $user = User::factory()->create();

    visit('/dashboard', function ($page) use ($user) {
        $page->actingAs($user)
            ->assertSee('SSL Monitor Dashboard')
            ->wait(2)

            // Open theme customizer
            ->click('[data-test="theme-customizer-toggle"]')
            ->wait(1)

            // Set to horizontal navigation mode first
            ->click('[data-test="navigation-horizontal"]')
            ->wait(1)

            // Then set to boxed layout
            ->click('[data-test="layout-boxed-layout"]')
            ->wait(2)

            // Take screenshot to show the fix
            ->screenshot('boxed-horizontal-after-fix')

            // Verify no JavaScript errors
            ->assertNoJavascriptErrors();
    });
});

it('verifies boxed layout vertical navigation works correctly', function () {
    $user = User::factory()->create();

    visit('/dashboard', function ($page) use ($user) {
        $page->actingAs($user)
            ->assertSee('SSL Monitor Dashboard')
            ->wait(2)

            // Open theme customizer
            ->click('[data-test="theme-customizer-toggle"]')
            ->wait(1)

            // Set to vertical navigation mode
            ->click('[data-test="navigation-vertical"]')
            ->wait(1)

            // Then set to boxed layout
            ->click('[data-test="layout-boxed-layout"]')
            ->wait(2)

            // Take screenshot to show vertical mode works
            ->screenshot('boxed-vertical-working')

            // Verify no JavaScript errors
            ->assertNoJavascriptErrors();
    });
});