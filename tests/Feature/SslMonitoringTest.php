<?php

use App\Models\User;
use App\Models\Website;
use App\Services\SslCertificateAnalysisService;
use Spatie\UptimeMonitor\Models\Monitor;

// SSL Dashboard Tests
test('ssl dashboard displays user statistics', function () {
    $user = $this->testUser;

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('sslStatistics')
        ->has('uptimeStatistics')
        ->has('recentSslActivity')
        ->has('recentUptimeActivity')
        ->has('criticalAlerts')
    );
});

test('ssl dashboard calculates statistics correctly', function () {
    $user = $this->testUser;
    $websites = $this->realWebsites;

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('sslStatistics.total_websites', 3)
        ->where('sslStatistics.valid_certificates', 3)
        ->where('sslStatistics.expired_certificates', 0)
        ->where('sslStatistics.expiring_soon', 0)
    );
});

test('ssl dashboard shows only user websites', function () {
    $user = $this->testUser;

    // Create another user with their own websites to ensure isolation
    $otherUser = User::factory()->create();
    Website::factory()->count(2)->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('sslStatistics.total_websites', 3)
    );
});

// SSL Certificate Analysis Tests
test('ssl certificate analysis service analyzes domain correctly', function () {
    $service = new SslCertificateAnalysisService();

    // Test with a real domain (example.com should have a valid certificate)
    $analysis = $service->analyzeCertificate('example.com');

    expect($analysis)->toHaveKeys([
        'basic_info',
        'validity',
        'domains',
        'security',
        'certificate_authority',
        'chain_info',
        'risk_assessment'
    ]);

    expect($analysis['security'])->toHaveKeys([
        'key_algorithm',
        'key_size',
        'signature_algorithm',
        'weak_signature',
        'weak_key',
        'security_score'
    ]);
});

test('ssl certificate analysis handles invalid domains gracefully', function () {
    $service = new SslCertificateAnalysisService();

    $analysis = $service->analyzeCertificate('invalid-domain-that-does-not-exist.test');

    expect($analysis['error'])->toBeTrue();
    expect($analysis['message'])->not->toBeEmpty();
});

test('ssl certificate analysis detects lets encrypt certificates', function () {
    $service = new SslCertificateAnalysisService();

    // Many sites use Let's Encrypt, we can test with a known one
    // Note: In a real test environment, you might want to mock this
    $analysis = $service->analyzeCertificate('example.com');

    expect($analysis['certificate_authority'])->toHaveKeys([
        'is_lets_encrypt',
        'ca_name',
        'ca_organization',
        'ca_country'
    ]);
});

// Website SSL Status Tests
test('website ssl status is retrieved from spatie monitor', function () {
    $website = $this->realWebsites->first();

    expect($website->getCurrentSslStatus())->toBe('valid');
});

test('website ssl status defaults when no monitor exists', function () {
    $user = $this->testUser;
    $website = Website::factory()->create(['user_id' => $user->id]);

    expect($website->getCurrentSslStatus())->toBe('not yet checked');
});

test('website uptime status is retrieved from spatie monitor', function () {
    $website = $this->realWebsites->first();

    expect($website->getCurrentUptimeStatus())->toBe('up');
});

// Critical SSL Alerts Tests
test('critical ssl alerts identify expiring certificates', function () {
    $user = $this->testUser;

    // Create a test website with expiring certificate
    $testWebsite = Website::factory()->create(['user_id' => $user->id]);

    // Create monitor with certificate expiring in 5 days
    Monitor::updateOrCreate(['url' => $testWebsite->url], [
        'url' => $testWebsite->url,
        'certificate_check_enabled' => true,
        'certificate_status' => 'valid',
        'certificate_expiration_date' => now()->addDays(5),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('criticalAlerts', 1)
        ->where('criticalAlerts.0.type', 'ssl_expiring_soon')
    );
});

test('critical ssl alerts identify expired certificates', function () {
    $user = $this->testUser;

    // Create a test website with expired certificate
    $testWebsite = Website::factory()->create(['user_id' => $user->id]);

    Monitor::updateOrCreate(['url' => $testWebsite->url], [
        'url' => $testWebsite->url,
        'certificate_check_enabled' => true,
        'certificate_status' => 'invalid',
        'certificate_expiration_date' => now()->subDays(1),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('criticalAlerts', 1)
        ->where('criticalAlerts.0.type', 'ssl_expired')
    );
});

test('ssl dashboard handles empty website list', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('sslStatistics.total_websites', 0)
        ->where('sslStatistics.valid_certificates', 0)
        ->where('sslStatistics.expired_certificates', 0)
        ->where('criticalAlerts', [])
    );
});

// Recent SSL Activity Tests
test('recent ssl activity shows latest monitor updates', function () {
    $user = User::factory()->create();
    $websites = Website::factory()->count(3)->create(['user_id' => $user->id]);

    foreach ($websites as $website) {
        Monitor::updateOrCreate(
            ['url' => $website->url],
            [
            'certificate_check_enabled' => true,
            'certificate_status' => 'valid',
            'updated_at' => now()->subMinutes(rand(1, 60)),
        ]);
    }

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('recentSslActivity', 3)
        ->has('recentSslActivity.0.website_name')
        ->has('recentSslActivity.0.status')
        ->has('recentSslActivity.0.time_ago')
    );
});

test('recent ssl activity excludes not yet checked monitors', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    Monitor::updateOrCreate(['url' => $website->url], [
        'url' => $website->url,
        'certificate_check_enabled' => true,
        'certificate_status' => 'not yet checked',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('recentSslActivity', [])
    );
});

// URL Normalization Tests
test('website url normalization works correctly', function () {
    $user = User::factory()->create();

    $testCases = [
        'example.com' => 'https://example.com',
        'http://example.com' => 'https://example.com',
        'https://example.com/' => 'https://example.com',
        'EXAMPLE.COM' => 'https://example.com',
        'example.com:8080' => 'https://example.com:8080',
        'example.com/path/../test' => 'https://example.com/test',
    ];

    foreach ($testCases as $input => $expected) {
        $website = Website::factory()->make(['user_id' => $user->id, 'url' => $input]);
        expect($website->url)->toBe($expected);
    }
});

// SSL Monitoring Enable/Disable Tests
test('website ssl monitoring can be enabled and disabled', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'ssl_monitoring_enabled' => true,
    ]);

    expect($website->isSslMonitoringEnabled())->toBeTrue();

    $website->update(['ssl_monitoring_enabled' => false]);
    expect($website->isSslMonitoringEnabled())->toBeFalse();
});

test('website uptime monitoring can be enabled and disabled', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'uptime_monitoring_enabled' => true,
    ]);

    expect($website->isUptimeMonitoringEnabled())->toBeTrue();

    $website->update(['uptime_monitoring_enabled' => false]);
    expect($website->isUptimeMonitoringEnabled())->toBeFalse();
});

// Spatie Monitor Integration Tests
test('website can retrieve associated spatie monitor', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    $monitor = Monitor::updateOrCreate(['url' => $website->url], [
        'url' => $website->url,
        'certificate_check_enabled' => true,
        'uptime_check_enabled' => true,
    ]);

    $retrievedMonitor = $website->getSpatieMonitor();
    expect($retrievedMonitor)->not->toBeNull();
    expect($retrievedMonitor->id)->toBe($monitor->id);
});

test('website returns null when no spatie monitor exists', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'ssl_monitoring_enabled' => false,
        'uptime_monitoring_enabled' => false,
    ]);

    expect($website->getSpatieMonitor())->toBeNull();
});
