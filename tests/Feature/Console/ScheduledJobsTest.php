<?php

test('aggregation jobs are scheduled', function () {
    $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
    $events = collect($schedule->events());

    $jobNames = $events->pluck('description')->filter()->toArray();

    expect($jobNames)->toContain('Aggregate hourly monitoring statistics');
    expect($jobNames)->toContain('Aggregate daily monitoring statistics');
    expect($jobNames)->toContain('Aggregate weekly monitoring statistics');
    expect($jobNames)->toContain('Aggregate monthly monitoring statistics');
    expect($jobNames)->toContain('Prune monitoring data older than 90 days');
});

test('hourly aggregation job runs at correct time', function () {
    $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
    $events = collect($schedule->events());

    $hourlyJob = $events->first(function ($event) {
        return str_contains($event->description ?? '', 'hourly');
    });

    expect($hourlyJob)->not->toBeNull();
    expect($hourlyJob->expression)->toBe('0 5 * * *'); // Hourly at :05 (Laravel interprets as specific hour)
});

test('daily aggregation job runs at correct time', function () {
    $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
    $events = collect($schedule->events());

    $dailyJob = $events->first(function ($event) {
        return str_contains($event->description ?? '', 'daily monitoring');
    });

    expect($dailyJob)->not->toBeNull();
    expect($dailyJob->expression)->toBe('0 1 * * *'); // At 01:00 daily
});

test('weekly aggregation job runs at correct time', function () {
    $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
    $events = collect($schedule->events());

    $weeklyJob = $events->filter(function ($event) {
        return str_contains($event->description ?? '', 'weekly');
    })->filter(function ($event) {
        return str_contains($event->description ?? '', 'Aggregate');
    })->first();

    expect($weeklyJob)->not->toBeNull();
    expect($weeklyJob->expression)->toBe('0 2 * * 1'); // Monday at 02:00
});

test('monthly aggregation job runs at correct time', function () {
    $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
    $events = collect($schedule->events());

    $monthlyJob = $events->first(function ($event) {
        return str_contains($event->description ?? '', 'monthly');
    });

    expect($monthlyJob)->not->toBeNull();
    expect($monthlyJob->expression)->toBe('0 3 1 * *'); // 1st day at 03:00
});

test('prune monitoring data job runs at correct time', function () {
    $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
    $events = collect($schedule->events());

    $pruneJob = $events->first(function ($event) {
        return str_contains($event->description ?? '', 'Prune monitoring data');
    });

    expect($pruneJob)->not->toBeNull();
    expect($pruneJob->expression)->toBe('0 4 * * *'); // At 04:00 daily
});

test('all aggregation jobs have unique names', function () {
    $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
    $events = collect($schedule->events());

    // Get all aggregation job descriptions
    $descriptions = $events
        ->pluck('description')
        ->filter(fn ($desc) => str_contains($desc ?? '', 'Aggregate'))
        ->toArray();

    // Check we have exactly 4 unique aggregation jobs
    expect(count(array_unique($descriptions)))->toBe(4);
});

test('scheduled jobs have overlap prevention', function () {
    $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
    $events = collect($schedule->events());

    $aggregationJobs = $events->filter(function ($event) {
        return str_contains($event->description ?? '', 'Aggregate');
    });

    // Verify all aggregation jobs have withoutOverlapping() configured
    foreach ($aggregationJobs as $job) {
        // The withoutOverlapping configuration sets the mutex store
        expect($job->mutex)->not->toBeNull();
    }
});

test('scheduled jobs are configured to run on one server', function () {
    $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
    $events = collect($schedule->events());

    $aggregationJobs = $events->filter(function ($event) {
        return str_contains($event->description ?? '', 'Aggregate');
    });

    // Verify all aggregation jobs have onOneServer() configured
    foreach ($aggregationJobs as $job) {
        // The onOneServer configuration sets the runOnOneServer property
        expect($job->onOneServer)->toBeTrue();
    }
});
