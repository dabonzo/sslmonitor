<?php

namespace App\Listeners;

use App\Events\MonitoringCheckCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckAlertConditions implements ShouldQueue
{
    public string $queue = 'monitoring-history';

    public function handle(MonitoringCheckCompleted $event): void
    {
        // TODO: Implement in Phase 4
        // This will check if alerts should be triggered based on check results
    }
}
