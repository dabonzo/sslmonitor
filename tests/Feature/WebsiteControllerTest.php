<?php

use App\Models\User;
use App\Models\Website;

test('websites index page loads without sslCertificates relationship error', function () {
    // Use the existing real user bonzo@konjscina.com (ID: 52)
    $user = User::where('email', 'bonzo@konjscina.com')->first();

    if (!$user) {
        $this->fail('Real user bonzo@konjscina.com not found in database');
    }

    // Authenticate as the real user
    $this->actingAs($user);

    // Make a request to the websites index page
    $response = $this->get('/ssl/websites');

    // Assert the response is successful (no sslCertificates relationship error)
    $response->assertStatus(200);

    // Assert that the page contains the expected website names
    $response->assertSee('Office Manager Pro');
    $response->assertSee('Redgas Austria');
    $response->assertSee('Fairnando');

    expect(true)->toBeTrue(); // SUCCESS: No sslCertificates relationship error!
});

test('website show page loads without sslCertificates relationship error', function () {
    // Use the existing real user
    $user = User::where('email', 'bonzo@konjscina.com')->first();

    if (!$user) {
        $this->fail('Real user bonzo@konjscina.com not found in database');
    }

    // Get one of the user's websites
    $website = Website::where('user_id', $user->id)->first();

    if (!$website) {
        $this->fail('No websites found for user');
    }

    // Authenticate as the real user
    $this->actingAs($user);

    // Make a request to the website show page
    $response = $this->get("/ssl/websites/{$website->id}");

    // Assert the response is successful (no sslCertificates relationship error)
    $response->assertStatus(200);

    expect(true)->toBeTrue(); // SUCCESS: No sslCertificates relationship error!
});