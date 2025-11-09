<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\MonitoringCheckSummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class MonitoringCacheService
{
    protected const CACHE_TTL = 300; // 5 minutes

    protected const SUMMARY_CACHE_TTL = 3600; // 1 hour

    protected const TREND_CACHE_TTL = 600; // 10 minutes

    /**
     * Get cached summary statistics for a monitor
     */
    public function getSummaryStats(Monitor $monitor, string $period = '30d'): array
    {
        $cacheKey = $this->getSummaryCacheKey($monitor->id, $period);

        return Cache::remember($cacheKey, self::SUMMARY_CACHE_TTL, function () use ($monitor, $period) {
            return $this->calculateSummaryStats($monitor, $period);
        });
    }

    /**
     * Get cached uptime percentage
     */
    public function getUptimePercentage(Monitor $monitor, string $period = '30d'): float
    {
        $cacheKey = "monitor:{$monitor->id}:uptime:{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($monitor, $period) {
            $startDate = $this->parsePeriod($period);

            $summary = MonitoringCheckSummary::where('monitor_id', $monitor->id)
                ->where('summary_period', 'daily')
                ->where('period_start', '>=', $startDate)
                ->get();

            return round($summary->avg('uptime_percentage') ?? 0, 2);
        });
    }

    /**
     * Get cached response time trend
     */
    public function getResponseTimeTrend(Monitor $monitor, string $period = '7d'): array
    {
        $cacheKey = "monitor:{$monitor->id}:response_trend:{$period}";

        return Cache::remember($cacheKey, self::TREND_CACHE_TTL, function () use ($monitor, $period) {
            $startDate = $this->parsePeriod($period);

            return MonitoringCheckSummary::where('monitor_id', $monitor->id)
                ->where('summary_period', 'hourly')
                ->where('period_start', '>=', $startDate)
                ->orderBy('period_start')
                ->get(['period_start', 'average_response_time_ms'])
                ->map(fn ($summary) => [
                    'timestamp' => $summary->period_start->toIso8601String(),
                    'avg_response_time' => $summary->average_response_time_ms,
                ])
                ->toArray();
        });
    }

    /**
     * Invalidate all caches for a monitor
     */
    public function invalidateMonitorCaches(int $monitorId): void
    {
        $periods = ['24h', '7d', '30d', '90d'];

        foreach ($periods as $period) {
            Cache::forget($this->getSummaryCacheKey($monitorId, $period));
            Cache::forget("monitor:{$monitorId}:uptime:{$period}");
            Cache::forget("monitor:{$monitorId}:response_trend:{$period}");
        }
    }

    /**
     * Invalidate caches for a website (all monitors)
     */
    public function invalidateWebsiteCaches(int $websiteId): void
    {
        // Get all monitor IDs for the website
        $monitorIds = Monitor::where('url', 'LIKE', "%website_id={$websiteId}%")
            ->pluck('id');

        foreach ($monitorIds as $monitorId) {
            $this->invalidateMonitorCaches($monitorId);
        }
    }

    /**
     * Calculate summary statistics (not cached)
     */
    protected function calculateSummaryStats(Monitor $monitor, string $period): array
    {
        $startDate = $this->parsePeriod($period);

        $summary = MonitoringCheckSummary::where('monitor_id', $monitor->id)
            ->where('summary_period', 'daily')
            ->where('period_start', '>=', $startDate)
            ->get();

        return [
            'uptime_percentage' => round($summary->avg('uptime_percentage') ?? 0, 2),
            'average_response_time' => round($summary->avg('average_response_time_ms') ?? 0, 2),
            'total_checks' => $summary->sum('total_checks'),
            'successful_checks' => $summary->sum('successful_uptime_checks'),
            'failed_checks' => $summary->sum('failed_uptime_checks'),
        ];
    }

    /**
     * Get cache key for summary stats
     */
    protected function getSummaryCacheKey(int $monitorId, string $period): string
    {
        return "monitor:{$monitorId}:summary:{$period}";
    }

    /**
     * Parse period string to Carbon date
     */
    protected function parsePeriod(string $period): Carbon
    {
        return match ($period) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(7),
        };
    }
}
