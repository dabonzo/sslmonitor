<?php

use App\Models\User;
use App\Models\Website;
use Spatie\UptimeMonitor\Models\Monitor;

it('displays response time data on dashboard', function () {
    // Create a test user and authenticate
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'name' => 'Test User'
    ]);

    $this->actingAs($user);

    // Create websites with monitors that have response times
    $websites = [
        ['url' => 'https://example.com', 'name' => 'Example'],
        ['url' => 'https://test.com', 'name' => 'Test Site'],
        ['url' => 'https://demo.com', 'name' => 'Demo Site'],
    ];

    foreach ($websites as $websiteData) {
        $website = Website::create([
            'name' => $websiteData['name'],
            'url' => $websiteData['url'],
            'user_id' => $user->id,
            'ssl_monitoring_enabled' => true,
            'uptime_monitoring_enabled' => true,
        ]);

        // Create corresponding Spatie monitor with response time
        Monitor::updateOrCreate(
            ['url' => $websiteData['url']],
            [
                'uptime_check_enabled' => true,
                'certificate_check_enabled' => true,
                'uptime_status' => 'up',
                'certificate_status' => 'valid',
                'uptime_check_response_time_in_ms' => rand(100, 500),
                'certificate_expiration_date' => now()->addDays(30),
            ]
        );
    }

    // Visit the dashboard
    $page = visit('/dashboard');

    // Take a screenshot to see the current state
    $page->screenshot('dashboard-response-time-before');

    // Check if response time data is showing
    $page->assertSee('Response Time')
         ->assertNoJavascriptErrors();

    // Take another screenshot after loading
    $page->screenshot('dashboard-response-time-after');
});