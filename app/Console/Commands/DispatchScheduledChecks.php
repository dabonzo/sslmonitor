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

        // Get monitors where uptime checking is enabled
        $monitors = Monitor::where('uptime_check_enabled', true)
            ->get()
            ->filter(fn ($monitor) => $monitor->shouldCheckUptime());

        $this->info("Found {$monitors->count()} monitors due for checking");

        // Dispatch CheckMonitorJob for each monitor
        $dispatched = 0;
        foreach ($monitors as $monitor) {
            CheckMonitorJob::dispatch($monitor);
            $dispatched++;

            $this->comment("Dispatched check for: {$monitor->url}");
        }

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        AutomationLogger::scheduler(
            'Completed scheduled checks dispatch',
            [
                'monitors_found' => $monitors->count(),
                'jobs_dispatched' => $dispatched,
                'execution_time_ms' => $executionTime,
            ]
        );

        $this->info("✓ Dispatched {$dispatched} check jobs in {$executionTime}ms");
        $this->info('Jobs will be processed by Horizon workers');

        return Command::SUCCESS;
    }
}
