<?php

use App\Models\User;
use App\Models\PluginConfiguration;

test('user can view plugin management dashboard', function () {
    $user = User::factory()->create();

    // Create sample plugins
    PluginConfiguration::factory()->agent()->active()->create([
        'user_id' => $user->id,
        'plugin_name' => 'system_metrics_agent',
    ]);

    PluginConfiguration::factory()->webhook()->active()->create([
        'user_id' => $user->id,
        'plugin_name' => 'slack_notifications',
    ]);

    $page = $this->actingAs($user)->visit('/plugins');

    $page->assertSee('Plugin Management')
        ->assertSee('system_metrics_agent')
        ->assertSee('slack_notifications')
        ->assertSee('Active')
        ->assertNoJavascriptErrors();
});

test('user can add new agent plugin', function () {
    $user = User::factory()->create();

    $page = $this->actingAs($user)->visit('/plugins');

    $page->click('[data-testid="add-plugin-button"]')
        ->waitForText('Add New Plugin')
        ->select('[data-testid="plugin-type"]', 'agent')
        ->fill('[data-testid="plugin-name"]', 'disk_monitor_agent')
        ->fill('[data-testid="collection-interval"]', '300')
        ->check('[data-testid="metric-cpu-usage"]')
        ->check('[data-testid="metric-disk-space"]')
        ->fill('[data-testid="api-key"]', 'test-api-key-123')
        ->click('[data-testid="save-plugin"]')
        ->waitForText('Plugin added successfully')
        ->assertSee('disk_monitor_agent')
        ->assertSee('Pending')
        ->assertNoJavascriptErrors();

    // Verify plugin was created in database
    $this->assertDatabaseHas('plugin_configurations', [
        'user_id' => $user->id,
        'plugin_type' => 'agent',
        'plugin_name' => 'disk_monitor_agent',
        'status' => 'pending',
    ]);
});

test('user can add webhook plugin', function () {
    $user = User::factory()->create();

    $page = $this->actingAs($user)->visit('/plugins');

    $page->click('[data-testid="add-plugin-button"]')
        ->waitForText('Add New Plugin')
        ->select('[data-testid="plugin-type"]', 'webhook')
        ->fill('[data-testid="plugin-name"]', 'discord_alerts')
        ->fill('[data-testid="webhook-url"]', 'https://discord.com/api/webhooks/test')
        ->check('[data-testid="event-ssl-expiring"]')
        ->check('[data-testid="event-website-down"]')
        ->fill('[data-testid="retry-attempts"]', '3')
        ->click('[data-testid="save-plugin"]')
        ->waitForText('Plugin added successfully')
        ->assertSee('discord_alerts')
        ->assertNoJavascriptErrors();
});

test('user can edit existing plugin configuration', function () {
    $user = User::factory()->create();
    $plugin = PluginConfiguration::factory()->agent()->create([
        'user_id' => $user->id,
        'plugin_name' => 'system_agent',
    ]);

    $page = $this->actingAs($user)->visit('/plugins');

    $page->click('[data-testid="edit-plugin-' . $plugin->id . '"]')
        ->waitForText('Edit Plugin Configuration')
        ->clear('[data-testid="collection-interval"]')
        ->fill('[data-testid="collection-interval"]', '600')
        ->click('[data-testid="save-plugin"]')
        ->waitForText('Plugin updated successfully')
        ->assertNoJavascriptErrors();

    // Verify plugin was updated
    $plugin->refresh();
    expect($plugin->configuration['collection_interval'])->toBe(600);
});

test('user can enable and disable plugins', function () {
    $user = User::factory()->create();
    $plugin = PluginConfiguration::factory()->active()->create([
        'user_id' => $user->id,
        'plugin_name' => 'test_plugin',
        'is_enabled' => true,
    ]);

    $page = $this->actingAs($user)->visit('/plugins');

    // Disable plugin
    $page->click('[data-testid="toggle-plugin-' . $plugin->id . '"]')
        ->waitForText('Plugin disabled')
        ->assertSee('Disabled')
        ->assertNoJavascriptErrors();

    // Enable plugin
    $page->click('[data-testid="toggle-plugin-' . $plugin->id . '"]')
        ->waitForText('Plugin enabled')
        ->assertSee('Enabled')
        ->assertNoJavascriptErrors();
});

test('user can delete plugin', function () {
    $user = User::factory()->create();
    $plugin = PluginConfiguration::factory()->create([
        'user_id' => $user->id,
        'plugin_name' => 'plugin_to_delete',
    ]);

    $page = $this->actingAs($user)->visit('/plugins');

    $page->click('[data-testid="delete-plugin-' . $plugin->id . '"]')
        ->waitForText('Confirm Plugin Deletion')
        ->assertSee('plugin_to_delete')
        ->click('[data-testid="confirm-delete"]')
        ->waitForText('Plugin deleted successfully')
        ->assertDontSee('plugin_to_delete')
        ->assertNoJavascriptErrors();

    // Verify plugin was deleted
    $this->assertDatabaseMissing('plugin_configurations', [
        'id' => $plugin->id,
    ]);
});

test('plugin status indicators work correctly', function () {
    $user = User::factory()->create();

    $activePlugin = PluginConfiguration::factory()->active()->create([
        'user_id' => $user->id,
        'plugin_name' => 'active_plugin',
    ]);

    $errorPlugin = PluginConfiguration::factory()->error()->create([
        'user_id' => $user->id,
        'plugin_name' => 'error_plugin',
        'status_message' => 'Connection failed',
    ]);

    $pendingPlugin = PluginConfiguration::factory()->pending()->create([
        'user_id' => $user->id,
        'plugin_name' => 'pending_plugin',
    ]);

    $page = $this->actingAs($user)->visit('/plugins');

    $page->assertSee('active_plugin')
        ->assertSee('error_plugin')
        ->assertSee('pending_plugin')
        ->assertSee('Active')
        ->assertSee('Error')
        ->assertSee('Pending')
        ->assertSee('Connection failed')
        ->assertNoJavascriptErrors();
});

test('plugin configuration form validates correctly', function () {
    $user = User::factory()->create();

    $page = $this->actingAs($user)->visit('/plugins');

    $page->click('[data-testid="add-plugin-button"]')
        ->waitForText('Add New Plugin')
        ->click('[data-testid="save-plugin"]')
        ->waitForText('The plugin type field is required')
        ->assertSee('The plugin name field is required')
        ->assertNoJavascriptErrors();
});

test('plugin type changes form fields dynamically', function () {
    $user = User::factory()->create();

    $page = $this->actingAs($user)->visit('/plugins');

    $page->click('[data-testid="add-plugin-button"]')
        ->waitForText('Add New Plugin')
        ->select('[data-testid="plugin-type"]', 'agent')
        ->waitFor('[data-testid="collection-interval"]')
        ->assertVisible('[data-testid="collection-interval"]')
        ->assertVisible('[data-testid="metrics-selection"]');

    $page->select('[data-testid="plugin-type"]', 'webhook')
        ->waitFor('[data-testid="webhook-url"]')
        ->assertVisible('[data-testid="webhook-url"]')
        ->assertVisible('[data-testid="notification-events"]')
        ->assertNotVisible('[data-testid="collection-interval"]')
        ->assertNoJavascriptErrors();
});

test('plugin connection testing works', function () {
    $user = User::factory()->create();
    $plugin = PluginConfiguration::factory()->webhook()->create([
        'user_id' => $user->id,
        'plugin_name' => 'test_webhook',
    ]);

    $page = $this->actingAs($user)->visit('/plugins');

    $page->click('[data-testid="test-connection-' . $plugin->id . '"]')
        ->waitForText('Testing connection...')
        ->waitForText('Connection test completed', 10)
        ->assertNoJavascriptErrors();
});

test('plugin metrics and statistics display correctly', function () {
    $user = User::factory()->create();

    $plugin = PluginConfiguration::factory()->agent()->active()->create([
        'user_id' => $user->id,
        'plugin_name' => 'metrics_agent',
        'last_contacted_at' => now()->subMinutes(5),
    ]);

    $page = $this->actingAs($user)->visit('/plugins');

    $page->click('[data-testid="view-metrics-' . $plugin->id . '"]')
        ->waitForText('Plugin Metrics')
        ->assertSee('Last Contact')
        ->assertSee('5 minutes ago')
        ->assertSee('Data Points Received')
        ->assertNoJavascriptErrors();
});

test('plugin logs can be viewed', function () {
    $user = User::factory()->create();
    $plugin = PluginConfiguration::factory()->active()->create([
        'user_id' => $user->id,
        'plugin_name' => 'logging_plugin',
    ]);

    $page = $this->actingAs($user)->visit('/plugins');

    $page->click('[data-testid="view-logs-' . $plugin->id . '"]')
        ->waitForText('Plugin Logs')
        ->assertSee('Recent Activity')
        ->assertNoJavascriptErrors();
});

test('plugin filtering and search work', function () {
    $user = User::factory()->create();

    PluginConfiguration::factory()->agent()->active()->create([
        'user_id' => $user->id,
        'plugin_name' => 'agent_plugin',
    ]);

    PluginConfiguration::factory()->webhook()->error()->create([
        'user_id' => $user->id,
        'plugin_name' => 'webhook_plugin',
    ]);

    $page = $this->actingAs($user)->visit('/plugins');

    // Filter by plugin type
    $page->click('[data-testid="filter-agent"]')
        ->waitFor('[data-testid="plugin-list"]')
        ->assertSee('agent_plugin')
        ->assertDontSee('webhook_plugin');

    // Filter by status
    $page->click('[data-testid="filter-error"]')
        ->waitFor('[data-testid="plugin-list"]')
        ->assertSee('webhook_plugin')
        ->assertDontSee('agent_plugin');

    // Search by name
    $page->click('[data-testid="filter-all"]')
        ->fill('[data-testid="search-plugins"]', 'agent')
        ->waitFor('[data-testid="plugin-list"]')
        ->assertSee('agent_plugin')
        ->assertDontSee('webhook_plugin')
        ->assertNoJavascriptErrors();
});

test('plugin export and import functionality works', function () {
    $user = User::factory()->create();

    PluginConfiguration::factory(3)->create(['user_id' => $user->id]);

    $page = $this->actingAs($user)->visit('/plugins');

    // Test export
    $page->click('[data-testid="export-plugins"]')
        ->waitForText('Export started')
        ->assertNoJavascriptErrors();

    // Test import
    $page->click('[data-testid="import-plugins"]')
        ->waitForText('Import Plugin Configurations')
        ->assertVisible('[data-testid="file-upload"]')
        ->assertNoJavascriptErrors();
});