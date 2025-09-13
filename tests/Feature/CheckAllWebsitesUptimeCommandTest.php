<?php

declare(strict_types=1);

use App\Jobs\CheckWebsiteUptimeJob;
use App\Models\Team;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create();
});

test('command can be executed', function () {
    $this->artisan('uptime:check-all')
        ->assertExitCode(0);
});

test('command shows no websites message when no websites exist', function () {
    $this->artisan('uptime:check-all')
        ->expectsOutput('No websites found with uptime monitoring enabled.')
        ->assertExitCode(0);
});

test('command queues jobs for websites with uptime monitoring enabled', function () {
    Queue::fake();

    // Create websites with different monitoring settings
    $uptimeWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
    ]);

    $sslOnlyWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => false,
    ]);

    $this->artisan('uptime:check-all')
        ->expectsOutput('Queued uptime checks for 1 website(s).')
        ->assertExitCode(0);

    // Only the uptime monitoring enabled website should be queued
    Queue::assertPushed(CheckWebsiteUptimeJob::class, 1);
    Queue::assertPushed(CheckWebsiteUptimeJob::class, function ($job) use ($uptimeWebsite) {
        return $job->website->id === $uptimeWebsite->id;
    });
});

test('command processes multiple websites with uptime monitoring', function () {
    Queue::fake();

    $websites = Website::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
    ]);

    // Create one without uptime monitoring
    Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => false,
    ]);

    $this->artisan('uptime:check-all')
        ->expectsOutput('Queued uptime checks for 3 website(s).')
        ->assertExitCode(0);

    Queue::assertPushed(CheckWebsiteUptimeJob::class, 3);
});

test('command can force check websites recently checked', function () {
    Queue::fake();

    $recentlyCheckedWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'last_uptime_check_at' => now()->subMinutes(5), // Recently checked
    ]);

    // Without --force, should skip recently checked
    $this->artisan('uptime:check-all')
        ->expectsOutput('No websites need uptime checking at this time.')
        ->assertExitCode(0);

    Queue::assertNotPushed(CheckWebsiteUptimeJob::class);

    // With --force, should queue even recently checked
    $this->artisan('uptime:check-all', ['--force' => true])
        ->expectsOutput('Queued uptime checks for 1 website(s) (forced).')
        ->assertExitCode(0);

    Queue::assertPushed(CheckWebsiteUptimeJob::class, 1);
});

test('command can filter by specific user', function () {
    Queue::fake();

    $user1 = $this->user;
    $user2 = User::factory()->create();

    $user1Website = Website::factory()->create([
        'user_id' => $user1->id,
        'uptime_monitoring' => true,
    ]);

    $user2Website = Website::factory()->create([
        'user_id' => $user2->id,
        'uptime_monitoring' => true,
    ]);

    $this->artisan('uptime:check-all', ['--user' => $user1->id])
        ->expectsOutput("Queued uptime checks for 1 website(s) for user {$user1->name}.")
        ->assertExitCode(0);

    Queue::assertPushed(CheckWebsiteUptimeJob::class, 1);
    Queue::assertPushed(CheckWebsiteUptimeJob::class, function ($job) use ($user1Website) {
        return $job->website->id === $user1Website->id;
    });
});

test('command handles invalid user filter gracefully', function () {
    $this->artisan('uptime:check-all', ['--user' => 99999])
        ->expectsOutput('User with ID 99999 not found.')
        ->assertExitCode(1);
});

test('command shows verbose output when requested', function () {
    Queue::fake();

    $website = Website::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Website',
        'url' => 'https://example.com',
        'uptime_monitoring' => true,
    ]);

    $this->artisan('uptime:check-all', ['--detailed' => true])
        ->expectsOutput('Checking uptime monitoring websites...')
        ->expectsOutput('Queuing: Test Website (https://example.com)')
        ->expectsOutput('Queued uptime checks for 1 website(s).')
        ->assertExitCode(0);
});

test('command respects minimum check interval without force', function () {
    Queue::fake();

    // Website checked 5 minutes ago (within 15 minute interval)
    $recentWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'last_uptime_check_at' => now()->subMinutes(5),
    ]);

    // Website checked 20 minutes ago (outside 15 minute interval)
    $oldWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
        'last_uptime_check_at' => now()->subMinutes(20),
    ]);

    $this->artisan('uptime:check-all')
        ->expectsOutput('Queued uptime checks for 1 website(s).')
        ->assertExitCode(0);

    Queue::assertPushed(CheckWebsiteUptimeJob::class, 1);
    Queue::assertPushed(CheckWebsiteUptimeJob::class, function ($job) use ($oldWebsite) {
        return $job->website->id === $oldWebsite->id;
    });
});

test('command handles team-based websites correctly', function () {
    Queue::fake();

    $teamWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
        'uptime_monitoring' => true,
    ]);

    $personalWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => null,
        'uptime_monitoring' => true,
    ]);

    $this->artisan('uptime:check-all')
        ->expectsOutput('Queued uptime checks for 2 website(s).')
        ->assertExitCode(0);

    Queue::assertPushed(CheckWebsiteUptimeJob::class, 2);
});

test('command shows proper counts with mixed monitoring settings', function () {
    Queue::fake();

    // 2 with uptime monitoring
    Website::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
    ]);

    // 3 without uptime monitoring
    Website::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => false,
    ]);

    $this->artisan('uptime:check-all', ['--detailed' => true])
        ->expectsOutput('Found 5 total websites, 2 with uptime monitoring enabled.')
        ->expectsOutput('Queued uptime checks for 2 website(s).')
        ->assertExitCode(0);

    Queue::assertPushed(CheckWebsiteUptimeJob::class, 2);
});

test('command handles empty results for user filter', function () {
    $user2 = User::factory()->create();

    Website::factory()->create([
        'user_id' => $this->user->id,
        'uptime_monitoring' => true,
    ]);

    $this->artisan('uptime:check-all', ['--user' => $user2->id])
        ->expectsOutput("No websites found with uptime monitoring enabled for user {$user2->name}.")
        ->assertExitCode(0);
});

test('command shows help information', function () {
    $this->artisan('uptime:check-all', ['--help'])
        ->expectsOutputToContain('Queue uptime checks for websites with uptime monitoring enabled')
        ->assertExitCode(0);
});
