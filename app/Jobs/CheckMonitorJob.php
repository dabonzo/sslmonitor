<?php

namespace App\Jobs;

use App\Events\MonitoringCheckCompleted;
use App\Events\MonitoringCheckFailed;
use App\Events\MonitoringCheckStarted;
use App\Models\Monitor;
use App\Support\AutomationLogger;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckMonitorJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

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
     * The type of check to perform (uptime, ssl, both).
     */
    public string $checkType = 'both';

    /**
     * The trigger type for this check.
     */
    public string $triggerType = 'scheduled';

    /**
     * The user ID who triggered this check (if manual).
     */
    public ?int $triggeredByUserId = null;

    /**
     * Create a new job instance.
     */
    public function __construct(Monitor $monitor, string $checkType = 'both')
    {
        $this->monitor = $monitor;
        $this->checkType = $checkType;
        $this->onQueue(env('QUEUE_DEFAULT', 'default'));
    }

    /**
     * Execute the job.
     */
    public function handle(): array
    {
        $startedAt = now();
        $startTime = microtime(true);

        // Fire started event
        event(new MonitoringCheckStarted(
            monitor: $this->monitor,
            triggerType: $this->triggerType,
            triggeredByUserId: $this->triggeredByUserId,
        ));

        try {
            AutomationLogger::jobStart(self::class, [
                'monitor_id' => $this->monitor->id,
                'monitor_url' => $this->monitor->url,
            ]);

            AutomationLogger::scheduler(
                "Starting scheduled check for monitor: {$this->monitor->url}",
                ['monitor_id' => $this->monitor->id]
            );

            // Perform checks based on the check type
            $uptimeResult = null;
            $sslResult = null;

            switch ($this->checkType) {
                case 'uptime':
                    $uptimeResult = $this->checkUptime();
                    break;
                case 'ssl':
                    $sslResult = $this->checkSsl();
                    break;
                case 'both':
                default:
                    $uptimeResult = $this->checkUptime();
                    $sslResult = $this->checkSsl();
                    break;
            }

            $results = [
                'monitor_id' => $this->monitor->id,
                'url' => $this->monitor->url,
                'checked_at' => Carbon::now()->toISOString(),
                'uptime' => $uptimeResult,
                'ssl' => $sslResult,
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

            // Gather check results for historical tracking
            $checkResults = [
                'check_type' => $this->checkType === 'ssl' ? 'ssl_certificate' : $this->checkType,
                'status' => $this->determineOverallStatus($results),
                'uptime_status' => $results['uptime']['status'] ?? null,
                'http_status_code' => $results['uptime']['status_code'] ?? null,
                'ssl_status' => $results['ssl']['status'] ?? null,
                'certificate_subject' => $results['ssl']['certificate_subject'] ?? null,
                'certificate_valid_from_date' => $results['ssl']['certificate_valid_from'] ?? null,
                'days_until_expiration' => $this->calculateDaysUntilExpiration(),
            ];

            // Fire completed event
            event(new MonitoringCheckCompleted(
                monitor: $this->monitor,
                triggerType: $this->triggerType,
                triggeredByUserId: $this->triggeredByUserId,
                startedAt: $startedAt,
                completedAt: now(),
                checkResults: $checkResults,
            ));

            return $results;

        } catch (\Throwable $exception) {
            AutomationLogger::jobFailed(self::class, $exception, [
                'monitor_id' => $this->monitor->id,
                'monitor_url' => $this->monitor->url,
            ]);

            // Fire failed event
            event(new MonitoringCheckFailed(
                monitor: $this->monitor,
                triggerType: $this->triggerType,
                triggeredByUserId: $this->triggeredByUserId,
                startedAt: $startedAt,
                exception: $exception,
            ));

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
            $consoleOutput->setOutput(new class extends \Illuminate\Console\Command
            {
                protected $signature = 'queue:dummy';

                public function info($string, $verbosity = null)
                {
                    return null;
                }

                public function error($string, $verbosity = null)
                {
                    return null;
                }

                public function warn($string, $verbosity = null)
                {
                    return null;
                }
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

            // Extract certificate data (subject + validity dates)
            $certificateData = $this->extractCertificateData();

            // Determine status using dynamic thresholds
            $status = $this->determineSslStatus(
                expiresAt: $this->monitor->certificate_expiration_date,
                validFrom: $certificateData['valid_from'],
                certificateStatus: $this->monitor->certificate_status
            );

            $result = [
                'status' => $status,
                'expires_at' => $this->monitor->certificate_expiration_date?->toISOString(),
                'issuer' => $this->monitor->certificate_issuer ?? 'Unknown',
                'certificate_status' => $this->monitor->certificate_status,
                'certificate_subject' => $certificateData['subject'],
                'certificate_valid_from' => $certificateData['valid_from']?->toISOString(),
                'failure_reason' => $this->monitor->certificate_check_failure_reason,
                'checked_at' => Carbon::now()->toISOString(),
                'check_duration_ms' => round((microtime(true) - $startTime) * 1000),
            ];

            AutomationLogger::websiteCheck(
                $this->monitor->url,
                'ssl',
                $result
            );

            // Trigger re-analysis if certificate has changed
            if ($this->hasCertificateChanged()) {
                // Certificate changed - trigger re-analysis to update saved data
                $website = \App\Models\Website::where('url', (string) $this->monitor->url)->first();

                if ($website) {
                    dispatch(new AnalyzeSslCertificateJob($website))
                        ->onQueue('monitoring-analysis');
                }
            }

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
     * Determine if SSL certificate should be checked based on configured interval.
     * Also forces SSL check if certificate has errors or is expiring soon.
     */
    private function shouldCheckSsl(): bool
    {
        // Always check SSL if certificate checking is disabled (to catch when it gets re-enabled)
        if (! $this->monitor->certificate_check_enabled) {
            return false;
        }

        // Get SSL check interval from config (default 12 hours)
        $sslCheckInterval = config('uptime-monitor.certificate_check.run_interval_in_minutes', 720);

        // Check if certificate has issues that need more frequent monitoring
        if ($this->monitor->certificate_status === 'invalid') {
            return true; // Always check invalid certificates
        }

        if ($this->monitor->certificate_expiration_date) {
            // Check more frequently if expiring soon
            $daysUntilExpiry = $this->monitor->certificate_expiration_date->diffInDays();
            if ($daysUntilExpiry <= 7) {
                return true; // Check daily for certificates expiring in 7 days
            }
            if ($daysUntilExpiry <= 30) {
                $sslCheckInterval = min($sslCheckInterval, 240); // Check every 4 hours for 30-day expiry
            }
        }

        // Check if enough time has passed since last SSL check
        if ($this->monitor->updated_at) {
            $minutesSinceLastCheck = $this->monitor->updated_at->diffInMinutes();

            return $minutesSinceLastCheck >= $sslCheckInterval;
        }

        // If no record of last check, check now
        return true;
    }

    /**
     * Get the last SSL check result when we're not performing a new check.
     */
    private function getLastSslResult(): array
    {
        // Determine status using dynamic thresholds
        // Note: We don't have valid_from from cache, so will use fallback logic
        $status = $this->determineSslStatus(
            expiresAt: $this->monitor->certificate_expiration_date,
            validFrom: null,  // Not available from cached data
            certificateStatus: $this->monitor->certificate_status
        );

        return [
            'status' => $status,
            'expires_at' => $this->monitor->certificate_expiration_date?->toISOString(),
            'issuer' => $this->monitor->certificate_issuer ?? 'Unknown',
            'certificate_status' => $this->monitor->certificate_status,
            'failure_reason' => $this->monitor->certificate_check_failure_reason,
            'checked_at' => $this->monitor->updated_at?->toISOString(),
            'check_duration_ms' => null, // Not a fresh check
            'from_cache' => true, // Indicate this is from cache
        ];
    }

    /**
     * Determine the check type based on monitor configuration.
     */
    private function determineCheckType(): string
    {
        if ($this->monitor->uptime_check_enabled && $this->monitor->certificate_check_enabled) {
            return 'both';
        }

        if ($this->monitor->uptime_check_enabled) {
            return 'uptime';
        }

        if ($this->monitor->certificate_check_enabled) {
            return 'ssl_certificate';
        }

        return 'both';
    }

    /**
     * Determine the overall status of the check.
     */
    private function determineOverallStatus(array $results): string
    {
        $uptimeOk = ! isset($results['uptime']['status']) || $results['uptime']['status'] === 'up';
        $sslOk = ! isset($results['ssl']['status']) || in_array($results['ssl']['status'], ['valid', 'expires_soon']);

        return ($uptimeOk && $sslOk) ? 'success' : 'failed';
    }

    /**
     * Calculate days until certificate expiration.
     */
    private function calculateDaysUntilExpiration(): ?int
    {
        if (! $this->monitor->certificate_expiration_date) {
            return null;
        }

        return (int) now()->diffInDays($this->monitor->certificate_expiration_date, false);
    }

    /**
     * Determine SSL certificate status based on dynamic, percentage-based thresholds.
     *
     * This method implements intelligent, adaptive SSL expiration detection that accounts for different
     * certificate validity periods. Unlike fixed day-based thresholds (e.g., "warn at 30 days"), this
     * approach uses the certificate's total validity period to determine when alerts should trigger.
     *
     * ## Dynamic Threshold Algorithm
     *
     * The system uses **two criteria** and triggers "expires_soon" when EITHER is met:
     * 1. **Percentage-based threshold**: < 33% of total validity period remaining
     * 2. **Minimum day threshold**: < 30 days remaining (safety net for any certificate)
     *
     * This dual approach ensures:
     * - Short-lived certificates (Let's Encrypt 90-day): Alert when ~30 days left (33% of 90)
     * - Medium-lived certificates (1-year commercial): Alert when ~120 days left (33% of 365)
     * - Long-lived certificates (2-year commercial): Alert when ~241 days left (33% of 730)
     *
     * ## Percentage Calculation Details
     *
     * When valid_from date is available:
     * ```
     * total_validity_days = validFrom.diffInDays(expiresAt)
     * days_remaining = now().diffInDays(expiresAt)
     * percent_remaining = (days_remaining / total_validity_days) * 100
     * ```
     *
     * If percentage < 33% OR days_remaining < 30, status becomes 'expires_soon'.
     *
     * ## Processing Flow
     *
     * 1. **Invalid certificates** (certificate_status === 'invalid'):
     *    - Always return 'invalid', regardless of expiration date
     *    - Indicates certificate validation error (e.g., hostname mismatch, untrusted CA)
     *
     * 2. **Missing expiration date**:
     *    - Return 'valid' (cannot determine status without expiration data)
     *    - Should not occur with standard SSL certificates
     *
     * 3. **Expired certificates** (expiresAt in past):
     *    - Return 'expired' immediately
     *    - Checked before percentage calculation for early exit
     *
     * 4. **With valid_from date** (percentage-based):
     *    - Calculate total validity period and remaining percentage
     *    - Apply dual thresholds (33% OR 30 days)
     *    - Return 'valid' or 'expires_soon'
     *
     * 5. **Fallback** (valid_from unavailable):
     *    - Use legacy 30-day threshold only
     *    - Ensures backward compatibility with older monitoring results
     *    - valid_from may be unavailable due to: cached results, old monitoring data, extraction failures
     *
     * ## Practical Examples
     *
     * ### Let's Encrypt 90-day Certificate
     * - Issued: 2025-10-27, Expires: 2025-12-26 (90 days)
     * - Today with 73 days remaining:
     *   - Percentage: (73 / 90) × 100 = 81.1% remaining
     *   - Status: VALID (81% > 33%, 73 days > 30)
     * - Why: Premature alerts would trigger multiple times during certificate's lifetime
     *
     * ### 1-Year Commercial Certificate
     * - Issued: 2024-10-27, Expires: 2025-10-27 (365 days)
     * - Today with 73 days remaining:
     *   - Percentage: (73 / 365) × 100 = 20% remaining
     *   - Status: EXPIRES_SOON (20% < 33%, 73 days > 30)
     * - Why: Only 20% of lifetime remaining warrants renewal alert
     *
     * ### 2-Year Commercial Certificate
     * - Issued: 2023-10-27, Expires: 2025-10-27 (730 days)
     * - Today with 73 days remaining:
     *   - Percentage: (73 / 730) × 100 = 10% remaining
     *   - Status: EXPIRES_SOON (10% < 33%, 73 days > 30)
     * - Why: With 10% left, renewal should already be in progress
     *
     * ### Edge Case: 10-Year Certificate with 25 Days Left
     * - Total validity: 3650 days
     * - Days remaining: 25
     * - Percentage: (25 / 3650) × 100 = 0.68% remaining
     * - Status: EXPIRES_SOON (0.68% < 33%, AND 25 days < 30 minimum)
     * - Why: 30-day minimum prevents missing imminent expiration of ultra-long certificates
     *
     * ## Backward Compatibility
     *
     * For older monitoring results where valid_from date was not extracted:
     * - Falls back to traditional 30-day threshold
     * - Allows gradual migration as new checks populate valid_from data
     * - Eventually all results will have valid_from, enabling full percentage-based logic
     *
     * @param  ?Carbon  $expiresAt  Certificate expiration date (Not Valid After)
     * @param  ?Carbon  $validFrom  Certificate issue date (Not Valid Before) - null for backward compatibility
     * @param  string  $certificateStatus  Spatie certificate validation status: 'valid' or 'invalid'
     * @return string Certificate status string:
     *                 - 'invalid': Certificate has validation errors
     *                 - 'expired': Certificate has passed expiration date
     *                 - 'expires_soon': Certificate < 33% validity remaining OR < 30 days
     *                 - 'valid': Certificate is healthy and not expiring soon
     *
     * @see https://carbon.nesbot.com/docs/ Carbon date manipulation documentation
     * @see https://www.php.net/manual/en/function.openssl-x509-parse.php OpenSSL certificate parsing
     */
    private function determineSslStatus(
        ?Carbon $expiresAt,
        ?Carbon $validFrom,
        string $certificateStatus
    ): string {
        // Handle invalid certificate
        if ($certificateStatus === 'invalid') {
            return 'invalid';
        }

        // Handle missing expiration date
        if (! $expiresAt) {
            return 'valid';
        }

        // Handle expired certificate
        if ($expiresAt->isPast()) {
            return 'expired';
        }

        $daysRemaining = now()->diffInDays($expiresAt, false);

        // If we have valid_from date, use percentage-based thresholds
        if ($validFrom && $validFrom->isBefore($expiresAt)) {
            $totalValidityDays = $validFrom->diffInDays($expiresAt);
            $percentRemaining = ($daysRemaining / $totalValidityDays) * 100;

            // Expires soon if:
            // - Less than 33% of validity period remaining
            // - OR less than 30 days (minimum threshold for any certificate)
            if ($percentRemaining < 33 || $daysRemaining < 30) {
                return 'expires_soon';
            }

            return 'valid';
        }

        // Fallback to legacy 30-day threshold if valid_from unavailable
        if ($daysRemaining <= 30) {
            return 'expires_soon';
        }

        return 'valid';
    }

    /**
     * Check if SSL certificate has changed (new certificate issued).
     *
     * @return bool True if certificate changed
     */
    private function hasCertificateChanged(): bool
    {
        $website = \App\Models\Website::where('url', (string) $this->monitor->url)->first();

        if (! $website || ! $website->latest_ssl_certificate) {
            return true; // No saved data, consider changed
        }

        $savedCertificate = $website->latest_ssl_certificate;
        $currentSerialNumber = $this->monitor->certificate_serial_number ?? null;
        $savedSerialNumber = $savedCertificate['serial_number'] ?? null;

        // If serial numbers differ, certificate was renewed
        if ($currentSerialNumber && $savedSerialNumber && $currentSerialNumber !== $savedSerialNumber) {
            AutomationLogger::info("Certificate renewal detected for: {$this->monitor->url}", [
                'old_serial' => $savedSerialNumber,
                'new_serial' => $currentSerialNumber,
            ]);

            return true;
        }

        // If expiration date changed significantly (more than 1 day difference)
        $savedExpiration = isset($savedCertificate['valid_until'])
            ? \Carbon\Carbon::parse($savedCertificate['valid_until'])
            : null;

        if ($this->monitor->certificate_expiration_date && $savedExpiration) {
            $daysDiff = abs($this->monitor->certificate_expiration_date->diffInDays($savedExpiration));

            if ($daysDiff > 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract certificate subject (CN + SANs) and validity dates.
     *
     * @return array{subject: ?string, valid_from: ?Carbon, expires_at: ?Carbon}
     */
    private function extractCertificateData(): array
    {
        try {
            $url = (string) $this->monitor->url;
            $parsedUrl = parse_url($url);

            if (! isset($parsedUrl['host'])) {
                return [
                    'subject' => null,
                    'valid_from' => null,
                    'expires_at' => null,
                ];
            }

            $host = $parsedUrl['host'];
            $port = $parsedUrl['scheme'] === 'https' ? 443 : 80;

            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $client = @stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (! $client) {
                return [
                    'subject' => null,
                    'valid_from' => null,
                    'expires_at' => null,
                ];
            }

            $params = stream_context_get_params($client);
            fclose($client);

            if (! isset($params['options']['ssl']['peer_certificate'])) {
                return [
                    'subject' => null,
                    'valid_from' => null,
                    'expires_at' => null,
                ];
            }

            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);

            if (! $cert) {
                return [
                    'subject' => null,
                    'valid_from' => null,
                    'expires_at' => null,
                ];
            }

            $domains = [];

            // Extract Common Name (CN)
            if (isset($cert['subject']['CN'])) {
                $domains[] = $cert['subject']['CN'];
            }

            // Extract Subject Alternative Names (SANs)
            if (isset($cert['extensions']['subjectAltName'])) {
                $sans = explode(', ', $cert['extensions']['subjectAltName']);
                foreach ($sans as $san) {
                    if (str_starts_with($san, 'DNS:')) {
                        $domain = substr($san, 4);
                        if (! in_array($domain, $domains)) {
                            $domains[] = $domain;
                        }
                    }
                }
            }

            // Extract validity dates
            $validFrom = isset($cert['validFrom_time_t'])
                ? Carbon::createFromTimestamp($cert['validFrom_time_t'])
                : null;

            $expiresAt = isset($cert['validTo_time_t'])
                ? Carbon::createFromTimestamp($cert['validTo_time_t'])
                : null;

            return [
                'subject' => ! empty($domains) ? implode(', ', $domains) : null,
                'valid_from' => $validFrom,
                'expires_at' => $expiresAt,
            ];

        } catch (\Throwable $exception) {
            AutomationLogger::error(
                "Failed to extract certificate data for monitor: {$this->monitor->url}",
                ['monitor_id' => $this->monitor->id],
                $exception
            );

            return [
                'subject' => null,
                'valid_from' => null,
                'expires_at' => null,
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
