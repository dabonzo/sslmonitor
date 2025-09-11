<?php

declare(strict_types=1);

use App\Models\NotificationPreference;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('NotificationPreference Model', function () {
    test('notification preference can be created with valid data', function () {
        $preference = NotificationPreference::create([
            'user_id' => $this->user->id,
            'email_enabled' => true,
            'email_address' => 'test@example.com',
            'expiry_days_notice' => [7, 14, 30],
            'error_alerts' => true,
            'daily_digest' => false,
        ]);

        expect($preference)->toBeInstanceOf(NotificationPreference::class);
        expect($preference->user_id)->toBe($this->user->id);
        expect($preference->email_enabled)->toBeTrue();
        expect($preference->email_address)->toBe('test@example.com');
        expect($preference->expiry_days_notice)->toBe([7, 14, 30]);
        expect($preference->error_alerts)->toBeTrue();
        expect($preference->daily_digest)->toBeFalse();
    });

    test('notification preference belongs to user', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create();

        expect($preference->user)->toBeInstanceOf(User::class);
        expect($preference->user->id)->toBe($this->user->id);
    });

    test('user can have one notification preference', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create();

        expect($this->user->notificationPreference)->toBeInstanceOf(NotificationPreference::class);
        expect($this->user->notificationPreference->id)->toBe($preference->id);
    });

    test('email address is validated', function () {
        $this->expectException(\Exception::class);
        
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'email_enabled' => true,
            'email_address' => 'invalid-email',
        ]);
    });

    test('expiry days notice defaults to common intervals', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'expiry_days_notice' => null,
        ]);

        $preference->refresh();
        expect($preference->expiry_days_notice)->toBe([7, 14, 30]);
    });

    test('user_id is required', function () {
        expect(fn() => NotificationPreference::create([
            'email_enabled' => true,
            'email_address' => 'test@example.com',
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('can disable all notifications', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => false,
            'error_alerts' => false,
            'daily_digest' => false,
        ]);

        expect($preference->email_enabled)->toBeFalse();
        expect($preference->error_alerts)->toBeFalse();
        expect($preference->daily_digest)->toBeFalse();
    });

    test('should send expiry notification returns correct boolean', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => true,
            'expiry_days_notice' => [7, 14, 30],
        ]);

        expect($preference->shouldSendExpiryNotification(7))->toBeTrue();
        expect($preference->shouldSendExpiryNotification(14))->toBeTrue();
        expect($preference->shouldSendExpiryNotification(30))->toBeTrue();
        expect($preference->shouldSendExpiryNotification(5))->toBeFalse();
        expect($preference->shouldSendExpiryNotification(21))->toBeFalse();
    });

    test('should send expiry notification returns false when email disabled', function () {
        $preference = NotificationPreference::factory()->for($this->user)->create([
            'email_enabled' => false,
            'expiry_days_notice' => [7, 14, 30],
        ]);

        expect($preference->shouldSendExpiryNotification(7))->toBeFalse();
    });
});