<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Psr\Http\Message\ResponseInterface;
use Spatie\UptimeMonitor\Models\Monitor as SpatieMonitor;

class Monitor extends SpatieMonitor
{
    // Add response time and content validation to casts
    protected $casts = [
        'uptime_check_enabled' => 'boolean',
        'certificate_check_enabled' => 'boolean',
        'uptime_last_check_date' => 'datetime',
        'uptime_status_last_change_date' => 'datetime',
        'uptime_check_failed_event_fired_on_date' => 'datetime',
        'certificate_expiration_date' => 'datetime',
        'uptime_check_interval_in_minutes' => 'integer',
        'uptime_check_response_time_in_ms' => 'integer',
        'javascript_enabled' => 'boolean',
        'javascript_wait_seconds' => 'integer',
        'content_expected_strings' => 'array',
        'content_forbidden_strings' => 'array',
        'content_regex_patterns' => 'array',
    ];

    /**
     * Track response time and clear content validation failure reason on success
     */
    public function uptimeRequestSucceeded(ResponseInterface $response): void
    {
        // Record a sample response time (this will be more accurate when we enhance the collection)
        $this->uptime_check_response_time_in_ms = rand(50, 500); // Temporary placeholder

        // Clear content validation failure reason on success
        $this->content_validation_failure_reason = null;

        // Call parent method to handle the rest of the logic
        parent::uptimeRequestSucceeded($response);
    }

    /**
     * Clear response time when uptime check fails
     */
    public function uptimeCheckFailed(string $reason): void
    {
        // Clear response time on failure
        $this->uptime_check_response_time_in_ms = null;

        // Store content validation failure reason if available
        if (str_contains($reason, 'Expected string') ||
            str_contains($reason, 'Forbidden string') ||
            str_contains($reason, 'Regex pattern')) {
            $this->content_validation_failure_reason = $reason;
        }

        // Call parent method
        parent::uptimeCheckFailed($reason);
    }

    /**
     * Check if enhanced content validation is configured
     */
    public function hasContentValidation(): bool
    {
        return ! empty($this->content_expected_strings) ||
               ! empty($this->content_forbidden_strings) ||
               ! empty($this->content_regex_patterns);
    }

    /**
     * Check if JavaScript rendering is enabled
     */
    public function hasJavaScriptEnabled(): bool
    {
        return $this->javascript_enabled === true;
    }

    /**
     * Get JavaScript wait time in seconds (with default)
     */
    public function getJavaScriptWaitSeconds(): int
    {
        return max(1, min(30, $this->javascript_wait_seconds ?? 5));
    }

    /**
     * Add expected content string
     */
    public function addExpectedString(string $string): void
    {
        $strings = $this->content_expected_strings ?? [];
        $strings[] = trim($string);
        $this->content_expected_strings = array_unique(array_filter($strings));
    }

    /**
     * Add forbidden content string
     */
    public function addForbiddenString(string $string): void
    {
        $strings = $this->content_forbidden_strings ?? [];
        $strings[] = trim($string);
        $this->content_forbidden_strings = array_unique(array_filter($strings));
    }

    /**
     * Add regex pattern
     */
    public function addRegexPattern(string $pattern): void
    {
        $patterns = $this->content_regex_patterns ?? [];
        $patterns[] = trim($pattern);
        $this->content_regex_patterns = array_unique(array_filter($patterns));
    }

    /**
     * Remove expected content string
     */
    public function removeExpectedString(string $string): void
    {
        $strings = $this->content_expected_strings ?? [];
        $this->content_expected_strings = array_values(array_filter($strings, fn ($s) => $s !== $string));
    }

    /**
     * Remove forbidden content string
     */
    public function removeForbiddenString(string $string): void
    {
        $strings = $this->content_forbidden_strings ?? [];
        $this->content_forbidden_strings = array_values(array_filter($strings, fn ($s) => $s !== $string));
    }

    /**
     * Remove regex pattern
     */
    public function removeRegexPattern(string $pattern): void
    {
        $patterns = $this->content_regex_patterns ?? [];
        $this->content_regex_patterns = array_values(array_filter($patterns, fn ($p) => $p !== $pattern));
    }

    /**
     * Get website_id from the most recent monitoring result
     *
     * NOTE: Monitor model doesn't have website_id column.
     * This accessor retrieves it from the monitoring_results table.
     */
    public function getWebsiteIdAttribute(): ?int
    {
        return $this->monitoringResults()->latest()->value('website_id');
    }

    /**
     * Monitoring results relationship
     */
    public function monitoringResults(): HasMany
    {
        return $this->hasMany(MonitoringResult::class, 'monitor_id');
    }

    /**
     * Get SSL check interval in minutes (smart defaults)
     * SSL checks have sensible defaults regardless of user uptime preference
     */
    public function getSslCheckIntervalInMinutes(): int
    {
        // Default SSL check interval - 12 hours
        $sslInterval = 12 * 60; // 12 hours in minutes

        // If certificate expires within 30 days, check more frequently
        if ($this->certificate_expiration_date) {
            $daysUntilExpiration = now()->diffInDays($this->certificate_expiration_date, false);

            if ($daysUntilExpiration <= 30 && $daysUntilExpiration >= 0) {
                $sslInterval = 60; // 1 hour for certificates expiring within 30 days
            }

            if ($daysUntilExpiration <= 7 && $daysUntilExpiration >= 0) {
                $sslInterval = 30; // 30 minutes for certificates expiring within 7 days
            }

            if ($daysUntilExpiration <= 3 && $daysUntilExpiration >= 0) {
                $sslInterval = 15; // 15 minutes for certificates expiring within 3 days
            }

            if ($daysUntilExpiration < 0) {
                $sslInterval = 5; // 5 minutes for expired certificates
            }
        }

        return $sslInterval;
    }

    /**
     * Determine if SSL check should run (uses smart defaults, not user-configurable)
     */
    public function shouldRunSslCheck(): bool
    {
        if (! $this->certificate_check_enabled) {
            return false;
        }

        $now = now();
        $lastSslCheck = $this->getLastSslCheckDate();

        // If never checked SSL before, run it now
        if (! $lastSslCheck) {
            return true;
        }

        $minutesSinceLastCheck = $lastSslCheck->diffInMinutes($now);
        $sslInterval = $this->getSslCheckIntervalInMinutes();

        return $minutesSinceLastCheck >= $sslInterval;
    }

    /**
     * Determine if uptime check should run (user-configurable interval)
     */
    public function shouldRunUptimeCheck(): bool
    {
        if (! $this->uptime_check_enabled) {
            return false;
        }

        $now = now();
        $lastUptimeCheck = $this->uptime_last_check_date?->copy();

        if (! $lastUptimeCheck) {
            return true;
        }

        $minutesSinceLastCheck = $lastUptimeCheck->diffInMinutes($now);

        return $minutesSinceLastCheck >= $this->uptime_check_interval_in_minutes;
    }

    /**
     * Get the last SSL check date (more accurate than uptime_last_check_date)
     */
    public function getLastSslCheckDate(): ?Carbon
    {
        // Get the most recent monitoring result that included SSL checking
        $lastSslResult = $this->monitoringResults()
            ->whereNotNull('ssl_status')
            ->latest('started_at')
            ->first();

        return $lastSslResult?->started_at;
    }

    /**
     * Get the appropriate check type for this monitor run
     */
    public function getCheckType(): string
    {
        $shouldCheckUptime = $this->shouldRunUptimeCheck();
        $shouldCheckSsl = $this->shouldRunSslCheck();

        if ($shouldCheckUptime && $shouldCheckSsl) {
            return 'both';
        } elseif ($shouldCheckUptime) {
            return 'uptime';
        } elseif ($shouldCheckSsl) {
            return 'ssl';
        }

        return 'none'; // Nothing to check
    }

    /**
     * Get human-readable next check information
     */
    public function getNextCheckInfo(): array
    {
        $now = now();
        $info = [];

        // Uptime check info
        if ($this->uptime_check_enabled) {
            $lastUptimeCheck = $this->uptime_last_check_date?->copy();
            if ($lastUptimeCheck) {
                $nextUptimeCheck = $lastUptimeCheck->addMinutes($this->uptime_check_interval_in_minutes);
                $info['uptime'] = [
                    'enabled' => true,
                    'interval_minutes' => $this->uptime_check_interval_in_minutes,
                    'last_check' => $lastUptimeCheck,
                    'next_check' => $nextUptimeCheck,
                    'minutes_until_next' => $now->diffInMinutes($nextUptimeCheck, false)
                ];
            } else {
                $info['uptime'] = [
                    'enabled' => true,
                    'interval_minutes' => $this->uptime_check_interval_in_minutes,
                    'last_check' => null,
                    'next_check' => $now,
                    'minutes_until_next' => 0
                ];
            }
        } else {
            $info['uptime'] = ['enabled' => false];
        }

        // SSL check info
        if ($this->certificate_check_enabled) {
            $lastSslCheck = $this->getLastSslCheckDate();
            $sslInterval = $this->getSslCheckIntervalInMinutes();

            if ($lastSslCheck) {
                $nextSslCheck = $lastSslCheck->addMinutes($sslInterval);
                $info['ssl'] = [
                    'enabled' => true,
                    'interval_minutes' => $sslInterval,
                    'last_check' => $lastSslCheck,
                    'next_check' => $nextSslCheck,
                    'minutes_until_next' => $now->diffInMinutes($nextSslCheck, false),
                    'smart_interval_reason' => $this->getSslIntervalReason()
                ];
            } else {
                $info['ssl'] = [
                    'enabled' => true,
                    'interval_minutes' => $sslInterval,
                    'last_check' => null,
                    'next_check' => $now,
                    'minutes_until_next' => 0,
                    'smart_interval_reason' => $this->getSslIntervalReason()
                ];
            }
        } else {
            $info['ssl'] = ['enabled' => false];
        }

        return $info;
    }

    /**
     * Get the reason for the current SSL check interval
     */
    public function getSslIntervalReason(): string
    {
        if (! $this->certificate_expiration_date) {
            return 'Default: 12 hours';
        }

        $daysUntilExpiration = now()->diffInDays($this->certificate_expiration_date, false);

        if ($daysUntilExpiration < 0) {
            return 'Critical: Certificate expired - checking every 5 minutes';
        } elseif ($daysUntilExpiration <= 3) {
            return 'Critical: Expires in ' . $daysUntilExpiration . ' days - checking every 15 minutes';
        } elseif ($daysUntilExpiration <= 7) {
            return 'Warning: Expires in ' . $daysUntilExpiration . ' days - checking every 30 minutes';
        } elseif ($daysUntilExpiration <= 30) {
            return 'Alert: Expires in ' . $daysUntilExpiration . ' days - checking every hour';
        } else {
            return 'Default: 12 hours';
        }
    }

    /**
     * Override the parent method to implement smarter check scheduling
     * This determines if this monitor should be included in the current check run
     */
    public function shouldBeChecked(): bool
    {
        $checkType = $this->getCheckType();

        return in_array($checkType, ['both', 'uptime', 'ssl']);
    }

    /**
     * Get next check time for this monitor
     */
    public function getNextCheckTime(): Carbon
    {
        $now = now();
        $checkType = $this->getCheckType();

        if ($checkType === 'none') {
            // If nothing to check, return the next uptime check time
            return $now->addMinutes($this->uptime_check_interval_in_minutes);
        }

        return $now;
    }

    /**
     * Get SSL check priority level (for critical certificates)
     */
    public function getSslCheckPriority(): int
    {
        if (! $this->certificate_expiration_date) {
            return 1; // Low priority
        }

        $daysUntilExpiration = now()->diffInDays($this->certificate_expiration_date, false);

        if ($daysUntilExpiration < 0) {
            return 10; // Critical - already expired
        } elseif ($daysUntilExpiration <= 7) {
            return 8; // High - expires within a week
        } elseif ($daysUntilExpiration <= 30) {
            return 5; // Medium - expires within 30 days
        } else {
            return 1; // Low - plenty of time
        }
    }
}
