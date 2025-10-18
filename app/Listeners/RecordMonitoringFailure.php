<?php

namespace App\Listeners;

use App\Events\MonitoringCheckFailed;
use App\Models\MonitoringResult;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordMonitoringFailure implements ShouldQueue
{
    public string $queue = 'monitoring-history';

    public function handle(MonitoringCheckFailed $event): void
    {
        $monitor = $event->monitor;

        MonitoringResult::create([
            'monitor_id' => $monitor->id,
            'website_id' => $this->getWebsiteIdFromMonitor($monitor),
            'check_type' => 'both',
            'trigger_type' => $event->triggerType,
            'triggered_by_user_id' => $event->triggeredByUserId,

            'started_at' => $event->startedAt,
            'completed_at' => now(),
            'duration_ms' => $event->startedAt->diffInMilliseconds(now()),

            'status' => 'error',
            'error_message' => $event->exception->getMessage(),
        ]);
    }

    private function getWebsiteIdFromMonitor($monitor): ?int
    {
        $website = \App\Models\Website::where('url', (string) $monitor->url)->first();

        return $website?->id;
    }
}
