<?php

/**
 * Password Reset Security Tests
 *
 * These tests ensure that the password reset functionality:
 * 1. Works correctly for valid users
 * 2. Prevents user enumeration attacks by showing identical messages for existing/non-existing users
 * 3. Only sends reset emails to users that actually exist
 * 4. Returns proper status codes from Laravel's Password facade
 */

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get(route('password.reset', $notification->token));

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post(route('password.store'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});

test('password cannot be reset with invalid token', function () {
    $user = User::factory()->create();

    $response = $this->post(route('password.store'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('email');
});

test('password reset request with non-existent email prevents user enumeration', function () {
    Notification::fake();

    // Request password reset for non-existent email
    $response = $this->post(route('password.email'), [
        'email' => 'nonexistent@example.com'
    ]);

    // Should show success message to prevent user enumeration
    $response
        ->assertStatus(302)
        ->assertSessionHas('status', 'We have emailed your password reset link.');

    // Should NOT send any notification for non-existent user
    Notification::assertNothingSent();
});

test('password reset request shows same message for existing and non-existent users', function () {
    Notification::fake();

    // Create a real user
    $user = User::factory()->create();

    // Test with existing user
    $existingUserResponse = $this->post(route('password.email'), [
        'email' => $user->email
    ]);

    // Test with non-existent user
    $nonExistentResponse = $this->post(route('password.email'), [
        'email' => 'fake@example.com'
    ]);

    // Both should show the same success message
    $existingUserResponse->assertSessionHas('status', 'We have emailed your password reset link.');
    $nonExistentResponse->assertSessionHas('status', 'We have emailed your password reset link.');

    // Only the real user should get a notification
    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertSentToTimes($user, ResetPassword::class, 1);
});

test('password facade returns correct status codes', function () {
    Notification::fake();

    // Create a real user
    $user = User::factory()->create();

    // Test with existing user - should return RESET_LINK_SENT
    $existingUserStatus = Password::sendResetLink(['email' => $user->email]);
    expect($existingUserStatus)->toBe(Password::RESET_LINK_SENT);

    // Test with non-existent user - should return INVALID_USER
    $nonExistentStatus = Password::sendResetLink(['email' => 'fake@nowhere.com']);
    expect($nonExistentStatus)->toBe(Password::INVALID_USER);

    // Only the real user should get a notification
    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertSentToTimes($user, ResetPassword::class, 1);
});