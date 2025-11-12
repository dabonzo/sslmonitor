<?php

namespace Tests\Browser\Traits;

/**
 * Trait for common website interaction patterns in browser tests
 */
trait InteractsWithWebsites
{
    /**
     * Helper to check if websites list is visible
     */
    protected function hasWebsitesList(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'Websites') !== false || strpos($snapshot, 'ssl/websites') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if website card is visible by name
     */
    protected function hasWebsiteCard(string $websiteName): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, $websiteName) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check SSL status badge
     */
    protected function hasSSLStatus(string $status): bool
    {
        try {
            $snapshot = $this->snapshot();
            $statusMap = [
                'valid' => 'Valid',
                'invalid' => 'Invalid',
                'expired' => 'Expired',
                'expiring' => 'Expiring Soon',
                'unknown' => 'Unknown',
            ];
            $displayStatus = $statusMap[$status] ?? $status;
            return strpos($snapshot, $displayStatus) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if create website button is visible
     */
    protected function hasCreateWebsiteButton(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'Add Website') !== false || strpos($snapshot, 'New Website') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if bulk operations menu is visible
     */
    protected function hasBulkOperationsButton(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'Bulk') !== false || strpos($snapshot, 'Check All') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to wait for website data to load
     */
    protected function waitForWebsiteDataLoad(int $seconds = 5): void
    {
        try {
            $this->waitForFunction("document.querySelectorAll('[data-testid=\"website-card\"]').length > 0", $seconds * 1000);
        } catch (\Exception $e) {
            // Ignore timeout, element might not exist
        }
    }

    /**
     * Helper to check if loading spinner is visible
     */
    protected function isLoading(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'loading') !== false || strpos($snapshot, 'spinner') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if no websites message is visible
     */
    protected function hasNoWebsitesMessage(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'No websites') !== false || strpos($snapshot, 'no data') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
