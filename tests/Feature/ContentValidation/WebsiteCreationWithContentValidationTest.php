<?php

use App\Models\User;
use App\Models\Website;
use Spatie\UptimeMonitor\Models\Monitor;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('user can create website with basic content validation', function () {
    $websiteData = [
        'name' => 'Test Website',
        'url' => 'https://example.com',
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => true,
        'monitoring_config' => [
            'description' => 'Test website description',
            'content_expected_strings' => ['Welcome', 'Home Page'],
            'content_forbidden_strings' => ['Error 500', 'Maintenance Mode'],
            'content_regex_patterns' => ['/status.*ok/i', '/welcome/'],
            'javascript_enabled' => false,
            'javascript_wait_seconds' => 5,
        ],
        'immediate_check' => false,
    ];

    $response = $this->post('/ssl/websites', $websiteData);

    $response->assertRedirect('/ssl/websites');

    // Verify website was created
    $website = Website::where('url', 'https://example.com')->first();
    expect($website)->not->toBeNull()
        ->and($website->name)->toBe('Test Website')
        ->and($website->user_id)->toBe($this->user->id);

    // Verify monitor was created with content validation
    $monitor = Monitor::where('url', 'https://example.com')->first();
    expect($monitor)->not->toBeNull()
        ->and($monitor->content_expected_strings)->toBe(['Welcome', 'Home Page'])
        ->and($monitor->content_forbidden_strings)->toBe(['Error 500', 'Maintenance Mode'])
        ->and($monitor->content_regex_patterns)->toBe(['/status.*ok/i', '/welcome/'])
        ->and($monitor->javascript_enabled)->toBeFalse()
        ->and($monitor->javascript_wait_seconds)->toBe(5);
});

test('user can create website with javascript support enabled', function () {
    $websiteData = [
        'name' => 'Dynamic Website',
        'url' => 'https://dynamic-site.com',
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => true,
        'monitoring_config' => [
            'description' => 'JavaScript-heavy website',
            'content_expected_strings' => ['App Loaded'],
            'content_forbidden_strings' => ['JavaScript Error'],
            'content_regex_patterns' => [],
            'javascript_enabled' => true,
            'javascript_wait_seconds' => 10,
        ],
        'immediate_check' => false,
    ];

    $response = $this->post('/ssl/websites', $websiteData);

    $response->assertRedirect('/ssl/websites');

    $monitor = Monitor::where('url', 'https://dynamic-site.com')->first();
    expect($monitor)->not->toBeNull()
        ->and($monitor->javascript_enabled)->toBeTrue()
        ->and($monitor->javascript_wait_seconds)->toBe(10)
        ->and($monitor->content_expected_strings)->toBe(['App Loaded'])
        ->and($monitor->content_forbidden_strings)->toBe(['JavaScript Error']);
});

test('website creation validates content validation fields', function () {
    $websiteData = [
        'name' => 'Test Website',
        'url' => 'https://example.com',
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => true,
        'monitoring_config' => [
            'content_expected_strings' => [str_repeat('a', 300)], // Too long
            'content_forbidden_strings' => ['Valid string'],
            'content_regex_patterns' => ['Valid pattern'],
            'javascript_enabled' => true,
            'javascript_wait_seconds' => 50, // Too high
        ],
        'immediate_check' => false,
    ];

    $response = $this->post('/ssl/websites', $websiteData);

    $response->assertSessionHasErrors([
        'monitoring_config.content_expected_strings.0',
        'monitoring_config.javascript_wait_seconds',
    ]);
});

test('website creation handles empty content validation arrays', function () {
    $websiteData = [
        'name' => 'Simple Website',
        'url' => 'https://simple.com',
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => true,
        'monitoring_config' => [
            'description' => 'Simple website with no content validation',
            'content_expected_strings' => [],
            'content_forbidden_strings' => [],
            'content_regex_patterns' => [],
            'javascript_enabled' => false,
            'javascript_wait_seconds' => 5,
        ],
        'immediate_check' => false,
    ];

    $response = $this->post('/ssl/websites', $websiteData);

    $response->assertRedirect('/ssl/websites');

    $monitor = Monitor::where('url', 'https://simple.com')->first();
    expect($monitor)->not->toBeNull()
        ->and($monitor->content_expected_strings)->toBe([])
        ->and($monitor->content_forbidden_strings)->toBe([])
        ->and($monitor->content_regex_patterns)->toBe([]);
});

test('website creation handles null content validation arrays', function () {
    $websiteData = [
        'name' => 'Basic Website',
        'url' => 'https://basic.com',
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => true,
        'monitoring_config' => [
            'description' => 'Basic website',
            'content_expected_strings' => null,
            'content_forbidden_strings' => null,
            'content_regex_patterns' => null,
            'javascript_enabled' => false,
            'javascript_wait_seconds' => 5,
        ],
        'immediate_check' => false,
    ];

    $response = $this->post('/ssl/websites', $websiteData);

    $response->assertRedirect('/ssl/websites');

    $monitor = Monitor::where('url', 'https://basic.com')->first();
    expect($monitor)->not->toBeNull()
        ->and($monitor->content_expected_strings)->toBeNull()
        ->and($monitor->content_forbidden_strings)->toBeNull()
        ->and($monitor->content_regex_patterns)->toBeNull();
});

test('website creation filters out empty strings from content validation arrays', function () {
    $websiteData = [
        'name' => 'Filtered Website',
        'url' => 'https://filtered.com',
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => true,
        'monitoring_config' => [
            'content_expected_strings' => ['Valid String', '', '  ', 'Another Valid'],
            'content_forbidden_strings' => ['Error', '', 'Maintenance'],
            'content_regex_patterns' => ['/valid/', '', '/another/'],
            'javascript_enabled' => false,
            'javascript_wait_seconds' => 5,
        ],
        'immediate_check' => false,
    ];

    $response = $this->post('/ssl/websites', $websiteData);

    $response->assertRedirect('/ssl/websites');

    $monitor = Monitor::where('url', 'https://filtered.com')->first();
    expect($monitor)->not->toBeNull()
        ->and($monitor->content_expected_strings)->toBe(['Valid String', '  ', 'Another Valid'])
        ->and($monitor->content_forbidden_strings)->toBe(['Error', 'Maintenance'])
        ->and($monitor->content_regex_patterns)->toBe(['/valid/', '/another/']);
});

test('javascript wait seconds are bounded correctly during creation', function () {
    // Test minimum bound
    $websiteData = [
        'name' => 'Min Wait Website',
        'url' => 'https://min-wait.com',
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => true,
        'monitoring_config' => [
            'javascript_enabled' => true,
            'javascript_wait_seconds' => 0, // Should fail validation
        ],
        'immediate_check' => false,
    ];

    $response = $this->post('/ssl/websites', $websiteData);
    $response->assertSessionHasErrors(['monitoring_config.javascript_wait_seconds']);

    // Test maximum bound
    $websiteData['url'] = 'https://max-wait.com';
    $websiteData['monitoring_config']['javascript_wait_seconds'] = 50; // Should fail validation

    $response = $this->post('/ssl/websites', $websiteData);
    $response->assertSessionHasErrors(['monitoring_config.javascript_wait_seconds']);

    // Test valid value
    $websiteData['url'] = 'https://valid-wait.com';
    $websiteData['monitoring_config']['javascript_wait_seconds'] = 15; // Should pass

    $response = $this->post('/ssl/websites', $websiteData);
    $response->assertRedirect('/ssl/websites');

    $monitor = Monitor::where('url', 'https://valid-wait.com')->first();
    expect($monitor)->not->toBeNull()
        ->and($monitor->javascript_wait_seconds)->toBe(15);
});

test('website creation with content validation creates monitor with correct response checker', function () {
    $websiteData = [
        'name' => 'Enhanced Website',
        'url' => 'https://enhanced.com',
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => true,
        'monitoring_config' => [
            'content_expected_strings' => ['Welcome'],
            'javascript_enabled' => true,
        ],
        'immediate_check' => false,
    ];

    $response = $this->post('/ssl/websites', $websiteData);

    $response->assertRedirect('/ssl/websites');

    $monitor = Monitor::where('url', 'https://enhanced.com')->first();
    expect($monitor)->not->toBeNull();

    // Verify that the uptime monitoring is enabled (which will use our EnhancedContentChecker)
    expect($monitor->uptime_check_enabled)->toBeTrue();
});

test('user cannot create duplicate websites even with different content validation', function () {
    // Create first website
    $websiteData = [
        'name' => 'Original Website',
        'url' => 'https://duplicate-test.com',
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => true,
        'monitoring_config' => [
            'content_expected_strings' => ['Welcome'],
        ],
        'immediate_check' => false,
    ];

    $response = $this->post('/ssl/websites', $websiteData);
    $response->assertRedirect('/ssl/websites');

    // Try to create duplicate with different content validation
    $websiteData['name'] = 'Duplicate Website';
    $websiteData['monitoring_config']['content_expected_strings'] = ['Different Content'];

    $response = $this->post('/ssl/websites', $websiteData);
    $response->assertSessionHasErrors(['url']);
});