<?php

namespace Tests\Traits;

use App\Models\User;
use App\Models\Website;

trait UsesSharedRealData
{
    protected function setUpSharedRealData(): void
    {
        // Ensure database is migrated once for shared data
        static $migrated = false;
        if (!$migrated) {
            $this->artisan('migrate:fresh');
            $migrated = true;
        }

        // Since PerformanceTest doesn't use RefreshDatabase,
        // the data should be persistent after first setup
        // We'll use the existing testUser if available

        if (!isset($this->testUser) || !$this->testUser) {
            // Get or create the seeded test user
            $user = User::where('email', 'bonzo@konjscina.com')->first();

            if (!$user) {
                // If no seeded data exists, run the seeder once
                $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\TestUserSeeder']);
                $user = User::where('email', 'bonzo@konjscina.com')->first();
            }

            $this->testUser = $user;
        }

        // Get the real websites for this user
        if (!isset($this->realWebsites) || $this->realWebsites->isEmpty()) {
            $this->realWebsites = Website::where('user_id', $this->testUser->id)->get();
        }
    }

    protected function getRealWebsite(string $domain): ?Website
    {
        return $this->realWebsites->first(function ($website) use ($domain) {
            return str_contains($website->url, $domain);
        });
    }

    protected function getRealWebsites(int $count = null): \Illuminate\Support\Collection
    {
        return $count ? $this->realWebsites->take($count) : $this->realWebsites;
    }
}