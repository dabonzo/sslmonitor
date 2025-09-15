<?php

namespace App\Jobs;

use App\Events\UptimeStatusChanged;
use App\Models\UptimeCheck;
use App\Models\Website;
use App\Services\UptimeChecker;
use App\Services\UptimeStatusCalculator;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
    public function __construct(
        public Website $website,
        public bool $forceCheck = false
    ) {
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
            Log::info("Skipping uptime check for {$this->website->url} - uptime monitoring disabled");

            return;
        }

        // Skip if we've checked this website recently (within last hour), unless forced
        $forceCheck = isset($this->forceCheck) ? $this->forceCheck : false;
        if (! $forceCheck) {
            $recentCheck = $this->website->uptimeChecks()
                ->where('checked_at', '>', now()->subHour())
                ->latest('checked_at')
                ->first();

            if ($recentCheck) {
                Log::info("Skipping uptime check for {$this->website->url} - checked recently at {$recentCheck->checked_at}");

                return;
            }
        }

        Log::info("Starting uptime check for {$this->website->url}", [
            'force_check' => $forceCheck,
            'website_id' => $this->website->id,
        ]);

        // Get previous status for real-time event comparison
        $previousCheck = $this->website->uptimeChecks()
            ->latest('checked_at')
            ->first();
        $previousStatus = $previousCheck?->status ?? 'unknown';

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

            Log::info("Uptime check completed for {$this->website->url}", [
                'status' => $uptimeCheck->status,
                'previous_status' => $previousStatus,
                'response_time' => $checkResult->responseTime,
                'website_id' => $this->website->id,
            ]);

            // Dispatch real-time event if status changed
            if ($uptimeCheck->status !== $previousStatus) {
                UptimeStatusChanged::dispatch($uptimeCheck, $previousStatus);
                Log::info('Uptime status change broadcasted', [
                    'website' => $this->website->url,
                    'from' => $previousStatus,
                    'to' => $uptimeCheck->status,
                ]);
            }

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
            $uptimeCheck = UptimeCheck::create([
                'website_id' => $this->website->id,
                'status' => 'down',
                'error_message' => $exception->getMessage(),
                'checked_at' => now(),
            ]);

            Log::error("Uptime check failed for {$this->website->url}", [
                'error' => $exception->getMessage(),
                'website_id' => $this->website->id,
            ]);

            // Dispatch real-time event for error status
            if ($previousStatus !== 'down') {
                UptimeStatusChanged::dispatch($uptimeCheck, $previousStatus);
                Log::info('Uptime error status broadcasted', [
                    'website' => $this->website->url,
                    'from' => $previousStatus,
                    'to' => 'down',
                ]);
            }
        }
    }
}
