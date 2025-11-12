<?php

namespace Tests\Browser\Traits;

/**
 * Trait for common authentication interactions in browser tests
 */
trait InteractsWithAuthentication
{
    /**
     * Helper to check if user is logged in (by checking for dashboard access)
     */
    protected function isLoggedIn(): bool
    {
        try {
            $snapshot = $this->snapshot();
            // Check if we can see dashboard or if redirect to login
            return strpos($snapshot, 'Dashboard') !== false || strpos($snapshot, 'SSL Monitor') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to wait for login redirect
     */
    protected function waitForLoginRedirect(): void
    {
        $this->waitForNavigation();
    }

    /**
     * Helper to check if 2FA challenge is required
     */
    protected function isTwoFactorChallenge(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'Two Factor') !== false || strpos($snapshot, 'Authentication Code') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if verification is required
     */
    protected function isEmailVerificationRequired(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'verify-email') !== false || strpos($snapshot, 'Verify Email') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check for toast/notification messages
     */
    protected function hasNotification(string $message): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, $message) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
