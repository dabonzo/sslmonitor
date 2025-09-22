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
                    ->screenshot('homepage-simple')

                    // Login with credentials
                    ->type('input[name="email"]', 'bonzo@konjscina.com')
                    ->type('input[name="password"]', 'to16ro12')
                    ->screenshot('login-form-filled')
                    ->press('SIGN IN')
                    ->pause(3000)
                    ->screenshot('after-login');
        });
    }
}