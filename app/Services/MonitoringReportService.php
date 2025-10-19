<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\MonitoringResult;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MonitoringReportService
{
    /**
     * Generate CSV export for monitoring data
     */
    public function generateCsvExport(Monitor $monitor, Carbon $startDate, Carbon $endDate): string
    {
        $results = MonitoringResult::where('monitor_id', $monitor->id)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->orderBy('started_at', 'desc')
            ->get();

        $csv = "Timestamp,Status,Uptime Status,Response Time (ms),SSL Status,Days Until Expiration,Error\n";

        foreach ($results as $result) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $result->started_at->toIso8601String(),
                $result->status,
                $result->uptime_status ?? 'N/A',
                $result->response_time_ms ?? 'N/A',
                $result->ssl_status ?? 'N/A',
                $result->days_until_expiration ?? 'N/A',
                str_replace(["\n", "\r", ','], ' ', $result->error_message ?? '')
            );
        }

        return $csv;
    }

    /**
     * Get summary report for period
     */
    public function getSummaryReport(Monitor $monitor, string $period = '30d'): array
    {
        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 30,
        };

        $results = MonitoringResult::where('monitor_id', $monitor->id)
            ->where('started_at', '>=', now()->subDays($days))
            ->get();

        return [
            'period' => $period,
            'total_checks' => $results->count(),
            'success_count' => $results->where('status', 'success')->count(),
            'failure_count' => $results->where('status', '!=', 'success')->count(),
            'avg_response_time' => round($results->avg('response_time_ms') ?? 0, 2),
            'uptime_percentage' => $this->calculateUptimePercentage($results),
            'ssl_checks' => $results->whereNotNull('ssl_status')->count(),
            'ssl_valid' => $results->where('ssl_status', 'valid')->count(),
            'ssl_issues' => $results->whereIn('ssl_status', ['invalid', 'expired'])->count(),
        ];
    }

    protected function calculateUptimePercentage(Collection $results): float
    {
        $total = $results->whereNotNull('uptime_status')->count();
        if ($total === 0) {
            return 0;
        }

        $up = $results->where('uptime_status', 'up')->count();

        return round(($up / $total) * 100, 2);
    }

    /**
     * Get daily breakdown for period
     */
    public function getDailyBreakdown(Monitor $monitor, Carbon $startDate, Carbon $endDate): array
    {
        $results = MonitoringResult::where('monitor_id', $monitor->id)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->selectRaw('DATE(started_at) as date, COUNT(*) as checks, AVG(response_time_ms) as avg_response')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $results->map(function ($day) {
            return [
                'date' => $day->date,
                'checks' => $day->checks,
                'avg_response_time' => round($day->avg_response ?? 0, 2),
            ];
        })->toArray();
    }
}
