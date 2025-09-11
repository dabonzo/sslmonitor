<?php

namespace App\Providers;

use App\Models\EmailSettings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class EmailConfigurationProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Override mail configuration with active email settings
        $this->configureMail();
    }

    /**
     * Configure mail settings from database
     */
    protected function configureMail(): void
    {
        try {
            $emailSettings = EmailSettings::active();

            if ($emailSettings) {
                $mailConfig = $emailSettings->toMailConfig();

                // Override the mail configuration
                Config::set('mail.default', $mailConfig['default']);
                Config::set('mail.mailers', array_merge(
                    Config::get('mail.mailers', []),
                    $mailConfig['mailers']
                ));
                Config::set('mail.from', $mailConfig['from']);
            }
        } catch (\Exception $e) {
            // Silently fail if database is not available or has issues
            // This allows the app to still work during migrations or database issues
            logger('Failed to load email configuration from database: '.$e->getMessage());
        }
    }
}
