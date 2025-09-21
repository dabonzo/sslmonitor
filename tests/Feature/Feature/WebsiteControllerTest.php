<?php

use App\Models\User;
use App\Models\Website;
use App\Models\SslCertificate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Helpers\TestUserHelper;

beforeEach(function () {
    // Always ensure the persistent test user exists
    TestUserHelper::ensureTestUserExists();

    // Create a separate test user for the test operations
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Website Controller', function () {
    describe('index method', function () {
        it('displays list of user websites with SSL status', function () {
            // Create websites for current user
            $userWebsites = Website::factory()->count(3)->create(['user_id' => $this->user->id]);

            // Create website for another user (should not be shown)
            $otherUser = User::factory()->create();
            Website::factory()->create(['user_id' => $otherUser->id]);

            // Add SSL certificates to user websites
            SslCertificate::factory()->create([
                'website_id' => $userWebsites[0]->id,
                'status' => 'valid'
            ]);

            $response = $this->get(route('ssl.websites.index'));

            $response->assertStatus(200)
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Ssl/Websites/Index')
                    ->has('websites')
                    ->where('websites.data', fn ($websites) => count($websites) === 3)
                    ->has('websites.data.0', fn (Assert $website) => $website
                        ->has('id')
                        ->has('name')
                        ->has('url')
                        ->has('ssl_status')
                        ->has('ssl_monitoring_enabled')
                        ->has('uptime_monitoring_enabled')
                        ->has('latest_ssl_certificate')
                        ->has('created_at')
                        ->etc()
                    )
                );
        });

        it('supports search functionality', function () {
            Website::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Example Website',
                'url' => 'https://example.com'
            ]);

            Website::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Test Site',
                'url' => 'https://test.com'
            ]);

            $response = $this->get(route('ssl.websites.index', ['search' => 'example']));

            $response->assertInertia(fn (Assert $page) => $page
                ->where('websites.data', fn ($websites) => count($websites) === 1)
                ->where('websites.data.0.name', 'Example Website')
            );
        });

        it('supports pagination', function () {
            Website::factory()->count(25)->create(['user_id' => $this->user->id]);

            $response = $this->get(route('ssl.websites.index'));

            $response->assertInertia(fn (Assert $page) => $page
                ->has('websites.data', 15) // Default pagination
                ->has('websites.links')
                ->has('websites.meta')
            );
        });
    });

    describe('store method', function () {
        it('creates new website with valid data', function () {
            $websiteData = [
                'name' => 'Test Website',
                'url' => 'https://example.com',
                'ssl_monitoring_enabled' => true,
                'uptime_monitoring_enabled' => false,
            ];

            $response = $this->post(route('ssl.websites.store'), $websiteData);

            $response->assertStatus(302)
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertDatabaseHas('websites', [
                'user_id' => $this->user->id,
                'name' => 'Test Website',
                'url' => 'https://example.com',
                'ssl_monitoring_enabled' => true,
                'uptime_monitoring_enabled' => false,
            ]);
        });

        it('validates required fields', function () {
            $response = $this->post(route('ssl.websites.store'), []);

            $response->assertStatus(302)
                ->assertSessionHasErrors(['name', 'url']);
        });

        it('normalizes URL automatically', function () {
            $this->post(route('ssl.websites.store'), [
                'name' => 'Test Website',
                'url' => 'example.com',
                'ssl_monitoring_enabled' => true,
            ]);

            $this->assertDatabaseHas('websites', [
                'url' => 'https://example.com',
            ]);
        });
    });

    describe('show method', function () {
        it('displays website details with SSL information', function () {
            $website = Website::factory()->create(['user_id' => $this->user->id]);

            $sslCertificate = SslCertificate::factory()->create([
                'website_id' => $website->id,
                'status' => 'valid'
            ]);

            $response = $this->get(route('ssl.websites.show', $website));

            $response->assertStatus(200)
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Ssl/Websites/Show')
                    ->has('website', fn (Assert $website) => $website
                        ->has('id')
                        ->has('name')
                        ->has('url')
                        ->has('ssl_certificates')
                        ->has('recent_ssl_checks')
                        ->has('ssl_status')
                        ->etc()
                    )
                );
        });

        it('prevents access to other users websites', function () {
            $otherUser = User::factory()->create();
            $website = Website::factory()->create(['user_id' => $otherUser->id]);

            $response = $this->get(route('ssl.websites.show', $website));

            $response->assertStatus(403);
        });
    });

    describe('update method', function () {
        it('updates website with valid data', function () {
            $website = Website::factory()->create(['user_id' => $this->user->id]);

            $updateData = [
                'name' => 'Updated Website Name',
                'url' => 'https://updated-example.com',
                'ssl_monitoring_enabled' => false,
                'uptime_monitoring_enabled' => true,
            ];

            $response = $this->put(route('ssl.websites.update', $website), $updateData);

            $response->assertStatus(302)
                ->assertRedirect()
                ->assertSessionHas('success');

            $website->refresh();

            expect($website->name)->toBe('Updated Website Name');
            expect($website->url)->toBe('https://updated-example.com');
            expect($website->ssl_monitoring_enabled)->toBeFalse();
            expect($website->uptime_monitoring_enabled)->toBeTrue();
        });
    });

    describe('destroy method', function () {
        it('deletes website and related data', function () {
            $website = Website::factory()->create(['user_id' => $this->user->id]);

            // Create related SSL data
            SslCertificate::factory()->create(['website_id' => $website->id]);

            $response = $this->delete(route('ssl.websites.destroy', $website));

            $response->assertStatus(302)
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertDatabaseMissing('websites', ['id' => $website->id]);
            $this->assertDatabaseMissing('ssl_certificates', ['website_id' => $website->id]);
        });
    });

    it('requires authentication for all actions', function () {
        auth()->logout();

        $website = Website::factory()->create();

        $this->get(route('ssl.websites.index'))->assertRedirect(route('login'));
        $this->post(route('ssl.websites.store'))->assertRedirect(route('login'));
        $this->get(route('ssl.websites.show', $website))->assertRedirect(route('login'));
        $this->put(route('ssl.websites.update', $website))->assertRedirect(route('login'));
        $this->delete(route('ssl.websites.destroy', $website))->assertRedirect(route('login'));
    });
});
