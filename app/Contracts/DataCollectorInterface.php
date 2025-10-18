<?php

namespace App\Contracts;

/**
 * Interface for data collectors (SSL monitoring, agents, external services)
 * This contract enables plugin-ready architecture for v1.1.0 agent system
 */
interface DataCollectorInterface
{
    /**
     * Collect data from the specified source
     */
    public function collect(array $config = []): array;

    /**
     * Validate that the data source is reachable and configured correctly
     */
    public function validate(): bool;

    /**
     * Get the collector's capabilities (what it can monitor/collect)
     */
    public function getCapabilities(): array;

    /**
     * Get the collector's configuration requirements
     */
    public function getConfigurationSchema(): array;

    /**
     * Get the collector's identifier (ssl_monitor, agent, external_service)
     */
    public function getCollectorType(): string;

    /**
     * Get the collector's name (system_metrics_agent, disk_space_monitor, etc.)
     */
    public function getCollectorName(): string;

    /**
     * Get the collector's version
     */
    public function getVersion(): string;

    /**
     * Set collector configuration
     */
    public function setConfiguration(array $config): void;

    /**
     * Get last collection timestamp
     */
    public function getLastCollectionAt(): ?\DateTime;

    /**
     * Get collector health status
     */
    public function getHealthStatus(): array;
}
