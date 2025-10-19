<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitoringCheckSummary;
use App\Models\MonitoringResult;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AggregateMonitoringSummariesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $period,
        public readonly ?Carbon $date = null,
    ) {}

    public function handle(): void
    {
        $targetDate = $this->date ?? now();

        Monitor::chunk(100, function ($monitors) use ($targetDate) {
            foreach ($monitors as $monitor) {
                $this->aggregateForMonitor($monitor, $targetDate);
            }
        });
    }

    protected function aggregateForMonitor(Monitor $monitor, Carbon $date): void
    {
        $dateRange = $this->getDateRange($date);

        $stats = MonitoringResult::where('monitor_id', $monitor->id)
            ->whereBetween('started_at', [$dateRange['start'], $dateRange['end']])
            ->select([
                DB::raw('website_id'),
                DB::raw('COUNT(*) as total_checks'),
                DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_checks'),
                DB::raw('SUM(CASE WHEN status != "success" THEN 1 ELSE 0 END) as failed_checks'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('MIN(response_time_ms) as min_response_time'),
                DB::raw('MAX(response_time_ms) as max_response_time'),
                DB::raw('SUM(CASE WHEN uptime_status = "up" THEN 1 ELSE 0 END) as uptime_count'),
                DB::raw('SUM(CASE WHEN uptime_status = "down" THEN 1 ELSE 0 END) as downtime_count'),
                DB::raw('SUM(CASE WHEN ssl_status = "valid" THEN 1 ELSE 0 END) as ssl_valid_count'),
                DB::raw('SUM(CASE WHEN ssl_status IN ("invalid", "expired") THEN 1 ELSE 0 END) as ssl_invalid_count'),
            ])
            ->groupBy('website_id')
            ->first();

        if ($stats && $stats->total_checks > 0) {
            $uptimeChecks = $stats->uptime_count + $stats->downtime_count;

            MonitoringCheckSummary::updateOrCreate(
                [
                    'monitor_id' => $monitor->id,
                    'website_id' => $stats->website_id,
                    'summary_period' => $this->period,
                    'period_start' => $dateRange['start'],
                ],
                [
                    'period_end' => $dateRange['end'],
                    'total_checks' => $stats->total_checks,
                    'total_uptime_checks' => $uptimeChecks,
                    'successful_uptime_checks' => $stats->uptime_count,
                    'failed_uptime_checks' => $stats->downtime_count,
                    'uptime_percentage' => $uptimeChecks > 0
                        ? round(($stats->uptime_count / $uptimeChecks) * 100, 2)
                        : 0,
                    'average_response_time_ms' => round($stats->avg_response_time ?? 0, 2),
                    'min_response_time_ms' => $stats->min_response_time ?? 0,
                    'max_response_time_ms' => $stats->max_response_time ?? 0,
                    'total_ssl_checks' => $stats->ssl_valid_count + $stats->ssl_invalid_count,
                    'successful_ssl_checks' => $stats->ssl_valid_count,
                    'failed_ssl_checks' => $stats->ssl_invalid_count,
                ]
            );
        }
    }

    protected function getDateRange(Carbon $date): array
    {
        return match ($this->period) {
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
