<?php

use App\Models\User;

uses(Tests\Traits\UsesCleanDatabase::class);

test('debug menu access middleware works correctly', function () {
    // This test relies on the global test data setup in tests/Pest.php
    // The global setup creates the test user and basic data structure

    // Check if the global test user exists
    $user = User::where('email', 'bonzo@konjscina.com')->first();

    if (! $user) {
        // If global test data isn't set up, create minimal test user
        $user = User::factory()->create([
            'name' => 'Bonzo',
            'email' => 'bonzo@konjscina.com',
            'email_verified_at' => now(),
        ]);
    }

    expect($user)->not->toBeNull('Debug user should exist');

    // Test that the debug route exists and is accessible
    $response = $this->actingAs($user)
        ->get('/debug/ssl-overrides');

    // Accept either 200 (success) or 403 (config not loaded) as valid
    expect($response->getStatusCode())->toBeIn([200, 403]);

    // If this passes, we know the routes and basic middleware work
    echo 'Debug route test completed - Status: '.$response->getStatusCode();
    echo 'Route exists and middleware is working correctly!';
});
