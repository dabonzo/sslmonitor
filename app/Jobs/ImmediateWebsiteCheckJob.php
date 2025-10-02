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
     * Check website uptime status using Spatie Monitor directly (optimized).
     */
    private function checkUptime(): array
    {
        try {
            $startTime = microtime(true);

            // Get monitoring config from website
            $config = $this->website->monitoring_config ?? [];

            // Get or create monitor (fast lookup) - Use our extended Monitor model
            $monitor = \App\Models\Monitor::firstOrCreate(
                ['url' => $this->website->url],
                [
                    'uptime_check_enabled' => $this->website->uptime_monitoring_enabled,
                    'certificate_check_enabled' => $this->website->ssl_monitoring_enabled,
                    'content_expected_strings' => $config['content_expected_strings'] ?? null,
                    'content_forbidden_strings' => $config['content_forbidden_strings'] ?? null,
                    'content_regex_patterns' => $config['content_regex_patterns'] ?? null,
                    'javascript_enabled' => $config['javascript_enabled'] ?? false,
                    'javascript_wait_seconds' => $config['javascript_wait_seconds'] ?? 5,
                ]
            );

            // Always sync content validation settings (in case they changed)
            // Convert empty arrays to null for proper database storage
            $expectedStrings = !empty($config['content_expected_strings']) ? $config['content_expected_strings'] : null;
            $forbiddenStrings = !empty($config['content_forbidden_strings']) ? $config['content_forbidden_strings'] : null;
            $regexPatterns = !empty($config['content_regex_patterns']) ? $config['content_regex_patterns'] : null;

            // Only use EnhancedContentChecker if content validation is configured
            $hasContentValidation = $expectedStrings || $forbiddenStrings || $regexPatterns;

            $monitor->update([
                'uptime_check_enabled' => $this->website->uptime_monitoring_enabled,
                'certificate_check_enabled' => $this->website->ssl_monitoring_enabled,
                'content_expected_strings' => $expectedStrings,
                'content_forbidden_strings' => $forbiddenStrings,
                'content_regex_patterns' => $regexPatterns,
                'javascript_enabled' => $config['javascript_enabled'] ?? false,
                'javascript_wait_seconds' => $config['javascript_wait_seconds'] ?? 5,
                'uptime_check_response_checker' => $hasContentValidation
                    ? \App\Services\UptimeMonitor\ResponseCheckers\EnhancedContentChecker::class
                    : null,
            ]);

            // Refresh to ensure the monitor instance has the latest values
            $monitor->refresh();

            // Initialize ConsoleOutput for queue context to prevent static property access errors
            // The ConsoleOutput helper expects a command context but we're in queue context
            $consoleOutput = app(\Spatie\UptimeMonitor\Helpers\ConsoleOutput::class);
            $consoleOutput->setOutput(new class extends \Illuminate\Console\Command {
                protected $signature = 'queue:dummy';
                public function info($string, $verbosity = null) { return null; }
                public function error($string, $verbosity = null) { return null; }
                public function warn($string, $verbosity = null) { return null; }
            });

            // Use Spatie's MonitorCollection for single monitor uptime check
            $collection = new \Spatie\UptimeMonitor\MonitorCollection([$monitor]);
            $collection->checkUptime();

            // Refresh to get latest data
            $monitor->refresh();

            $result = [
                'status' => $monitor->uptime_status,
                'response_time' => $monitor->uptime_check_response_time_in_ms,
                'status_code' => $monitor->uptime_check_response_status_code,
                'failure_reason' => $monitor->uptime_check_failure_reason,
                'checked_at' => $monitor->uptime_last_check_date?->toISOString(),
                'check_duration_ms' => round((microtime(true) - $startTime) * 1000),
            ];

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
                'check_duration_ms' => round((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    /**
     * Check website SSL certificate status using Spatie Monitor directly (optimized).
     */
    private function checkSsl(): array
    {
        try {
            $startTime = microtime(true);

            // Get the same monitor instance (already created in uptime check) - Use our extended Monitor model
            $monitor = \App\Models\Monitor::where('url', $this->website->url)->first();

            if (!$monitor) {
                // Fallback: create monitor if not exists - Use our extended Monitor model
                $monitor = \App\Models\Monitor::create([
                    'url' => $this->website->url,
                    'uptime_check_enabled' => $this->website->uptime_monitoring_enabled,
                    'certificate_check_enabled' => $this->website->ssl_monitoring_enabled,
                ]);
            }

            // Use Spatie's checkCertificate method directly
            $monitor->checkCertificate();

            // Refresh to get latest data
            $monitor->refresh();

            // Determine status based on Spatie's certificate data
            $status = 'valid';
            if ($monitor->certificate_status === 'invalid') {
                $status = 'invalid';
            } elseif ($monitor->certificate_expiration_date && $monitor->certificate_expiration_date->isPast()) {
                $status = 'expired';
            } elseif ($monitor->certificate_expiration_date && $monitor->certificate_expiration_date->diffInDays() <= 30) {
                $status = 'expires_soon';
            }

            $result = [
                'status' => $status,
                'expires_at' => $monitor->certificate_expiration_date?->toISOString(),
                'issuer' => $monitor->certificate_issuer ?? 'Unknown',
                'certificate_status' => $monitor->certificate_status,
                'failure_reason' => $monitor->certificate_check_failure_reason,
                'checked_at' => Carbon::now()->toISOString(),
                'check_duration_ms' => round((microtime(true) - $startTime) * 1000),
            ];

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
            'website_id' => $this->website->id,
            'website_url' => $this->website->url,
            'attempts' => $this->attempts(),
        ]);
    }
}