<?php

namespace App\Observers;

use App\Models\Monitor;
use App\Models\Website;
use Illuminate\Support\Facades\Log;

class MonitorObserver
{
    /**
     * Handle the Monitor "creating" event (before save).
     */
    public function creating(Monitor $monitor): void
    {
        // Check if a Website exists with this URL
        $website = Website::where('url', (string) $monitor->url)->first();

        if (! $website) {
            // Log warning about orphaned monitor creation
            Log::warning('Monitor being created without matching Website', [
                'monitor_url' => $monitor->url,
                'certificate_check_enabled' => $monitor->certificate_check_enabled,
                'uptime_check_enabled' => $monitor->uptime_check_enabled,
                'created_via' => $this->detectCreationSource(),
            ]);

            // In production, this could indicate:
            // 1. Manual creation via tinker (should use Website model instead)
            // 2. Test factory creating Monitor directly (use Website factory)
            // 3. Delayed observer execution (race condition)
        }
    }

    /**
     * Handle the Monitor "created" event.
     */
    public function created(Monitor $monitor): void
    {
        // Verify relationship after creation
        $website = Website::where('url', (string) $monitor->url)->first();

        if (! $website) {
            Log::error('Orphaned Monitor created - no matching Website found', [
                'monitor_id' => $monitor->id,
                'monitor_url' => $monitor->url,
                'created_at' => $monitor->created_at,
                'action_required' => 'Create Website model or delete orphaned Monitor',
            ]);
        }
    }

    /**
     * Handle the Monitor "deleting" event.
     */
    public function deleting(Monitor $monitor): void
    {
        // Check if Website still exists
        $website = Website::where('url', (string) $monitor->url)->first();

        if ($website) {
            Log::info('Monitor being deleted while Website still exists', [
                'monitor_id' => $monitor->id,
                'monitor_url' => $monitor->url,
                'website_id' => $website->id,
                'website_name' => $website->name,
                'note' => 'This is expected when Website.deleted observer handles cleanup',
            ]);
        }
    }

    /**
     * Detect where the Monitor is being created from
     */
    private function detectCreationSource(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);

        foreach ($trace as $frame) {
            // Check for common creation sources
            if (isset($frame['class'])) {
                // WebsiteObserver creating monitor (expected)
                if (str_contains($frame['class'], 'WebsiteObserver')) {
                    return 'WebsiteObserver (expected)';
                }

                // Factory creation (test)
                if (str_contains($frame['class'], 'Factory')) {
                    return 'Factory (test - should use Website factory)';
                }

                // Seeder creation
                if (str_contains($frame['class'], 'Seeder')) {
                    return 'Seeder (should create via Website model)';
                }

                // Tinker/Artisan command
                if (str_contains($frame['class'], 'Tinker') || str_contains($frame['class'], 'Command')) {
                    return 'Tinker/Command (should create via Website model)';
                }
            }

            // Check for test execution
            if (isset($frame['file']) && str_contains($frame['file'], '/tests/')) {
                return 'Test execution (should use Website factory)';
            }
        }

        return 'Unknown source';
    }
}
