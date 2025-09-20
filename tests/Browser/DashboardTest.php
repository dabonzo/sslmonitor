<?php

it('can take screenshot of dashboard with default user', function () {
    // Use the default user we created with tinker
    $page = visit('/login');

    $page->fill('email', 'admin@sslmonitor.test')
        ->fill('password', 'password123')
        ->submit()
        ->wait(2)
        ->assertSee('Dashboard')
        ->screenshot(filename: 'dashboard-authenticated')
        ->screenshot(fullPage: true, filename: 'dashboard-authenticated-full');
});