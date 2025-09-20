<?php

namespace App\Console\Commands;

use App\Jobs\CheckSslCertificateJob;
use App\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckAllSslCertificates extends Command
{
    protected $signature = 'ssl:check-all {--force : Force check even if recently checked}';

    protected $description = 'Queue SSL certificate checks for all websites';

    public function handle(): int
    {
        $this->info('🔍 Starting SSL certificate checks for all websites...');

        $force = $this->option('force');
        $totalWebsites = 0;
        $queuedWebsites = 0;
        $skippedWebsites = 0;

        Website::chunk(100, function ($websites) use (&$totalWebsites, &$queuedWebsites, &$skippedWebsites, $force) {
            foreach ($websites as $website) {
                $totalWebsites++;

                // Skip if recently checked (unless forced)
                if (! $force) {
                    $recentCheck = $website->sslChecks()
                        ->where('checked_at', '>', now()->subHour())
                        ->exists();

                    if ($recentCheck) {
                        $skippedWebsites++;
                        $this->line("   ⏭️  Skipping {$website->name} - checked recently");

                        continue;
                    }
                }

                // Queue the SSL check job
                CheckSslCertificateJob::dispatch($website);
                $queuedWebsites++;

                $this->line("   ✅ Queued {$website->name} ({$website->url})");
            }
        });

        // Summary
        $this->newLine();
        $this->info('📊 SSL Check Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total websites', $totalWebsites],
                ['Queued for checking', $queuedWebsites],
                ['Skipped (recent checks)', $skippedWebsites],
            ]
        );

        if ($queuedWebsites > 0) {
            $this->info("🚀 {$queuedWebsites} SSL checks have been queued!");
            $this->info('💡 Process the queue with: php artisan queue:work ssl-monitoring');
        } else {
            $this->warn('⚠️  No SSL checks were queued.');
            if ($totalWebsites > 0 && ! $force) {
                $this->info('💡 Use --force to check all websites regardless of recent checks');
            }
        }

        // Log the activity
        Log::info('SSL check command completed', [
            'total_websites' => $totalWebsites,
            'queued_websites' => $queuedWebsites,
            'skipped_websites' => $skippedWebsites,
            'forced' => $force,
        ]);

        return Command::SUCCESS;
    }
}
