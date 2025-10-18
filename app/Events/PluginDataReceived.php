<?php

namespace App\Events;

use App\Models\PluginConfiguration;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PluginDataReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PluginConfiguration $plugin,
        public array $data,
        public string $dataType,
        public array $metadata = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->plugin->user_id),
            new PrivateChannel('plugin.'.$this->plugin->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'plugin-data-received';
    }

    public function broadcastWith(): array
    {
        return [
            'plugin' => [
                'id' => $this->plugin->id,
                'plugin_name' => $this->plugin->plugin_name,
                'plugin_type' => $this->plugin->plugin_type,
                'status' => $this->plugin->status,
            ],
            'data' => $this->data,
            'data_type' => $this->dataType,
            'metadata' => $this->metadata,
            'timestamp' => now()->toISOString(),
        ];
    }
}
