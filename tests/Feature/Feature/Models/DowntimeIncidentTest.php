<?php

declare(strict_types=1);

use App\Models\DowntimeIncident;
use App\Models\User;
use App\Models\Website;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->website = Website::factory()->create(['user_id' => $this->user->id]);
});

describe('DowntimeIncident Model', function () {
    test('can create downtime incident with basic attributes', function () {
        $startedAt = Carbon::now()->subHour();

        $incident = DowntimeIncident::create([
            'website_id' => $this->website->id,
            'started_at' => $startedAt,
            'incident_type' => 'timeout',
            'error_details' => 'Connection timeout after 30 seconds',
        ]);

        expect($incident)
            ->toBeInstanceOf(DowntimeIncident::class)
            ->and($incident->website_id)->toBe($this->website->id)
            ->and($incident->incident_type)->toBe('timeout')
            ->and($incident->error_details)->toBe('Connection timeout after 30 seconds')
            ->and($incident->started_at->format('Y-m-d H:i:s'))->toBe($startedAt->format('Y-m-d H:i:s'))
            ->and($incident->ended_at)->toBeNull()
            ->and($incident->resolved_automatically)->toBeFalse();
    });

    test('belongs to website', function () {
        $incident = DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
        ]);

        expect($incident->website)
            ->toBeInstanceOf(Website::class)
            ->and($incident->website->id)->toBe($this->website->id);
    });

    test('can calculate duration when resolved', function () {
        $startedAt = Carbon::now()->subHours(2);
        $endedAt = Carbon::now()->subHour();

        $incident = DowntimeIncident::create([
            'website_id' => $this->website->id,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'incident_type' => 'http_error',
        ]);

        // Duration should be calculated and stored
        expect((int) $incident->duration_minutes)->toBe(60);
    });

    test('can resolve incident automatically', function () {
        $incident = DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subHour(),
            'ended_at' => null,
        ]);

        $incident->resolve(true);

        expect($incident->ended_at)->not->toBeNull()
            ->and($incident->resolved_automatically)->toBeTrue()
            ->and($incident->duration_minutes)->toBeGreaterThan(0);
    });

    test('can resolve incident manually', function () {
        $incident = DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subMinutes(30),
            'ended_at' => null,
        ]);

        $incident->resolve(false);

        expect($incident->ended_at)->not->toBeNull()
            ->and($incident->resolved_automatically)->toBeFalse()
            ->and((int) $incident->duration_minutes)->toBe(30);
    });

    test('has valid incident types', function () {
        $validTypes = ['timeout', 'http_error', 'content_mismatch'];

        foreach ($validTypes as $type) {
            $incident = DowntimeIncident::create([
                'website_id' => $this->website->id,
                'started_at' => now(),
                'incident_type' => $type,
            ]);

            expect($incident->incident_type)->toBe($type);
        }
    });

    test('can check if incident is ongoing', function () {
        $ongoingIncident = DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subHour(),
            'ended_at' => null,
        ]);

        $resolvedIncident = DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subHours(2),
            'ended_at' => Carbon::now()->subHour(),
        ]);

        expect($ongoingIncident->isOngoing())->toBeTrue()
            ->and($resolvedIncident->isOngoing())->toBeFalse();
    });

    test('can get ongoing incidents for website', function () {
        // Create resolved incident
        DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subHours(3),
            'ended_at' => Carbon::now()->subHours(2),
        ]);

        // Create ongoing incidents
        $ongoing1 = DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subHour(),
            'ended_at' => null,
        ]);

        $ongoing2 = DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subMinutes(30),
            'ended_at' => null,
        ]);

        $ongoingIncidents = DowntimeIncident::where('website_id', $this->website->id)
            ->whereNull('ended_at')
            ->get();

        expect($ongoingIncidents)->toHaveCount(2);
    });

    test('can get recent incidents for website', function () {
        // Create old incident
        DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subDays(10),
            'ended_at' => Carbon::now()->subDays(10)->addHour(),
        ]);

        // Create recent incidents
        $recent1 = DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subDays(2),
            'ended_at' => Carbon::now()->subDays(2)->addHours(2),
        ]);

        $recent2 = DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subDay(),
            'ended_at' => Carbon::now()->subDay()->addHour(),
        ]);

        $recentIncidents = DowntimeIncident::where('website_id', $this->website->id)
            ->where('started_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('started_at', 'desc')
            ->get();

        expect($recentIncidents)->toHaveCount(2)
            ->and($recentIncidents->first()->id)->toBe($recent2->id);
    });

    test('can calculate total downtime for website', function () {
        // Create incidents with known durations
        DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subDays(2),
            'ended_at' => Carbon::now()->subDays(2)->addHour(),
            'duration_minutes' => 60,
        ]);

        DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
            'started_at' => Carbon::now()->subDay(),
            'ended_at' => Carbon::now()->subDay()->addMinutes(30),
            'duration_minutes' => 30,
        ]);

        $totalDowntime = DowntimeIncident::where('website_id', $this->website->id)
            ->whereNotNull('ended_at')
            ->sum('duration_minutes');

        expect((int) $totalDowntime)->toBe(90);
    });

    test('has proper timestamps', function () {
        $incident = DowntimeIncident::factory()->create([
            'website_id' => $this->website->id,
        ]);

        expect($incident->created_at)->toBeInstanceOf(Carbon::class)
            ->and($incident->updated_at)->toBeInstanceOf(Carbon::class)
            ->and($incident->started_at)->toBeInstanceOf(Carbon::class);
    });
});
