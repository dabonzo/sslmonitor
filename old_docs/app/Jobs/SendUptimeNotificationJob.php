<?php

namespace App\Jobs;

use App\Models\UptimeCheck;
use App\Services\UptimeNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendUptimeNotificationJob implements ShouldQueue
{
    use Queueable;

    public UptimeCheck $uptimeCheck;

    public string $notificationType;

    /**
     * Create a new job instance.
     */
    public function __construct(UptimeCheck $uptimeCheck, string $notificationType)
    {
        $this->uptimeCheck = $uptimeCheck;
        $this->notificationType = $notificationType;
        // Use default queue for simplicity and reliability
    }

    /**
     * Execute the job.
     */
    public function handle(UptimeNotificationService $service): void
    {
        match ($this->notificationType) {
            'downtime' => $service->sendDowntimeNotification($this->uptimeCheck),
            'recovery' => $service->sendRecoveryNotification($this->uptimeCheck),
            default => throw new \InvalidArgumentException("Unknown notification type: {$this->notificationType}"),
        };
    }
}
