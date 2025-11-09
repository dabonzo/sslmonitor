<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryPerformanceService
{
    /**
     * Log slow queries (> 100ms)
     */
    public function enableSlowQueryLogging(): void
    {
        DB::listen(function ($query) {
            if ($query->time > 100) {
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                ]);
            }
        });
    }

    /**
     * Get slow query statistics
     */
    public function getSlowQueryStats(): array
    {
        // This would integrate with Laravel Telescope or log aggregation
        return [
            'slow_query_count' => 0,
            'average_query_time' => 0,
            'slowest_query_time' => 0,
        ];
    }
}
