<?php

namespace Tests\Browser\Traits;

/**
 * Trait for common dashboard interaction patterns in browser tests
 */
trait InteractsWithDashboard
{
    /**
     * Helper to check if dashboard is visible
     */
    protected function isDashboardVisible(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'Dashboard') !== false || strpos($snapshot, 'SSL Monitor') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if metrics cards are visible
     */
    protected function hasMetricsCards(): bool
    {
        try {
            $snapshot = $this->snapshot();
            // Look for common metric labels
            return (strpos($snapshot, 'Website') !== false ||
                    strpos($snapshot, 'Checks') !== false ||
                    strpos($snapshot, 'Alerts') !== false);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if website count metric is visible
     */
    protected function hasWebsiteCountMetric(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'Websites') !== false || strpos($snapshot, 'website') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if check history chart is visible
     */
    protected function hasCheckHistoryChart(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'chart') !== false || strpos($snapshot, 'history') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if recent checks timeline is visible
     */
    protected function hasRecentChecksTimeline(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'Recent') !== false || strpos($snapshot, 'Timeline') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if status indicator is visible
     */
    protected function hasStatusIndicator(string $status = 'up'): bool
    {
        try {
            $snapshot = $this->snapshot();
            $statusMap = [
                'up' => 'Up',
                'down' => 'Down',
                'checking' => 'Checking',
            ];
            $displayStatus = $statusMap[$status] ?? $status;
            return strpos($snapshot, $displayStatus) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to wait for dashboard data to load
     */
    protected function waitForDashboardLoad(int $seconds = 10): void
    {
        try {
            $this->waitForFunction("document.querySelectorAll('[class*=\"card\"]').length > 0", $seconds * 1000);
        } catch (\Exception $e) {
            // Ignore timeout
        }
    }

    /**
     * Helper to check if no data state is shown
     */
    protected function hasNoDataState(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'No data') !== false || strpos($snapshot, 'No websites') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if error state is shown
     */
    protected function hasErrorState(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'Error') !== false || strpos($snapshot, 'Failed') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper to check if refresh button is visible
     */
    protected function hasRefreshButton(): bool
    {
        try {
            $snapshot = $this->snapshot();
            return strpos($snapshot, 'Refresh') !== false || strpos($snapshot, 'reload') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
