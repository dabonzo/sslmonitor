<?php

namespace App\Jobs;

use App\Models\SslCheck;
use App\Services\SslNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSslNotificationJob implements ShouldQueue
{
    use Queueable;

    public SslCheck $sslCheck;

    public string $notificationType;

    /**
     * Create a new job instance.
     */
    public function __construct(SslCheck $sslCheck, string $notificationType)
    {
        $this->sslCheck = $sslCheck;
        $this->notificationType = $notificationType;
        // Use default queue for simplicity and reliability
    }

    /**
     * Execute the job.
     */
    public function handle(SslNotificationService $service): void
    {
        match ($this->notificationType) {
            'expiry' => $service->sendExpiryNotification($this->sslCheck),
            'error' => $service->sendErrorNotification($this->sslCheck),
            'expired' => $service->sendExpiredNotification($this->sslCheck),
            default => throw new \InvalidArgumentException("Unknown notification type: {$this->notificationType}"),
        };
    }
}
