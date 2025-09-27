<?php

it('loads analytics dashboard with comprehensive functionality', function () {
    $page = visit('/analytics');

    $page->assertSee('Analytics')
         ->screenshot(filename: 'analytics-dashboard');
});

it('displays bulk operations interface', function () {
    $page = visit('/ssl/bulk-operations');

    $page->assertSee('Bulk Operations')
         ->screenshot(filename: 'bulk-operations');
});

it('shows reports dashboard with data', function () {
    $page = visit('/reports');

    $page->assertSee('Reports')
         ->screenshot(filename: 'reports-dashboard');
});

it('loads advanced alerting system', function () {
    $page = visit('/settings/alerts');

    $page->assertSee('Alert Configuration')
         ->screenshot(filename: 'alert-system');
});

it('works on mobile devices', function () {
    $page = visit('/')->on()->mobile();

    $page->assertSee('Dashboard')
         ->screenshot(filename: 'mobile-dashboard');
});

it('supports dark mode switching', function () {
    $page = visit('/')->inDarkMode();

    $page->assertSee('Dashboard')
         ->screenshot(filename: 'dark-mode-dashboard');
});

it('has no javascript errors on main pages', function () {
    $pages = visit(['/', '/analytics', '/reports', '/ssl/bulk-operations']);

    $pages->assertNoJavaScriptErrors();
});