<?php

use App\Models\User;
use App\Models\Website;
use App\Models\SslCertificate;
use App\Models\SslCheck;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('SSL Dashboard Controller', function () {
    it('returns dashboard view with SSL statistics', function () {
        // Create test websites with different SSL statuses
        $websites = Website::factory()->count(5)->create(['user_id' => $this->user->id]);

        // Create SSL certificates with different statuses
        $validCert = SslCertificate::factory()->create([
            'website_id' => $websites[0]->id,
            'expires_at' => now()->addDays(90),
            'status' => 'valid'
        ]);

        $expiringSoon = SslCertificate::factory()->create([
            'website_id' => $websites[1]->id,
            'expires_at' => now()->addDays(7),
            'status' => 'expiring'
        ]);

        $expired = SslCertificate::factory()->create([
            'website_id' => $websites[2]->id,
            'expires_at' => now()->subDays(1),
            'status' => 'expired'
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('sslStatistics')
                ->where('sslStatistics.total_websites', 5)
                ->where('sslStatistics.valid_certificates', 1)
                ->where('sslStatistics.expiring_soon', 1)
                ->where('sslStatistics.expired_certificates', 1)
                ->has('recentSslActivity')
                ->has('criticalAlerts')
            );
    });

    it('calculates SSL statistics correctly for user websites only', function () {
        $otherUser = User::factory()->create();

        // Create websites for current user
        $userWebsites = Website::factory()->count(3)->create(['user_id' => $this->user->id]);

        // Create websites for other user (should not be included)
        $otherWebsites = Website::factory()->count(2)->create(['user_id' => $otherUser->id]);

        // Create SSL certificates for current user's websites
        SslCertificate::factory()->create([
            'website_id' => $userWebsites[0]->id,
            'status' => 'valid'
        ]);

        SslCertificate::factory()->create([
            'website_id' => $userWebsites[1]->id,
            'status' => 'expiring'
        ]);

        // Create SSL certificate for other user (should not be counted)
        SslCertificate::factory()->create([
            'website_id' => $otherWebsites[0]->id,
            'status' => 'valid'
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('sslStatistics.total_websites', 3)
            ->where('sslStatistics.valid_certificates', 1)
            ->where('sslStatistics.expiring_soon', 1)
        );
    });

    it('returns recent SSL activity for dashboard', function () {
        $website = Website::factory()->create(['user_id' => $this->user->id]);

        // Create recent SSL checks
        SslCheck::factory()->count(3)->create([
            'website_id' => $website->id,
            'checked_at' => now()->subMinutes(30),
            'status' => 'valid'
        ]);

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
        $website = Website::factory()->create(['user_id' => $this->user->id]);

        // Create expired certificate (critical alert)
        SslCertificate::factory()->create([
            'website_id' => $website->id,
            'expires_at' => now()->subDays(1),
            'status' => 'expired'
        ]);

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

        // Create SSL checks with response times
        SslCheck::factory()->create([
            'website_id' => $website->id,
            'response_time' => 200
        ]);

        SslCheck::factory()->create([
            'website_id' => $website->id,
            'response_time' => 300
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('sslStatistics.avg_response_time', 250)
        );
    });

    it('handles dashboard when user has no websites', function () {
        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('sslStatistics.total_websites', 0)
            ->where('sslStatistics.valid_certificates', 0)
            ->where('sslStatistics.expiring_soon', 0)
            ->where('sslStatistics.expired_certificates', 0)
            ->where('recentSslActivity', [])
            ->where('criticalAlerts', [])
        );
    });

    it('requires authentication to access dashboard', function () {
        auth()->logout();

        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    });
});
