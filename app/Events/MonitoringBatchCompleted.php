<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringBatchCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $totalChecks,
        public readonly int $successfulChecks,
        public readonly int $failedChecks,
        public readonly array $monitorIds,
    ) {}
}
