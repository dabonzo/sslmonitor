<?php

declare(strict_types=1);

use App\Console\Commands\CheckAllSslCertificates;
use App\Jobs\CheckSslCertificateJob;
use App\Models\User;
use App\Models\Website;
use App\Models\SslCheck;
use Illuminate\Support\Facades\Queue;

test('ssl check all command queues jobs for all websites', function () {
    Queue::fake();
    
    $user = User::factory()->create();
    $websites = Website::factory()->for($user)->count(3)->create();

    $this->artisan(CheckAllSslCertificates::class)
        ->expectsOutput('🔍 Starting SSL certificate checks for all websites...')
        ->expectsOutput('📊 SSL Check Summary:')
        ->expectsOutput('🚀 3 SSL checks have been queued!')
        ->assertExitCode(0);

    Queue::assertPushed(CheckSslCertificateJob::class, 3);
});

test('ssl check all command skips recently checked websites', function () {
    Queue::fake();
    
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $website1 = Website::factory()->for($user1)->create();
    $website2 = Website::factory()->for($user2)->create();
    
    // Create recent check for website1
    SslCheck::factory()->for($website1)->create([
        'checked_at' => now()->subMinutes(30),
    ]);
    
    // Create old check for website2
    SslCheck::factory()->for($website2)->create([
        'checked_at' => now()->subHours(2),
    ]);

    $this->artisan(CheckAllSslCertificates::class)
        ->expectsOutput('Skipping')
        ->expectsOutput('Queued')
        ->assertExitCode(0);

    Queue::assertPushed(CheckSslCertificateJob::class, 1);
    Queue::assertPushed(CheckSslCertificateJob::class, function ($job) use ($website2) {
        return $job->website->id === $website2->id;
    });
});

test('ssl check all command forces check with --force option', function () {
    Queue::fake();
    
    $user = User::factory()->create();
    $website = Website::factory()->for($user)->create();
    
    // Create recent check
    SslCheck::factory()->for($website)->create([
        'checked_at' => now()->subMinutes(30),
    ]);

    $this->artisan(CheckAllSslCertificates::class, ['--force' => true])
        ->expectsOutput('🚀 1 SSL checks have been queued!')
        ->assertExitCode(0);

    Queue::assertPushed(CheckSslCertificateJob::class, 1);
});

test('ssl check all command handles no websites', function () {
    Queue::fake();

    $this->artisan(CheckAllSslCertificates::class)
        ->expectsOutput('⚠️  No SSL checks were queued.')
        ->assertExitCode(0);

    Queue::assertNothingPushed();
});

test('ssl check all command handles large number of websites efficiently', function () {
    Queue::fake();
    
    $user = User::factory()->create();
    Website::factory()->for($user)->count(250)->create();

    $this->artisan(CheckAllSslCertificates::class)
        ->expectsOutput('🚀 250 SSL checks have been queued!')
        ->assertExitCode(0);

    Queue::assertPushed(CheckSslCertificateJob::class, 250);
});

test('ssl check all command provides helpful output when all websites are recently checked', function () {
    Queue::fake();
    
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $websites = [
        Website::factory()->for($user1)->create(),
        Website::factory()->for($user2)->create(),
    ];
    
    // Create recent checks for all websites
    foreach ($websites as $website) {
        SslCheck::factory()->for($website)->create([
            'checked_at' => now()->subMinutes(30),
        ]);
    }

    $this->artisan(CheckAllSslCertificates::class)
        ->expectsOutput('⚠️  No SSL checks were queued.')
        ->expectsOutput('💡 Use --force to check all websites regardless of recent checks')
        ->assertExitCode(0);

    Queue::assertNothingPushed();
});

test('ssl check all command can be run from artisan', function () {
    Queue::fake();
    
    $exitCode = $this->artisan('ssl:check-all');
    
    expect($exitCode)->toBe(0);
});