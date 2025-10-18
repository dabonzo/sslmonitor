<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plugin_type',
        'plugin_name',
        'plugin_version',
        'configuration',
        'authentication',
        'endpoints',
        'is_enabled',
        'last_contacted_at',
        'status',
        'status_message',
        'collection_schedule',
        'data_retention',
        'alert_thresholds',
        'description',
        'capabilities',
        'metadata',
    ];

    protected $hidden = [
        'authentication',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'authentication' => 'encrypted:array',
            'endpoints' => 'array',
            'is_enabled' => 'boolean',
            'last_contacted_at' => 'datetime',
            'collection_schedule' => 'array',
            'data_retention' => 'array',
            'alert_thresholds' => 'array',
            'capabilities' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Query scopes for plugin management
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeDisabled(Builder $query): Builder
    {
        return $query->where('is_enabled', false);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('plugin_type', $type);
    }

    public function scopeAgents(Builder $query): Builder
    {
        return $query->where('plugin_type', 'agent');
    }

    public function scopeWebhooks(Builder $query): Builder
    {
        return $query->where('plugin_type', 'webhook');
    }

    public function scopeExternalServices(Builder $query): Builder
    {
        return $query->where('plugin_type', 'external_service');
    }

    // Plugin configuration methods
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->configuration[$key] ?? $default;
    }

    public function setConfig(string $key, mixed $value): void
    {
        $config = $this->configuration ?? [];
        $config[$key] = $value;
        $this->configuration = $config;
    }

    public function getAuthToken(string $type = 'default'): ?string
    {
        return $this->authentication[$type] ?? null;
    }

    public function setAuthToken(string $type, string $token): void
    {
        $auth = $this->authentication ?? [];
        $auth[$type] = $token;
        $this->authentication = $auth;
    }

    public function getEndpoint(string $type): ?string
    {
        return $this->endpoints[$type] ?? null;
    }

    public function setEndpoint(string $type, string $url): void
    {
        $endpoints = $this->endpoints ?? [];
        $endpoints[$type] = $url;
        $this->endpoints = $endpoints;
    }

    public function getAlertThreshold(string $metric): mixed
    {
        return $this->alert_thresholds[$metric] ?? null;
    }

    public function setAlertThreshold(string $metric, mixed $threshold): void
    {
        $thresholds = $this->alert_thresholds ?? [];
        $thresholds[$metric] = $threshold;
        $this->alert_thresholds = $thresholds;
    }

    public function getCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    public function addCapability(string $capability): void
    {
        $capabilities = $this->capabilities ?? [];
        if (! in_array($capability, $capabilities)) {
            $capabilities[] = $capability;
            $this->capabilities = $capabilities;
        }
    }

    public function removeCapability(string $capability): void
    {
        $capabilities = $this->capabilities ?? [];
        $this->capabilities = array_values(array_filter($capabilities, fn ($c) => $c !== $capability));
    }

    // Status management
    public function markAsActive(?string $message = null): void
    {
        $this->status = 'active';
        $this->status_message = $message;
        $this->last_contacted_at = now();
    }

    public function markAsError(string $message): void
    {
        $this->status = 'error';
        $this->status_message = $message;
    }

    public function markAsDisabled(?string $reason = null): void
    {
        $this->status = 'disabled';
        $this->is_enabled = false;
        $this->status_message = $reason;
    }

    public function markAsInactive(?string $message = null): void
    {
        $this->status = 'inactive';
        $this->status_message = $message;
        $this->is_enabled = false;
        $this->save();
    }

    public function updateLastContacted(): void
    {
        $this->last_contacted_at = now();
        $this->save();
    }

    public function isRecentlyContacted(int $minutesThreshold = 60): bool
    {
        if (! $this->last_contacted_at) {
            return false;
        }

        return $this->last_contacted_at->isAfter(now()->subMinutes($minutesThreshold));
    }

    public function isAgent(): bool
    {
        return $this->plugin_type === 'agent';
    }

    public function isWebhook(): bool
    {
        return $this->plugin_type === 'webhook';
    }

    public function isExternalService(): bool
    {
        return $this->plugin_type === 'external_service';
    }

    public function isHealthy(): bool
    {
        return $this->is_enabled && $this->status === 'active';
    }

    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function setMetadata(string $key, mixed $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
    }
}
