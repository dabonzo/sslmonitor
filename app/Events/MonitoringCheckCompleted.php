<?php

namespace App\Events;

use App\Models\Monitor;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringCheckCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Monitor $monitor,
        public readonly string $triggerType,
        public readonly ?int $triggeredByUserId,
        public readonly Carbon $startedAt,
        public readonly Carbon $completedAt,
        public readonly array $checkResults,
    ) {}
}
