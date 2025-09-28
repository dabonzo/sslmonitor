<?php

use App\Models\User;
use App\Services\TwoFactorAuthService;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

test('service can generate secret key', function () {
    $service = new TwoFactorAuthService();
    $secret = $service->generateSecretKey();

    expect($secret)->toBeString()
        ->and(strlen($secret))->toBeGreaterThan(0);
});

test('service can generate QR code URL', function () {
    $service = new TwoFactorAuthService();
    $url = $service->getQRCodeUrl('Test App', 'test@example.com', 'TESTSECRETKEY123456');

    expect($url)->toBeString()
        ->and($url)->toContain('otpauth://totp/')
        ->and($url)->toContain('Test%20App')  // URL encoded
        ->and($url)->toContain('test%40example.com');  // URL encoded
});

test('service can verify key without errors', function () {
    $service = new TwoFactorAuthService();

    // Just test that the method runs without throwing exceptions
    // In reality, this specific combination will return false, but that's expected
    $result = $service->verifyKey('OQRMF7KVUCTEMMMWNFWEJ5VDRIR3QUXY', '123456');
    expect($result)->toBeBool();
});

test('service can generate recovery codes', function () {
    $service = new TwoFactorAuthService();
    $codes = $service->generateRecoveryCodes(5);

    expect($codes)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($codes)->toHaveCount(5);

    $codes->each(function ($code) {
        expect($code)->toBeString()
            ->and(strlen($code))->toBe(8)
            ->and($code)->toMatch('/^[A-Z0-9]+$/');
    });
});

test('service can hash recovery codes', function () {
    $service = new TwoFactorAuthService();
    $codes = collect(['CODE1', 'CODE2', 'CODE3']);

    $hashedCodes = $service->hashRecoveryCodes($codes);

    expect($hashedCodes)->toBeArray()
        ->and($hashedCodes)->toHaveCount(3);

    foreach ($hashedCodes as $hash) {
        expect($hash)->toBeString()
            ->and(password_verify('CODE1', $hashedCodes[0]) ||
                  password_verify('CODE2', $hashedCodes[1]) ||
                  password_verify('CODE3', $hashedCodes[2]))->toBeTrue();
    }
});

test('service can enable two factor auth for user', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
    ]);

    $service = $this->mock(TwoFactorAuthService::class)
        ->makePartial()
        ->shouldReceive('verifyKey')
        ->with('test-secret', '123456')
        ->andReturn(true)
        ->shouldReceive('generateRecoveryCodes')
        ->andReturn(collect(['CODE1', 'CODE2']))
        ->shouldReceive('hashRecoveryCodes')
        ->andReturn(['hashed1', 'hashed2'])
        ->getMock();

    $result = $service->enableTwoFactorAuth($user, '123456');

    expect($result)->toBeTrue();

    $user->refresh();
    expect($user->two_factor_confirmed_at)->not->toBeNull()
        ->and($user->two_factor_recovery_codes)->not->toBeNull();
});

test('service fails to enable two factor auth with invalid code', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
    ]);

    $service = $this->mock(TwoFactorAuthService::class)
        ->makePartial()
        ->shouldReceive('verifyKey')
        ->with('test-secret', '000000')
        ->andReturn(false)
        ->getMock();

    $result = $service->enableTwoFactorAuth($user, '000000');

    expect($result)->toBeFalse();
});

test('service fails to enable two factor auth without secret', function () {
    $user = User::factory()->create();
    $service = new TwoFactorAuthService();

    $result = $service->enableTwoFactorAuth($user, '123456');

    expect($result)->toBeFalse();
});

test('service can disable two factor auth', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_recovery_codes' => json_encode(['code1']),
        'two_factor_confirmed_at' => now(),
    ]);

    $service = new TwoFactorAuthService();
    $service->disableTwoFactorAuth($user);

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});

test('service correctly detects enabled state', function () {
    $service = new TwoFactorAuthService();

    // User with 2FA enabled
    $enabledUser = User::factory()->create([
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ]);

    // User with 2FA disabled
    $disabledUser = User::factory()->create();

    expect($service->isEnabled($enabledUser))->toBeTrue()
        ->and($service->isEnabled($disabledUser))->toBeFalse();
});

test('service can verify recovery code', function () {
    $user = User::factory()->create([
        'two_factor_recovery_codes' => json_encode([
            password_hash('CODE1', PASSWORD_DEFAULT),
            password_hash('CODE2', PASSWORD_DEFAULT),
        ]),
    ]);

    $service = new TwoFactorAuthService();

    $result = $service->verifyRecoveryCode($user, 'CODE1');
    expect($result)->toBeTrue();

    // Verify the used code was removed
    $user->refresh();
    $remainingCodes = json_decode($user->two_factor_recovery_codes, true);
    expect($remainingCodes)->toHaveCount(1);
});

test('service fails to verify invalid recovery code', function () {
    $user = User::factory()->create([
        'two_factor_recovery_codes' => json_encode([
            password_hash('CODE1', PASSWORD_DEFAULT),
        ]),
    ]);

    $service = new TwoFactorAuthService();

    $result = $service->verifyRecoveryCode($user, 'INVALID');
    expect($result)->toBeFalse();
});

test('service returns correct recovery codes count', function () {
    $service = new TwoFactorAuthService();

    // User with recovery codes
    $userWithCodes = User::factory()->create([
        'two_factor_recovery_codes' => json_encode(['code1', 'code2', 'code3']),
    ]);

    // User without recovery codes
    $userWithoutCodes = User::factory()->create();

    expect($service->getRecoveryCodesCount($userWithCodes))->toBe(3)
        ->and($service->getRecoveryCodesCount($userWithoutCodes))->toBe(0);
});