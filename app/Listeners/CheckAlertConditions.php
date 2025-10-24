<?php

namespace App\Listeners;

use App\Events\MonitoringCheckCompleted;
use App\Models\MonitoringResult;
use App\Services\AlertCorrelationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckAlertConditions implements ShouldQueue
{
    public $queue = 'monitoring-history';

    public $tries = 2;

    public $timeout = 60;

    /**
     * Create the event listener
     */
    public function __construct(
        protected AlertCorrelationService $alertService
    ) {}

    /**
     * Handle the event by checking alert conditions
     */
    public function handle(MonitoringCheckCompleted $event): void
    {
        $monitor = $event->monitor;
        $checkResults = $event->checkResults;

        // Get the latest monitoring result for this check
        $result = MonitoringResult::where('monitor_id', $monitor->id)
            ->where('started_at', $event->startedAt)
            ->first();

        if (! $result) {
            // Result not yet persisted (race condition)
            // This is OK - RecordMonitoringResult listener handles it
            return;
        }

        // Check and create alerts based on the result
        $this->alertService->checkAndCreateAlerts($result);

        // Auto-resolve alerts if conditions improved
        $this->alertService->autoResolveAlerts($result);
    }
}
