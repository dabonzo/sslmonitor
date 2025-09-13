<?php

declare(strict_types=1);

use App\Models\NotificationPreference;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('NotificationPreference Uptime Notifications', function () {
    test('notification preference has default uptime alert settings enabled', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create();

        expect($preference->uptime_alerts)->toBeTrue();
        expect($preference->downtime_recovery_alerts)->toBeTrue();
        expect($preference->slow_response_alerts)->toBeTrue();
        expect($preference->content_mismatch_alerts)->toBeTrue();
    });

    test('should send uptime notification returns correct boolean for downtime', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
            'uptime_alerts' => true,
        ]);

        expect($preference->shouldSendUptimeNotification('downtime'))->toBeTrue();
        expect($preference->shouldSendUptimeNotification('down'))->toBeTrue();

        $preference->update(['uptime_alerts' => false]);
        expect($preference->shouldSendUptimeNotification('downtime'))->toBeFalse();
    });

    test('should send uptime notification returns correct boolean for recovery', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
            'downtime_recovery_alerts' => true,
        ]);

        expect($preference->shouldSendUptimeNotification('recovery'))->toBeTrue();
        expect($preference->shouldSendUptimeNotification('up'))->toBeTrue();

        $preference->update(['downtime_recovery_alerts' => false]);
        expect($preference->shouldSendUptimeNotification('recovery'))->toBeFalse();
    });

    test('should send uptime notification returns correct boolean for slow response', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
            'slow_response_alerts' => true,
        ]);

        expect($preference->shouldSendUptimeNotification('slow'))->toBeTrue();

        $preference->update(['slow_response_alerts' => false]);
        expect($preference->shouldSendUptimeNotification('slow'))->toBeFalse();
    });

    test('should send uptime notification returns correct boolean for content mismatch', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
            'content_mismatch_alerts' => true,
        ]);

        expect($preference->shouldSendUptimeNotification('content_mismatch'))->toBeTrue();

        $preference->update(['content_mismatch_alerts' => false]);
        expect($preference->shouldSendUptimeNotification('content_mismatch'))->toBeFalse();
    });

    test('should send uptime notification returns false when email disabled', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => false,
            'uptime_alerts' => true,
            'downtime_recovery_alerts' => true,
            'slow_response_alerts' => true,
            'content_mismatch_alerts' => true,
        ]);

        expect($preference->shouldSendUptimeNotification('downtime'))->toBeFalse();
        expect($preference->shouldSendUptimeNotification('recovery'))->toBeFalse();
        expect($preference->shouldSendUptimeNotification('slow'))->toBeFalse();
        expect($preference->shouldSendUptimeNotification('content_mismatch'))->toBeFalse();
    });

    test('should send uptime notification returns false for unknown notification type', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
        ]);

        expect($preference->shouldSendUptimeNotification('unknown_type'))->toBeFalse();
    });

    test('can disable specific uptime alert types independently', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
            'uptime_alerts' => true,
            'downtime_recovery_alerts' => false,
            'slow_response_alerts' => true,
            'content_mismatch_alerts' => false,
        ]);

        expect($preference->shouldSendUptimeNotification('downtime'))->toBeTrue();
        expect($preference->shouldSendUptimeNotification('recovery'))->toBeFalse();
        expect($preference->shouldSendUptimeNotification('slow'))->toBeTrue();
        expect($preference->shouldSendUptimeNotification('content_mismatch'))->toBeFalse();
    });
});
