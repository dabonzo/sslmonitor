<?php

use App\Events\MonitoringCheckCompleted;
use App\Events\MonitoringCheckFailed;
use App\Events\MonitoringCheckStarted;
use App\Models\Monitor;
use App\Models\MonitoringResult;
use App\Models\Website;
use Illuminate\Support\Facades\Event;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

test('MonitoringCheckStarted event can be fired', function () {
    Event::fake([MonitoringCheckStarted::class]);

    $monitor = Monitor::first();

    event(new MonitoringCheckStarted(
        monitor: $monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
    ));

    Event::assertDispatched(MonitoringCheckStarted::class);
});

test('MonitoringCheckCompleted event creates monitoring result', function () {
    $monitor = Monitor::first();
    $website = Website::first();

    $startedAt = now()->subSeconds(2);
    $completedAt = now();

    event(new MonitoringCheckCompleted(
        monitor: $monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $startedAt,
        completedAt: $completedAt,
        checkResults: [
            'check_type' => 'both',
            'status' => 'success',
            'uptime_status' => 'up',
            'http_status_code' => 200,
            'ssl_status' => 'valid',
        ],
    ));

    // Wait for queue processing (in tests, listeners execute synchronously by default)
    $result = MonitoringResult::where('monitor_id', $monitor->id)->first();

    expect($result)->not->toBeNull();
    expect($result->trigger_type)->toBe('scheduled');
    expect($result->status)->toBe('success');
    expect($result->uptime_status)->toBe('up');
});

test('MonitoringCheckFailed event creates error record', function () {
    $monitor = Monitor::first();

    $startedAt = now()->subSeconds(1);
    $exception = new \Exception('Test error');

    event(new MonitoringCheckFailed(
        monitor: $monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $startedAt,
        exception: $exception,
    ));

    $result = MonitoringResult::where('monitor_id', $monitor->id)->first();

    expect($result)->not->toBeNull();
    expect($result->status)->toBe('error');
    expect($result->error_message)->toBe('Test error');
});

test('manual check records triggered_by_user_id', function () {
    $monitor = Monitor::first();
    $user = \App\Models\User::first();

    event(new MonitoringCheckCompleted(
        monitor: $monitor,
        triggerType: 'manual_immediate',
        triggeredByUserId: $user->id,
        startedAt: now()->subSeconds(2),
        completedAt: now(),
        checkResults: [
            'check_type' => 'both',
            'status' => 'success',
        ],
    ));

    $result = MonitoringResult::where('monitor_id', $monitor->id)->first();

    expect($result->trigger_type)->toBe('manual_immediate');
    expect($result->triggered_by_user_id)->toBe($user->id);
});

test('check duration is calculated correctly', function () {
    $monitor = Monitor::first();

    $startedAt = now()->subMilliseconds(1500);
    $completedAt = now();

    event(new MonitoringCheckCompleted(
        monitor: $monitor,
        triggerType: 'scheduled',
        triggeredByUserId: null,
        startedAt: $startedAt,
        completedAt: $completedAt,
        checkResults: ['check_type' => 'both', 'status' => 'success'],
    ));

    $result = MonitoringResult::where('monitor_id', $monitor->id)->first();

    expect($result->duration_ms)->toBeGreaterThanOrEqual(1400);
    expect($result->duration_ms)->toBeLessThanOrEqual(1600);
});
