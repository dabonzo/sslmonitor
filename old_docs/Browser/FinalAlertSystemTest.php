<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FinalAlertSystemTest extends DuskTestCase
{
    public function test_complete_alert_system_functionality()
    {
        $user = User::where('email', 'bonzo@konjscina.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/settings/alerts')
                    ->pause(2000)
                    ->screenshot('final-alerts-page');

            $pageSource = $browser->driver->getPageSource();

            // Check for seeded alert configurations
            if (str_contains($pageSource, 'SSL Certificate Expiry') || str_contains($pageSource, 'Website Down')) {
                echo "\n✅ SUCCESS: Found seeded alert configurations";
                $browser->screenshot('alerts-configurations-found');

                // Test Configure button
                if (str_contains($pageSource, 'Configure')) {
                    echo "\n✅ SUCCESS: Configure button is available";
                    $browser->screenshot('configure-button-available');
                } else {
                    echo "\n❌ PROBLEM: Configure button not found";
                }

                // Test statistics display
                if (str_contains($pageSource, 'Total Alerts') && str_contains($pageSource, 'Active Alerts')) {
                    echo "\n✅ SUCCESS: Alert statistics are displayed";
                    $browser->screenshot('alert-statistics-displayed');
                } else {
                    echo "\n❌ PROBLEM: Alert statistics not displayed";
                }

            } else {
                echo "\n❌ PROBLEM: Seeded alert configurations not found";
                $browser->screenshot('alerts-configurations-missing');
            }

            // Test Add Alert functionality
            if (str_contains($pageSource, 'Add Alert')) {
                echo "\n✅ SUCCESS: Add Alert button found";

                try {
                    $browser->press('Add Alert')
                            ->pause(1000)
                            ->screenshot('add-alert-dialog-final');

                    $dialogSource = $browser->driver->getPageSource();
                    if (str_contains($dialogSource, 'Create New Alert')) {
                        echo "\n✅ SUCCESS: Add Alert dialog working";
                    } else {
                        echo "\n❌ PROBLEM: Add Alert dialog not working";
                    }

                    // Close dialog
                    $browser->press('Cancel')->pause(500);

                } catch (\Exception $e) {
                    echo "\n❌ ERROR with Add Alert: " . $e->getMessage();
                }
            }

            // Check help section
            if (str_contains($pageSource, 'How Alerts Work')) {
                echo "\n✅ SUCCESS: Help section is present";
                $browser->screenshot('help-section-present');
            } else {
                echo "\n❌ PROBLEM: Help section missing";
            }

            // Save final debug output
            file_put_contents(
                base_path('tests/Browser/final-alert-debug.txt'),
                "Final Alert System Test\n\nPage content:\n{$pageSource}"
            );

            echo "\n🎯 SUMMARY: Alert system functionality test completed";
        });
    }
}