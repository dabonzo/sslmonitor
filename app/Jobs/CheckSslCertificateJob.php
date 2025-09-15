<?php

namespace App\Jobs;

use App\Events\SslStatusChanged;
use App\Models\Website;
use App\Services\SslCertificateChecker;
use App\Services\SslNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckSslCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public Website $website,
        public bool $forceCheck = false
    ) {
        // Use default queue for simplicity and reliability
    }

    public function handle(SslCertificateChecker $checker): void
    {
        // Skip if website no longer exists
        if (! $this->website->exists) {
            Log::info("Skipping SSL check for deleted website ID: {$this->website->id}");

            return;
        }

        // Skip if we've checked this website recently (within last hour), unless forced
        $forceCheck = isset($this->forceCheck) ? $this->forceCheck : false;
        if (! $forceCheck) {
            $recentCheck = $this->website->sslChecks()
                ->where('checked_at', '>', now()->subHour())
                ->latest('checked_at')
                ->first();

            if ($recentCheck) {
                Log::info("Skipping SSL check for {$this->website->url} - checked recently at {$recentCheck->checked_at}");

                return;
            }
        }

        // Get previous status for real-time event comparison
        $previousCheck = $this->website->sslChecks()
            ->latest('checked_at')
            ->first();
        $previousStatus = $previousCheck?->status ?? 'unknown';

        try {
            Log::info("Starting SSL certificate check for: {$this->website->url}", [
                'force_check' => $forceCheck,
                'website_id' => $this->website->id,
            ]);

            $sslCheck = $checker->checkAndStoreCertificate($this->website);

            Log::info("SSL check completed for {$this->website->url}", [
                'status' => $sslCheck->status,
                'previous_status' => $previousStatus,
                'days_until_expiry' => $sslCheck->days_until_expiry ?? 'N/A',
                'website_id' => $this->website->id,
            ]);

            // Dispatch real-time event if status changed
            if ($sslCheck->status !== $previousStatus) {
                SslStatusChanged::dispatch($sslCheck, $previousStatus);
                Log::info('SSL status change broadcasted', [
                    'website' => $this->website->url,
                    'from' => $previousStatus,
                    'to' => $sslCheck->status,
                ]);
            }

            // Dispatch notification events for critical statuses
            $this->dispatchNotificationIfNeeded($sslCheck);

        } catch (\Exception $e) {
            Log::error("SSL check failed for {$this->website->url}", [
                'error' => $e->getMessage(),
                'website_id' => $this->website->id,
            ]);

            // Create error record
            $errorCheck = $this->website->sslChecks()->create([
                'status' => 'error',
                'error_message' => 'Check failed: '.$e->getMessage(),
                'checked_at' => now(),
                'is_valid' => false,
            ]);

            // Dispatch real-time event for error status
            if ($previousStatus !== 'error') {
                SslStatusChanged::dispatch($errorCheck, $previousStatus);
                Log::info('SSL error status broadcasted', [
                    'website' => $this->website->url,
                    'from' => $previousStatus,
                    'to' => 'error',
                ]);
            }

            // Queue error notification
            $this->dispatchNotificationIfNeeded($errorCheck);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    public function backoff(): array
    {
        return [30, 60, 120]; // Retry after 30s, 1min, 2min
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SSL check job failed permanently for {$this->website->url}", [
            'error' => $exception->getMessage(),
            'website_id' => $this->website->id,
            'attempts' => $this->attempts(),
        ]);
    }

    private function dispatchNotificationIfNeeded($sslCheck): void
    {
        $notificationService = app(SslNotificationService::class);

        // Send appropriate notification based on status
        match ($sslCheck->status) {
            'expired' => $notificationService->queueExpiredNotification($sslCheck),
            'expiring_soon' => $notificationService->queueExpiryNotification($sslCheck),
            'error' => $notificationService->queueErrorNotification($sslCheck),
            default => null, // No notification needed for valid status
        };

        // Log the action taken
        if (in_array($sslCheck->status, ['expired', 'expiring_soon', 'error'])) {
            Log::info('SSL notification queued', [
                'website' => $this->website->url,
                'status' => $sslCheck->status,
                'days_until_expiry' => $sslCheck->days_until_expiry ?? 'N/A',
                'user_id' => $this->website->user_id,
            ]);
        }
    }
}
