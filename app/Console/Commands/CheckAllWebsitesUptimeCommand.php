<?php

namespace App\Console\Commands;

use App\Jobs\CheckWebsiteUptimeJob;
use App\Models\User;
use App\Models\Website;
use Illuminate\Console\Command;

class CheckAllWebsitesUptimeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'uptime:check-all 
                            {--force : Force check even if recently checked}
                            {--user= : Check websites for specific user ID only}
                            {--detailed : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Queue uptime checks for websites with uptime monitoring enabled';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('detailed')) {
            $this->info('Checking uptime monitoring websites...');
        }

        $force = $this->option('force');
        $userId = $this->option('user');
        $verbose = $this->option('detailed');

        // Validate user if provided
        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User with ID {$userId} not found.");

                return Command::FAILURE;
            }
        }

        // Build query for websites with uptime monitoring enabled
        $query = Website::where('uptime_monitoring', true);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Add minimum check interval unless forced (5 minutes)
        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('last_uptime_check_at')
                    ->orWhere('last_uptime_check_at', '<=', now()->subMinutes(5));
            });
        }

        $totalWebsites = Website::count();
        $monitoringWebsites = Website::where('uptime_monitoring', true)->count();
        $websites = $query->get();
        $queuedCount = $websites->count();

        if ($verbose && $monitoringWebsites > 0) {
            $this->info("Found {$totalWebsites} total websites, {$monitoringWebsites} with uptime monitoring enabled.");
        }

        if ($queuedCount === 0) {
            if ($monitoringWebsites === 0) {
                $this->info('No websites found with uptime monitoring enabled.');
            } elseif ($userId && ! $websites->count() && ! $force) {
                $user = User::find($userId);
                $userWebsites = Website::where('user_id', $userId)->where('uptime_monitoring', true)->count();
                if ($userWebsites === 0) {
                    $this->info("No websites found with uptime monitoring enabled for user {$user->name}.");
                } else {
                    $this->info("No websites found with uptime monitoring enabled for user {$user->name}.");
                }
            } else {
                $this->info('No websites need uptime checking at this time.');
            }

            return Command::SUCCESS;
        }

        // Queue the uptime checks
        foreach ($websites as $website) {
            CheckWebsiteUptimeJob::dispatch($website);

            if ($verbose) {
                $this->info("Queuing: {$website->name} ({$website->url})");
            }
        }

        // Output results
        if ($userId) {
            $user = User::find($userId);
            if ($force) {
                $this->info("Queued uptime checks for {$queuedCount} website(s) for user {$user->name} (forced).");
            } else {
                $this->info("Queued uptime checks for {$queuedCount} website(s) for user {$user->name}.");
            }
        } else {
            if ($force) {
                $this->info("Queued uptime checks for {$queuedCount} website(s) (forced).");
            } else {
                $this->info("Queued uptime checks for {$queuedCount} website(s).");
            }
        }

        return Command::SUCCESS;
    }
}
