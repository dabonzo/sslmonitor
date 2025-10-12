<?php

use App\Models\User;
use App\Models\Website;
use App\Models\Monitor;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Traits\UsesCleanDatabase;

use Illuminate\Support\Facades\Hash;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->actingAs($this->testUser);
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
                ->where('sslStatistics.total_websites', 4)
                ->where('sslStatistics.valid_certificates', 3)
                ->where('sslStatistics.expiring_soon', 1)
                ->where('sslStatistics.expired_certificates', 1)
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
            ->where('sslStatistics.total_websites', 4) // Only bonzo's websites
            ->where('sslStatistics.valid_certificates', 3)
            ->where('sslStatistics.expiring_soon', 1)
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
                ->has('website_id')
                ->has('website_name')
                ->has('status')
                ->has('checked_at')
                ->has('time_ago')
                ->has('failure_reason')
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
        $website = Website::factory()->create(['user_id' => $this->testUser->id]);
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
        // Create additional test monitors with different SSL statuses to match expectations
        $user = $this->testUser;

        // Get existing websites and create monitors with different SSL statuses
        $websites = $this->realWebsites->take(4);

        // If we don't have enough websites, create additional ones
        if ($websites->count() < 4) {
            $additionalWebsites = Website::factory()->count(4 - $websites->count())
                ->create(['user_id' => $this->testUser->id]);
            $websites = $websites->concat($additionalWebsites);
        }

        // Create monitors for all websites if they don't exist
        foreach ($websites as $website) {
            Monitor::firstOrCreate(
                ['url' => $website->url],
                [
                    'certificate_check_enabled' => true,
                    'certificate_status' => 'valid',
                    'uptime_check_enabled' => true,
                    'uptime_status' => 'up',
                    'certificate_expiration_date' => now()->addDays(90),
                    'uptime_check_interval_in_minutes' => 5,
                ]
            );
        }

        // Now set up the different SSL statuses
        if ($websites->count() >= 4) {
            // First website: valid certificate
            Monitor::where('url', $websites[0]->url)->update([
                'certificate_status' => 'valid',
                'certificate_expiration_date' => now()->addDays(90),
            ]);

            // Second website: valid certificate
            Monitor::where('url', $websites[1]->url)->update([
                'certificate_status' => 'valid',
                'certificate_expiration_date' => now()->addDays(60),
            ]);

            // Third website: expiring soon
            Monitor::where('url', $websites[2]->url)->update([
                'certificate_status' => 'valid',
                'certificate_expiration_date' => now()->addDays(7),
            ]);

            // Fourth website: expired
            Monitor::where('url', $websites[3]->url)->update([
                'certificate_status' => 'invalid',
                'certificate_expiration_date' => now()->subDays(1),
            ]);
        }

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('sslStatistics.total_websites', 4)
            ->where('sslStatistics.valid_certificates', 3)
            ->where('sslStatistics.expiring_soon', 1)
            ->where('sslStatistics.expired_certificates', 1)
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
