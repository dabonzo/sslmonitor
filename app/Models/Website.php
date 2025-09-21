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
        'is_active',
        'check_interval',
    ];

    protected $attributes = [
        'ssl_monitoring_enabled' => true,
        'uptime_monitoring_enabled' => false,
        'monitoring_config' => '{"is_active": true}',
        'plugin_data' => '{}',
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

    // Virtual attributes for test compatibility
    public function getIsActiveAttribute(): bool
    {
        return $this->getMonitoringConfig('is_active') ?? true;
    }

    public function setIsActiveAttribute(bool $value): void
    {
        $this->setMonitoringConfig('is_active', $value);
    }

    public function getCheckIntervalAttribute(): ?int
    {
        return $this->getMonitoringConfig('check_interval');
    }

    public function setCheckIntervalAttribute(int $value): void
    {
        $this->setMonitoringConfig('check_interval', $value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    protected function setUrlAttribute(string $value): void
    {
        // URL sanitization logic from old_docs
        $url = trim($value);

        // Remove trailing slashes
        $url = rtrim($url, '/');

        // Add https if no protocol specified (case-insensitive check)
        if (!str_starts_with(strtolower($url), 'http://') && !str_starts_with(strtolower($url), 'https://')) {
            $url = 'https://' . $url;
        }

        // Convert all URLs to https and normalize (case-insensitive)
        $url = preg_replace('/^http:\/\//i', 'https://', $url);

        // Parse URL to normalize it properly
        $parsed = parse_url($url);
        if ($parsed && isset($parsed['host'])) {
            $normalized = 'https://';
            $normalized .= strtolower($parsed['host']);

            if (isset($parsed['port']) && $parsed['port'] !== 443) {
                $normalized .= ':' . $parsed['port'];
            }

            // Normalize path - remove .. and empty segments
            if (isset($parsed['path']) && $parsed['path'] !== '/') {
                $pathParts = array_filter(explode('/', $parsed['path']), function($part) {
                    return $part !== '' && $part !== '.';
                });

                // Remove .. references
                $finalParts = [];
                foreach ($pathParts as $part) {
                    if ($part === '..') {
                        array_pop($finalParts);
                    } else {
                        $finalParts[] = $part;
                    }
                }

                if (!empty($finalParts)) {
                    $normalized .= '/' . implode('/', $finalParts);
                }
            }

            $url = $normalized;
        }

        $this->attributes['url'] = $url;
    }

    public function getSpatieMonitor(): ?\Spatie\UptimeMonitor\Models\Monitor
    {
        return \Spatie\UptimeMonitor\Models\Monitor::where('url', $this->url)->first();
    }

    public function getCurrentSslStatus(): string
    {
        $monitor = $this->getSpatieMonitor();
        return $monitor?->certificate_status ?? 'not yet checked';
    }

    public function getCurrentUptimeStatus(): string
    {
        $monitor = $this->getSpatieMonitor();
        return $monitor?->uptime_status ?? 'not yet checked';
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

    public function setPluginData(string $pluginName, string|array $key, mixed $value = null): void
    {
        $data = $this->plugin_data ?? [];

        if (is_array($key)) {
            // If key is an array, set the entire plugin data
            $data[$pluginName] = $key;
        } else {
            // If key is a string, set a specific key-value pair
            $data[$pluginName][$key] = $value;
        }

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
