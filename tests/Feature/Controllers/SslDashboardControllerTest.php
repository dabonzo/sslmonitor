<?php

use App\Models\Monitor;
use App\Models\User;
use App\Models\Website;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Traits\UsesCleanDatabase;

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
        $testUrl = 'https://response-test-'.hrtime(true).'.example.com';
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
        // Clear existing test data from Pest.php beforeEach to ensure clean state
        Website::where('user_id', $this->testUser->id)->delete();
        Monitor::whereIn('url', ['https://omp.office-manager-pro.com', 'https://www.redgas.at', 'https://www.fairnando.at', 'https://www.gebrauchte.at'])->delete();

        // Create test data efficiently with unique URLs - much faster than firstOrCreate + update
        $timestamp = hrtime(true) . '_' . rand(1000, 9999);

        // Create monitors directly (without separate websites) with specific SSL statuses
        // Monitor 1 & 2: valid certificates
        $monitor1 = Monitor::factory()->create([
            'url' => "https://valid1-{$timestamp}.example.com",
            'certificate_check_enabled' => true,
            'certificate_status' => 'valid',
            'certificate_expiration_date' => now()->addDays(90),
        ]);

        $monitor2 = Monitor::factory()->create([
            'url' => "https://valid2-{$timestamp}.example.com",
            'certificate_check_enabled' => true,
            'certificate_status' => 'valid',
            'certificate_expiration_date' => now()->addDays(60),
        ]);

        // Monitor 3: expiring soon
        $monitor3 = Monitor::factory()->create([
            'url' => "https://expiring-{$timestamp}.example.com",
            'certificate_check_enabled' => true,
            'certificate_status' => 'valid',
            'certificate_expiration_date' => now()->addDays(7),
        ]);

        // Monitor 4: expired
        $monitor4 = Monitor::factory()->create([
            'url' => "https://expired-{$timestamp}.example.com",
            'certificate_check_enabled' => true,
            'certificate_status' => 'invalid',
            'certificate_expiration_date' => now()->subDays(1),
        ]);

        // Create corresponding websites for the user
        foreach ([$monitor1, $monitor2, $monitor3, $monitor4] as $monitor) {
            Website::factory()->create([
                'user_id' => $this->testUser->id,
                'url' => $monitor->url,
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
