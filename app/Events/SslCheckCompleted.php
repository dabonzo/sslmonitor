<?php

namespace App\Events;

use App\Models\SslCheck;
use App\Models\Website;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SslCheckCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SslCheck $sslCheck,
        public Website $website,
        public array $metrics = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->website->user_id),
            new PrivateChannel('website.' . $this->website->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ssl-check-completed';
    }

    public function broadcastWith(): array
    {
        return [
            'ssl_check' => [
                'id' => $this->sslCheck->id,
                'status' => $this->sslCheck->status,
                'checked_at' => $this->sslCheck->checked_at,
                'expires_at' => $this->sslCheck->expires_at,
                'days_until_expiry' => $this->sslCheck->days_until_expiry,
                'is_valid' => $this->sslCheck->is_valid,
                'error_message' => $this->sslCheck->error_message,
                'check_source' => $this->sslCheck->check_source,
            ],
            'website' => [
                'id' => $this->website->id,
                'name' => $this->website->name,
                'url' => $this->website->url,
            ],
            'metrics' => $this->metrics,
            'timestamp' => now()->toISOString(),
        ];
    }
}
