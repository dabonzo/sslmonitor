<?php
use App\Models\Website;
use App\Models\User;
describe('Website Management', function () {
    test('authenticated user can access websites list', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('ssl.websites.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('SSL/Websites/Index')
        );
    });
    test('unauthenticated user cannot access websites list', function () {
        $response = $this->get(route('ssl.websites.index'));
        $response->assertRedirect(route('login'));
    });
    test('websites list displays user websites', function () {
        $user = User::factory()->create();
        Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Example Website',
            'url' => 'https://example.com',
        ]);
        $response = $this->actingAs($user)->get(route('ssl.websites.index'));
        $response->assertStatus(200);
        $response->assertSee('Example Website')
            ->assertSee('example.com');
    });
    test('create website page is accessible', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('ssl.websites.create'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('SSL/Websites/Create')
        );
    });
    test('create website page has required form fields', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('ssl.websites.create'));
        $response->assertStatus(200);
        $response->assertSee('Website Name')
            ->assertSee('URL')
            ->assertSee('SSL Monitoring')
            ->assertSee('Uptime Monitoring')
            ->assertSee('Create Website');
    });
    test('user can create website with valid data', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('ssl.websites.store'), [
            'name' => 'New Website',
            'url' => 'https://newwebsite.com',
            'ssl_monitoring_enabled' => true,
            'uptime_monitoring_enabled' => true,
        ]);
        $website = Website::where('url', 'https://newwebsite.com')->first();
        expect($website)->not->toBeNull();
        expect($website->name)->toBe('New Website');
        expect($website->user_id)->toBe($user->id);
    });
    test('website creation requires name', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('ssl.websites.store'), [
            'name' => '',
            'url' => 'https://example.com',
            'ssl_monitoring_enabled' => true,
        ]);
        $response->assertInertia(fn ($page) => $page
            ->has('errors.name')
        );
    });
    test('website creation requires valid HTTPS URL', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('ssl.websites.store'), [
            'name' => 'Test Website',
            'url' => 'http://example.com',
            'ssl_monitoring_enabled' => true,
        ]);
        $response->assertInertia(fn ($page) => $page
            ->has('errors.url')
        );
    });
    test('website creation requires valid URL format', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('ssl.websites.store'), [
            'name' => 'Test Website',
            'url' => 'not-a-valid-url',
            'ssl_monitoring_enabled' => true,
        ]);
        $response->assertInertia(fn ($page) => $page
            ->has('errors.url')
        );
    });
    test('user can edit own website', function () {
        $user = User::factory()->create();
        $website = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Name',
            'url' => 'https://original.com',
        ]);
        $response = $this->actingAs($user)->get(route('ssl.websites.edit', $website));
        $response->assertStatus(200);
        $response->assertSee('Original Name')
            ->assertSee('original.com');
    });
    test('user cannot edit other users website', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $website = Website::factory()->create(['user_id' => $user1->id]);
        $response = $this->actingAs($user2)->get(route('ssl.websites.edit', $website));
        $response->assertStatus(403);
    });
    test('user can update website details', function () {
        $user = User::factory()->create();
        $website = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Name',
        ]);
        $response = $this->actingAs($user)->put(route('ssl.websites.update', $website), [
            'name' => 'Updated Name',
            'ssl_monitoring_enabled' => false,
            'uptime_monitoring_enabled' => true,
        ]);
        $website->refresh();
        expect($website->name)->toBe('Updated Name');
        expect($website->ssl_monitoring_enabled)->toBeFalse();
        expect($website->uptime_monitoring_enabled)->toBeTrue();
    });
    test('user can delete website', function () {
        $user = User::factory()->create();
        $website = Website::factory()->create(['user_id' => $user->id]);
        $response = $this->actingAs($user)->delete(route('ssl.websites.destroy', $website));
        expect(Website::find($website->id))->toBeNull();
    });
    test('user cannot delete other users website', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $website = Website::factory()->create(['user_id' => $user1->id]);
        $response = $this->actingAs($user2)->delete(route('ssl.websites.destroy', $website));
        $response->assertStatus(403);
        expect(Website::find($website->id))->not->toBeNull();
    });
    test('website monitoring configuration is persisted', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('ssl.websites.store'), [
            'name' => 'Monitored Website',
            'url' => 'https://monitored.com',
            'ssl_monitoring_enabled' => true,
            'uptime_monitoring_enabled' => true,
        ]);
        $website = Website::where('url', 'https://monitored.com')->first();
        expect($website->ssl_monitoring_enabled)->toBeTrue();
        expect($website->uptime_monitoring_enabled)->toBeTrue();
    });
    test('empty websites list shows helpful message', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('ssl.websites.index'));
        $response->assertStatus(200);
        // Check for helpful message or create button
        $response->assertSee('Add Website')
            ->or($response)->assertSee('Create')
            ->or($response)->assertSee('No websites');
    });
})->group('websites', 'management');
