<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AlertSystemTest extends DuskTestCase
{
    public function test_alert_system_functionality()
    {
        $user = User::where('email', 'bonzo@konjscina.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/settings/alerts')
                    ->pause(1000)
                    ->screenshot('alerts-page-initial');

            // Check if alert configurations are displayed
            $pageSource = $browser->driver->getPageSource();

            if (str_contains($pageSource, 'Your Custom Alert Configurations')) {
                $browser->screenshot('alerts-custom-section-found');
                echo "\n✅ SUCCESS: Found custom alert configurations section";

                // Test Configure button functionality
                if (str_contains($pageSource, 'Configure')) {
                    $browser->screenshot('configure-button-found');
                    echo "\n✅ SUCCESS: Configure button is present";

                    // Try to click Configure button
                    try {
                        $browser->press('Configure')
                                ->pause(1000)
                                ->screenshot('after-configure-click');

                        $newPageSource = $browser->driver->getPageSource();

                        if (str_contains($newPageSource, 'Configure Alert:')) {
                            $browser->screenshot('configure-dialog-opened');
                            echo "\n✅ SUCCESS: Configure dialog opened";
                        } else {
                            $browser->screenshot('configure-dialog-failed');
                            echo "\n❌ PROBLEM: Configure dialog did not open";
                        }

                    } catch (\Exception $e) {
                        $browser->screenshot('configure-button-click-failed');
                        echo "\n❌ ERROR clicking Configure button: " . $e->getMessage();
                    }
                } else {
                    echo "\n❌ PROBLEM: Configure button not found";
                }
            } else {
                $browser->screenshot('alerts-no-custom-configs');
                echo "\n⚠️  INFO: No custom alert configurations found";
            }

            // Test Add Alert functionality
            if (str_contains($pageSource, 'Add Alert')) {
                $browser->screenshot('add-alert-button-found');
                echo "\n✅ SUCCESS: Add Alert button is present";

                try {
                    $browser->press('Add Alert')
                            ->pause(1000)
                            ->screenshot('after-add-alert-click');

                    $addPageSource = $browser->driver->getPageSource();

                    if (str_contains($addPageSource, 'Create New Alert')) {
                        $browser->screenshot('add-alert-dialog-opened');
                        echo "\n✅ SUCCESS: Add Alert dialog opened";
                    } else {
                        $browser->screenshot('add-alert-dialog-failed');
                        echo "\n❌ PROBLEM: Add Alert dialog did not open";
                    }

                } catch (\Exception $e) {
                    $browser->screenshot('add-alert-button-click-failed');
                    echo "\n❌ ERROR clicking Add Alert button: " . $e->getMessage();
                }
            } else {
                echo "\n❌ PROBLEM: Add Alert button not found";
            }

            // Check for help section
            if (str_contains($pageSource, 'How Alerts Work')) {
                $browser->screenshot('help-section-found');
                echo "\n✅ SUCCESS: Help section explaining alerts is present";
            } else {
                echo "\n❌ PROBLEM: Help section missing";
            }

            // Save page content for debugging
            file_put_contents(
                base_path('tests/Browser/alert-system-debug.txt'),
                "Alert System Test Debug\n\nPage content:\n{$pageSource}"
            );
        });
    }
}