<?php

declare(strict_types=1);

use App\Models\DowntimeIncident;
use App\Models\UptimeCheck;
use App\Models\User;
use App\Models\Website;
use App\Services\UptimeStatusCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->website = Website::factory()->create(['user_id' => $this->user->id]);
    $this->calculator = new UptimeStatusCalculator;
});

describe('UptimeStatusCalculator Service', function () {
    test('returns unknown status when no checks exist', function () {
        $status = $this->calculator->calculateStatus($this->website);

        expect($status)->toBe('unknown');
    });

    test('returns up status from latest check', function () {
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'checked_at' => Carbon::now(),
        ]);

        $status = $this->calculator->calculateStatus($this->website);

        expect($status)->toBe('up');
    });

    test('returns down status from latest check', function () {
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'down',
            'checked_at' => Carbon::now(),
        ]);

        $status = $this->calculator->calculateStatus($this->website);

        expect($status)->toBe('down');
    });

    test('returns latest status ignoring older checks', function () {
        // Older check is up
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'checked_at' => Carbon::now()->subMinutes(10),
        ]);

        // Latest check is down
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'down',
            'checked_at' => Carbon::now(),
        ]);

        $status = $this->calculator->calculateStatus($this->website);

        expect($status)->toBe('down');
    });

    test('returns unknown status when latest check is too old', function () {
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'checked_at' => Carbon::now()->subHours(2), // Too old
        ]);

        $status = $this->calculator->calculateStatus($this->website);

        expect($status)->toBe('unknown');
    });

    test('calculates 100% uptime when all checks are up', function () {
        UptimeCheck::factory()->count(10)->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'checked_at' => Carbon::now()->subDays(rand(1, 7)),
        ]);

        $percentage = $this->calculator->calculateUptimePercentage($this->website, 30);

        expect($percentage)->toBe(100.0);
    });

    test('calculates 80% uptime with mixed checks', function () {
        // 8 up checks
        UptimeCheck::factory()->count(8)->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'checked_at' => Carbon::now()->subDays(rand(1, 7)),
        ]);

        // 2 down checks
        UptimeCheck::factory()->count(2)->create([
            'website_id' => $this->website->id,
            'status' => 'down',
            'checked_at' => Carbon::now()->subDays(rand(1, 7)),
        ]);

        $percentage = $this->calculator->calculateUptimePercentage($this->website, 30);

        expect($percentage)->toBe(80.0);
    });

    test('calculates 0% uptime when all checks are down', function () {
        UptimeCheck::factory()->count(5)->create([
            'website_id' => $this->website->id,
            'status' => 'down',
            'checked_at' => Carbon::now()->subDays(rand(1, 7)),
        ]);

        $percentage = $this->calculator->calculateUptimePercentage($this->website, 30);

        expect($percentage)->toBe(0.0);
    });

    test('excludes checks outside date range for uptime calculation', function () {
        // Old checks (outside 7 days range)
        UptimeCheck::factory()->count(5)->create([
            'website_id' => $this->website->id,
            'status' => 'down',
            'checked_at' => Carbon::now()->subDays(10),
        ]);

        // Recent checks (within 7 days range) - all up
        UptimeCheck::factory()->count(3)->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'checked_at' => Carbon::now()->subDays(3),
        ]);

        $percentage = $this->calculator->calculateUptimePercentage($this->website, 7);

        expect($percentage)->toBe(100.0); // Only recent checks count
    });

    test('treats slow and content_mismatch as down for uptime calculation', function () {
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'checked_at' => Carbon::now()->subDays(1),
        ]);

        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'slow',
            'checked_at' => Carbon::now()->subDays(2),
        ]);

        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'content_mismatch',
            'checked_at' => Carbon::now()->subDays(3),
        ]);

        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'down',
            'checked_at' => Carbon::now()->subDays(4),
        ]);

        // 1 up out of 4 total = 25%
        $percentage = $this->calculator->calculateUptimePercentage($this->website, 30);

        expect($percentage)->toBe(25.0);
    });

    test('detects new downtime incident when transitioning from up to down', function () {
        // Latest check was up
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'checked_at' => Carbon::now()->subMinutes(10),
        ]);

        // Current check is down
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'down',
            'checked_at' => Carbon::now(),
        ]);

        $incident = $this->calculator->detectDowntimeIncident($this->website);

        expect($incident)->toBeInstanceOf(DowntimeIncident::class)
            ->and($incident->website_id)->toBe($this->website->id)
            ->and($incident->started_at)->not->toBeNull()
            ->and($incident->ended_at)->toBeNull()
            ->and($incident->incident_type)->toBe('http_error');
    });

    test('continues existing incident when still down', function () {
        // Create an existing ongoing incident
        $existingIncident = DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subMinutes(30),
            'ended_at' => null,
            'incident_type' => 'timeout',
        ]);

        // Previous check was down
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'down',
            'checked_at' => Carbon::now()->subMinutes(10),
        ]);

        // Current check is still down
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'down',
            'checked_at' => Carbon::now(),
        ]);

        $incident = $this->calculator->detectDowntimeIncident($this->website);

        // Should return the existing incident, not create a new one
        expect($incident)->toBeInstanceOf(DowntimeIncident::class)
            ->and($incident->id)->toBe($existingIncident->id)
            ->and($incident->ended_at)->toBeNull();
    });

    test('returns null when no incident detected (still up)', function () {
        // Previous and current checks are up
        UptimeCheck::factory()->count(2)->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'checked_at' => Carbon::now()->subMinutes(rand(1, 30)),
        ]);

        $incident = $this->calculator->detectDowntimeIncident($this->website);

        expect($incident)->toBeNull();
    });

    test('resolves incident when transitioning from down to up', function () {
        // Create an existing ongoing incident
        $existingIncident = DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subMinutes(30),
            'ended_at' => null,
        ]);

        // Previous check was down
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'down',
            'checked_at' => Carbon::now()->subMinutes(10),
        ]);

        // Current check is up
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'checked_at' => Carbon::now(),
        ]);

        $incident = $this->calculator->detectDowntimeIncident($this->website);

        // Should resolve the existing incident
        $existingIncident->refresh();
        expect($existingIncident->ended_at)->not->toBeNull()
            ->and($existingIncident->resolved_automatically)->toBeTrue()
            ->and($incident)->toBeNull();
    });

    test('handles content mismatch incidents correctly', function () {
        // Previous check was up
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'checked_at' => Carbon::now()->subMinutes(10),
        ]);

        // Current check has content mismatch
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'content_mismatch',
            'checked_at' => Carbon::now(),
        ]);

        $incident = $this->calculator->detectDowntimeIncident($this->website);

        expect($incident)->toBeInstanceOf(DowntimeIncident::class)
            ->and($incident->incident_type)->toBe('content_mismatch');
    });

    test('handles slow response incidents correctly', function () {
        // Previous check was up
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'checked_at' => Carbon::now()->subMinutes(10),
        ]);

        // Current check is slow
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'status' => 'slow',
            'checked_at' => Carbon::now(),
        ]);

        $incident = $this->calculator->detectDowntimeIncident($this->website);

        expect($incident)->toBeInstanceOf(DowntimeIncident::class)
            ->and($incident->incident_type)->toBe('timeout');
    });
});
