<?php

namespace App\Livewire;

use App\Models\SslCheck;
use App\Models\Website;
use App\Services\SslStatusCalculator;
use Illuminate\Support\Collection;
use Livewire\Component;

class SslDashboard extends Component
{
    public int $recentChecksLimit = 10;

    public function getStatusCountsProperty(): array
    {
        $userWebsites = auth()->user()->accessibleWebsites()->pluck('id');
        
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
            ->whereIn('id', function($query) use ($userWebsites) {
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
        $userWebsiteIds = auth()->user()->accessibleWebsites()->pluck('id');

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
        $userWebsiteIds = auth()->user()->accessibleWebsites()->pluck('id');

        if ($userWebsiteIds->isEmpty()) {
            return collect();
        }

        // Get latest checks with critical statuses (expired or error)
        return SslCheck::with('website')
            ->whereIn('website_id', $userWebsiteIds)
            ->whereIn('status', [SslStatusCalculator::STATUS_EXPIRED, SslStatusCalculator::STATUS_ERROR])
            ->whereIn('id', function($query) use ($userWebsiteIds) {
                $query->selectRaw('MAX(id)')
                    ->from('ssl_checks')
                    ->whereIn('website_id', $userWebsiteIds)
                    ->groupBy('website_id');
            })
            ->latest('checked_at')
            ->get();
    }

    public function refresh(): void
    {
        // This will trigger a re-render and recalculate all computed properties
        $this->dispatch('ssl-status-refreshed');
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
        ]);
    }
}