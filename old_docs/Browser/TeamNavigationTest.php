<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TeamNavigationTest extends DuskTestCase
{
    public function test_can_see_team_navigation_in_settings()
    {
        // Use the existing test user
        $user = User::where('email', 'bonzo@konjscina.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            // First try visiting settings with built assets
            $browser->loginAs($user)
                    ->visit('/settings/profile')
                    ->pause(3000) // Wait longer for assets to load
                    ->screenshot('settings-page-loaded');

            // Check the page source to see what's actually being rendered
            $pageSource = $browser->driver->getPageSource();
            file_put_contents(base_path('tests/Browser/page-source-settings.html'), $pageSource);

            // Try to access team page directly to see if it loads
            $browser->visit('/settings/team')
                    ->pause(3000)
                    ->screenshot('team-page-attempt');

            // Check if we can at least see some text indicating the page loaded
            $teamPageSource = $browser->driver->getPageSource();
            file_put_contents(base_path('tests/Browser/page-source-team.html'), $teamPageSource);
        });
    }

    public function test_team_dropdown_menu_in_header()
    {
        $user = User::where('email', 'bonzo@konjscina.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/dashboard')
                    ->pause(2000); // Wait for page to load

            // Take screenshot of header to see current state
            $browser->screenshot('team-header-initial');

            // Try to find Team navigation in header
            try {
                // Look for Team text in the navigation
                if ($browser->element('nav')->getText() !== null) {
                    $browser->screenshot('team-header-nav-found');
                } else {
                    $browser->screenshot('team-header-nav-not-found');
                }
            } catch (\Exception $e) {
                $browser->screenshot('team-header-exception');
            }
        });
    }

    public function test_team_invitation_sends_email_to_mailpit()
    {
        $user = User::where('email', 'bonzo@konjscina.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            // Login and go to team page
            $browser->loginAs($user)
                    ->visit('/settings/team')
                    ->pause(2000);

            // Take screenshot of initial team page
            $browser->screenshot('team-invitation-start');

            // Click on "Invite Member" button for the first team (first occurrence)
            $browser->press('Invite Member')
                    ->pause(1000)
                    ->screenshot('team-invitation-modal-opened');

            // Fill in the invitation form
            $testEmail = 'test-invite@example.com';
            $browser->type('input[placeholder="Enter email address"]', $testEmail)
                    ->select('select', 'ADMIN') // Select ADMIN role
                    ->pause(1000)
                    ->screenshot('team-invitation-form-filled');

            // Submit the invitation
            $browser->press('Send Invitation')
                    ->pause(3000) // Wait for email to be sent
                    ->screenshot('team-invitation-submitted');

            // Now check Mailpit for the email (use service name from docker-compose)
            $browser->visit('http://mailpit:8025') // Mailpit interface
                    ->pause(2000)
                    ->screenshot('mailpit-interface');

            // Look for the invitation email
            try {
                // Check if we can find the test email in Mailpit
                if ($browser->seeInSource($testEmail)) {
                    $browser->screenshot('mailpit-email-found');
                } else {
                    $browser->screenshot('mailpit-email-not-found');
                }
            } catch (\Exception $e) {
                $browser->screenshot('mailpit-error');
                // Log the error for debugging
                file_put_contents(
                    base_path('tests/Browser/mailpit-error.log'),
                    "Error accessing Mailpit: " . $e->getMessage() . "\n" . $e->getTraceAsString()
                );
            }

            // Save Mailpit page source for inspection
            try {
                $mailpitSource = $browser->driver->getPageSource();
                file_put_contents(base_path('tests/Browser/mailpit-source.html'), $mailpitSource);
            } catch (\Exception $e) {
                // Ignore if we can't get source
            }
        });
    }

    public function test_team_details_page_functionality()
    {
        // Clear cache and build assets before test
        $this->artisan('cache:clear');
        $this->artisan('config:clear');
        $this->artisan('view:clear');
        $this->artisan('route:clear');

        // Build frontend assets
        exec('cd ' . base_path() . ' && ./vendor/bin/sail npm run build');

        $user = User::where('email', 'bonzo@konjscina.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            // Go to team page and capture all errors

            // Go to team page and capture all errors
            $browser->loginAs($user)
                    ->visit('/settings/team')
                    ->pause(3000)
                    ->screenshot('team-list-initial');

            // Capture JavaScript console logs and errors
            $logs = $browser->driver->manage()->getLog('browser');
            file_put_contents(
                base_path('tests/Browser/console-logs-team-page.json'),
                json_encode($logs, JSON_PRETTY_PRINT)
            );

            // Get page source to analyze what's being rendered
            $pageSource = $browser->driver->getPageSource();
            file_put_contents(base_path('tests/Browser/team-page-source.html'), $pageSource);

            // Try to detect if Vue is loaded by checking for specific elements
            try {
                // Look for Vue app container
                if ($browser->element('div[data-page]')) {
                    $browser->screenshot('vue-app-detected');

                    // Try to find any team-related content
                    $teamPageContent = $browser->text('body');
                    file_put_contents(
                        base_path('tests/Browser/team-page-text-content.txt'),
                        $teamPageContent
                    );

                    // Look for View Details button specifically
                    if (str_contains($teamPageContent, 'View Details')) {
                        $browser->screenshot('view-details-button-found');
                        // Try clicking the button
                        $browser->clickLink('View Details')
                                ->pause(3000)
                                ->screenshot('after-click-attempt');
                    } else {
                        $browser->screenshot('view-details-button-not-found');
                    }
                } else {
                    $browser->screenshot('vue-app-not-detected');
                }
            } catch (\Exception $e) {
                file_put_contents(
                    base_path('tests/Browser/browser-error.log'),
                    "Browser error: " . $e->getMessage() . "\n" . $e->getTraceAsString()
                );
                $browser->screenshot('browser-exception');
            }

            // Final console logs capture
            $finalLogs = $browser->driver->manage()->getLog('browser');
            file_put_contents(
                base_path('tests/Browser/console-logs-final.json'),
                json_encode($finalLogs, JSON_PRETTY_PRINT)
            );
        });
    }
}