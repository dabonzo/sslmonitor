<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\Website;
use App\Services\MonitorIntegrationService;
use App\Support\AutomationLogger;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImmediateWebsiteCheckJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job should run.
     * Immediate checks get more time since they're user-initiated.
     */
    public int $timeout = 120;

    /**
     * The website to check.
     */
    public Website $website;

    /**
     * Create a new job instance.
     */
    public function __construct(Website $website)
    {
        $this->website = $website;
        $this->onQueue(env('QUEUE_DEFAULT', 'default'));
    }

    /**
     * Execute the job.
     *
     * Thin wrapper: finds Monitor for Website and delegates to CheckMonitorJob.
     */
    public function handle(MonitorIntegrationService $monitorService): array
    {
        $startTime = microtime(true);

        try {
            AutomationLogger::jobStart(self::class, [
                'website_id' => $this->website->id,
                'website_url' => $this->website->url,
            ]);

            AutomationLogger::immediateCheck(
                "Starting immediate check for website: {$this->website->url}",
                ['website_id' => $this->website->id]
            );

            // Get or create Monitor (synced automatically by Observer)
            $monitor = $monitorService->getMonitorForWebsite($this->website);

            if (!$monitor) {
                // If no monitor exists, create one (Observer should have done this, but safety fallback)
                $monitor = $monitorService->createOrUpdateMonitorForWebsite($this->website);
            }

            // Delegate to CheckMonitorJob - call handle() directly for synchronous execution with return value
            $checkJob = new CheckMonitorJob($monitor);
            $results = $checkJob->handle();

            // Add website context to results
            $results['website_id'] = $this->website->id;

            // Update website last checked timestamp with microsecond precision
            $this->website->updated_at = Carbon::now()->format('Y-m-d H:i:s.u');
            $this->website->save();

            AutomationLogger::jobComplete(self::class, $startTime, [
                'website_id' => $this->website->id,
                'monitor_id' => $monitor->id,
                'results' => $results,
            ]);

            AutomationLogger::immediateCheck(
                "Completed immediate check for website: {$this->website->url}",
                [
                    'website_id' => $this->website->id,
                    'uptime_status' => $results['uptime']['status'] ?? 'unknown',
                    'ssl_status' => $results['ssl']['status'] ?? 'unknown',
                ]
            );

            return $results;

        } catch (\Throwable $exception) {
            AutomationLogger::jobFailed(self::class, $exception, [
                'website_id' => $this->website->id,
                'website_url' => $this->website->url,
            ]);

            // Return error results instead of failing completely
            return [
                'website_id' => $this->website->id,
                'url' => $this->website->url,
                'checked_at' => Carbon::now()->toISOString(),
                'uptime' => ['status' => 'error', 'error' => $exception->getMessage()],
                'ssl' => ['status' => 'error', 'error' => $exception->getMessage()],
                'error' => true,
            ];
        }
    }

    /**
     * Calculate the time at which the job should timeout.
     */
    public function retryUntil(): Carbon
    {
        return Carbon::now()->addMinutes(5);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        AutomationLogger::jobFailed(self::class, $exception, [
            'website_id' => $this->website->id,
            'website_url' => $this->website->url,
            'attempts' => $this->attempts(),
        ]);
    }
}
