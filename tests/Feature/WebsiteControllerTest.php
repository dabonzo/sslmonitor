<?php

use App\Models\User;
use App\Models\Website;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

test('websites index page loads without sslCertificates relationship error', function () {
    // Use test user and websites
    $user = $this->testUser;
    $websites = $this->realWebsites;

    // Authenticate as the test user
    $this->actingAs($user);

    // Make a request to the websites index page
    $response = $this->get('/ssl/websites');

    // Assert the response is successful (no sslCertificates relationship error)
    $response->assertStatus(200);

    expect(true)->toBeTrue(); // SUCCESS: No sslCertificates relationship error!
});

test('website show page loads without sslCertificates relationship error', function () {
    // Create test user and website
    $user = User::factory()->create();
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'ssl_monitoring_enabled' => true
    ]);

    // Authenticate as the test user
    $this->actingAs($user);

    // Make a request to the website show page
    $response = $this->get("/ssl/websites/{$website->id}");

    // Assert the response is successful (no sslCertificates relationship error)
    $response->assertStatus(200);

    expect(true)->toBeTrue(); // SUCCESS: No sslCertificates relationship error!
});