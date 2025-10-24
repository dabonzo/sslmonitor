<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\MonitoringResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

final class MonitoringHistoryService
{
    /**
     * Get trend data for charts with labels and datasets
     *
     * @param  string  $period  '7d', '30d', '90d'
     * @return array{labels: array<string>, datasets: array<array{name: string, data: array<int|float>}>}
     */
    public function getTrendData(Monitor $monitor, string $period = '7d'): array
    {
        $periodInfo = $this->getPeriodInfo($period);

        $results = MonitoringResult::query()
            ->where('monitor_id', $monitor->id)
            ->where('started_at', '>=', $periodInfo['start'])
            ->whereNotNull('response_time_ms')
            ->orderBy('started_at')
            ->get();

        // Group results by time intervals
        $grouped = $results->groupBy(function ($result) use ($period) {
            return match ($period) {
                '7d' => $result->started_at->format('Y-m-d H:00'),
                '30d' => $result->started_at->format('Y-m-d'),
                '90d' => $result->started_at->format('Y-m-d'),
                default => $result->started_at->format('Y-m-d H:00'),
            };
        });

        $labels = [];
        $uptimeData = [];
        $responseTimeData = [];

        foreach ($grouped as $time => $items) {
            $labels[] = $time;
            $uptimeData[] = $items->where('status', 'success')->count();
            $responseTimeData[] = round($items->avg('response_time_ms') ?? 0, 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'name' => 'Successful Checks',
                    'data' => $uptimeData,
                ],
                [
                    'name' => 'Response Time (ms)',
                    'data' => $responseTimeData,
                ],
            ],
        ];
    }

    /**
     * Get recent monitoring results with pagination
     *
     * @return Collection<int, MonitoringResult>
     */
    public function getRecentHistory(Monitor $monitor, int $limit = 50): Collection
    {
        return MonitoringResult::query()
            ->where('monitor_id', $monitor->id)
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate summary statistics for a given period
     *
     * @param  string  $period  '7d', '30d', '90d'
     * @return array{
     *     total_checks: int,
     *     successful_checks: int,
     *     failed_checks: int,
     *     uptime_percentage: float,
     *     avg_response_time: float,
     *     min_response_time: int|null,
     *     max_response_time: int|null,
     *     period_start: Carbon,
     *     period_end: Carbon
     * }
     */
    public function getSummaryStats(Monitor $monitor, string $period = '30d'): array
    {
        $periodInfo = $this->getPeriodInfo($period);

        $stats = MonitoringResult::query()
            ->where('monitor_id', $monitor->id)
            ->where('started_at', '>=', $periodInfo['start'])
            ->where('started_at', '<=', $periodInfo['end'])
            ->selectRaw('
                COUNT(*) as total_checks,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as successful_checks,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_checks,
                AVG(CASE WHEN response_time_ms IS NOT NULL THEN response_time_ms END) as avg_response_time,
                MIN(response_time_ms) as min_response_time,
                MAX(response_time_ms) as max_response_time
            ', ['success', 'failed'])
            ->first();

        $totalChecks = (int) $stats->total_checks;
        $successfulChecks = (int) $stats->successful_checks;
        $uptimePercentage = $totalChecks > 0 ? round(($successfulChecks / $totalChecks) * 100, 2) : 0.0;

        return [
            'total_checks' => $totalChecks,
            'successful_checks' => $successfulChecks,
            'failed_checks' => (int) $stats->failed_checks,
            'uptime_percentage' => $uptimePercentage,
            'avg_response_time' => round((float) $stats->avg_response_time, 2),
            'min_response_time' => $stats->min_response_time,
            'max_response_time' => $stats->max_response_time,
            'period_start' => $periodInfo['start'],
            'period_end' => $periodInfo['end'],
        ];
    }

    /**
     * Calculate uptime percentage for a given period
     *
     * @param  string  $period  '7d', '30d', '90d'
     */
    public function getUptimePercentage(Monitor $monitor, string $period = '30d'): float
    {
        $periodInfo = $this->getPeriodInfo($period);

        $result = MonitoringResult::query()
            ->where('monitor_id', $monitor->id)
            ->where('started_at', '>=', $periodInfo['start'])
            ->where('started_at', '<=', $periodInfo['end'])
            ->selectRaw('
                COUNT(*) as total_checks,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as successful_checks
            ', ['success'])
            ->first();

        $totalChecks = (int) $result->total_checks;

        if ($totalChecks === 0) {
            return 0.0;
        }

        return round(((int) $result->successful_checks / $totalChecks) * 100, 2);
    }

    /**
     * Get response time trend data for charts
     *
     * @param  string  $period  '7d', '30d', '90d'
     * @return array{labels: array<string>, data: array<int|float>, avg: float}
     */
    public function getResponseTimeTrend(Monitor $monitor, string $period = '7d'): array
    {
        $periodInfo = $this->getPeriodInfo($period);

        $results = MonitoringResult::query()
            ->where('monitor_id', $monitor->id)
            ->where('started_at', '>=', $periodInfo['start'])
            ->whereNotNull('response_time_ms')
            ->orderBy('started_at')
            ->get();

        // Group by time intervals
        $grouped = $results->groupBy(function ($result) use ($period) {
            return match ($period) {
                '7d' => $result->started_at->format('Y-m-d H:00'),
                '30d' => $result->started_at->format('Y-m-d'),
                '90d' => $result->started_at->format('Y-m-d'),
                default => $result->started_at->format('Y-m-d H:00'),
            };
        });

        $labels = [];
        $data = [];
        $allResponseTimes = [];

        foreach ($grouped as $time => $items) {
            $labels[] = $time;
            $avgTime = round($items->avg('response_time_ms'), 2);
            $data[] = $avgTime;
            $allResponseTimes = array_merge($allResponseTimes, $items->pluck('response_time_ms')->toArray());
        }

        $overallAvg = count($allResponseTimes) > 0
            ? round(array_sum($allResponseTimes) / count($allResponseTimes), 2)
            : 0.0;

        return [
            'labels' => $labels,
            'data' => $data,
            'avg' => $overallAvg,
        ];
    }

    /**
     * Get SSL certificate expiration trend
     *
     * @return array{
     *     current_expiration: string|null,
     *     days_until_expiration: int|null,
     *     issuer: string|null,
     *     subject: string|null,
     *     historical_data: array<array{date: string, days_until: int|null}>
     * }
     */
    public function getSslExpirationTrend(Monitor $monitor): array
    {
        // Get current certificate info from latest result
        $latestResult = MonitoringResult::query()
            ->where('monitor_id', $monitor->id)
            ->whereNotNull('certificate_expiration_date')
            ->orderBy('started_at', 'desc')
            ->first();

        $currentExpiration = $latestResult?->certificate_expiration_date?->format('Y-m-d H:i:s');
        $daysUntilExpiration = $latestResult?->days_until_expiration;
        $issuer = $latestResult?->certificate_issuer;
        $subject = $latestResult?->certificate_subject;

        // Get historical expiration data
        $historicalData = MonitoringResult::query()
            ->where('monitor_id', $monitor->id)
            ->whereNotNull('certificate_expiration_date')
            ->orderBy('started_at', 'desc')
            ->limit(30) // Last 30 SSL check results
            ->get()
            ->map(function ($result) {
                return [
                    'date' => $result->started_at->format('Y-m-d H:i:s'),
                    'days_until' => $result->days_until_expiration,
                ];
            })
            ->values()
            ->toArray();

        return [
            'current_expiration' => $currentExpiration,
            'days_until_expiration' => $daysUntilExpiration,
            'issuer' => $issuer,
            'subject' => $subject,
            'historical_data' => $historicalData,
        ];
    }

    /**
     * Get period information (start and end dates) based on period string
     *
     * @return array{start: Carbon, end: Carbon}
     */
    private function getPeriodInfo(string $period): array
    {
        $now = now();

        return match ($period) {
            '7d' => [
                'start' => $now->copy()->subDays(7),
                'end' => $now,
            ],
            '30d' => [
                'start' => $now->copy()->subDays(30),
                'end' => $now,
            ],
            '90d' => [
                'start' => $now->copy()->subDays(90),
                'end' => $now,
            ],
            default => [
                'start' => $now->copy()->subDays(7),
                'end' => $now,
            ],
        };
    }
}
