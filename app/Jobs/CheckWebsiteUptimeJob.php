<?php

namespace App\Jobs;

use App\Models\UptimeCheck;
use App\Models\Website;
use App\Services\UptimeChecker;
use App\Services\UptimeStatusCalculator;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckWebsiteUptimeJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(public Website $website)
    {
        // Use default queue for simplicity and reliability
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Exponential backoff: 30s, 1m, 2m
    }

    /**
     * Execute the job.
     */
    public function handle(UptimeChecker $checker, UptimeStatusCalculator $calculator): void
    {
        // Skip if uptime monitoring is not enabled for this website
        if (! $this->website->uptime_monitoring) {
            return;
        }

        try {
            // Perform uptime check
            $checkResult = $checker->checkWebsite($this->website);

            // Store the uptime check result
            $uptimeCheck = UptimeCheck::create([
                'website_id' => $this->website->id,
                'status' => $checkResult->status,
                'http_status_code' => $checkResult->httpStatusCode,
                'response_time_ms' => $checkResult->responseTime,
                'response_size_bytes' => $checkResult->responseSize,
                'content_check_passed' => $checkResult->contentCheckPassed,
                'content_check_error' => $checkResult->contentCheckError,
                'error_message' => $checkResult->errorMessage,
                'checked_at' => now(),
            ]);

            // Detect and handle downtime incidents
            $incident = $calculator->detectDowntimeIncident($this->website);

            // Update website uptime status
            $currentStatus = $calculator->calculateStatus($this->website);
            $this->website->update([
                'uptime_status' => $currentStatus,
                'last_uptime_check_at' => now(),
            ]);

        } catch (Exception $exception) {
            // Handle service exceptions gracefully by creating error record
            UptimeCheck::create([
                'website_id' => $this->website->id,
                'status' => 'down',
                'error_message' => $exception->getMessage(),
                'checked_at' => now(),
            ]);
        }
    }
}
