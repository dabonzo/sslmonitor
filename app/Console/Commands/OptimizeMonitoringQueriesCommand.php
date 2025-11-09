<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OptimizeMonitoringQueriesCommand extends Command
{
    protected $signature = 'monitoring:optimize-queries';

    protected $description = 'Analyze and optimize monitoring queries';

    public function handle(): int
    {
        $this->info('Analyzing monitoring queries...');

        // Check for missing indexes
        $this->checkIndexes();

        // Analyze slow queries
        $this->analyzeSlowQueries();

        // Table statistics
        $this->showTableStatistics();

        $this->comment('Query optimization analysis complete!');

        return self::SUCCESS;
    }

    protected function checkIndexes(): void
    {
        $this->info("\n🔍 Checking indexes...");

        $tables = ['monitoring_results', 'monitoring_check_summaries', 'monitoring_alerts'];

        foreach ($tables as $table) {
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            $this->info("  ✓ {$table}: " . count($indexes) . ' indexes');
        }
    }

    protected function analyzeSlowQueries(): void
    {
        $this->info("\n⏱️  Analyzing query performance...");

        // Test common queries with EXPLAIN
        $queries = [
            'Recent results' => "SELECT * FROM monitoring_results WHERE monitor_id = 1 AND started_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            'Daily summaries' => "SELECT * FROM monitoring_check_summaries WHERE monitor_id = 1 AND summary_period = 'daily'",
            'Unresolved alerts' => 'SELECT * FROM monitoring_alerts WHERE resolved_at IS NULL',
        ];

        foreach ($queries as $name => $query) {
            try {
                $explain = DB::select("EXPLAIN {$query}");

                if (! empty($explain)) {
                    $explainData = (array) $explain[0];
                    $type = $explainData['type'] ?? 'unknown';
                    $rows = $explainData['rows'] ?? 0;

                    $this->info("  {$name}: {$type} scan, {$rows} rows");
                }
            } catch (\Exception $e) {
                $this->warn("  {$name}: Unable to analyze - " . $e->getMessage());
            }
        }
    }

    protected function showTableStatistics(): void
    {
        $this->info("\n📊 Table statistics...");

        try {
            $stats = DB::select("
                SELECT
                    table_name,
                    table_rows,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
                AND table_name LIKE 'monitoring_%'
            ");

            foreach ($stats as $stat) {
                $this->info("  {$stat->table_name}: {$stat->table_rows} rows, {$stat->size_mb} MB");
            }
        } catch (\Exception $e) {
            $this->warn("  Unable to fetch table statistics: " . $e->getMessage());
        }
    }
}
