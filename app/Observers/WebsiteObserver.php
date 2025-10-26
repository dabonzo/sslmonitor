<?php

namespace App\Observers;

use App\Models\Website;
use App\Services\MonitorIntegrationService;
use Illuminate\Support\Facades\Log;

class WebsiteObserver
{
    public function __construct(
        private MonitorIntegrationService $monitorService
    ) {}

    /**
     * Handle the Website "created" event.
     */
    public function created(Website $website): void
    {
        // Only create monitor if monitoring is enabled
        if ($website->uptime_monitoring_enabled || $website->ssl_monitoring_enabled) {
            try {
                $this->monitorService->createOrUpdateMonitorForWebsite($website);
            } catch (\Exception $e) {
                Log::error('Failed to create monitor for new website', [
                    'website_id' => $website->id,
                    'url' => $website->url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Automatically analyze SSL certificate if SSL monitoring is enabled
        if ($website->ssl_monitoring_enabled) {
            dispatch(new \App\Jobs\AnalyzeSslCertificateJob($website))
                ->onQueue('monitoring-analysis')
                ->delay(now()->addSeconds(5)); // Small delay to ensure monitor is created
        }
    }

    /**
     * Handle the Website "updated" event.
     */
    public function updated(Website $website): void
    {
        // Check if monitoring settings or URL changed
        $monitoringChanged = $website->wasChanged([
            'uptime_monitoring_enabled',
            'ssl_monitoring_enabled',
            'monitoring_config',
            'url',
        ]);

        if ($monitoringChanged) {
            try {
                if ($website->uptime_monitoring_enabled || $website->ssl_monitoring_enabled) {
                    // Create or update monitor
                    $this->monitorService->createOrUpdateMonitorForWebsite($website);
                } else {
                    // Remove monitor if monitoring is disabled
                    $this->monitorService->removeMonitorForWebsite($website);
                }
            } catch (\Exception $e) {
                Log::error('Failed to sync monitor for updated website', [
                    'website_id' => $website->id,
                    'url' => $website->url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the Website "deleted" event.
     */
    public function deleted(Website $website): void
    {
        try {
            $this->monitorService->removeMonitorForWebsite($website);
        } catch (\Exception $e) {
            Log::error('Failed to remove monitor for deleted website', [
                'website_id' => $website->id,
                'url' => $website->url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Website "restored" event.
     */
    public function restored(Website $website): void
    {
        // Recreate monitor if monitoring is enabled
        if ($website->uptime_monitoring_enabled || $website->ssl_monitoring_enabled) {
            try {
                $this->monitorService->createOrUpdateMonitorForWebsite($website);
            } catch (\Exception $e) {
                Log::error('Failed to recreate monitor for restored website', [
                    'website_id' => $website->id,
                    'url' => $website->url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the Website "force deleted" event.
     */
    public function forceDeleted(Website $website): void
    {
        try {
            $this->monitorService->removeMonitorForWebsite($website);
        } catch (\Exception $e) {
            Log::error('Failed to remove monitor for force deleted website', [
                'website_id' => $website->id,
                'url' => $website->url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
