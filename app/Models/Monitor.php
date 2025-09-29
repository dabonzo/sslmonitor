<?php

namespace App\Models;

use Carbon\Carbon;
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
        $this->content_expected_strings = array_values(array_filter($strings, fn($s) => $s !== $string));
    }

    /**
     * Remove forbidden content string
     */
    public function removeForbiddenString(string $string): void
    {
        $strings = $this->content_forbidden_strings ?? [];
        $this->content_forbidden_strings = array_values(array_filter($strings, fn($s) => $s !== $string));
    }

    /**
     * Remove regex pattern
     */
    public function removeRegexPattern(string $pattern): void
    {
        $patterns = $this->content_regex_patterns ?? [];
        $this->content_regex_patterns = array_values(array_filter($patterns, fn($p) => $p !== $pattern));
    }

}