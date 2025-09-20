<?php

use App\Models\User;
use App\Models\Website;

describe('Vue Migration Browser Tests', function () {

    beforeEach(function () {
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    });

    test('user can login and see Vue dashboard', function () {
        $page = visit('/login');

        $page->assertSee('Email')
             ->assertSee('Password')
             ->fill('email', 'test@example.com')
             ->fill('password', 'password')
             ->click('Log in')
             ->assertUrlIs('/dashboard')
             ->assertSee('Dashboard')
             ->assertSee('SSL Monitor')
             ->assertNoJavaScriptErrors();

        // Take screenshot of dashboard
        $page->screenshot('vue-dashboard');
    });

    test('websites page renders Vue components correctly', function () {
        $this->actingAs($this->user);

        // Create some test websites
        $websites = Website::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $page = visit('/websites');

        $page->assertSee('Website Management')
             ->assertSee('Manage your SSL certificates and uptime monitoring')
             ->assertSee('Add Website')
             ->assertNoJavaScriptErrors();

        // Check that websites are displayed
        foreach ($websites as $website) {
            $page->assertSee($website->name)
                 ->assertSee($website->url);
        }

        // Take screenshot of websites listing
        $page->screenshot('vue-websites-list');
    });

    test('can navigate to website details page', function () {
        $this->actingAs($this->user);

        $website = Website::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Website',
            'url' => 'https://example.com',
        ]);

        $page = visit('/websites');

        $page->click('View Details')
             ->assertSee('Test Website')
             ->assertSee('https://example.com')
             ->assertSee('SSL Certificate Details')
             ->assertSee('Auto-updating')  // Real-time indicator
             ->assertNoJavaScriptErrors();

        // Take screenshot of website details
        $page->screenshot('vue-website-details');
    });

    test('can open and interact with website modal', function () {
        $this->actingAs($this->user);

        $page = visit('/websites');

        $page->click('Add Website')
             ->assertSee('Add New Website')
             ->assertSee('Website URL')
             ->assertSee('Display Name')
             ->fill('name', 'New Test Site')
             ->fill('url', 'https://newsite.com')
             ->assertNoJavaScriptErrors();

        // Take screenshot of website modal
        $page->screenshot('vue-website-modal');

        $page->click('Cancel');
    });

    test('settings pages render Vue components correctly', function () {
        $this->actingAs($this->user);

        // Test Profile settings
        $page = visit('/settings/profile');
        $page->assertSee('Profile Settings')
             ->assertSee('Profile Information')
             ->assertSee('Update Password')
             ->assertSee($this->user->name)
             ->assertSee($this->user->email)
             ->assertNoJavaScriptErrors();
        $page->screenshot('vue-settings-profile');

        // Test Email settings
        $page->navigate('/settings/email');
        $page->assertSee('Email Settings')
             ->assertSee('Email Notifications')
             ->assertSee('SSL Certificate Alerts')
             ->assertSee('Uptime Monitoring')
             ->assertNoJavaScriptErrors();
        $page->screenshot('vue-settings-email');

        // Test Team settings
        $page->navigate('/settings/team');
        $page->assertSee('Team Management')
             ->assertSee('Create or Join a Team')
             ->assertNoJavaScriptErrors();
        $page->screenshot('vue-settings-team');

        // Test Appearance settings
        $page->navigate('/settings/appearance');
        $page->assertSee('Appearance Settings')
             ->assertSee('Theme')
             ->assertSee('Light Theme')
             ->assertSee('Dark Theme')
             ->assertSee('System')
             ->assertNoJavaScriptErrors();
        $page->screenshot('vue-settings-appearance');
    });

    test('theme switching works correctly', function () {
        $this->actingAs($this->user);

        $page = visit('/settings/appearance');

        // Test dark theme
        $page->click('Dark Theme')
             ->wait(500)  // Wait for theme change
             ->screenshot('vue-dark-theme');

        // Test light theme
        $page->click('Light Theme')
             ->wait(500)
             ->screenshot('vue-light-theme');
    });

    test('Vue test route works correctly', function () {
        $page = visit('/test-vue');

        $page->assertSee('🎉 Success!')
             ->assertSee('Vue + Inertia.js is working!')
             ->assertSee('Laravel 12 + Vue 3 + Inertia.js + Pinia + TailwindCSS v4')
             ->assertSee('Go to Login')
             ->assertSee('Test Pinia State')
             ->assertNoJavaScriptErrors();

        // Test Pinia state management
        $page->click('Test Pinia State')
             ->assertSee('Test Pinia State: 1')
             ->click('Test Pinia State')
             ->assertSee('Test Pinia State: 2');

        $page->screenshot('vue-test-page');
    });

    test('navigation between pages works with Inertia', function () {
        $this->actingAs($this->user);

        $page = visit('/dashboard');

        // Test navigation via menu
        $page->click('Websites')
             ->assertUrlIs('/websites')
             ->assertSee('Website Management');

        // Test back navigation
        $page->click('Dashboard')
             ->assertUrlIs('/dashboard')
             ->assertSee('Monitor your SSL certificates');
    });

    test('responsive design works on mobile', function () {
        $this->actingAs($this->user);

        $page = visit('/dashboard')->on()->mobile();

        $page->assertSee('Dashboard')
             ->assertSee('SSL Monitor')
             ->assertNoJavaScriptErrors();

        $page->screenshot('vue-mobile-dashboard');
    });

    test('no smoke test for all main pages', function () {
        $this->actingAs($this->user);

        $pages = visit([
            '/dashboard',
            '/websites',
            '/settings/profile',
            '/settings/email',
            '/settings/team',
            '/settings/appearance',
            '/test-vue'
        ]);

        $pages->assertNoSmoke()
             ->assertNoConsoleLogs()
             ->assertNoJavaScriptErrors();
    });
});
