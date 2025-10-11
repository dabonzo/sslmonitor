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

    // Verify unified monitor dispatch command is scheduled (replaces separate uptime/SSL commands)
    $hasDispatchCommand = collect($commands)->contains(function ($command) {
        return str_contains($command, 'monitors:dispatch-scheduled-checks');
    });
    expect($hasDispatchCommand)->toBeTrue('monitors:dispatch-scheduled-checks command should be scheduled');

    // Verify queue health check command is scheduled
    $hasQueueCommand = collect($commands)->contains(function ($command) {
        return str_contains($command, 'queue-health-check');
    });
    expect($hasQueueCommand)->toBeTrue('queue-health-check should be scheduled');

    // Verify website-monitor sync command is scheduled
    $hasSyncCommand = collect($commands)->contains(function ($command) {
        return str_contains($command, 'monitors:sync-websites');
    });
    expect($hasSyncCommand)->toBeTrue('monitors:sync-websites command should be scheduled');
});

test('scheduler unified monitor dispatch runs every minute', function () {
    $schedule = app(Schedule::class);
    $events = $schedule->events();

    $dispatchEvents = collect($events)->filter(function (Event $event) {
        $command = $event->command ?? $event->getSummaryForDisplay();
        return str_contains($command, 'monitors:dispatch-scheduled-checks');
    });

    expect($dispatchEvents)->not()->toBeEmpty('monitors:dispatch-scheduled-checks should be scheduled');

    // Verify it's scheduled to run every minute (queue-based architecture)
    $dispatchEvent = $dispatchEvents->first();
    expect($dispatchEvent->expression)->toBe('* * * * *', 'Should run every minute');
});

test('scheduler sync command runs every 30 minutes', function () {
    $schedule = app(Schedule::class);
    $events = $schedule->events();

    $syncEvents = collect($events)->filter(function (Event $event) {
        $command = $event->command ?? $event->getSummaryForDisplay();
        return str_contains($command, 'monitors:sync-websites');
    });

    expect($syncEvents)->not()->toBeEmpty('monitors:sync-websites should be scheduled');

    // Verify it's scheduled to run every 30 minutes
    $syncEvent = $syncEvents->first();
    expect($syncEvent->expression)->toBe('*/30 * * * *', 'Should run every 30 minutes');
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
        return str_contains($command, 'monitors:dispatch-scheduled-checks') ||
               str_contains($command, 'monitors:sync-websites');
    });

    expect($monitoringEvents)->not()->toBeEmpty('Should have monitoring commands with overlap protection');

    foreach ($monitoringEvents as $event) {
        // Verify withoutOverlapping is configured
        expect($event->withoutOverlapping)->toBe(true, 'Monitoring commands should have overlap protection');
    }
});

test('scheduler runs sync command in background', function () {
    $schedule = app(Schedule::class);
    $events = $schedule->events();

    $syncEvents = collect($events)->filter(function (Event $event) {
        $command = $event->command ?? $event->getSummaryForDisplay();
        return str_contains($command, 'monitors:sync-websites');
    });

    expect($syncEvents)->not()->toBeEmpty('monitors:sync-websites should be scheduled');

    foreach ($syncEvents as $event) {
        // Verify runInBackground is configured for sync command
        expect($event->runInBackground)->toBe(true, 'Sync command should run in background');
    }
});

test('scheduler configuration matches production requirements', function () {
    $schedule = app(Schedule::class);
    $events = $schedule->events();

    // Count different types of scheduled tasks in queue-based architecture
    $dispatchCount = 0;
    $queueCount = 0;
    $syncCount = 0;
    $cleanupCount = 0;
    $reportCount = 0;

    foreach ($events as $event) {
        $command = $event->command ?? $event->getSummaryForDisplay();

        if (str_contains($command, 'monitors:dispatch-scheduled-checks')) {
            $dispatchCount++;
        } elseif (str_contains($command, 'queue-health-check')) {
            $queueCount++;
        } elseif (str_contains($command, 'monitors:sync-websites')) {
            $syncCount++;
        } elseif (str_contains($command, 'daily-cleanup')) {
            $cleanupCount++;
        } elseif (str_contains($command, 'weekly-health-report')) {
            $reportCount++;
        }
    }

    // Verify we have the expected scheduled tasks for queue-based architecture
    expect($dispatchCount)->toBeGreaterThanOrEqual(1, 'Should have monitors:dispatch-scheduled-checks')
        ->and($queueCount)->toBeGreaterThanOrEqual(1, 'Should have queue-health-check')
        ->and($syncCount)->toBeGreaterThanOrEqual(1, 'Should have monitors:sync-websites')
        ->and($cleanupCount)->toBeGreaterThanOrEqual(1, 'Should have daily-cleanup')
        ->and($reportCount)->toBeGreaterThanOrEqual(1, 'Should have weekly-health-report');

    // Verify total events include our automation tasks (at least 5: dispatch, queue, sync, cleanup, report)
    expect(count($events))->toBeGreaterThanOrEqual(5, 'Should have at least 5 scheduled tasks');
});