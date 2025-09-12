<?php

declare(strict_types=1);

use App\Models\UptimeCheck;
use App\Models\User;
use App\Models\Website;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->website = Website::factory()->create(['user_id' => $this->user->id]);
});

describe('UptimeCheck Model', function () {
    test('can create uptime check with basic attributes', function () {
        $uptimeCheck = UptimeCheck::create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'http_status_code' => 200,
            'response_time_ms' => 250,
            'response_size_bytes' => 1024,
            'content_check_passed' => true,
            'checked_at' => now(),
        ]);

        expect($uptimeCheck)
            ->toBeInstanceOf(UptimeCheck::class)
            ->and($uptimeCheck->website_id)->toBe($this->website->id)
            ->and($uptimeCheck->status)->toBe('up')
            ->and($uptimeCheck->http_status_code)->toBe(200)
            ->and($uptimeCheck->response_time_ms)->toBe(250)
            ->and($uptimeCheck->response_size_bytes)->toBe(1024)
            ->and($uptimeCheck->content_check_passed)->toBeTrue();
    });

    test('belongs to website', function () {
        $uptimeCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
        ]);

        expect($uptimeCheck->website)
            ->toBeInstanceOf(Website::class)
            ->and($uptimeCheck->website->id)->toBe($this->website->id);
    });

    test('can have error message and content check error', function () {
        $uptimeCheck = UptimeCheck::create([
            'website_id' => $this->website->id,
            'status' => 'down',
            'error_message' => 'Connection timeout after 30 seconds',
            'content_check_passed' => false,
            'content_check_error' => 'Expected text "Welcome" not found on page',
            'checked_at' => now(),
        ]);

        expect($uptimeCheck->error_message)->toBe('Connection timeout after 30 seconds')
            ->and($uptimeCheck->content_check_passed)->toBeFalse()
            ->and($uptimeCheck->content_check_error)->toBe('Expected text "Welcome" not found on page');
    });

    test('has valid status values', function () {
        $validStatuses = ['up', 'down', 'slow', 'content_mismatch'];

        foreach ($validStatuses as $status) {
            $uptimeCheck = UptimeCheck::create([
                'website_id' => $this->website->id,
                'status' => $status,
                'checked_at' => now(),
            ]);

            expect($uptimeCheck->status)->toBe($status);
        }
    });

    test('can get recent uptime checks for website', function () {
        // Create old check
        UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'checked_at' => Carbon::now()->subDays(5),
            'status' => 'down',
        ]);

        // Create recent checks
        $recentCheck1 = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'checked_at' => Carbon::now()->subHour(),
            'status' => 'up',
        ]);

        $recentCheck2 = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'checked_at' => Carbon::now(),
            'status' => 'up',
        ]);

        $recentChecks = UptimeCheck::where('website_id', $this->website->id)
            ->where('checked_at', '>=', Carbon::now()->subDays(1))
            ->orderBy('checked_at', 'desc')
            ->get();

        expect($recentChecks)->toHaveCount(2)
            ->and($recentChecks->first()->id)->toBe($recentCheck2->id)
            ->and($recentChecks->last()->id)->toBe($recentCheck1->id);
    });

    test('can calculate uptime percentage for website', function () {
        // Create mix of up and down checks
        UptimeCheck::factory()->count(8)->create([
            'website_id' => $this->website->id,
            'status' => 'up',
            'checked_at' => Carbon::now()->subHours(rand(1, 24)),
        ]);

        UptimeCheck::factory()->count(2)->create([
            'website_id' => $this->website->id,
            'status' => 'down',
            'checked_at' => Carbon::now()->subHours(rand(1, 24)),
        ]);

        $uptimePercentage = UptimeCheck::where('website_id', $this->website->id)
            ->selectRaw('
                COUNT(*) as total_checks,
                COUNT(CASE WHEN status = "up" THEN 1 END) as up_checks,
                ROUND((COUNT(CASE WHEN status = "up" THEN 1 END) * 100.0) / COUNT(*), 2) as uptime_percentage
            ')
            ->first();

        expect($uptimePercentage->total_checks)->toBe(10)
            ->and($uptimePercentage->up_checks)->toBe(8)
            ->and((float) $uptimePercentage->uptime_percentage)->toBe(80.0);
    });

    test('can get latest uptime check for website', function () {
        $oldCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'checked_at' => Carbon::now()->subHour(),
            'status' => 'down',
        ]);

        $latestCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
            'checked_at' => Carbon::now(),
            'status' => 'up',
        ]);

        $latest = UptimeCheck::where('website_id', $this->website->id)
            ->latest('checked_at')
            ->first();

        expect($latest->id)->toBe($latestCheck->id)
            ->and($latest->status)->toBe('up');
    });

    test('has proper timestamps', function () {
        $uptimeCheck = UptimeCheck::factory()->create([
            'website_id' => $this->website->id,
        ]);

        expect($uptimeCheck->created_at)->toBeInstanceOf(Carbon::class)
            ->and($uptimeCheck->updated_at)->toBeInstanceOf(Carbon::class)
            ->and($uptimeCheck->checked_at)->toBeInstanceOf(Carbon::class);
    });

    test('can scope by status', function () {
        UptimeCheck::factory()->count(3)->create([
            'website_id' => $this->website->id,
            'status' => 'up',
        ]);

        UptimeCheck::factory()->count(2)->create([
            'website_id' => $this->website->id,
            'status' => 'down',
        ]);

        $upChecks = UptimeCheck::where('status', 'up')->count();
        $downChecks = UptimeCheck::where('status', 'down')->count();

        expect($upChecks)->toBe(3)
            ->and($downChecks)->toBe(2);
    });

    test('validates required fields', function () {
        expect(fn () => UptimeCheck::create([]))
            ->toThrow(Exception::class);
    });
});
