<?php

namespace App\Listeners;

use App\Events\MonitoringCheckCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateMonitoringSummaries implements ShouldQueue
{
    public string $queue = 'monitoring-aggregation';

    public function handle(MonitoringCheckCompleted $event): void
    {
        // TODO: Implement in Phase 4
        // This will calculate and update hourly/daily summaries
    }
}
