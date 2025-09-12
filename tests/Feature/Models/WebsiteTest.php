<?php

use App\Models\User;
use App\Models\Website;

test('website can be created with valid data', function () {
    $user = User::factory()->create();

    $website = Website::create([
        'name' => 'Example Site',
        'url' => 'https://example.com',
        'user_id' => $user->id,
    ]);

    expect($website)->toBeInstanceOf(Website::class)
        ->and($website->name)->toBe('Example Site')
        ->and($website->url)->toBe('https://example.com')
        ->and($website->user_id)->toBe($user->id);
});

test('website belongs to a user', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    expect($website->user)->toBeInstanceOf(User::class)
        ->and($website->user->id)->toBe($user->id);
});

test('website url is sanitized on save', function () {
    $user = User::factory()->create();
    $website = Website::create([
        'name' => 'Example Site',
        'url' => 'HTTP://EXAMPLE.COM/PATH/../',
        'user_id' => $user->id,
    ]);

    expect($website->url)->toBe('https://example.com');
});

test('website url is normalized to lowercase and https', function () {
    $user = User::factory()->create();
    $website = Website::create([
        'name' => 'Example Site',
        'url' => 'HTTP://EXAMPLE.COM',
        'user_id' => $user->id,
    ]);

    expect($website->url)->toBe('https://example.com');
});

test('website has ssl certificates relationship method', function () {
    $website = Website::factory()->create();

    expect(method_exists($website, 'sslCertificates'))->toBeTrue();
});

test('website has ssl checks relationship method', function () {
    $website = Website::factory()->create();

    expect(method_exists($website, 'sslChecks'))->toBeTrue();
});

test('website can get latest ssl certificate', function () {
    $website = Website::factory()->create();

    expect(method_exists($website, 'getLatestSslCertificate'))->toBeTrue();
    expect($website->getLatestSslCertificate())->toBeNull();
});

test('website can get current ssl status', function () {
    $website = Website::factory()->create();

    expect(method_exists($website, 'getCurrentSslStatus'))->toBeTrue();
    expect($website->getCurrentSslStatus())->toBe('unknown');
});

test('website enforces unique url per user', function () {
    $user = User::factory()->create();

    Website::create([
        'name' => 'First Site',
        'url' => 'https://example.com',
        'user_id' => $user->id,
    ]);

    expect(fn () => Website::create([
        'name' => 'Second Site',
        'url' => 'https://example.com',
        'user_id' => $user->id,
    ]))->toThrow(Illuminate\Database\QueryException::class);
});

test('different users can have same url', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $website1 = Website::create([
        'name' => 'User 1 Site',
        'url' => 'https://example.com',
        'user_id' => $user1->id,
    ]);

    $website2 = Website::create([
        'name' => 'User 2 Site',
        'url' => 'https://example.com',
        'user_id' => $user2->id,
    ]);

    expect($website1)->toBeInstanceOf(Website::class)
        ->and($website2)->toBeInstanceOf(Website::class);
});

test('website has default uptime monitoring settings', function () {
    $user = User::factory()->create();
    $website = Website::create([
        'name' => 'Example Site',
        'url' => 'https://example.com',
        'user_id' => $user->id,
    ]);

    expect($website->expected_status_code)->toBe(200)
        ->and($website->expected_content)->toBeNull()
        ->and($website->forbidden_content)->toBeNull()
        ->and($website->max_response_time)->toBe(30000)
        ->and($website->follow_redirects)->toBeTrue()
        ->and($website->max_redirects)->toBe(3);
});

test('website can be created with custom uptime monitoring settings', function () {
    $user = User::factory()->create();
    $website = Website::create([
        'name' => 'Custom Site',
        'url' => 'https://custom.com',
        'user_id' => $user->id,
        'expected_status_code' => 201,
        'expected_content' => 'Welcome to our site',
        'forbidden_content' => 'Error 503',
        'max_response_time' => 5000,
        'follow_redirects' => false,
        'max_redirects' => 1,
    ]);

    expect($website->expected_status_code)->toBe(201)
        ->and($website->expected_content)->toBe('Welcome to our site')
        ->and($website->forbidden_content)->toBe('Error 503')
        ->and($website->max_response_time)->toBe(5000)
        ->and($website->follow_redirects)->toBeFalse()
        ->and($website->max_redirects)->toBe(1);
});

test('website has uptime checks relationship', function () {
    $website = Website::factory()->create();

    expect(method_exists($website, 'uptimeChecks'))->toBeTrue();
    expect($website->uptimeChecks())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('website has downtime incidents relationship', function () {
    $website = Website::factory()->create();

    expect(method_exists($website, 'downtimeIncidents'))->toBeTrue();
    expect($website->downtimeIncidents())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});
