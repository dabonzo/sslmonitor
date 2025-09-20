<?php

namespace App\Livewire;

use App\Models\SslCheck;
use App\Models\Website;
use App\Services\SslStatusCalculator;
use Illuminate\Support\Collection;
use Livewire\Component;

class SslDashboard extends Component
{
    public int $recentChecksLimit = 5;

    public function getStatusCountsProperty(): array
    {
        $userWebsites = auth()->user()->accessibleWebsitesQuery()->pluck('id');

        if ($userWebsites->isEmpty()) {
            return [
                'valid' => 0,
                'expiring_soon' => 0,
                'expired' => 0,
                'error' => 0,
                'pending' => 0,
                'total' => 0,
            ];
        }

        // Get latest SSL check for each website
        $latestChecks = SslCheck::whereIn('website_id', $userWebsites)
            ->whereIn('id', function ($query) use ($userWebsites) {
                $query->selectRaw('MAX(id)')
                    ->from('ssl_checks')
                    ->whereIn('website_id', $userWebsites)
                    ->groupBy('website_id');
            })
            ->get()
            ->keyBy('website_id');

        // Count websites by status
        $statusCounts = [
            'valid' => 0,
            'expiring_soon' => 0,
            'expired' => 0,
            'error' => 0,
            'pending' => 0,
        ];

        foreach ($userWebsites as $websiteId) {
            if ($latestChecks->has($websiteId)) {
                $check = $latestChecks[$websiteId];
                $status = $check->status;
                $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
            } else {
                $statusCounts['pending']++;
            }
        }

        $statusCounts['total'] = $userWebsites->count();

        return $statusCounts;
    }

    public function getStatusPercentagesProperty(): array
    {
        $counts = $this->statusCounts;
        $total = $counts['total'];

        if ($total === 0) {
            return [
                'valid' => 0.0,
                'expiring_soon' => 0.0,
                'expired' => 0.0,
                'error' => 0.0,
                'pending' => 0.0,
            ];
        }

        return [
            'valid' => round(($counts['valid'] / $total) * 100, 1),
            'expiring_soon' => round(($counts['expiring_soon'] / $total) * 100, 1),
            'expired' => round(($counts['expired'] / $total) * 100, 1),
            'error' => round(($counts['error'] / $total) * 100, 1),
            'pending' => round(($counts['pending'] / $total) * 100, 1),
        ];
    }

    public function getRecentChecksProperty(): Collection
    {
        $userWebsiteIds = auth()->user()->accessibleWebsitesQuery()->pluck('id');

        if ($userWebsiteIds->isEmpty()) {
            return collect();
        }

        return SslCheck::with('website')
            ->whereIn('website_id', $userWebsiteIds)
            ->latest('checked_at')
            ->limit($this->recentChecksLimit)
            ->get();
    }

    public function getCriticalIssuesProperty(): Collection
    {
        $userWebsiteIds = auth()->user()->accessibleWebsitesQuery()->pluck('id');

        if ($userWebsiteIds->isEmpty()) {
            return collect();
        }

        // Get latest checks with critical statuses (expired or error)
        return SslCheck::with('website')
            ->whereIn('website_id', $userWebsiteIds)
            ->whereIn('status', [SslStatusCalculator::STATUS_EXPIRED, SslStatusCalculator::STATUS_ERROR])
            ->whereIn('id', function ($query) use ($userWebsiteIds) {
                $query->selectRaw('MAX(id)')
                    ->from('ssl_checks')
                    ->whereIn('website_id', $userWebsiteIds)
                    ->groupBy('website_id');
            })
            ->latest('checked_at')
            ->get();
    }

    public function getUptimeStatusCountsProperty(): array
    {
        $userWebsites = auth()->user()->accessibleWebsitesQuery()->get();

        if ($userWebsites->isEmpty()) {
            return [
                'up' => 0,
                'down' => 0,
                'slow' => 0,
                'content_mismatch' => 0,
                'unknown' => 0,
                'total_monitored' => 0,
                'total_websites' => 0,
            ];
        }

        // Filter websites with uptime monitoring enabled
        $monitoredWebsites = $userWebsites->where('uptime_monitoring', true);

        // Count websites by uptime status
        $statusCounts = [
            'up' => 0,
            'down' => 0,
            'slow' => 0,
            'content_mismatch' => 0,
            'unknown' => 0,
        ];

        foreach ($monitoredWebsites as $website) {
            $status = $website->uptime_status ?? 'unknown';
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
        }

        $statusCounts['total_monitored'] = $monitoredWebsites->count();
        $statusCounts['total_websites'] = $userWebsites->count();

        return $statusCounts;
    }

    public function getUptimeStatusPercentagesProperty(): array
    {
        $counts = $this->uptimeStatusCounts;
        $totalMonitored = $counts['total_monitored'];

        if ($totalMonitored === 0) {
            return [
                'up' => 0.0,
                'down' => 0.0,
                'slow' => 0.0,
                'content_mismatch' => 0.0,
                'unknown' => 0.0,
            ];
        }

        return [
            'up' => round(($counts['up'] / $totalMonitored) * 100, 1),
            'down' => round(($counts['down'] / $totalMonitored) * 100, 1),
            'slow' => round(($counts['slow'] / $totalMonitored) * 100, 1),
            'content_mismatch' => round(($counts['content_mismatch'] / $totalMonitored) * 100, 1),
            'unknown' => round(($counts['unknown'] / $totalMonitored) * 100, 1),
        ];
    }

    public function getUptimeAvailabilityProperty(): float
    {
        $counts = $this->uptimeStatusCounts;
        $totalMonitored = $counts['total_monitored'];

        if ($totalMonitored === 0) {
            return 0.0;
        }

        // Consider 'up' and 'slow' as available, everything else as unavailable
        $available = $counts['up'] + $counts['slow'];

        return round(($available / $totalMonitored) * 100, 1);
    }

    public function getUptimeCriticalIssuesProperty(): Collection
    {
        $userWebsites = auth()->user()->accessibleWebsitesQuery()->get();

        if ($userWebsites->isEmpty()) {
            return collect();
        }

        // Get websites with uptime monitoring that have critical issues
        return $userWebsites
            ->where('uptime_monitoring', true)
            ->whereIn('uptime_status', ['down', 'content_mismatch'])
            ->values();
    }

    public function getUptimeOverviewProperty(): array
    {
        $userWebsites = auth()->user()->accessibleWebsitesQuery()->get();

        $totalWebsites = $userWebsites->count();
        $monitoredWebsites = $userWebsites->where('uptime_monitoring', true)->count();
        $sslOnlyWebsites = $totalWebsites - $monitoredWebsites;

        return [
            'total_websites' => $totalWebsites,
            'monitored_websites' => $monitoredWebsites,
            'ssl_only_websites' => $sslOnlyWebsites,
            'has_uptime_monitoring' => $monitoredWebsites > 0,
            'has_websites' => $totalWebsites > 0,
        ];
    }

    public function getWebsiteCardsProperty(): Collection
    {
        $userWebsites = auth()->user()->accessibleWebsitesQuery()->get();

        if ($userWebsites->isEmpty()) {
            return collect();
        }

        // Get latest SSL checks for all websites
        $latestSslChecks = SslCheck::whereIn('website_id', $userWebsites->pluck('id'))
            ->whereIn('id', function ($query) use ($userWebsites) {
                $query->selectRaw('MAX(id)')
                    ->from('ssl_checks')
                    ->whereIn('website_id', $userWebsites->pluck('id'))
                    ->groupBy('website_id');
            })
            ->get()
            ->keyBy('website_id');

        // Prepare website cards with combined SSL and uptime status
        return $userWebsites->map(function ($website) use ($latestSslChecks) {
            $sslCheck = $latestSslChecks->get($website->id);

            return (object) [
                'id' => $website->id,
                'name' => $website->name,
                'url' => $website->url,
                'ssl_status' => $sslCheck?->status ?? 'pending',
                'ssl_checked_at' => $sslCheck?->checked_at,
                'uptime_status' => $website->uptime_status,
                'uptime_monitoring' => $website->uptime_monitoring,
                'last_uptime_check_at' => $website->last_uptime_check_at,
                'has_issues' => $this->websiteHasIssues($website, $sslCheck),
                'priority' => $this->getWebsitePriority($website, $sslCheck),
            ];
        })->sortBy('priority')->take(8); // Show top 8 websites
    }

    private function websiteHasIssues($website, $sslCheck): bool
    {
        // Check for SSL issues
        if ($sslCheck && in_array($sslCheck->status, ['expired', 'error', 'expiring_soon'])) {
            return true;
        }

        // Check for uptime issues
        if ($website->uptime_monitoring && in_array($website->uptime_status, ['down', 'content_mismatch'])) {
            return true;
        }

        return false;
    }

    private function getWebsitePriority($website, $sslCheck): int
    {
        // Lower number = higher priority (shows first)

        // Critical SSL issues get highest priority
        if ($sslCheck && in_array($sslCheck->status, ['expired', 'error'])) {
            return 1;
        }

        // Uptime down gets high priority
        if ($website->uptime_monitoring && $website->uptime_status === 'down') {
            return 2;
        }

        // Expiring soon SSL
        if ($sslCheck && $sslCheck->status === 'expiring_soon') {
            return 3;
        }

        // Content mismatch uptime
        if ($website->uptime_monitoring && $website->uptime_status === 'content_mismatch') {
            return 4;
        }

        // Valid/working websites
        return 5;
    }

    public function refresh(): void
    {
        // This will trigger a re-render and recalculate all computed properties
        $this->dispatch('ssl-status-refreshed');
    }

    public function goToWebsiteDetails($websiteId): void
    {
        $this->redirect(route('websites.show', $websiteId), navigate: true);
    }

    public function checkWebsite($websiteId): void
    {
        $website = Website::findOrFail($websiteId);

        // Dispatch SSL check job for immediate check
        \App\Jobs\CheckSslCertificateJob::dispatch($website, true);

        session()->flash('info', 'SSL check queued. Results will appear shortly.');
        $this->refresh();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.ssl-dashboard', [
            'statusCounts' => $this->statusCounts,
            'statusPercentages' => $this->statusPercentages,
            'recentChecks' => $this->recentChecks,
            'criticalIssues' => $this->criticalIssues,
            'team' => $user->primaryTeam(),
            'personalWebsitesCount' => $user->websites()->count(),
            'teamWebsitesCount' => $user->primaryTeam() ? $user->primaryTeam()->websites()->count() : 0,
            'uptimeStatusCounts' => $this->uptimeStatusCounts,
            'uptimeStatusPercentages' => $this->uptimeStatusPercentages,
            'uptimeAvailability' => $this->uptimeAvailability,
            'uptimeCriticalIssues' => $this->uptimeCriticalIssues,
            'uptimeOverview' => $this->uptimeOverview,
            'websiteCards' => $this->websiteCards,
        ]);
    }
}
