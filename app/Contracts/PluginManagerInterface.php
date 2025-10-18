<?php

namespace App\Contracts;

use App\Models\PluginConfiguration;

/**
 * Interface for managing plugins (agents, webhooks, external services)
 * Central management system for v1.1.0 plugin architecture
 */
interface PluginManagerInterface
{
    /**
     * Install a new plugin
     */
    public function install(array $pluginConfig): PluginConfiguration;

    /**
     * Uninstall a plugin
     */
    public function uninstall(string $pluginId): bool;

    /**
     * Enable a plugin
     */
    public function enable(string $pluginId): bool;

    /**
     * Disable a plugin
     */
    public function disable(string $pluginId): bool;

    /**
     * Update plugin configuration
     */
    public function updateConfiguration(string $pluginId, array $config): bool;

    /**
     * Get plugin by ID
     */
    public function getPlugin(string $pluginId): ?PluginConfiguration;

    /**
     * Get all plugins by type
     */
    public function getPluginsByType(string $type): array;

    /**
     * Get all active plugins
     */
    public function getActivePlugins(): array;

    /**
     * Check plugin health
     */
    public function checkHealth(string $pluginId): array;

    /**
     * Get plugin metrics/status
     */
    public function getPluginMetrics(string $pluginId): array;

    /**
     * Validate plugin configuration
     */
    public function validateConfiguration(array $config): array;

    /**
     * Discover available plugins
     */
    public function discoverPlugins(): array;

    /**
     * Get plugin logs
     */
    public function getPluginLogs(string $pluginId, int $lines = 100): array;

    /**
     * Update plugin status
     */
    public function updateStatus(string $pluginId, string $status, ?string $message = null): bool;
}
