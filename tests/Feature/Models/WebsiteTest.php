<?php

use App\Models\Website;
use App\Models\User;

test('website can be created with valid data', function () {
    $user = User::factory()->create();

    $website = Website::create([
        'name' => 'Example Site',
        'url' => 'https://example.com',
        'user_id' => $user->id,
    ]);

    expect($website)->toBeInstanceOf(Website::class)
        ->and($website->name)->toBe('Example Site')
        ->and($website->url)->toBe('https://example.com')
        ->and($website->user_id)->toBe($user->id)
        ->and($website->ssl_monitoring_enabled)->toBeTrue()
        ->and($website->uptime_monitoring_enabled)->toBeFalse()
        ->and($website->is_active)->toBeTrue();
});

test('website belongs to a user', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    expect($website->user)->toBeInstanceOf(User::class)
        ->and($website->user->id)->toBe($user->id);
});

test('website url is sanitized on save', function () {
    $user = User::factory()->create();
    $website = Website::create([
        'name' => 'Example Site',
        'url' => 'HTTP://EXAMPLE.COM/PATH/../',
        'user_id' => $user->id,
    ]);

    expect($website->url)->toBe('https://example.com');
});

test('website url is normalized to lowercase and https', function () {
    $user = User::factory()->create();
    $website = Website::create([
        'name' => 'Example Site',
        'url' => 'HTTP://EXAMPLE.COM',
        'user_id' => $user->id,
    ]);

    expect($website->url)->toBe('https://example.com');
});

test('website can get spatie monitor', function () {
    $website = Website::factory()->create();

    expect(method_exists($website, 'getSpatieMonitor'))->toBeTrue();
    // Since SSL monitoring is enabled by default, a monitor should be created automatically
    expect($website->getSpatieMonitor())->not->toBeNull();
    expect($website->getSpatieMonitor())->toBeInstanceOf(\Spatie\UptimeMonitor\Models\Monitor::class);
});

test('website can get current ssl status from spatie monitor', function () {
    $website = Website::factory()->create();

    expect(method_exists($website, 'getCurrentSslStatus'))->toBeTrue();
    expect($website->getCurrentSslStatus())->toBe('not yet checked');
});

test('website can get current uptime status from spatie monitor', function () {
    $website = Website::factory()->create();

    expect(method_exists($website, 'getCurrentUptimeStatus'))->toBeTrue();
    expect($website->getCurrentUptimeStatus())->toBe('not yet checked');
});

test('website has plugin data methods', function () {
    $website = Website::factory()->create();

    expect(method_exists($website, 'getPluginData'))->toBeTrue();
    expect(method_exists($website, 'setPluginData'))->toBeTrue();
    expect($website->getPluginData('test-plugin'))->toBe([]);
});

test('website enforces unique url per user', function () {
    $user = User::factory()->create();

    Website::create([
        'name' => 'First Site',
        'url' => 'https://example.com',
        'user_id' => $user->id,
    ]);

    expect(fn() => Website::create([
        'name' => 'Second Site',
        'url' => 'https://example.com',
        'user_id' => $user->id,
    ]))->toThrow(Illuminate\Database\QueryException::class);
});

test('different users can have same url', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $website1 = Website::create([
        'name' => 'User 1 Site',
        'url' => 'https://example.com',
        'user_id' => $user1->id,
    ]);

    $website2 = Website::create([
        'name' => 'User 2 Site',
        'url' => 'https://example.com',
        'user_id' => $user2->id,
    ]);

    expect($website1)->toBeInstanceOf(Website::class)
        ->and($website2)->toBeInstanceOf(Website::class);
});


test('website can update plugin data', function () {
    $website = Website::factory()->create();

    $website->setPluginData('test_plugin', ['key' => 'value']);
    $website->save();

    expect($website->getPluginData('test_plugin'))->toBe(['key' => 'value'])
        ->and($website->getPluginData('test_plugin', 'key'))->toBe('value');
});

test('website monitoring configuration can be set', function () {
    $config = [
        'timeout' => 30,
        'retries' => 3,
        'alert_days_before_expiry' => 14,
    ];

    $website = Website::factory()->create([
        'monitoring_config' => $config,
    ]);

    expect($website->monitoring_config)->toBe($config)
        ->and($website->monitoring_config['timeout'])->toBe(30)
        ->and($website->monitoring_config['retries'])->toBe(3)
        ->and($website->monitoring_config['alert_days_before_expiry'])->toBe(14);
});

test('website can have different monitoring types enabled', function () {
    $sslOnly = Website::factory()->withSslOnly()->create();
    $uptimeOnly = Website::factory()->withUptimeOnly()->create();
    $both = Website::factory()->withBothMonitoring()->create();

    expect($sslOnly->ssl_monitoring_enabled)->toBeTrue()
        ->and($sslOnly->uptime_monitoring_enabled)->toBeFalse()
        ->and($uptimeOnly->ssl_monitoring_enabled)->toBeFalse()
        ->and($uptimeOnly->uptime_monitoring_enabled)->toBeTrue()
        ->and($both->ssl_monitoring_enabled)->toBeTrue()
        ->and($both->uptime_monitoring_enabled)->toBeTrue();
});

test('website check interval can be customized', function () {
    $website = Website::factory()->withCustomInterval(7200)->create();

    expect($website->check_interval)->toBe(7200);
});

test('website can be inactive', function () {
    $inactive = Website::factory()->inactive()->create();

    expect($inactive->is_active)->toBeFalse();
});

test('website has proper fillable attributes', function () {
    $website = new Website();

    $fillable = $website->getFillable();

    expect($fillable)->toContain('name')
        ->and($fillable)->toContain('url')
        ->and($fillable)->toContain('user_id')
        ->and($fillable)->toContain('ssl_monitoring_enabled')
        ->and($fillable)->toContain('uptime_monitoring_enabled')
        ->and($fillable)->toContain('check_interval')
        ->and($fillable)->toContain('monitoring_config')
        ->and($fillable)->toContain('plugin_data')
        ->and($fillable)->toContain('is_active');
});

test('website casts monitoring_config and plugin_data to arrays', function () {
    $website = Website::factory()->create([
        'monitoring_config' => ['test' => 'value'],
        'plugin_data' => ['plugin' => 'data'],
    ]);

    expect($website->monitoring_config)->toBeArray()
        ->and($website->plugin_data)->toBeArray();
});