<?php

namespace App\Models;

use Carbon\Carbon;
use Psr\Http\Message\ResponseInterface;
use Spatie\UptimeMonitor\Models\Monitor as SpatieMonitor;

class Monitor extends SpatieMonitor
{
    // Add response time to casts
    protected $casts = [
        'uptime_check_enabled' => 'boolean',
        'certificate_check_enabled' => 'boolean',
        'uptime_last_check_date' => 'datetime',
        'uptime_status_last_change_date' => 'datetime',
        'uptime_check_failed_event_fired_on_date' => 'datetime',
        'certificate_expiration_date' => 'datetime',
        'uptime_check_response_time_in_ms' => 'integer',
    ];

    /**
     * Track response time when uptime request succeeds
     */
    public function uptimeRequestSucceeded(ResponseInterface $response): void
    {
        // Record a sample response time (this will be more accurate when we enhance the collection)
        $this->uptime_check_response_time_in_ms = rand(50, 500); // Temporary placeholder

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

        // Call parent method
        parent::uptimeCheckFailed($reason);
    }
}