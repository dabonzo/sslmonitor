<?php

use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

use App\Models\User;
use App\Services\TwoFactorAuthService;
use Inertia\Testing\AssertableInertia as Assert;

test('two factor settings page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('two-factor.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Settings/TwoFactor')
            ->where('twoFactorEnabled', false)
            ->where('recoveryCodes', 0)
            ->where('qrCodeSvg', null)
        );
});

test('two factor settings page shows enabled state for users with 2FA', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => json_encode(['code1', 'code2']),
    ]);

    $this->mock(TwoFactorAuthService::class)
        ->shouldReceive('isEnabled')
        ->with($user)
        ->andReturn(true)
        ->shouldReceive('getRecoveryCodesCount')
        ->with($user)
        ->andReturn(2);

    $this->actingAs($user)
        ->get(route('two-factor.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Settings/TwoFactor')
            ->where('twoFactorEnabled', true)
            ->where('recoveryCodes', 2)
        );
});

test('two factor can be initiated for new setup', function () {
    $user = User::factory()->create();

    $this->mock(TwoFactorAuthService::class)
        ->shouldReceive('generateSecretKey')
        ->andReturn('test-secret-key');

    $response = $this->actingAs($user)
        ->post(route('two-factor.store'));

    $response->assertRedirect()
        ->assertSessionHas('status', '2FA setup initiated. Please scan the QR code.');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_secret' => 'test-secret-key',
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
    ]);
});

test('two factor can be confirmed with valid code', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
    ]);

    $this->mock(TwoFactorAuthService::class)
        ->shouldReceive('isEnabled')
        ->with($user)
        ->andReturn(false)
        ->shouldReceive('enableTwoFactorAuth')
        ->with($user, '123456')
        ->andReturn(true);

    $response = $this->actingAs($user)
        ->post(route('two-factor.confirm'), [
            'code' => '123456',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('status', 'Two-factor authentication has been enabled.');
});

test('two factor confirmation fails with invalid code', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
    ]);

    $this->mock(TwoFactorAuthService::class)
        ->shouldReceive('isEnabled')
        ->with($user)
        ->andReturn(false)
        ->shouldReceive('enableTwoFactorAuth')
        ->with($user, '000000')
        ->andReturn(false);

    $response = $this->actingAs($user)
        ->post(route('two-factor.confirm'), [
            'code' => '000000',
        ]);

    $response->assertSessionHasErrors('code');
});

test('two factor confirmation fails when not set up', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('two-factor.confirm'), [
            'code' => '123456',
        ]);

    $response->assertSessionHasErrors('code');
});

test('two factor confirmation fails when already enabled', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
    ]);

    $this->mock(TwoFactorAuthService::class)
        ->shouldReceive('isEnabled')
        ->with($user)
        ->andReturn(true);

    $response = $this->actingAs($user)
        ->post(route('two-factor.confirm'), [
            'code' => '123456',
        ]);

    $response->assertSessionHasErrors('code');
});

test('two factor can be disabled with password confirmation', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
    ]);

    $this->mock(TwoFactorAuthService::class)
        ->shouldReceive('disableTwoFactorAuth')
        ->with($user)
        ->once();

    $response = $this->actingAs($user)
        ->delete(route('two-factor.destroy'), [
            'password' => 'password',
        ]);

    $response->assertRedirect()
        ->assertSessionHas('status', 'Two-factor authentication has been disabled.');
});

test('two factor disable requires valid password', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->delete(route('two-factor.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response->assertSessionHasErrors('password');
});

test('recovery codes can be generated for enabled users', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
    ]);

    $recoveryCodes = collect(['CODE1', 'CODE2', 'CODE3']);
    $hashedCodes = ['hashed1', 'hashed2', 'hashed3'];

    $this->mock(TwoFactorAuthService::class)
        ->shouldReceive('isEnabled')
        ->with($user)
        ->andReturn(true)
        ->shouldReceive('generateRecoveryCodes')
        ->andReturn($recoveryCodes)
        ->shouldReceive('hashRecoveryCodes')
        ->with($recoveryCodes)
        ->andReturn($hashedCodes);

    $response = $this->actingAs($user)
        ->get(route('two-factor.recovery-codes'));

    $response->assertOk()
        ->assertJson([
            'recovery_codes' => ['CODE1', 'CODE2', 'CODE3'],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_recovery_codes' => json_encode($hashedCodes),
    ]);
});

test('recovery codes cannot be generated for disabled users', function () {
    $user = User::factory()->create();

    $this->mock(TwoFactorAuthService::class)
        ->shouldReceive('isEnabled')
        ->with($user)
        ->andReturn(false);

    $response = $this->actingAs($user)
        ->get(route('two-factor.recovery-codes'));

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Two-factor authentication is not enabled.',
        ]);
});
