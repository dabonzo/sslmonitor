<?php

declare(strict_types=1);

use App\Jobs\SendUptimeNotificationJob;
use App\Mail\UptimeDownNotification;
use App\Mail\UptimeRecoveredNotification;
use App\Models\NotificationPreference;
use App\Models\UptimeCheck;
use App\Models\User;
use App\Models\Website;
use App\Services\UptimeNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->website = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
    ]);

    $this->notificationPreference = NotificationPreference::factory()->create([
        'user_id' => $this->user->id,
        'email_enabled' => true,
        'email_address' => 'test@example.com',
        'error_alerts' => true,
    ]);

    $this->service = new UptimeNotificationService;
});

describe('UptimeNotificationService', function () {
    test('can send downtime notification when uptime check fails', function () {
        Mail::fake();

        $uptimeCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'down',
            'http_status_code' => 503,
            'response_time_ms' => null,
            'error_message' => 'Service Unavailable',
            'checked_at' => now(),
        ]);

        $this->service->sendDowntimeNotification($uptimeCheck);

        Mail::assertQueued(UptimeDownNotification::class, function ($mail) use ($uptimeCheck) {
            return $mail->uptimeCheck->id === $uptimeCheck->id;
        });
    });

    test('does not send downtime notification if email disabled', function () {
        Mail::fake();

        $this->notificationPreference->update(['email_enabled' => false]);

        $uptimeCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'down',
        ]);

        $this->service->sendDowntimeNotification($uptimeCheck);

        Mail::assertNotQueued(UptimeDownNotification::class);
    });

    test('does not send downtime notification if uptime alerts disabled', function () {
        Mail::fake();

        $this->notificationPreference->update(['uptime_alerts' => false]);

        $uptimeCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'down',
        ]);

        $this->service->sendDowntimeNotification($uptimeCheck);

        Mail::assertNotQueued(UptimeDownNotification::class);
    });

    test('can send recovery notification when website comes back up', function () {
        Mail::fake();

        $uptimeCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'http_status_code' => 200,
            'response_time_ms' => 450,
            'checked_at' => now(),
        ]);

        $this->service->sendRecoveryNotification($uptimeCheck);

        Mail::assertQueued(UptimeRecoveredNotification::class, function ($mail) use ($uptimeCheck) {
            return $mail->uptimeCheck->id === $uptimeCheck->id;
        });
    });

    test('does not send recovery notification if recovery alerts disabled', function () {
        Mail::fake();

        $this->notificationPreference->update(['downtime_recovery_alerts' => false]);

        $uptimeCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'up',
        ]);

        $this->service->sendRecoveryNotification($uptimeCheck);

        Mail::assertNotQueued(UptimeRecoveredNotification::class);
    });

    test('can queue downtime notification for background processing', function () {
        Queue::fake();

        $uptimeCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'down',
        ]);

        $this->service->queueDowntimeNotification($uptimeCheck);

        Queue::assertPushed(SendUptimeNotificationJob::class, function ($job) use ($uptimeCheck) {
            return $job->uptimeCheck->id === $uptimeCheck->id
                && $job->notificationType === 'downtime';
        });
    });

    test('can queue recovery notification for background processing', function () {
        Queue::fake();

        $uptimeCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'up',
        ]);

        $this->service->queueRecoveryNotification($uptimeCheck);

        Queue::assertPushed(SendUptimeNotificationJob::class, function ($job) use ($uptimeCheck) {
            return $job->uptimeCheck->id === $uptimeCheck->id
                && $job->notificationType === 'recovery';
        });
    });

    test('sends content mismatch notification for hosting company takeovers', function () {
        Mail::fake();

        $uptimeCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'content_mismatch',
            'http_status_code' => 200,
            'response_time_ms' => 350,
            'error_message' => 'Expected content not found: Welcome to Our Store',
            'checked_at' => now(),
        ]);

        $this->service->sendContentMismatchNotification($uptimeCheck);

        Mail::assertQueued(UptimeDownNotification::class, function ($mail) use ($uptimeCheck) {
            return $mail->uptimeCheck->id === $uptimeCheck->id;
        });
    });

    test('sends slow response notification for performance issues', function () {
        Mail::fake();

        $uptimeCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'slow',
            'http_status_code' => 200,
            'response_time_ms' => 8500, // 8.5 seconds
            'error_message' => 'Response time 8500ms exceeds threshold of 5000ms',
            'checked_at' => now(),
        ]);

        $this->service->sendSlowResponseNotification($uptimeCheck);

        Mail::assertQueued(UptimeDownNotification::class, function ($mail) use ($uptimeCheck) {
            return $mail->uptimeCheck->id === $uptimeCheck->id;
        });
    });

    test('does not send notification if user has no notification preferences', function () {
        Mail::fake();

        $this->notificationPreference->delete();

        $uptimeCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'down',
        ]);

        $this->service->sendDowntimeNotification($uptimeCheck);

        Mail::assertNotQueued(UptimeDownNotification::class);
    });

    test('uses correct email address from notification preferences', function () {
        Mail::fake();

        $customEmail = 'custom-alerts@company.com';
        $this->notificationPreference->update(['email_address' => $customEmail]);

        $uptimeCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'down',
        ]);

        $this->service->sendDowntimeNotification($uptimeCheck);

        Mail::assertQueued(UptimeDownNotification::class, function ($mail) use ($customEmail) {
            return $mail->hasTo($customEmail);
        });
    });
});
