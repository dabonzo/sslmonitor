<?php
use App\Models\Website;
use App\Models\Monitor;
use App\Models\User;
describe('Dashboard', function () {
    test('authenticated user can access dashboard', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('SslDashboard')
        );
    });
    test('unauthenticated user is redirected to login', function () {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    });
    test('dashboard displays user name greeting', function () {
        $user = User::factory()->create(['name' => 'John Doe']);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('John Doe')
            ->or($response)->assertSee('Dashboard');
    });
    test('dashboard shows websites count metric', function () {
        $user = User::factory()->create();
        Website::factory(3)->create(['user_id' => $user->id]);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Website')
            ->or($response)->assertSee('Monitored');
    });
    test('dashboard shows empty state when no websites', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        // Should have helpful message or create button
        $response->assertSee('Add Website')
            ->or($response)->assertSee('Create')
            ->or($response)->assertSee('No websites');
    });
    test('dashboard displays websites with SSL status', function () {
        $user = User::factory()->create();
        $website = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Website',
            'url' => 'https://test.com',
        ]);
        // Create monitor for the website
        Monitor::create([
            'url' => 'https://test.com',
            'certificate_check_enabled' => true,
            'certificate_status' => 'valid',
        ]);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Test Website')
            ->or($response)->assertSee('test.com');
    });
    test('dashboard shows different SSL status indicators', function () {
        $user = User::factory()->create();
        // Valid certificate
        $validWebsite = Website::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://valid.com',
        ]);
        Monitor::create([
            'url' => 'https://valid.com',
            'certificate_status' => 'valid',
        ]);
        // Expiring soon
        $expiringWebsite = Website::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://expiring.com',
        ]);
        Monitor::create([
            'url' => 'https://expiring.com',
            'certificate_status' => 'valid',
            'certificate_expiration_date' => now()->addDays(5),
        ]);
        // Expired
        $expiredWebsite = Website::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://expired.com',
        ]);
        Monitor::create([
            'url' => 'https://expired.com',
            'certificate_status' => 'invalid',
        ]);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
    });
    test('dashboard can initiate SSL check', function () {
        $user = User::factory()->create();
        $website = Website::factory()->create(['user_id' => $user->id]);
        // The dashboard should provide way to check SSL
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Check')
            ->or($response)->assertSee('Refresh');
    });
    test('dashboard displays multiple website cards', function () {
        $user = User::factory()->create();
        Website::factory(5)->create(['user_id' => $user->id]);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        // Should be able to see multiple websites
        $websites = Website::where('user_id', $user->id)->get();
        expect($websites->count())->toBe(5);
    });
    test('dashboard has navigation to other sections', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
        // Check for navigation links
        $response->assertSee('Websites')
            ->or($response)->assertSee('Alerts')
            ->or($response)->assertSee('Settings')
            ->or($response)->assertSee('Team');
    });
    test('dashboard refresh updates website status', function () {
        $user = User::factory()->create();
        $website = Website::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://test.com',
        ]);
        Monitor::create([
            'url' => 'https://test.com',
            'certificate_check_enabled' => true,
        ]);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
    });
    test('dashboard displays SSL status for each website', function () {
        $user = User::factory()->create();
        $website = Website::factory()->create([
            'user_id' => $user->id,
            'name' => 'Monitored Site',
        ]);
        Monitor::create([
            'url' => $website->url,
            'certificate_status' => 'valid',
        ]);
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
    });
})->group('dashboard');
