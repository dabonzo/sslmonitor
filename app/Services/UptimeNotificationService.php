<?php

namespace App\Services;

use App\Jobs\SendUptimeNotificationJob;
use App\Mail\UptimeDownNotification;
use App\Mail\UptimeRecoveredNotification;
use App\Models\UptimeCheck;
use Illuminate\Support\Facades\Mail;

class UptimeNotificationService
{
    /**
     * Send a downtime notification for an uptime check
     */
    public function sendDowntimeNotification(UptimeCheck $uptimeCheck): void
    {
        $user = $uptimeCheck->website->user;
        $preference = $user->notificationPreference;

        if (! $preference || ! $preference->shouldSendUptimeNotification('downtime')) {
            return;
        }

        Mail::to($preference->email_address)
            ->send(new UptimeDownNotification($uptimeCheck));
    }

    /**
     * Send a recovery notification for an uptime check
     */
    public function sendRecoveryNotification(UptimeCheck $uptimeCheck): void
    {
        $user = $uptimeCheck->website->user;
        $preference = $user->notificationPreference;

        if (! $preference || ! $preference->shouldSendUptimeNotification('recovery')) {
            return;
        }

        Mail::to($preference->email_address)
            ->send(new UptimeRecoveredNotification($uptimeCheck));
    }

    /**
     * Send a content mismatch notification for an uptime check
     */
    public function sendContentMismatchNotification(UptimeCheck $uptimeCheck): void
    {
        $user = $uptimeCheck->website->user;
        $preference = $user->notificationPreference;

        if (! $preference || ! $preference->shouldSendUptimeNotification('content_mismatch')) {
            return;
        }

        Mail::to($preference->email_address)
            ->send(new UptimeDownNotification($uptimeCheck));
    }

    /**
     * Send a slow response notification for an uptime check
     */
    public function sendSlowResponseNotification(UptimeCheck $uptimeCheck): void
    {
        $user = $uptimeCheck->website->user;
        $preference = $user->notificationPreference;

        if (! $preference || ! $preference->shouldSendUptimeNotification('slow')) {
            return;
        }

        Mail::to($preference->email_address)
            ->send(new UptimeDownNotification($uptimeCheck));
    }

    /**
     * Queue a downtime notification for background processing
     */
    public function queueDowntimeNotification(UptimeCheck $uptimeCheck): void
    {
        SendUptimeNotificationJob::dispatch($uptimeCheck, 'downtime');
    }

    /**
     * Queue a recovery notification for background processing
     */
    public function queueRecoveryNotification(UptimeCheck $uptimeCheck): void
    {
        SendUptimeNotificationJob::dispatch($uptimeCheck, 'recovery');
    }
}
