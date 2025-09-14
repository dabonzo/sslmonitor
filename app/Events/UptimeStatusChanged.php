<?php

namespace App\Events;

use App\Models\UptimeCheck;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UptimeStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public UptimeCheck $uptimeCheck,
        public string $previousStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('uptime-monitoring'),
            new PrivateChannel('uptime-monitoring.website.'.$this->uptimeCheck->website_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'uptime.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'uptime_check' => [
                'id' => $this->uptimeCheck->id,
                'website_id' => $this->uptimeCheck->website_id,
                'website_url' => $this->uptimeCheck->website->url,
                'status' => $this->uptimeCheck->status,
                'previous_status' => $this->previousStatus,
                'response_time' => $this->uptimeCheck->response_time_ms,
                'status_code' => $this->uptimeCheck->http_status_code,
                'checked_at' => $this->uptimeCheck->checked_at,
                'failure_reason' => $this->uptimeCheck->error_message,
                'content_check_passed' => $this->uptimeCheck->content_check_passed,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
