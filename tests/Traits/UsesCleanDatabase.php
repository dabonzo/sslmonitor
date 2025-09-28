<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Website;

trait UsesCleanDatabase
{
    use RefreshDatabase;

    protected User $testUser;
    protected $realWebsites;

    protected function setUpCleanDatabase(): void
    {
        // Seed the database with real test data
        $this->seed(\Database\Seeders\TestUserSeeder::class);

        // Get the freshly seeded test user
        $this->testUser = User::where('email', 'bonzo@konjscina.com')->first();

        // Get the real websites for this user
        $this->realWebsites = Website::where('user_id', $this->testUser->id)->get();
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