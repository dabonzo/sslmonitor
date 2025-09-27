<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class SimpleScreenshotTest extends DuskTestCase
{
    public function test_simple_homepage_screenshot()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->pause(2000)
                    ->screenshot('homepage-simple');

            // Check if we can find the email input field (login page)
            try {
                $browser->assertPresent('input[name="email"]');
                // We're on login page
                $browser
                    ->type('email', 'bonzo@konjscina.com')
                    ->type('password', 'to16ro12')
                    ->screenshot('login-form-filled')
                    ->press('SIGN IN')
                    ->pause(3000)
                    ->screenshot('after-login');
            } catch (\Exception $e) {
                // No login form found, we might be on dashboard or another page
                $browser->screenshot('no-login-form-found');
            }
        });
    }
}