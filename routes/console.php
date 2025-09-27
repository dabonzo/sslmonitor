<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Production automation scheduling
// Every minute: Routine uptime checks for active websites
Schedule::command('monitor:check-uptime')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Twice daily: SSL certificate checks (6 AM and 6 PM)
Schedule::command('monitor:check-certificate')
    ->twiceDaily(6, 18)
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Every 5 minutes: Queue health monitoring
Schedule::call(function () {
    \App\Support\AutomationLogger::scheduler('Queue health check started');

    // Check failed jobs count
    $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();

    if ($failedJobs > env('MAX_FAILED_JOBS', 10)) {
        \App\Support\AutomationLogger::error('High number of failed jobs detected', [
            'failed_jobs_count' => $failedJobs,
            'threshold' => env('MAX_FAILED_JOBS', 10)
        ]);
    }

    \App\Support\AutomationLogger::scheduler('Queue health check completed', [
        'failed_jobs_count' => $failedJobs
    ]);
})
    ->everyFiveMinutes()
    ->name('queue-health-check');

// Daily at 2 AM: Cleanup old jobs and logs
Schedule::call(function () {
    \App\Support\AutomationLogger::scheduler('Daily cleanup started');

    // Clean up failed jobs older than 7 days
    $deletedFailedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')
        ->where('failed_at', '<', now()->subDays(7))
        ->delete();

    // Clean up completed jobs older than 24 hours
    $deletedJobs = \Illuminate\Support\Facades\DB::table('jobs')
        ->where('created_at', '<', now()->subDay())
        ->delete();

    \App\Support\AutomationLogger::scheduler('Daily cleanup completed', [
        'deleted_failed_jobs' => $deletedFailedJobs,
        'deleted_completed_jobs' => $deletedJobs
    ]);
})
    ->dailyAt('02:00')
    ->name('daily-cleanup');

// Every 30 minutes: Sync websites with monitors
Schedule::command('monitors:sync-websites')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Weekly on Sunday at 3 AM: System health report
Schedule::call(function () {
    \App\Support\AutomationLogger::scheduler('Weekly health report started');

    $stats = [
        'total_websites' => \App\Models\Website::count(),
        'active_websites' => \App\Models\Website::where('ssl_monitoring_enabled', true)
            ->orWhere('uptime_monitoring_enabled', true)->count(),
        'total_users' => \App\Models\User::count(),
        'failed_jobs_last_week' => \Illuminate\Support\Facades\DB::table('failed_jobs')
            ->where('failed_at', '>', now()->subWeek())->count(),
        'avg_response_time' => \Spatie\UptimeMonitor\Models\Monitor::avg('latest_run_runtime') ?? 0,
    ];

    \App\Support\AutomationLogger::scheduler('Weekly health report', $stats);
})
    ->weeklyOn(0, '03:00')
    ->name('weekly-health-report');
