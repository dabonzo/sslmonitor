<?php

namespace Tests\Traits;

use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;

trait UsesCleanDatabase
{
    use RefreshDatabase;

    protected User $testUser;

    protected $realWebsites;

    protected function setUpCleanDatabase(): void
    {
        // Test data is now set up in Pest.php beforeEach hook
        // Just get the references that were set up there
        $this->testUser = User::where('email', 'bonzo@konjscina.com')->first();

        if ($this->testUser) {
            $this->realWebsites = Website::where('user_id', $this->testUser->id)->get();
        } else {
            $this->realWebsites = collect();
        }
    }

    protected function getRealWebsite(string $domain): ?Website
    {
        return $this->realWebsites->first(function ($website) use ($domain) {
            return str_contains($website->url, $domain);
        });
    }

    protected function getRealWebsites(?int $count = null): \Illuminate\Support\Collection
    {
        return $count ? $this->realWebsites->take($count) : $this->realWebsites;
    }
}
