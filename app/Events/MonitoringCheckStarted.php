<?php

namespace App\Events;

use App\Models\Monitor;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringCheckStarted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Monitor $monitor,
        public readonly string $triggerType,
        public readonly ?int $triggeredByUserId = null,
    ) {}
}
