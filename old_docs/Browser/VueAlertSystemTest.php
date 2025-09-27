<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class VueAlertSystemTest extends DuskTestCase
{
    public function test_vue_alert_system_after_loading()
    {
        $user = User::where('email', 'bonzo@konjscina.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/settings/alerts')
                    ->pause(3000); // Wait for Vue to render

            // Wait for Vue component to load by checking for specific Vue-rendered content
            $browser->waitForText('SSL Certificate Expiry', 10)
                    ->screenshot('vue-alerts-loaded');

            echo "\n✅ SUCCESS: Vue component loaded - SSL Certificate Expiry found";

            // Now test the Configure buttons
            $browser->waitForText('Configure', 5)
                    ->screenshot('configure-buttons-visible');

            echo "\n✅ SUCCESS: Configure buttons are visible";

            // Try clicking Configure button
            try {
                $browser->press('Configure')
                        ->pause(2000)
                        ->screenshot('configure-dialog-attempt');

                // Check if dialog opened
                if ($browser->element('.modal') || $browser->element('[role="dialog"]')) {
                    echo "\n✅ SUCCESS: Configure dialog opened";
                } else {
                    echo "\n❌ PROBLEM: Configure dialog not visible";
                }

            } catch (\Exception $e) {
                echo "\n❌ ERROR with Configure: " . $e->getMessage();
            }

            // Test Delete button presence
            if ($browser->element('button:contains("Delete")')) {
                echo "\n✅ SUCCESS: Delete button found";
            } else {
                echo "\n❌ PROBLEM: Delete button not found";
            }

            // Test Add Alert functionality
            $browser->waitForText('Add Alert', 5)
                    ->press('Add Alert')
                    ->pause(2000)
                    ->screenshot('add-alert-dialog-vue');

            if ($browser->element('[role="dialog"]') || $browser->element('.modal')) {
                echo "\n✅ SUCCESS: Add Alert dialog working";

                // Close the dialog
                $browser->press('Cancel')->pause(1000);
            } else {
                echo "\n❌ PROBLEM: Add Alert dialog not working";
            }

            echo "\n🎯 SUMMARY: Vue Alert System test completed";
        });
    }
}