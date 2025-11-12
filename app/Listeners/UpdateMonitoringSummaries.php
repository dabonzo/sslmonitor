<?php

namespace App\Listeners;

use App\Events\MonitoringCheckCompleted;
use App\Models\MonitoringCheckSummary;
use App\Models\MonitoringResult;
use App\Services\MonitoringCacheService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class UpdateMonitoringSummaries implements ShouldQueue
{
    public $queue = 'monitoring-aggregation';

    public $tries = 2;

    public $timeout = 120;

    public function __construct(
        protected MonitoringCacheService $cache
    ) {}

    /**
     * Handle the event by updating summary statistics
     */
    public function handle(MonitoringCheckCompleted $event): void
    {
        $monitor = $event->monitor;
        $results = $event->checkResults;

        // Update hourly summary (most granular real-time tracking)
        $this->updateSummary($monitor->id, 'hourly', now());

        // Invalidate caches after updating summaries
        $this->cache->invalidateMonitorCaches($monitor->id);
    }

    /**
     * Update summary for a specific period
     */
    protected function updateSummary(int $monitorId, string $period, Carbon $date): void
    {
        $dateRange = $this->getDateRange($date, $period);

        // Get website_id from monitoring results (NOT from monitor)
        $stats = MonitoringResult::where('monitor_id', $monitorId)
            ->whereBetween('started_at', [$dateRange['start'], $dateRange['end']])
            ->select([
                DB::raw('website_id'),
                DB::raw('COUNT(*) as total_checks'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_checks'),
                DB::raw('SUM(CASE WHEN status != "success" THEN 1 ELSE 0 END) as failed_checks'),

                // Uptime statistics
                DB::raw('SUM(CASE WHEN check_type IN ("uptime", "both") THEN 1 ELSE 0 END) as total_uptime_checks'),
                DB::raw('SUM(CASE WHEN uptime_status = "up" THEN 1 ELSE 0 END) as successful_uptime_checks'),
                DB::raw('SUM(CASE WHEN uptime_status = "down" THEN 1 ELSE 0 END) as failed_uptime_checks'),

                // Response time statistics
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('MIN(response_time_ms) as min_response_time'),
                DB::raw('MAX(response_time_ms) as max_response_time'),

                // SSL statistics
                DB::raw('SUM(CASE WHEN check_type IN ("ssl_certificate", "both") AND ssl_status IS NOT NULL THEN 1 ELSE 0 END) as total_ssl_checks'),
                DB::raw('SUM(CASE WHEN ssl_status = "valid" THEN 1 ELSE 0 END) as successful_ssl_checks'),
                DB::raw('SUM(CASE WHEN ssl_status IN ("invalid", "expired") THEN 1 ELSE 0 END) as failed_ssl_checks'),
                DB::raw('SUM(CASE WHEN days_until_expiration IS NOT NULL AND days_until_expiration <= 30 THEN 1 ELSE 0 END) as certificates_expiring'),
                DB::raw('SUM(CASE WHEN ssl_status = "expired" THEN 1 ELSE 0 END) as certificates_expired'),

                // Content validation statistics
                DB::raw('SUM(CASE WHEN content_validation_enabled = 1 THEN 1 ELSE 0 END) as total_content_validations'),
                DB::raw('SUM(CASE WHEN content_validation_status = "passed" THEN 1 ELSE 0 END) as successful_content_validations'),
                DB::raw('SUM(CASE WHEN content_validation_status = "failed" THEN 1 ELSE 0 END) as failed_content_validations'),
            ])
            ->groupBy('website_id')
            ->first();

        if (! $stats || $stats->total_checks === 0 || ! $stats->website_id) {
            return; // No data to aggregate or missing website_id
        }

        // Calculate percentile values (p95, p99)
        $percentiles = $this->calculatePercentiles($monitorId, $dateRange);

        // Update or create summary
        MonitoringCheckSummary::updateOrCreate(
            [
                'monitor_id' => $monitorId,
                'website_id' => $stats->website_id,
                'summary_period' => $period,
                'period_start' => $dateRange['start'],
            ],
            [
                'period_end' => $dateRange['end'],

                // Overall counts
                'total_checks' => $stats->total_checks,

                // Uptime metrics
                'total_uptime_checks' => $stats->total_uptime_checks ?? 0,
                'successful_uptime_checks' => $stats->successful_uptime_checks ?? 0,
                'failed_uptime_checks' => $stats->failed_uptime_checks ?? 0,
                'uptime_percentage' => $this->calculatePercentage(
                    $stats->successful_uptime_checks ?? 0,
                    $stats->total_uptime_checks ?? 0
                ),

                // Response time metrics
                'average_response_time_ms' => round($stats->avg_response_time ?? 0),
                'min_response_time_ms' => $stats->min_response_time ?? 0,
                'max_response_time_ms' => $stats->max_response_time ?? 0,
                'p95_response_time_ms' => $percentiles['p95'] ?? 0,
                'p99_response_time_ms' => $percentiles['p99'] ?? 0,

                // SSL metrics
                'total_ssl_checks' => $stats->total_ssl_checks ?? 0,
                'successful_ssl_checks' => $stats->successful_ssl_checks ?? 0,
                'failed_ssl_checks' => $stats->failed_ssl_checks ?? 0,
                'certificates_expiring' => $stats->certificates_expiring ?? 0,
                'certificates_expired' => $stats->certificates_expired ?? 0,

                // Content validation metrics
                'total_content_validations' => $stats->total_content_validations ?? 0,
                'successful_content_validations' => $stats->successful_content_validations ?? 0,
                'failed_content_validations' => $stats->failed_content_validations ?? 0,

                // Metadata
                'total_check_duration_ms' => 0, // Can be calculated if needed
                'average_check_duration_ms' => 0, // Can be calculated if needed
            ]
        );
    }

    /**
     * Calculate response time percentiles
     */
    protected function calculatePercentiles(int $monitorId, array $dateRange): array
    {
        $responseTimes = MonitoringResult::where('monitor_id', $monitorId)
            ->whereBetween('started_at', [$dateRange['start'], $dateRange['end']])
            ->whereNotNull('response_time_ms')
            ->orderBy('response_time_ms')
            ->pluck('response_time_ms')
            ->toArray();

        if (empty($responseTimes)) {
            return ['p95' => 0, 'p99' => 0];
        }

        $count = count($responseTimes);
        $p95Index = (int) ceil($count * 0.95) - 1;
        $p99Index = (int) ceil($count * 0.99) - 1;

        return [
            'p95' => $responseTimes[$p95Index] ?? 0,
            'p99' => $responseTimes[$p99Index] ?? 0,
        ];
    }

    /**
     * Calculate percentage with proper rounding
     */
    protected function calculatePercentage(int $numerator, int $denominator): float
    {
        if ($denominator === 0) {
            return 0.00;
        }

        return round(($numerator / $denominator) * 100, 2);
    }

    /**
     * Get date range for the period
     */
    protected function getDateRange(Carbon $date, string $period): array
    {
        return match ($period) {
            'hourly' => [
                'start' => $date->copy()->startOfHour(),
                'end' => $date->copy()->endOfHour(),
            ],
            'daily' => [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ],
            'weekly' => [
                'start' => $date->copy()->startOfWeek(),
                'end' => $date->copy()->endOfWeek(),
            ],
            'monthly' => [
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
            ],
            default => [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ],
        };
    }
}
