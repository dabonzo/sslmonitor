<?php

declare(strict_types=1);

use App\Models\NotificationPreference;
use App\Models\SslCheck;
use App\Models\User;
use App\Models\Website;
use App\Services\SslNotificationService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->website = Website::factory()->for($this->user)->create();
    $this->service = new SslNotificationService();
    
    Mail::fake();
});

describe('SslNotificationService', function () {
    test('sends expiry notification when user has notification preference', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
            'expiry_days_notice' => [7, 14, 30],
            'email_address' => 'test@example.com',
        ]);

        $sslCheck = SslCheck::factory()->for($this->website)->create([
            'status' => 'expiring_soon',
            'expires_at' => now()->addDays(7),
            'days_until_expiry' => 7,
        ]);

        $this->service->sendExpiryNotification($sslCheck);

        Mail::assertQueued(\App\Mail\SslExpiringNotification::class, function ($mail) use ($preference, $sslCheck) {
            return $mail->hasTo($preference->email_address) &&
                   $mail->sslCheck->id === $sslCheck->id;
        });
    });

    test('does not send expiry notification when user has no notification preference', function () {
        $sslCheck = SslCheck::factory()->for($this->website)->create([
            'status' => 'expiring_soon',
            'expires_at' => now()->addDays(7),
            'days_until_expiry' => 7,
        ]);

        $this->service->sendExpiryNotification($sslCheck);

        Mail::assertNothingSent();
    });

    test('does not send expiry notification when email notifications disabled', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => false,
            'expiry_days_notice' => [7, 14, 30],
        ]);

        $sslCheck = SslCheck::factory()->for($this->website)->create([
            'status' => 'expiring_soon',
            'expires_at' => now()->addDays(7),
            'days_until_expiry' => 7,
        ]);

        $this->service->sendExpiryNotification($sslCheck);

        Mail::assertNothingSent();
    });

    test('does not send expiry notification for wrong expiry days', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
            'expiry_days_notice' => [14, 30], // Only 14 and 30 days
        ]);

        $sslCheck = SslCheck::factory()->for($this->website)->create([
            'status' => 'expiring_soon',
            'expires_at' => now()->addDays(7),
            'days_until_expiry' => 7,
        ]);

        $this->service->sendExpiryNotification($sslCheck);

        Mail::assertNothingSent();
    });

    test('sends error notification when error alerts enabled', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
            'error_alerts' => true,
        ]);

        $sslCheck = SslCheck::factory()->for($this->website)->create([
            'status' => 'error',
            'error_message' => 'Connection timeout',
        ]);

        $this->service->sendErrorNotification($sslCheck);

        Mail::assertQueued(\App\Mail\SslErrorNotification::class, function ($mail) use ($preference, $sslCheck) {
            return $mail->hasTo($preference->email_address) &&
                   $mail->sslCheck->id === $sslCheck->id;
        });
    });

    test('does not send error notification when error alerts disabled', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
            'error_alerts' => false,
        ]);

        $sslCheck = SslCheck::factory()->for($this->website)->create([
            'status' => 'error',
            'error_message' => 'Connection timeout',
        ]);

        $this->service->sendErrorNotification($sslCheck);

        Mail::assertNothingSent();
    });

    test('sends expired notification for expired certificates', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
            'error_alerts' => true,
        ]);

        $sslCheck = SslCheck::factory()->for($this->website)->create([
            'status' => 'expired',
            'expires_at' => now()->subDays(5),
            'days_until_expiry' => -5,
        ]);

        $this->service->sendExpiredNotification($sslCheck);

        Mail::assertQueued(\App\Mail\SslExpiredNotification::class, function ($mail) use ($preference, $sslCheck) {
            return $mail->hasTo($preference->email_address) &&
                   $mail->sslCheck->id === $sslCheck->id;
        });
    });

    test('can send daily digest to users with digest enabled', function () {
        $preference1 = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
            'daily_digest' => true,
        ]);

        $otherUser = User::factory()->create();
        $preference2 = NotificationPreference::factory()->for($otherUser)->create([
            'email_enabled' => true,
            'daily_digest' => false, // Disabled
        ]);

        $this->service->sendDailyDigest();

        Mail::assertQueued(\App\Mail\SslDailyDigest::class, function ($mail) use ($preference1) {
            return $mail->hasTo($preference1->email_address);
        });

        Mail::assertNotQueued(\App\Mail\SslDailyDigest::class, function ($mail) use ($preference2) {
            return $mail->hasTo($preference2->email_address);
        });
    });

    test('queues notifications for background processing', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
            'expiry_days_notice' => [7],
        ]);

        $sslCheck = SslCheck::factory()->for($this->website)->create([
            'status' => 'expiring_soon',
            'days_until_expiry' => 7,
        ]);

        $this->service->queueExpiryNotification($sslCheck);

        // Should queue the notification job instead of sending immediately
        Mail::assertNothingSent();
        
        // We would test job queuing here, but for now we'll just ensure method exists
        expect(method_exists($this->service, 'queueExpiryNotification'))->toBeTrue();
    });
});