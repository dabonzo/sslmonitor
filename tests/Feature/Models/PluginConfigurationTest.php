<?php

use App\Models\PluginConfiguration;
use App\Models\User;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

test('plugin configuration can be created with valid data', function () {
    $user = $this->testUser;

    $plugin = PluginConfiguration::create([
        'user_id' => $user->id,
        'plugin_type' => 'agent',
        'plugin_name' => 'system_metrics_agent',
        'configuration' => ['interval' => 300],
        'authentication' => ['type' => 'api_key', 'key' => 'test123'],
        'endpoints' => ['data' => '/api/v1/data'],
        'status' => 'active',
        'is_enabled' => true,
    ]);

    expect($plugin)->toBeInstanceOf(PluginConfiguration::class)
        ->and($plugin->user_id)->toBe($user->id)
        ->and($plugin->plugin_type)->toBe('agent')
        ->and($plugin->plugin_name)->toBe('system_metrics_agent')
        ->and($plugin->status)->toBe('active')
        ->and($plugin->is_enabled)->toBeTrue();
});

test('plugin configuration belongs to user', function () {
    $user = $this->testUser;
    $plugin = PluginConfiguration::factory()->create(['user_id' => $user->id]);

    expect($plugin->user)->toBeInstanceOf(User::class)
        ->and($plugin->user->id)->toBe($user->id);
});

test('plugin configuration can have different types', function () {
    $user = $this->testUser;

    $agent = PluginConfiguration::factory()->agent()->create(['user_id' => $user->id]);
    $webhook = PluginConfiguration::factory()->webhook()->create(['user_id' => $user->id]);
    $externalService = PluginConfiguration::factory()->externalService()->create(['user_id' => $user->id]);

    expect($agent->plugin_type)->toBe('agent')
        ->and($webhook->plugin_type)->toBe('webhook')
        ->and($externalService->plugin_type)->toBe('external_service');
});

test('plugin configuration can have different statuses', function () {
    $user = $this->testUser;

    $active = PluginConfiguration::factory()->active()->create(['user_id' => $user->id]);
    $inactive = PluginConfiguration::factory()->inactive()->create(['user_id' => $user->id]);
    $error = PluginConfiguration::factory()->error()->create(['user_id' => $user->id]);
    $pending = PluginConfiguration::factory()->pending()->create(['user_id' => $user->id]);

    expect($active->status)->toBe('active')
        ->and($inactive->status)->toBe('inactive')
        ->and($error->status)->toBe('error')
        ->and($pending->status)->toBe('pending');
});

test('plugin configuration can be marked as active', function () {
    $plugin = PluginConfiguration::factory()->pending()->create();

    $plugin->markAsActive('Successfully connected');

    expect($plugin->status)->toBe('active')
        ->and($plugin->status_message)->toBe('Successfully connected')
        ->and($plugin->last_contacted_at)->not->toBeNull();
});

test('plugin configuration can be marked as error', function () {
    $plugin = PluginConfiguration::factory()->active()->create();

    $plugin->markAsError('Connection failed');

    expect($plugin->status)->toBe('error')
        ->and($plugin->status_message)->toBe('Connection failed');
});

test('plugin configuration can be marked as inactive', function () {
    $plugin = PluginConfiguration::factory()->active()->create();

    $plugin->markAsInactive('Disabled by user');

    expect($plugin->status)->toBe('inactive')
        ->and($plugin->status_message)->toBe('Disabled by user')
        ->and($plugin->is_enabled)->toBeFalse();
});

test('plugin configuration can update last contacted timestamp', function () {
    $plugin = PluginConfiguration::factory()->create();
    $originalTime = $plugin->last_contacted_at;

    sleep(1);
    $plugin->updateLastContacted();

    expect($plugin->last_contacted_at)->not->toBe($originalTime);
});

test('plugin configuration determines if recently contacted', function () {
    $recentlyContacted = PluginConfiguration::factory()->create([
        'last_contacted_at' => now()->subMinutes(5),
    ]);

    $notRecentlyContacted = PluginConfiguration::factory()->create([
        'last_contacted_at' => now()->subHours(2),
    ]);

    $neverContacted = PluginConfiguration::factory()->create([
        'last_contacted_at' => null,
    ]);

    expect($recentlyContacted->isRecentlyContacted())->toBeTrue()
        ->and($notRecentlyContacted->isRecentlyContacted())->toBeFalse()
        ->and($neverContacted->isRecentlyContacted())->toBeFalse();
});

test('plugin configuration can customize recently contacted threshold', function () {
    $plugin = PluginConfiguration::factory()->create([
        'last_contacted_at' => now()->subMinutes(45),
    ]);

    expect($plugin->isRecentlyContacted(30))->toBeFalse() // 30 minutes threshold
        ->and($plugin->isRecentlyContacted(60))->toBeTrue(); // 60 minutes threshold
});

test('plugin configuration has proper fillable attributes', function () {
    $plugin = new PluginConfiguration();

    $fillable = $plugin->getFillable();

    expect($fillable)->toContain('user_id')
        ->and($fillable)->toContain('plugin_type')
        ->and($fillable)->toContain('plugin_name')
        ->and($fillable)->toContain('configuration')
        ->and($fillable)->toContain('authentication')
        ->and($fillable)->toContain('endpoints')
        ->and($fillable)->toContain('status')
        ->and($fillable)->toContain('status_message')
        ->and($fillable)->toContain('last_contacted_at')
        ->and($fillable)->toContain('is_enabled');
});

test('plugin configuration casts json fields correctly', function () {
    $plugin = PluginConfiguration::factory()->create([
        'configuration' => ['key' => 'value'],
        'authentication' => ['type' => 'api_key'],
        'endpoints' => ['api' => '/api/v1'],
    ]);

    expect($plugin->configuration)->toBeArray()
        ->and($plugin->authentication)->toBeArray()
        ->and($plugin->endpoints)->toBeArray();
});

test('plugin configuration casts last_contacted_at to datetime', function () {
    $plugin = PluginConfiguration::factory()->create([
        'last_contacted_at' => '2025-01-01 12:00:00',
    ]);

    expect($plugin->last_contacted_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('plugin configuration agent type has expected configuration structure', function () {
    $agent = PluginConfiguration::factory()->agent()->create();

    expect($agent->configuration)->toBeArray()
        ->and($agent->configuration)->toHaveKey('collection_interval')
        ->and($agent->configuration)->toHaveKey('metrics_to_collect')
        ->and($agent->configuration['metrics_to_collect'])->toBeArray();
});

test('plugin configuration webhook type has expected configuration structure', function () {
    $webhook = PluginConfiguration::factory()->webhook()->create();

    expect($webhook->configuration)->toBeArray()
        ->and($webhook->configuration)->toHaveKey('webhook_url')
        ->and($webhook->configuration)->toHaveKey('notification_events')
        ->and($webhook->configuration['notification_events'])->toBeArray();
});

test('plugin configuration external service type has expected configuration structure', function () {
    $service = PluginConfiguration::factory()->externalService()->create();

    expect($service->configuration)->toBeArray()
        ->and($service->configuration)->toHaveKey('api_endpoint')
        ->and($service->configuration)->toHaveKey('data_format')
        ->and($service->configuration)->toHaveKey('sync_interval');
});

test('plugin configuration can have custom configuration', function () {
    $customConfig = [
        'custom_setting' => 'value',
        'nested' => ['key' => 'value'],
    ];

    $plugin = PluginConfiguration::factory()->withCustomConfiguration($customConfig)->create();

    expect($plugin->configuration)->toBe($customConfig)
        ->and($plugin->configuration['custom_setting'])->toBe('value')
        ->and($plugin->configuration['nested']['key'])->toBe('value');
});

test('plugin configuration can have custom authentication', function () {
    $customAuth = [
        'type' => 'oauth2',
        'client_id' => 'test-client',
        'client_secret' => 'test-secret',
    ];

    $plugin = PluginConfiguration::factory()->withAuthentication($customAuth)->create();

    expect($plugin->authentication)->toBe($customAuth)
        ->and($plugin->authentication['type'])->toBe('oauth2')
        ->and($plugin->authentication['client_id'])->toBe('test-client');
});

test('plugin configuration scopes work correctly', function () {
    $user = $this->testUser;

    PluginConfiguration::factory()->active()->create(['user_id' => $user->id]);
    PluginConfiguration::factory()->active()->agent()->create(['user_id' => $user->id]);
    PluginConfiguration::factory()->inactive()->create(['user_id' => $user->id]);
    PluginConfiguration::factory()->error()->create(['user_id' => $user->id]);
    PluginConfiguration::factory()->agent()->create(['user_id' => $user->id]);
    PluginConfiguration::factory()->webhook()->create(['user_id' => $user->id]);

    expect(PluginConfiguration::active()->count())->toBeGreaterThanOrEqual(2)
        ->and(PluginConfiguration::inactive()->count())->toBeGreaterThanOrEqual(1)
        ->and(PluginConfiguration::where('status', 'error')->count())->toBeGreaterThanOrEqual(1)
        ->and(PluginConfiguration::where('plugin_type', 'agent')->count())->toBeGreaterThanOrEqual(2)
        ->and(PluginConfiguration::where('plugin_type', 'webhook')->count())->toBeGreaterThanOrEqual(1);
});

test('plugin configuration enabled scope works correctly', function () {
    PluginConfiguration::factory()->create(['is_enabled' => true]);
    PluginConfiguration::factory()->create(['is_enabled' => false]);

    expect(PluginConfiguration::enabled()->count())->toBeGreaterThanOrEqual(1)
        ->and(PluginConfiguration::disabled()->count())->toBeGreaterThanOrEqual(1);
});