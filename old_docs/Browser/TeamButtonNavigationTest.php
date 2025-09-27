<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TeamButtonNavigationTest extends DuskTestCase
{
    public function test_development_team_button_navigation()
    {
        $user = User::where('email', 'bonzo@konjscina.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->pause(1000)
                    ->screenshot('dashboard-initial');

            // Check if Development Team button appears
            $pageSource = $browser->driver->getPageSource();

            if (str_contains($pageSource, 'Development Team')) {
                $browser->screenshot('development-team-button-found');

                // Try to click the Development Team button
                try {
                    $browser->clickLink('Development Team')
                            ->pause(1000)
                            ->screenshot('after-team-button-click');

                    // Check what page we landed on
                    $currentUrl = $browser->driver->getCurrentURL();
                    echo "\nCurrent URL after click: " . $currentUrl;

                    $newPageSource = $browser->driver->getPageSource();

                    // Save page content for debugging
                    file_put_contents(
                        base_path('tests/Browser/team-navigation-debug.txt'),
                        "URL: {$currentUrl}\n\nPage content:\n{$newPageSource}"
                    );

                    // Check what we see on the page
                    if (str_contains($newPageSource, 'Create New Team')) {
                        $browser->screenshot('shows-team-creation-page-wrong');
                        echo "\n❌ PROBLEM: Shows team creation page instead of team details";
                    } elseif (str_contains($newPageSource, 'Development Team')) {
                        $browser->screenshot('shows-team-details-correct');
                        echo "\n✅ SUCCESS: Shows team details page";
                    } else {
                        $browser->screenshot('shows-unknown-page');
                        echo "\n❓ UNKNOWN: Shows different page";
                    }

                } catch (\Exception $e) {
                    $browser->screenshot('team-button-click-failed');
                    echo "\n❌ ERROR clicking Development Team button: " . $e->getMessage();
                }
            } else {
                $browser->screenshot('development-team-button-not-found');
                echo "\n❌ Development Team button not found on dashboard";
            }
        });
    }
}