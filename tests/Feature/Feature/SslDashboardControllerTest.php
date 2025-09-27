<?php

use App\Models\User;
use App\Models\Website;
use Spatie\UptimeMonitor\Models\Monitor;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    // Use the seeded test user instead of creating a new one
    $this->user = User::firstOrCreate(
        ['email' => 'bonzo@konjscina.com'],
        [
            'name' => 'Bonzo',
            'email' => 'bonzo@konjscina.com',
            'password' => Hash::make('to16ro12'),
            'email_verified_at' => now(),
        ]
    );
    $this->actingAs($this->user);
});

describe('SSL Dashboard Controller', function () {
    it('returns dashboard view with SSL statistics', function () {
        // Seed the test data using the TestUserSeeder pattern
        $this->artisan('db:seed', ['--class' => 'TestUserSeeder']);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('sslStatistics')
                ->where('sslStatistics.total_websites', 3)
                ->where('sslStatistics.valid_certificates', 3)
                ->where('sslStatistics.expiring_soon', 0)
                ->where('sslStatistics.expired_certificates', 0)
                ->has('recentSslActivity')
                ->has('criticalAlerts')
            );
    });

    it('calculates SSL statistics correctly for user websites only', function () {
        // Seed test data
        $this->artisan('db:seed', ['--class' => 'TestUserSeeder']);

        // Create another user with websites to ensure filtering works
        $otherUser = User::factory()->create();
        $otherWebsite = Website::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('sslStatistics.total_websites', 3) // Only bonzo's websites
            ->where('sslStatistics.valid_certificates', 3)
            ->where('sslStatistics.expiring_soon', 0)
        );
    });

    it('returns recent SSL activity for dashboard', function () {
        // Seed test data which creates SSL monitoring data
        $this->artisan('db:seed', ['--class' => 'TestUserSeeder']);

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('recentSslActivity')
            ->has('recentSslActivity.0', fn (Assert $activity) => $activity
                ->has('id')
                ->has('website_name')
                ->has('status')
                ->has('checked_at')
                ->has('time_ago')
            )
        );
    });

    it('identifies critical SSL alerts', function () {
        // Seed test data which includes expired certificate
        $this->artisan('db:seed', ['--class' => 'TestUserSeeder']);

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('criticalAlerts')
            ->has('criticalAlerts.0', fn (Assert $alert) => $alert
                ->where('type', 'ssl_expired')
                ->has('website_name')
                ->has('message')
                ->has('expires_at')
            )
        );
    });

    it('calculates average response time for SSL checks', function () {
        $website = Website::factory()->create(['user_id' => $this->user->id]);
        $testUrl = 'https://response-test-' . hrtime(true) . '.example.com';
        $website->update(['url' => $testUrl]);

        // Create Spatie monitor (Note: Spatie doesn't store SSL response time by default)
        Monitor::firstOrCreate(
            ['url' => $testUrl],
            [
                'certificate_check_enabled' => true,
                'certificate_status' => 'valid',
            ]
        );

        $response = $this->get(route('dashboard'));

        // Since Spatie doesn't track SSL response time, expect 0
        $response->assertInertia(fn (Assert $page) => $page
            ->where('sslStatistics.avg_response_time', 0)
        );
    });

    it('handles dashboard with real user websites', function () {
        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('sslStatistics.total_websites', 3)
            ->where('sslStatistics.valid_certificates', 3)
            ->where('sslStatistics.expiring_soon', 0)
            ->where('sslStatistics.expired_certificates', 0)
            ->has('recentSslActivity')
            ->has('criticalAlerts')
        );
    });

    it('requires authentication to access dashboard', function () {
        auth()->logout();

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    });
});
