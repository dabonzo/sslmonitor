<?php

namespace App\Providers;

use App\Models\Monitor;
use App\Models\MonitoringAlert;
use App\Models\Website;
use App\Observers\MonitoringAlertObserver;
use App\Observers\MonitorObserver;
use App\Observers\WebsiteObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Website::observe(WebsiteObserver::class);
        Monitor::observe(MonitorObserver::class);
        MonitoringAlert::observe(MonitoringAlertObserver::class);

        // Event listeners are auto-discovered via type-hinted handle() methods
        // See: app/Listeners/* - Laravel automatically registers listeners
        // that have type-hinted event parameters in their handle() method
    }
}
