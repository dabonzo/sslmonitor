<?php

namespace App\Contracts;

/**
 * Interface for notification channels (email, SMS, Slack, webhooks, etc.)
 * Extensible notification system for v1.1.0 plugin architecture
 */
interface NotificationChannelInterface
{
    /**
     * Send notification through this channel
     */
    public function send(array $notification): bool;

    /**
     * Validate channel configuration
     */
    public function validateConfiguration(array $config): bool;

    /**
     * Test channel connectivity
     */
    public function test(): bool;

    /**
     * Get channel type identifier
     */
    public function getChannelType(): string;

    /**
     * Get channel name
     */
    public function getChannelName(): string;

    /**
     * Get supported notification types
     */
    public function getSupportedTypes(): array;

    /**
     * Get configuration schema
     */
    public function getConfigurationSchema(): array;

    /**
     * Format notification for this channel
     */
    public function formatNotification(array $data, string $type): array;

    /**
     * Get delivery status
     */
    public function getDeliveryStatus(string $messageId): array;

    /**
     * Get channel capabilities
     */
    public function getCapabilities(): array;

    /**
     * Get rate limits
     */
    public function getRateLimits(): array;

    /**
     * Check if channel is healthy
     */
    public function isHealthy(): bool;
}