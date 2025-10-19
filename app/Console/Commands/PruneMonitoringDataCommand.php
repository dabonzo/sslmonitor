<?php

namespace App\Console\Commands;

use App\Models\MonitoringResult;
use Illuminate\Console\Command;

class PruneMonitoringDataCommand extends Command
{
    protected $signature = 'monitoring:prune-old-data
                            {--days=90 : Number of days to retain raw monitoring data}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Prune old monitoring result data (keeps summaries, removes raw results)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $cutoffDate = now()->subDays($days);

        $this->info("Pruning monitoring results older than {$days} days (before {$cutoffDate->toDateString()})");

        $query = MonitoringResult::where('started_at', '<', $cutoffDate);
        $count = $query->count();

        if ($count === 0) {
            $this->info('No records to prune.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("DRY RUN: Would delete {$count} monitoring result records");

            return self::SUCCESS;
        }

        if (! $this->confirm("Delete {$count} monitoring result records?")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $deleted = 0;
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $query->chunkById(1000, function ($results) use (&$deleted, $bar) {
            foreach ($results as $result) {
                $result->delete();
                $deleted++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info("Successfully deleted {$deleted} monitoring result records.");

        return self::SUCCESS;
    }
}
