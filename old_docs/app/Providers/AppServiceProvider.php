<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
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
        // Configure Pulse dashboard authorization
        Gate::define('viewPulse', function ($user = null) {
            // Allow access in local development environment
            if (app()->environment('local')) {
                return true;
            }

            // In production, restrict to specific users
            return in_array(optional($user)->email, [
                // Add authorized user emails here for production
            ]);
        });
    }
}
