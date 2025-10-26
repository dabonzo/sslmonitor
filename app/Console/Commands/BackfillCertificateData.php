<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeSslCertificateJob;
use App\Models\Website;
use Illuminate\Console\Command;

class BackfillCertificateData extends Command
{
    protected $signature = 'ssl:backfill-certificates
                            {--limit=10 : Number of websites to process}
                            {--force : Process all websites regardless of existing data}
                            {--no-delay : Skip delay between dispatches (useful for testing)}';

    protected $description = 'Backfill SSL certificate data for existing websites';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $query = Website::where('ssl_monitoring_enabled', true);

        if (! $force) {
            $query->whereNull('ssl_certificate_analyzed_at');
        }

        $websites = $query->limit($limit)->get();

        if ($websites->isEmpty()) {
            $this->info('No websites need certificate analysis.');

            return 0;
        }

        $this->info("Processing {$websites->count()} websites...");

        $bar = $this->output->createProgressBar($websites->count());

        foreach ($websites as $website) {
            $this->info("\nAnalyzing: {$website->url}");

            dispatch(new AnalyzeSslCertificateJob($website))
                ->onQueue('monitoring-analysis');

            $bar->advance();

            // Small delay to avoid overwhelming the queue (skip in tests with --no-delay)
            if (! $this->option('no-delay')) {
                usleep(500000); // 0.5 seconds
            }
        }

        $bar->finish();

        $this->info("\n\nQueued {$websites->count()} certificate analysis jobs.");
        $this->info('Check Horizon dashboard to monitor progress.');

        return 0;
    }
}
