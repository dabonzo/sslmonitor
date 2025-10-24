<?php

namespace App\Console\Commands;

use App\Jobs\CheckMonitorJob;
use App\Models\Monitor;
use App\Support\AutomationLogger;
use Illuminate\Console\Command;

class DispatchScheduledChecks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'monitors:dispatch-scheduled-checks';

    /**
     * The console command description.
     */
    protected $description = 'Dispatch queue jobs for monitors that are due for checking';

    /**
     * Execute the console command.
     *
     * Lightweight command that dispatches jobs and returns immediately.
     * Jobs are processed asynchronously by Horizon workers.
     */
    public function handle(): int
    {
        $startTime = microtime(true);

        AutomationLogger::scheduler(
            'Starting scheduled checks dispatch',
            ['command' => $this->signature]
        );

        $this->info('Finding monitors due for checking...');

        // Get all monitors with smart scheduling logic
        $allMonitors = Monitor::where('uptime_check_enabled', true)
            ->orWhere('certificate_check_enabled', true)
            ->get();

        $dispatched = 0;
        $uptimeChecks = 0;
        $sslChecks = 0;
        $bothChecks = 0;

        foreach ($allMonitors as $monitor) {
            $checkType = $monitor->getCheckType();

            if ($checkType === 'none') {
                continue; // Skip if nothing to check
            }

            // Dispatch job with the correct check type
            CheckMonitorJob::dispatch($monitor, $checkType);
            $dispatched++;

            switch ($checkType) {
                case 'uptime':
                    $uptimeChecks++;
                    $this->comment("Dispatched uptime check for: {$monitor->url}");
                    break;
                case 'ssl':
                    $sslChecks++;
                    $this->comment("Dispatched SSL check for: {$monitor->url}");
                    break;
                case 'both':
                    $bothChecks++;
                    $this->comment("Dispatched combined check for: {$monitor->url}");
                    break;
            }
        }

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        AutomationLogger::scheduler(
            'Completed scheduled checks dispatch',
            [
                'total_monitors' => $allMonitors->count(),
                'jobs_dispatched' => $dispatched,
                'uptime_checks' => $uptimeChecks,
                'ssl_checks' => $sslChecks,
                'both_checks' => $bothChecks,
                'execution_time_ms' => $executionTime,
            ]
        );

        $this->info("✓ Dispatched {$dispatched} check jobs in {$executionTime}ms");
        $this->info("  Uptime checks: {$uptimeChecks}");
        $this->info("  SSL checks: {$sslChecks}");
        $this->info("  Combined checks: {$bothChecks}");
        $this->info('Jobs will be processed by Horizon workers');

        return Command::SUCCESS;
    }
}
