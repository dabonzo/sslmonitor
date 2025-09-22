<?php

use App\Models\User;
use App\Models\Website;
use App\Models\AlertConfiguration;
use Spatie\UptimeMonitor\Models\Monitor;

// Website List/Index Tests
test('user can view their websites list', function () {
    $user = $this->testUser;

    $response = $this->actingAs($user)->get('/ssl/websites');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Ssl/Websites/Index')
        ->where('websites.meta.total', 3)
        ->has('filters')
        ->has('filterStats')
        ->has('current_filter')
        ->has('current_team_filter')
    );
});

test('website list shows only user websites', function () {
    $user = $this->testUser;

    // Create another user with their own websites to test isolation
    $otherUser = User::factory()->create();
    Website::factory()->count(2)->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->get('/ssl/websites');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('websites.meta.total', 3) // Our real user should only see their 3 websites
    );
});

test('website list includes ssl and uptime status', function () {
    $user = $this->testUser;

    $response = $this->actingAs($user)->get('/ssl/websites');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Ssl/Websites/Index')
        ->has('websites.data.0.ssl_status')
        ->has('websites.data.0.uptime_status')
        ->where('websites.data.0.ssl_status', 'valid')
        ->where('websites.data.0.uptime_status', 'up')
    );
});

test('website list can be filtered', function () {
    $user = $this->testUser;

    // Test filtering functionality with real data (all our real websites are valid)
    $response = $this->actingAs($user)
        ->get('/ssl/websites?filter=all');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('websites.meta.total', 3)
        ->has('filterStats')
        ->has('current_filter')
    );
});

// Website Creation Tests
test('user can create a new website', function () {
    $user = $this->testUser;

    $response = $this->actingAs($user)
        ->post('/ssl/websites', [
            'name' => 'Test Website',
            'url' => 'https://test-unique-' . time() . '.example.com',
            'ssl_monitoring_enabled' => true,
            'uptime_monitoring_enabled' => false,
        ]);

    $response->assertRedirect('/ssl/websites');
    $response->assertSessionHas('success');

    expect(Website::where('name', 'Test Website')->exists())->toBeTrue();
});

test('website creation normalizes url', function () {
    $user = $this->testUser;

    $response = $this->actingAs($user)
        ->post('/ssl/websites', [
            'name' => 'Normalized Website',
            'url' => 'Example-Normalize-' . time() . '.COM/',
        ]);

    $response->assertRedirect('/ssl/websites');

    $website = Website::where('name', 'Normalized Website')->first();
    expect($website->url)->toStartWith('https://example-normalize-');
});

test('website creation validates required fields', function () {
    $user = $this->testUser;

    $response = $this->actingAs($user)
        ->post('/ssl/websites', []);

    $response->assertSessionHasErrors(['name', 'url']);
});

test('website creation validates url format', function () {
    $user = $this->testUser;

    $response = $this->actingAs($user)
        ->post('/ssl/websites', [
            'name' => 'Test Website',
            'url' => 'invalid://not..a..valid..url..with..dots',
        ]);

    $response->assertSessionHasErrors(['url']);
});

test('user cannot create duplicate websites', function () {
    $user = $this->testUser;

    // Use one of our real websites to test duplicate detection
    $realWebsite = $this->realWebsites->first();

    $response = $this->actingAs($user)
        ->post('/ssl/websites', [
            'name' => 'Duplicate Website',
            'url' => $realWebsite->url,
        ]);

    $response->assertSessionHasErrors(['url']);
});

// Website Update Tests
test('user can update their website', function () {
    $user = $this->testUser;
    $website = $this->realWebsites->first();

    $originalName = $website->name;
    $originalUrl = $website->url;

    $response = $this->actingAs($user)
        ->put("/ssl/websites/{$website->id}", [
            'name' => 'Updated Website Name',
            'url' => $originalUrl, // Keep the same URL to avoid Monitor conflicts
            'ssl_monitoring_enabled' => false,
            'uptime_monitoring_enabled' => true,
        ]);

    $response->assertRedirect("/ssl/websites/{$website->id}");
    $response->assertSessionHas('success');

    $website->refresh();
    expect($website->name)->toBe('Updated Website Name');
    expect($website->ssl_monitoring_enabled)->toBeFalse();
    expect($website->uptime_monitoring_enabled)->toBeTrue();

    // Restore original name for other tests
    $website->update(['name' => $originalName]);
});

test('user cannot update other users websites', function () {
    $user = $this->testUser;
    $otherUser = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->put("/ssl/websites/{$website->id}", [
            'name' => 'Hacked Website',
            'url' => $website->url, // Include required URL field
        ]);

    $response->assertForbidden();
});

// Website Deletion Tests
test('user can delete their website', function () {
    $user = $this->testUser;

    // Create a test website for deletion
    $website = Website::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->delete("/ssl/websites/{$website->id}");

    $response->assertRedirect('/ssl/websites');
    $response->assertSessionHas('success');

    expect(Website::find($website->id))->toBeNull();
});

test('user cannot delete other users websites', function () {
    $user = $this->testUser;
    $otherUser = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->delete("/ssl/websites/{$website->id}");

    $response->assertForbidden();
});

test('deleting website also deletes related alert configurations', function () {
    $user = $this->testUser;
    $website = Website::factory()->create(['user_id' => $user->id]);

    // Create alert configurations for the website
    AlertConfiguration::factory()->count(2)->create([
        'user_id' => $user->id,
        'website_id' => $website->id,
    ]);

    expect(AlertConfiguration::where('website_id', $website->id)->count())->toBe(2);

    $this->actingAs($user)->delete("/ssl/websites/{$website->id}");

    expect(AlertConfiguration::where('website_id', $website->id)->count())->toBe(0);
});

test('user cannot manually check other users websites', function () {
    $user = $this->testUser;
    $otherUser = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->post("/ssl/websites/{$website->id}/check");

    $response->assertForbidden();
});

// Website Details Tests
test('user can view website details', function () {
    $user = $this->testUser;
    $website = $this->realWebsites->first();

    $response = $this->actingAs($user)
        ->get("/ssl/websites/{$website->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Ssl/Websites/Show')
        ->has('website')
        ->where('website.id', $website->id)
        ->where('website.name', $website->name)
        ->where('website.url', $website->url)
    );
});

test('user cannot view other users website details', function () {
    $user = $this->testUser;
    $otherUser = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->get("/ssl/websites/{$website->id}");

    $response->assertForbidden();
});

test('user can view certificate analysis', function () {
    $user = $this->testUser;
    $website = $this->realWebsites->first();

    $response = $this->actingAs($user)
        ->get("/ssl/websites/{$website->id}/certificate-analysis");

    $response->assertSuccessful();
    $response->assertJson([
        'website' => [
            'id' => $website->id,
            'name' => $website->name,
            'url' => $website->url,
        ],
        'analysis' => [],
    ]);
});

test('user cannot view other users certificate analysis', function () {
    $user = $this->testUser;
    $otherUser = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->get("/ssl/websites/{$website->id}/certificate-analysis");

    $response->assertForbidden();
});

// Website Model Tests
test('website model has correct relationships', function () {
    $user = $this->testUser;
    $website = $this->realWebsites->first();

    expect($website->user)->toBeInstanceOf(User::class);
    expect($website->user->id)->toBe($user->id);
});

test('website model url normalization works', function () {
    $user = $this->testUser;

    $testCases = [
        'example.com' => 'https://example.com',
        'HTTP://EXAMPLE.COM/' => 'https://example.com',
        'Example.Com/path' => 'https://example.com/path',
    ];

    foreach ($testCases as $input => $expected) {
        $website = Website::factory()->make(['user_id' => $user->id, 'url' => $input]);
        expect($website->url)->toBe($expected);
    }
});

test('website model ssl and uptime status methods work', function () {
    $website = $this->realWebsites->first();

    expect($website->getCurrentSslStatus())->toBe('valid');
    expect($website->getCurrentUptimeStatus())->toBe('up');
    expect($website->getSpatieMonitor())->not->toBeNull();
});

test('website model monitoring enabled methods work', function () {
    $website = $this->realWebsites->first();

    expect($website->isSslMonitoringEnabled())->toBeTrue();
    expect($website->isUptimeMonitoringEnabled())->toBeTrue();
});

// Search Tests
test('website list can be searched by name', function () {
    $user = $this->testUser;

    // Use real website name for search
    $website = $this->realWebsites->first();
    $searchTerm = substr($website->name, 0, 3); // First 3 characters

    $response = $this->actingAs($user)
        ->get("/ssl/websites?search={$searchTerm}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('websites.data')
        ->where('filters.search', $searchTerm)
    );
});

test('website list can be searched by url', function () {
    $user = $this->testUser;

    // Use real website URL for search
    $website = $this->realWebsites->first();
    $searchTerm = 'redgas'; // Part of www.redgas.at

    $response = $this->actingAs($user)
        ->get("/ssl/websites?search={$searchTerm}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('websites.data')
        ->where('filters.search', $searchTerm)
    );
});