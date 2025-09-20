<?php

namespace App\Console\Commands;

use App\Services\MonitorIntegrationService;
use Illuminate\Console\Command;

class SyncWebsitesWithMonitors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitors:sync-websites {--force : Force sync all websites regardless of current state}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Website models with Spatie Monitor models for uptime and SSL monitoring';

    /**
     * Execute the console command.
     */
    public function handle(MonitorIntegrationService $monitorService): int
    {
        $this->info('Starting website-monitor synchronization...');

        try {
            $result = $monitorService->syncAllWebsitesWithMonitors();

            $this->info("Synchronization completed!");
            $this->info("Total websites processed: {$result['total_websites']}");
            $this->info("Successfully synced: {$result['synced_count']}");

            if ($result['error_count'] > 0) {
                $this->warn("Errors encountered: {$result['error_count']}");

                if ($this->option('verbose')) {
                    $this->table(
                        ['Website ID', 'URL', 'Error'],
                        collect($result['errors'])->map(fn($error) => [
                            $error['website_id'],
                            $error['url'],
                            $error['error']
                        ])->toArray()
                    );
                }
            }

            if ($this->option('verbose')) {
                $this->table(
                    ['Website ID', 'Monitor ID', 'URL'],
                    collect($result['synced'])->map(fn($sync) => [
                        $sync['website_id'],
                        $sync['monitor_id'],
                        $sync['url']
                    ])->toArray()
                );
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Synchronization failed: {$e->getMessage()}");

            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }
}
