<?php

use App\Models\Monitor;
use App\Models\User;
use App\Models\Website;
use App\Services\MonitorIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Website Controller', function () {
    describe('index method', function () {
        it('displays list of user websites with SSL status', function () {
            // Create websites for current user (using withoutEvents to prevent observer delays)
            $userWebsites = Website::withoutEvents(fn() => Website::factory()->count(3)->create(['user_id' => $this->user->id]));

            // Create website for another user (should not be shown)
            $otherUser = User::factory()->create();
            Website::withoutEvents(fn() => Website::factory()->create(['user_id' => $otherUser->id]));

            // Create Spatie monitor for SSL monitoring
            $timestamp = time().'-'.rand(1000, 9999);
            $testUrl = 'https://test-ssl-'.$timestamp.'.example.com';
            $userWebsites[0]->update(['url' => $testUrl]);

            Monitor::firstOrCreate(
                ['url' => $testUrl],
                [
                    'certificate_check_enabled' => true,
                    'certificate_status' => 'valid',
                ]
            );

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
                        ->has('created_at')
                        ->etc()
                    )
                );
        });

        it('supports search functionality', function () {
            Website::withoutEvents(fn() => Website::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Example Website',
                'url' => 'https://example.com',
            ]));

            Website::withoutEvents(fn() => Website::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Test Site',
                'url' => 'https://test.com',
            ]));

            $response = $this->get(route('ssl.websites.index', ['search' => 'example']));

            $response->assertInertia(fn (Assert $page) => $page
                ->where('websites.data', fn ($websites) => count($websites) === 1)
                ->where('websites.data.0.name', 'Example Website')
            );
        });

        it('supports pagination', function () {
            Website::withoutEvents(fn() => Website::factory()->count(25)->create(['user_id' => $this->user->id]));

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
            $website = Website::withoutEvents(fn() => Website::factory()->create(['user_id' => $this->user->id]));

            // Create Spatie monitor for SSL monitoring
            $timestamp = time().'-'.rand(1000, 9999);
            $testUrl = 'https://test-show-'.$timestamp.'.example.com';
            $website->update(['url' => $testUrl]);

            Monitor::firstOrCreate(
                ['url' => $testUrl],
                [
                    'certificate_check_enabled' => true,
                    'certificate_status' => 'valid',
                ]
            );

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
            $website = Website::withoutEvents(fn() => Website::factory()->create(['user_id' => $otherUser->id]));

            $response = $this->get(route('ssl.websites.show', $website));

            $response->assertStatus(403);
        });
    });

    describe('update method', function () {
        it('updates website with valid data', function () {
            // Mock MonitorIntegrationService to avoid slow database operations
            $this->mock(MonitorIntegrationService::class, function ($mock) {
                $mock->shouldReceive('createOrUpdateMonitorForWebsite')->zeroOrMoreTimes();
                $mock->shouldReceive('removeMonitorForWebsite')->never();
            });

            $website = Website::withoutEvents(fn() => Website::factory()->create(['user_id' => $this->user->id]));

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
            $website = Website::withoutEvents(fn() => Website::factory()->create(['user_id' => $this->user->id]));

            // Create related Spatie monitor data
            $timestamp = time().'-'.rand(1000, 9999);
            $testUrl = 'https://test-delete-'.$timestamp.'.example.com';
            $website->update(['url' => $testUrl]);

            $monitor = Monitor::firstOrCreate(
                ['url' => $testUrl],
                [
                    'certificate_check_enabled' => true,
                    'certificate_status' => 'valid',
                ]
            );

            $response = $this->delete(route('ssl.websites.destroy', $website));

            $response->assertStatus(302)
                ->assertRedirect()
                ->assertSessionHas('success');

            // With SoftDeletes, the record still exists but has a deleted_at timestamp
            $this->assertSoftDeleted('websites', ['id' => $website->id]);
        });
    });

    it('requires authentication for all actions', function () {
        auth()->logout();

        $website = Website::withoutEvents(fn() => Website::factory()->create());

        $this->get(route('ssl.websites.index'))->assertRedirect(route('login'));
        $this->post(route('ssl.websites.store'))->assertRedirect(route('login'));
        $this->get(route('ssl.websites.show', $website))->assertRedirect(route('login'));
        $this->put(route('ssl.websites.update', $website))->assertRedirect(route('login'));
        $this->delete(route('ssl.websites.destroy', $website))->assertRedirect(route('login'));
    });
});
