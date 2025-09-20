<?php

namespace App\Contracts;

/**
 * Interface for communicating with monitoring agents
 * Designed for v1.1.0 agent system implementation
 */
interface AgentCommunicatorInterface
{
    /**
     * Send configuration to agent
     */
    public function sendConfiguration(string $agentId, array $config): bool;

    /**
     * Request data collection from agent
     */
    public function requestCollection(string $agentId, array $metrics = []): array;

    /**
     * Check agent health and connectivity
     */
    public function checkHealth(string $agentId): array;

    /**
     * Get agent capabilities and supported metrics
     */
    public function getCapabilities(string $agentId): array;

    /**
     * Register a new agent
     */
    public function registerAgent(array $agentConfig): string;

    /**
     * Unregister an agent
     */
    public function unregisterAgent(string $agentId): bool;

    /**
     * Get list of active agents
     */
    public function getActiveAgents(): array;

    /**
     * Send command to agent (restart, update, etc.)
     */
    public function sendCommand(string $agentId, string $command, array $params = []): array;

    /**
     * Get agent logs
     */
    public function getAgentLogs(string $agentId, int $lines = 100): array;

    /**
     * Update agent software
     */
    public function updateAgent(string $agentId, string $version = null): bool;
}