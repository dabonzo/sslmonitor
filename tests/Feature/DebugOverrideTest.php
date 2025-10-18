<?php

use App\Models\DebugOverride;
use App\Models\User;
use App\Models\Website;

// TDD RED PHASE: Test with real data from MariaDB database
test('debug ssl override functionality works with real websites', function () {
    // RED: This test should fail initially because we haven't implemented the functionality

    // Configure this test to use MariaDB instead of SQLite
    config(['database.default' => 'mariadb']);
    config(['database.connections.mariadb.database' => 'laravel']);

    // 1. Use the real debug user and real websites from the database
    $user = User::where('email', 'bonzo@konjscina.com')->first();
    expect($user)->not->toBeNull('Debug user should exist in real database');

    // Get a real website with SSL monitoring
    $website = Website::where('user_id', $user->id)
        ->where('ssl_monitoring_enabled', true)
        ->first();
    expect($website)->not->toBeNull('Should have at least one real website with SSL monitoring');

    // Clean up any existing debug overrides for this website to avoid confusion
    DebugOverride::where('targetable_type', Website::class)
        ->where('targetable_id', $website->id)
        ->where('user_id', $user->id)
        ->delete();

    // Get the real monitor for this website
    $monitor = $website->getSpatieMonitor();
    expect($monitor)->not->toBeNull('Real website should have a monitor');
    expect($monitor->certificate_expiration_date)->not->toBeNull('Monitor should have SSL expiry date');

    // 2. Test creating an SSL override
    $override = DebugOverride::create([
        'user_id' => $user->id,
        'module_type' => 'ssl_expiry',
        'targetable_type' => Website::class,
        'targetable_id' => $website->id,
        'override_data' => [
            'expiry_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'original_expiry' => $monitor->certificate_expiration_date,
            'reason' => 'TDD test override',
        ],
        'is_active' => true,
    ]);

    // 3. Test that the override affects the website's effective expiry
    $effectiveExpiry = $website->getEffectiveSslExpiryDate($user->id);
    $daysRemaining = $website->getDaysRemaining($user->id);

    // Debug: Let's see what's actually happening
    dump([
        'website_id' => $website->id,
        'website_name' => $website->name,
        'override_expiry_date' => $override->override_data['expiry_date'],
        'expected_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
        'effective_expiry_date' => $effectiveExpiry?->format('Y-m-d H:i:s'),
        'days_remaining' => $daysRemaining,
        'real_expiry' => $monitor->certificate_expiration_date?->format('Y-m-d H:i:s'),
        'override_found' => $website->getDebugOverride('ssl_expiry', $user->id)?->id,
    ]);

    // Assertions - these should work when functionality is implemented
    expect($override)->toBeInstanceOf(DebugOverride::class);
    expect($override->module_type)->toBe('ssl_expiry');
    expect($override->is_active)->toBeTrue();

    expect($effectiveExpiry)->not->toBeNull();
    // TDD: This should use the MOST RECENT override, not the first one found
    expect($effectiveExpiry->format('Y-m-d'))->toBe(now()->addDays(7)->format('Y-m-d'));
    expect($daysRemaining)->toBeGreaterThanOrEqual(6); // Allow for time differences

    // 4. Test cleanup functionality (this needs investigation - TDD reveals a bug)
    $override->deactivate();

    $newEffectiveExpiry = $website->getEffectiveSslExpiryDate($user->id);
    $newDaysRemaining = $website->getDaysRemaining($user->id);

    // Debug: Show what happens after deactivation
    dump([
        'after_deactivation' => [
            'override_id' => $override->id,
            'override_is_active' => $override->refresh()->is_active,
            'new_effective_expiry' => $newEffectiveExpiry?->format('Y-m-d H:i:s'),
            'new_days_remaining' => $newDaysRemaining,
            'original_expiry' => $monitor->certificate_expiration_date?->format('Y-m-d H:i:s'),
        ],
    ]);

    // TDD: After deactivation, should revert to original SSL expiry
    expect($override->refresh()->is_active)->toBeFalse();
    expect($newEffectiveExpiry)->not->toBeNull();
    expect($newEffectiveExpiry->format('Y-m-d'))->toBe($monitor->certificate_expiration_date->format('Y-m-d')); // Original expiry date
    expect($newDaysRemaining)->toBeGreaterThan(0); // Should be positive days remaining
});

// TDD: Test multiple overrides scenario
test('multiple ssl overrides work with correct precedence', function () {
    config(['database.default' => 'mariadb']);
    config(['database.connections.mariadb.database' => 'laravel']);

    $user = User::where('email', 'bonzo@konjscina.com')->first();
    $website = Website::where('user_id', $user->id)
        ->where('ssl_monitoring_enabled', true)
        ->first();

    // Clean up ALL existing overrides for this website to ensure clean test
    DebugOverride::where('targetable_type', Website::class)
        ->where('targetable_id', $website->id)
        ->delete();

    $monitor = $website->getSpatieMonitor();

    // Create multiple overrides with different dates
    $override1 = DebugOverride::create([
        'user_id' => $user->id,
        'module_type' => 'ssl_expiry',
        'targetable_type' => Website::class,
        'targetable_id' => $website->id,
        'override_data' => [
            'expiry_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'original_expiry' => $monitor->certificate_expiration_date,
            'reason' => 'First override',
        ],
        'is_active' => true,
    ]);

    $override2 = DebugOverride::create([
        'user_id' => $user->id,
        'module_type' => 'ssl_expiry',
        'targetable_type' => Website::class,
        'targetable_id' => $website->id,
        'override_data' => [
            'expiry_date' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'original_expiry' => $monitor->certificate_expiration_date,
            'reason' => 'Second override',
        ],
        'is_active' => true,
    ]);

    // Should use the most recent override (10 days)
    $effectiveExpiry = $website->getEffectiveSslExpiryDate($user->id);
    $daysRemaining = $website->getDaysRemaining($user->id);

    expect($effectiveExpiry->format('Y-m-d'))->toBe(now()->addDays(10)->format('Y-m-d'));
    expect($daysRemaining)->toBeGreaterThanOrEqual(9);

    // Deactivate the most recent override - should fall back to the older one
    $override2->deactivate();
    $newEffectiveExpiry = $website->getEffectiveSslExpiryDate($user->id);
    $newDaysRemaining = $website->getDaysRemaining($user->id);

    expect($newEffectiveExpiry->format('Y-m-d'))->toBe(now()->addDays(3)->format('Y-m-d'));
    expect($newDaysRemaining)->toBeGreaterThanOrEqual(2);

    // Deactivate all overrides - should revert to original
    $override1->deactivate();
    $finalEffectiveExpiry = $website->getEffectiveSslExpiryDate($user->id);
    $finalDaysRemaining = $website->getDaysRemaining($user->id);

    expect($finalEffectiveExpiry->format('Y-m-d'))->toBe($monitor->certificate_expiration_date->format('Y-m-d'));
    expect($finalDaysRemaining)->toBeGreaterThan(0);
});

// TDD: Test expired overrides are ignored
test('expired ssl overrides are ignored', function () {
    config(['database.default' => 'mariadb']);
    config(['database.connections.mariadb.database' => 'laravel']);

    $user = User::where('email', 'bonzo@konjscina.com')->first();
    $website = Website::where('user_id', $user->id)
        ->where('ssl_monitoring_enabled', true)
        ->first();

    // Clean up ALL existing overrides for this website to ensure clean test
    DebugOverride::where('targetable_type', Website::class)
        ->where('targetable_id', $website->id)
        ->delete();

    $monitor = $website->getSpatieMonitor();

    // Create an expired override
    $expiredOverride = DebugOverride::create([
        'user_id' => $user->id,
        'module_type' => 'ssl_expiry',
        'targetable_type' => Website::class,
        'targetable_id' => $website->id,
        'override_data' => [
            'expiry_date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'original_expiry' => $monitor->certificate_expiration_date,
            'reason' => 'Expired override',
        ],
        'is_active' => true,
        'expires_at' => now()->subMinutes(1), // Expired 1 minute ago
    ]);

    // Create a valid override
    $validOverride = DebugOverride::create([
        'user_id' => $user->id,
        'module_type' => 'ssl_expiry',
        'targetable_type' => Website::class,
        'targetable_id' => $website->id,
        'override_data' => [
            'expiry_date' => now()->addDays(15)->format('Y-m-d H:i:s'),
            'original_expiry' => $monitor->certificate_expiration_date,
            'reason' => 'Valid override',
        ],
        'is_active' => true,
        'expires_at' => now()->addHours(1), // Expires in 1 hour
    ]);

    $effectiveExpiry = $website->getEffectiveSslExpiryDate($user->id);
    $daysRemaining = $website->getDaysRemaining($user->id);

    // Should use the valid override, not the expired one
    expect($effectiveExpiry->format('Y-m-d'))->toBe(now()->addDays(15)->format('Y-m-d'));
    expect($daysRemaining)->toBeGreaterThanOrEqual(14);
});

// TDD: Test user isolation works correctly
test('ssl overrides are isolated by user', function () {
    config(['database.default' => 'mariadb']);
    config(['database.connections.mariadb.database' => 'laravel']);

    $debugUser = User::where('email', 'bonzo@konjscina.com')->first();

    // Get a different user (if exists)
    $otherUser = User::where('email', '!=', 'bonzo@konjscina.com')->first();
    if (! $otherUser) {
        $this->markTestSkipped('Need multiple users for isolation test');
    }

    $website = Website::where('user_id', $debugUser->id)
        ->where('ssl_monitoring_enabled', true)
        ->first();

    // Clean up existing overrides
    DebugOverride::where('targetable_type', Website::class)
        ->where('targetable_id', $website->id)
        ->delete();

    $monitor = $website->getSpatieMonitor();

    // Create override for debug user
    $debugOverride = DebugOverride::create([
        'user_id' => $debugUser->id,
        'module_type' => 'ssl_expiry',
        'targetable_type' => Website::class,
        'targetable_id' => $website->id,
        'override_data' => [
            'expiry_date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'original_expiry' => $monitor->certificate_expiration_date,
            'reason' => 'Debug user override',
        ],
        'is_active' => true,
    ]);

    // Debug user should see the override
    $debugExpiry = $website->getEffectiveSslExpiryDate($debugUser->id);
    expect($debugExpiry->format('Y-m-d'))->toBe(now()->addDays(5)->format('Y-m-d'));

    // Other user should see the original expiry
    $otherExpiry = $website->getEffectiveSslExpiryDate($otherUser->id);
    expect($otherExpiry->format('Y-m-d'))->toBe($monitor->certificate_expiration_date->format('Y-m-d'));
});
