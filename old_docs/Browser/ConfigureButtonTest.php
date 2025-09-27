<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ConfigureButtonTest extends DuskTestCase
{
    public function test_configure_button_functionality()
    {
        $user = User::where('email', 'bonzo@konjscina.com')->first();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit('/settings/alerts')
                    ->pause(2000)
                    ->screenshot('before-configure-click');

            // Look for Configure button and try to click it
            try {
                // Try to find and click the first Configure button
                $browser->press('Configure')
                        ->pause(2000)
                        ->screenshot('after-configure-click');

                $pageSource = $browser->driver->getPageSource();

                if (str_contains($pageSource, 'Configure Alert:')) {
                    echo "\n✅ SUCCESS: Configure dialog opened";
                    $browser->screenshot('configure-dialog-success');

                    // Try to interact with the dialog
                    if (str_contains($pageSource, 'Alert Level')) {
                        echo "\n✅ SUCCESS: Alert Level field found in dialog";
                        $browser->screenshot('configure-dialog-fields');
                    }

                    // Try to close dialog
                    if (str_contains($pageSource, 'Cancel')) {
                        $browser->press('Cancel')->pause(1000);
                        echo "\n✅ SUCCESS: Cancel button works";
                    }

                } else {
                    echo "\n❌ PROBLEM: Configure dialog did not open";
                    $browser->screenshot('configure-dialog-failed');

                    // Check for any JavaScript errors
                    $logs = $browser->driver->manage()->getLog('browser');
                    foreach ($logs as $log) {
                        if ($log['level'] === 'SEVERE') {
                            echo "\n🚫 JS ERROR: " . $log['message'];
                        }
                    }
                }

            } catch (\Exception $e) {
                echo "\n❌ ERROR clicking Configure: " . $e->getMessage();
                $browser->screenshot('configure-click-error');
            }

            // Test Delete button functionality
            try {
                echo "\n\n🗑️ Testing Delete functionality...";

                // Look for Delete button
                if (str_contains($browser->driver->getPageSource(), 'Delete')) {
                    echo "\n✅ SUCCESS: Delete button found";

                    // Note: We won't actually click delete in test to preserve data
                    echo "\n⚠️ SKIPPING: Delete button click (preserving test data)";
                } else {
                    echo "\n❌ PROBLEM: Delete button not found";
                }

            } catch (\Exception $e) {
                echo "\n❌ ERROR with Delete: " . $e->getMessage();
            }

            // Save page source for debugging
            file_put_contents(
                base_path('tests/Browser/configure-button-debug.txt'),
                "Configure Button Test Debug\n\nPage source:\n" . $browser->driver->getPageSource()
            );
        });
    }
}