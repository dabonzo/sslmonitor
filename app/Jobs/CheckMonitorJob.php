<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Support\AutomationLogger;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckMonitorJob implements ShouldQueue
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
     * The monitor to check.
     */
    public Monitor $monitor;

    /**
     * Create a new job instance.
     */
    public function __construct(Monitor $monitor)
    {
        $this->monitor = $monitor;
        $this->onQueue(env('QUEUE_DEFAULT', 'default'));
    }

    /**
     * Execute the job.
     */
    public function handle(): array
    {
        $startTime = microtime(true);

        try {
            AutomationLogger::jobStart(self::class, [
                'monitor_id' => $this->monitor->id,
                'monitor_url' => $this->monitor->url,
            ]);

            AutomationLogger::scheduler(
                "Starting scheduled check for monitor: {$this->monitor->url}",
                ['monitor_id' => $this->monitor->id]
            );

            $results = [
                'monitor_id' => $this->monitor->id,
                'url' => $this->monitor->url,
                'checked_at' => Carbon::now()->toISOString(),
                'uptime' => $this->checkUptime(),
                'ssl' => $this->checkSsl(),
            ];

            AutomationLogger::jobComplete(self::class, $startTime, [
                'monitor_id' => $this->monitor->id,
                'results' => $results,
            ]);

            AutomationLogger::scheduler(
                "Completed scheduled check for monitor: {$this->monitor->url}",
                [
                    'monitor_id' => $this->monitor->id,
                    'uptime_status' => $results['uptime']['status'] ?? 'unknown',
                    'ssl_status' => $results['ssl']['status'] ?? 'unknown',
                ]
            );

            return $results;

        } catch (\Throwable $exception) {
            AutomationLogger::jobFailed(self::class, $exception, [
                'monitor_id' => $this->monitor->id,
                'monitor_url' => $this->monitor->url,
            ]);

            // Return error results instead of failing completely
            return [
                'monitor_id' => $this->monitor->id,
                'url' => $this->monitor->url,
                'checked_at' => Carbon::now()->toISOString(),
                'uptime' => ['status' => 'error', 'error' => $exception->getMessage()],
                'ssl' => ['status' => 'error', 'error' => $exception->getMessage()],
                'error' => true,
            ];
        }
    }

    /**
     * Check monitor uptime status using Spatie Monitor directly.
     */
    private function checkUptime(): array
    {
        try {
            $startTime = microtime(true);

            // Refresh monitor to ensure we have latest data
            $this->monitor->refresh();

            // Initialize ConsoleOutput for queue context to prevent static property access errors
            $consoleOutput = app(\Spatie\UptimeMonitor\Helpers\ConsoleOutput::class);
            $consoleOutput->setOutput(new class extends \Illuminate\Console\Command {
                protected $signature = 'queue:dummy';
                public function info($string, $verbosity = null) { return null; }
                public function error($string, $verbosity = null) { return null; }
                public function warn($string, $verbosity = null) { return null; }
            });

            // Use Spatie's MonitorCollection for uptime check
            $collection = new \Spatie\UptimeMonitor\MonitorCollection([$this->monitor]);
            $collection->checkUptime();

            // Refresh to get latest data
            $this->monitor->refresh();

            $result = [
                'status' => $this->monitor->uptime_status,
                'response_time' => $this->monitor->uptime_check_response_time_in_ms,
                'status_code' => $this->monitor->uptime_check_response_status_code,
                'failure_reason' => $this->monitor->uptime_check_failure_reason,
                'checked_at' => $this->monitor->uptime_last_check_date?->toISOString(),
                'check_duration_ms' => round((microtime(true) - $startTime) * 1000),
            ];

            AutomationLogger::websiteCheck(
                $this->monitor->url,
                'uptime',
                $result
            );

            return $result;

        } catch (\Throwable $exception) {
            AutomationLogger::error(
                "Uptime check failed for monitor: {$this->monitor->url}",
                ['monitor_id' => $this->monitor->id],
                $exception
            );

            return [
                'status' => 'error',
                'error' => $exception->getMessage(),
                'checked_at' => Carbon::now()->toISOString(),
                'check_duration_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    /**
     * Check monitor SSL certificate status using Spatie Monitor directly.
     */
    private function checkSsl(): array
    {
        try {
            $startTime = microtime(true);

            // Use Spatie's checkCertificate method directly
            $this->monitor->checkCertificate();

            // Refresh to get latest data
            $this->monitor->refresh();

            // Determine status based on Spatie's certificate data
            $status = 'valid';
            if ($this->monitor->certificate_status === 'invalid') {
                $status = 'invalid';
            } elseif ($this->monitor->certificate_expiration_date && $this->monitor->certificate_expiration_date->isPast()) {
                $status = 'expired';
            } elseif ($this->monitor->certificate_expiration_date && $this->monitor->certificate_expiration_date->diffInDays() <= 30) {
                $status = 'expires_soon';
            }

            $result = [
                'status' => $status,
                'expires_at' => $this->monitor->certificate_expiration_date?->toISOString(),
                'issuer' => $this->monitor->certificate_issuer ?? 'Unknown',
                'certificate_status' => $this->monitor->certificate_status,
                'failure_reason' => $this->monitor->certificate_check_failure_reason,
                'checked_at' => Carbon::now()->toISOString(),
                'check_duration_ms' => round((microtime(true) - $startTime) * 1000),
            ];

            AutomationLogger::websiteCheck(
                $this->monitor->url,
                'ssl',
                $result
            );

            return $result;

        } catch (\Throwable $exception) {
            AutomationLogger::error(
                "SSL check failed for monitor: {$this->monitor->url}",
                ['monitor_id' => $this->monitor->id],
                $exception
            );

            return [
                'status' => 'error',
                'error' => $exception->getMessage(),
                'checked_at' => Carbon::now()->toISOString(),
                'check_duration_ms' => round((microtime(true) - $startTime) * 1000),
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
            'monitor_id' => $this->monitor->id,
            'monitor_url' => $this->monitor->url,
            'attempts' => $this->attempts(),
        ]);
    }
}
