<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\MonitorIntegrationService;
use App\Services\SslCertificateAnalysisService;
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
     */
    public int $timeout = 60;

    /**
     * The queue this job should be sent to.
     */
    public string $onQueue;

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
        $this->onQueue(env('QUEUE_IMMEDIATE', 'immediate'));
    }

    /**
     * Execute the job.
     */
    public function handle(): array
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

            $results = [
                'website_id' => $this->website->id,
                'url' => $this->website->url,
                'checked_at' => Carbon::now()->toISOString(),
                'uptime' => $this->checkUptime(),
                'ssl' => $this->checkSsl(),
            ];

            // Update website last checked timestamp with microsecond precision
            $this->website->updated_at = Carbon::now()->format('Y-m-d H:i:s.u');
            $this->website->save();

            AutomationLogger::jobComplete(self::class, $startTime, [
                'website_id' => $this->website->id,
                'results' => $results,
            ]);

            AutomationLogger::immediateCheck(
                "Completed immediate check for website: {$this->website->url}",
                [
                    'website_id' => $this->website->id,
                    'uptime_status' => $results['uptime']['status'],
                    'ssl_status' => $results['ssl']['status'],
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
     * Check website uptime status.
     */
    private function checkUptime(): array
    {
        try {
            $monitorService = app(MonitorIntegrationService::class);
            $result = $monitorService->checkWebsiteUptime($this->website);

            AutomationLogger::websiteCheck(
                $this->website->url,
                'uptime',
                $result
            );

            return $result;

        } catch (\Throwable $exception) {
            AutomationLogger::error(
                "Uptime check failed for website: {$this->website->url}",
                ['website_id' => $this->website->id],
                $exception
            );

            return [
                'status' => 'error',
                'error' => $exception->getMessage(),
                'checked_at' => Carbon::now()->toISOString(),
            ];
        }
    }

    /**
     * Check website SSL certificate status.
     */
    private function checkSsl(): array
    {
        try {
            $sslService = app(SslCertificateAnalysisService::class);
            $result = $sslService->analyzeWebsite($this->website->url);

            AutomationLogger::websiteCheck(
                $this->website->url,
                'ssl',
                $result
            );

            return $result;

        } catch (\Throwable $exception) {
            AutomationLogger::error(
                "SSL check failed for website: {$this->website->url}",
                ['website_id' => $this->website->id],
                $exception
            );

            return [
                'status' => 'error',
                'error' => $exception->getMessage(),
                'checked_at' => Carbon::now()->toISOString(),
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