<?php

namespace App\Console\Commands;

use App\Services\SslNotificationService;
use Illuminate\Console\Command;

class SendSslDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ssl:send-digest 
                            {--dry-run : Show what would be sent without actually sending emails}';

    /**
     * The console command description.
     */
    protected $description = 'Send daily SSL digest emails to users who have opted in';

    /**
     * Execute the console command.
     */
    public function handle(SslNotificationService $notificationService): int
    {
        $this->info('🔒 Starting SSL Daily Digest...');

        if ($this->option('dry-run')) {
            $this->warn('📧 DRY RUN MODE - No emails will be sent');
        }

        try {
            $startTime = microtime(true);

            // Get count of users who have daily digest enabled
            $digestUsers = \App\Models\NotificationPreference::where('email_enabled', true)
                ->where('daily_digest', true)
                ->with('user')
                ->get();

            if ($digestUsers->isEmpty()) {
                $this->info('📭 No users have daily digest enabled');

                return Command::SUCCESS;
            }

            $this->info("📬 Found {$digestUsers->count()} users with daily digest enabled");

            if (! $this->option('dry-run')) {
                $notificationService->sendDailyDigest();

                $duration = round(microtime(true) - $startTime, 2);
                $this->info("✅ Daily digest emails sent to {$digestUsers->count()} users in {$duration}s");
            } else {
                foreach ($digestUsers as $preference) {
                    $this->line("  📧 Would send to: {$preference->email_address} ({$preference->user->name})");
                }
                $this->info('✅ Dry run completed');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Failed to send daily digest: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
