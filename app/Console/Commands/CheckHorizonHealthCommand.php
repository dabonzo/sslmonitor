<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CheckHorizonHealthCommand extends Command
{
    protected $signature = 'horizon:health-check';

    protected $description = 'Check Horizon health and queue status';

    public function handle(): int
    {
        $this->info('🏥 Checking Horizon health...');

        // Check if Horizon is running
        if (! $this->isHorizonRunning()) {
            $this->error('❌ Horizon is not running!');

            return self::FAILURE;
        }

        $this->info('✅ Horizon is running');

        // Check queue depth
        $queueDepth = $this->getQueueDepth();
        $this->info("📊 Queue depth: {$queueDepth} jobs");

        if ($queueDepth > 100) {
            $this->warn("⚠️  High queue depth: {$queueDepth} jobs");
        }

        // Check failed jobs
        $failedJobs = \DB::table('failed_jobs')->count();
        $this->info("❌ Failed jobs: {$failedJobs}");

        if ($failedJobs > 10) {
            $this->warn("⚠️  High failed job count: {$failedJobs}");
        }

        // Check recent job processing rate
        $processingRate = $this->getProcessingRate();
        $this->info("⚡ Processing rate: {$processingRate} jobs/min");

        return self::SUCCESS;
    }

    protected function isHorizonRunning(): bool
    {
        try {
            $masters = Redis::connection('horizon')->smembers('masters');

            return is_array($masters) && count($masters) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getQueueDepth(): int
    {
        $queues = ['monitoring-history', 'monitoring-aggregation', 'default'];
        $total = 0;

        foreach ($queues as $queue) {
            try {
                $total += Redis::connection()->llen("queues:{$queue}");
            } catch (\Exception $e) {
                // Queue doesn't exist or connection failed
            }
        }

        return $total;
    }

    protected function getProcessingRate(): int
    {
        // This would calculate jobs processed in last minute
        // For now, return a placeholder
        return 0;
    }
}
