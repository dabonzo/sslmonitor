<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

test('scheduler has all required monitoring commands configured', function () {
    $schedule = app(Schedule::class);
    $events = $schedule->events();

    // Convert events to array of command strings for easier testing
    $commands = collect($events)->map(function (Event $event) {
        return $event->command ?? $event->getSummaryForDisplay();
    })->toArray();

    // Verify uptime monitoring command is scheduled
    $hasUptimeCommand = collect($commands)->contains(function ($command) {
        return str_contains($command, 'monitor:check-uptime');
    });
    expect($hasUptimeCommand)->toBeTrue();

    // Verify SSL certificate monitoring command is scheduled
    $hasSslCommand = collect($commands)->contains(function ($command) {
        return str_contains($command, 'monitor:check-certificate');
    });
    expect($hasSslCommand)->toBeTrue();

    // Verify queue health check command is scheduled
    $hasQueueCommand = collect($commands)->contains(function ($command) {
        return str_contains($command, 'queue-health-check');
    });
    expect($hasQueueCommand)->toBeTrue();

    // Verify website-monitor sync command is scheduled
    $hasSyncCommand = collect($commands)->contains(function ($command) {
        return str_contains($command, 'monitors:sync-websites');
    });
    expect($hasSyncCommand)->toBeTrue();
});

test('scheduler uptime monitoring runs every minute', function () {
    $schedule = app(Schedule::class);
    $events = $schedule->events();

    $uptimeEvents = collect($events)->filter(function (Event $event) {
        $command = $event->command ?? $event->getSummaryForDisplay();
        return str_contains($command, 'monitor:check-uptime');
    });

    expect($uptimeEvents)->not()->toBeEmpty();

    // Verify it's scheduled to run every 5 minutes
    $uptimeEvent = $uptimeEvents->first();
    expect($uptimeEvent->expression)->toBe('*/5 * * * *');
});

test('scheduler ssl monitoring runs twice daily', function () {
    $schedule = app(Schedule::class);
    $events = $schedule->events();

    $sslEvents = collect($events)->filter(function (Event $event) {
        $command = $event->command ?? $event->getSummaryForDisplay();
        return str_contains($command, 'monitor:check-certificate');
    });

    expect($sslEvents)->not()->toBeEmpty();

    // Verify it's scheduled to run twice daily (6 AM and 6 PM)
    $sslEvent = $sslEvents->first();
    expect($sslEvent->expression)->toBe('0 6,18 * * *');
});

test('scheduler queue monitoring runs regularly', function () {
    $schedule = app(Schedule::class);
    $events = $schedule->events();

    $queueEvents = collect($events)->filter(function (Event $event) {
        $command = $event->command ?? $event->getSummaryForDisplay();
        return str_contains($command, 'queue-health-check');
    });

    expect($queueEvents)->not()->toBeEmpty();

    // Verify queue monitoring runs every 5 minutes
    $queueEvent = $queueEvents->first();
    expect($queueEvent->expression)->toBe('*/5 * * * *');
});

test('scheduler commands have proper overlap protection', function () {
    $schedule = app(Schedule::class);
    $events = $schedule->events();

    $monitoringEvents = collect($events)->filter(function (Event $event) {
        $command = $event->command ?? $event->getSummaryForDisplay();
        return str_contains($command, 'monitor:check-uptime') ||
               str_contains($command, 'monitor:check-certificate');
    });

    foreach ($monitoringEvents as $event) {
        // Verify withoutOverlapping is configured
        expect($event->withoutOverlapping)->toBe(true);
    }
});

test('scheduler runs in background for monitoring commands', function () {
    $schedule = app(Schedule::class);
    $events = $schedule->events();

    $monitoringEvents = collect($events)->filter(function (Event $event) {
        $command = $event->command ?? $event->getSummaryForDisplay();
        return str_contains($command, 'monitor:check-uptime') ||
               str_contains($command, 'monitor:check-certificate');
    });

    foreach ($monitoringEvents as $event) {
        // Verify runInBackground is configured
        expect($event->runInBackground)->toBe(true);
    }
});

test('scheduler configuration matches production requirements', function () {
    $schedule = app(Schedule::class);
    $events = $schedule->events();

    // Count different types of scheduled tasks
    $uptimeCount = 0;
    $sslCount = 0;
    $queueCount = 0;
    $syncCount = 0;

    foreach ($events as $event) {
        $command = $event->command ?? $event->getSummaryForDisplay();

        if (str_contains($command, 'monitor:check-uptime')) {
            $uptimeCount++;
        } elseif (str_contains($command, 'monitor:check-certificate')) {
            $sslCount++;
        } elseif (str_contains($command, 'queue-health-check')) {
            $queueCount++;
        } elseif (str_contains($command, 'monitors:sync-websites')) {
            $syncCount++;
        }
    }

    // Verify we have the expected number of each type of scheduled task
    expect($uptimeCount)->toBeGreaterThanOrEqual(1)
        ->and($sslCount)->toBeGreaterThanOrEqual(1)
        ->and($queueCount)->toBeGreaterThanOrEqual(1)
        ->and($syncCount)->toBeGreaterThanOrEqual(1);

    // Verify total events include our automation tasks
    expect(count($events))->toBeGreaterThanOrEqual(4);
});