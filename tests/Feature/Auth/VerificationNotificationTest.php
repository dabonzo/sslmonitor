<?php

use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    // Clear rate limiter cache to prevent 429 responses
    \Illuminate\Support\Facades\Cache::flush();
});

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('home'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('does not send verification notification if email is verified', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard', absolute: false));

    Notification::assertNothingSent();
});