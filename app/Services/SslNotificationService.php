<?php

namespace App\Services;

use App\Jobs\SendSslNotificationJob;
use App\Mail\SslDailyDigest;
use App\Mail\SslErrorNotification;
use App\Mail\SslExpiredNotification;
use App\Mail\SslExpiringNotification;
use App\Models\NotificationPreference;
use App\Models\SslCheck;
use Illuminate\Support\Facades\Mail;

class SslNotificationService
{
    /**
     * Send an expiry notification for an SSL certificate
     */
    public function sendExpiryNotification(SslCheck $sslCheck): void
    {
        $user = $sslCheck->website->user;
        $preference = $user->notificationPreference;

        if (! $preference || ! $preference->shouldSendExpiryNotification($sslCheck->days_until_expiry)) {
            return;
        }

        Mail::to($preference->email_address)
            ->send(new SslExpiringNotification($sslCheck));
    }

    /**
     * Send an error notification for an SSL certificate
     */
    public function sendErrorNotification(SslCheck $sslCheck): void
    {
        $user = $sslCheck->website->user;
        $preference = $user->notificationPreference;

        if (! $preference || ! $preference->email_enabled || ! $preference->error_alerts) {
            return;
        }

        Mail::to($preference->email_address)
            ->send(new SslErrorNotification($sslCheck));
    }

    /**
     * Send an expired notification for an SSL certificate
     */
    public function sendExpiredNotification(SslCheck $sslCheck): void
    {
        $user = $sslCheck->website->user;
        $preference = $user->notificationPreference;

        if (! $preference || ! $preference->email_enabled || ! $preference->error_alerts) {
            return;
        }

        Mail::to($preference->email_address)
            ->send(new SslExpiredNotification($sslCheck));
    }

    /**
     * Send daily digest to all users who have it enabled
     */
    public function sendDailyDigest(): void
    {
        $preferences = NotificationPreference::where('email_enabled', true)
            ->where('daily_digest', true)
            ->with('user.websites')
            ->get();

        foreach ($preferences as $preference) {
            Mail::to($preference->email_address)
                ->send(new SslDailyDigest($preference->user));
        }
    }

    /**
     * Queue an expiry notification for background processing
     */
    public function queueExpiryNotification(SslCheck $sslCheck): void
    {
        SendSslNotificationJob::dispatch($sslCheck, 'expiry');
    }

    /**
     * Queue an error notification for background processing
     */
    public function queueErrorNotification(SslCheck $sslCheck): void
    {
        SendSslNotificationJob::dispatch($sslCheck, 'error');
    }

    /**
     * Queue an expired notification for background processing
     */
    public function queueExpiredNotification(SslCheck $sslCheck): void
    {
        SendSslNotificationJob::dispatch($sslCheck, 'expired');
    }
}
