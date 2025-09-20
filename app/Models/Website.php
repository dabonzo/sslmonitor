<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'user_id',
        'monitoring_config',
        'ssl_monitoring_enabled',
        'uptime_monitoring_enabled',
        'plugin_data',
    ];

    protected function casts(): array
    {
        return [
            'monitoring_config' => 'array',
            'ssl_monitoring_enabled' => 'boolean',
            'uptime_monitoring_enabled' => 'boolean',
            'plugin_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sslCertificates(): HasMany
    {
        return $this->hasMany(SslCertificate::class);
    }

    public function sslChecks(): HasMany
    {
        return $this->hasMany(SslCheck::class);
    }

    protected function setUrlAttribute(string $value): void
    {
        // URL sanitization logic from old_docs
        $url = trim($value);

        // Remove trailing slashes and normalize
        $url = rtrim($url, '/');

        // Add https if no protocol specified
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $url = 'https://' . $url;
        }

        // Convert http to https and normalize
        $url = str_replace('http://', 'https://', $url);
        $url = strtolower($url);

        // Parse and rebuild URL to normalize
        $parsed = parse_url($url);
        if ($parsed) {
            $normalized = ($parsed['scheme'] ?? 'https') . '://';
            $normalized .= $parsed['host'] ?? '';

            if (isset($parsed['port']) && $parsed['port'] !== 443) {
                $normalized .= ':' . $parsed['port'];
            }

            // Normalize path
            if (isset($parsed['path']) && $parsed['path'] !== '/') {
                $normalized .= rtrim($parsed['path'], '/');
            }

            $url = $normalized;
        }

        $this->attributes['url'] = $url;
    }

    public function getLatestSslCertificate(): ?SslCertificate
    {
        return $this->sslCertificates()->latest()->first();
    }

    public function getCurrentSslStatus(): string
    {
        $latestCheck = $this->sslChecks()->latest('checked_at')->first();

        return $latestCheck?->status ?? 'unknown';
    }

    // Plugin-ready: Methods for future agent integration
    public function getMonitoringConfig(string $key = null): mixed
    {
        $config = $this->monitoring_config ?? [];

        return $key ? ($config[$key] ?? null) : $config;
    }

    public function setMonitoringConfig(string $key, mixed $value): void
    {
        $config = $this->monitoring_config ?? [];
        $config[$key] = $value;
        $this->monitoring_config = $config;
    }

    public function getPluginData(string $pluginName, string $key = null): mixed
    {
        $data = $this->plugin_data[$pluginName] ?? [];

        return $key ? ($data[$key] ?? null) : $data;
    }

    public function setPluginData(string $pluginName, string $key, mixed $value): void
    {
        $data = $this->plugin_data ?? [];
        $data[$pluginName][$key] = $value;
        $this->plugin_data = $data;
    }

    public function isSslMonitoringEnabled(): bool
    {
        return $this->ssl_monitoring_enabled;
    }

    public function isUptimeMonitoringEnabled(): bool
    {
        return $this->uptime_monitoring_enabled;
    }
}
