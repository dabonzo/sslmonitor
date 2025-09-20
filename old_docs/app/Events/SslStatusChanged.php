<?php

namespace App\Events;

use App\Models\SslCheck;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SslStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SslCheck $sslCheck,
        public string $previousStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ssl-monitoring'),
            new PrivateChannel('ssl-monitoring.website.'.$this->sslCheck->website_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ssl.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'ssl_check' => [
                'id' => $this->sslCheck->id,
                'website_id' => $this->sslCheck->website_id,
                'website_url' => $this->sslCheck->website->url,
                'status' => $this->sslCheck->status,
                'previous_status' => $this->previousStatus,
                'expires_at' => $this->sslCheck->expires_at,
                'days_until_expiry' => $this->sslCheck->days_until_expiry,
                'checked_at' => $this->sslCheck->checked_at,
                'issuer' => $this->sslCheck->issuer,
                'fingerprint' => $this->sslCheck->fingerprint,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
