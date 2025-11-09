<?php

namespace App\Console\Commands;

use App\Events\MonitoringCheckCompleted;
use App\Models\Monitor;
use App\Models\Website;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LoadTestMonitoringCommand extends Command
{
    protected $signature = 'monitoring:load-test
                            {--websites=10 : Number of websites to simulate}
                            {--checks=100 : Number of checks per website}
                            {--duration=60 : Duration in seconds}';

    protected $description = 'Load test the monitoring system';

    public function handle(): int
    {
        $websiteCount = (int) $this->option('websites');
        $checksPerWebsite = (int) $this->option('checks');
        $duration = (int) $this->option('duration');

        $this->info('🚀 Starting load test...');
        $this->info("  Websites: {$websiteCount}");
        $this->info("  Checks per website: {$checksPerWebsite}");
        $this->info("  Duration: {$duration}s");

        $startTime = now();
        $totalChecks = 0;
        $successfulChecks = 0;
        $failedChecks = 0;

        // Get or create test monitors
        $monitors = $this->getTestMonitors($websiteCount);

        $this->withProgressBar($monitors, function ($monitor) use (&$totalChecks, &$successfulChecks, &$failedChecks, $checksPerWebsite) {
            for ($i = 0; $i < $checksPerWebsite; $i++) {
                try {
                    event(new MonitoringCheckCompleted(
                        monitor: $monitor,
                        triggerType: 'load_test',
                        triggeredByUserId: null,
                        startedAt: now()->subSeconds(rand(1, 5)),
                        completedAt: now(),
                        checkResults: $this->generateCheckResults()
                    ));

                    $successfulChecks++;
                } catch (\Exception $e) {
                    $failedChecks++;
                }

                $totalChecks++;

                // Small delay to simulate real checks
                usleep(10000); // 10ms
            }
        });

        $this->newLine(2);

        $endTime = now();
        $duration = $endTime->diffInSeconds($startTime);

        $this->info('✅ Load test complete!');
        $this->info("  Total checks: {$totalChecks}");
        $this->info("  Successful: {$successfulChecks}");
        $this->info("  Failed: {$failedChecks}");
        $this->info("  Duration: {$duration}s");
        $this->info('  Checks/second: ' . round($totalChecks / max($duration, 1), 2));

        // Check database size
        $this->checkDatabaseGrowth();

        return self::SUCCESS;
    }

    protected function getTestMonitors(int $count): \Illuminate\Support\Collection
    {
        $monitors = Monitor::where('url', 'LIKE', 'http://load-test-%')->limit($count)->get();

        if ($monitors->count() < $count) {
            $this->info("Creating {$count} test monitors...");

            for ($i = $monitors->count(); $i < $count; $i++) {
                $website = Website::factory()->create([
                    'url' => "http://load-test-{$i}.example.com",
                ]);

                $monitors->push(Monitor::factory()->create([
                    'url' => $website->url,
                ]));
            }
        }

        return $monitors;
    }

    protected function generateCheckResults(): array
    {
        return [
            'check_type' => 'both',
            'uptime_status' => rand(1, 100) > 5 ? 'up' : 'down',
            'http_status_code' => rand(1, 100) > 5 ? 200 : 500,
            'response_time_ms' => rand(50, 500),
            'ssl_status' => rand(1, 100) > 5 ? 'valid' : 'invalid',
        ];
    }

    protected function checkDatabaseGrowth(): void
    {
        $this->info("\n📊 Database statistics:");

        $results = \DB::table('monitoring_results')->count();
        $summaries = \DB::table('monitoring_check_summaries')->count();
        $alerts = \DB::table('monitoring_alerts')->count();

        $this->info("  monitoring_results: {$results} rows");
        $this->info("  monitoring_check_summaries: {$summaries} rows");
        $this->info("  monitoring_alerts: {$alerts} rows");
    }
}
