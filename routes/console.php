<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule SSL certificate checks to run daily at 6:00 AM
Schedule::command('ssl:check-all')->dailyAt('06:00');

// Schedule daily digest emails to run at 8:00 AM (after SSL checks)
Schedule::command('ssl:send-digest')->dailyAt('08:00');

// Alternative: Run SSL checks every 4 hours for more frequent monitoring
// Schedule::command('ssl:check-all')->cron('0 */4 * * *');
