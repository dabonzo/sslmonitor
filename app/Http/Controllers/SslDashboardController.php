<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\SslMonitoringCacheService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\UptimeMonitor\Models\Monitor;

class SslDashboardController extends Controller
{
    public function index(Request $request, SslMonitoringCacheService $cacheService): Response
    {
        $user = $request->user();

        // Cache user's team IDs to avoid repeated queries
        $userTeamIds = $cacheService->cacheUserTeamIds(
            $user->id,
            fn() => $user->teams()->pluck('teams.id')
        );

        // Get all websites accessible to user with optimized eager loading
        $allUserWebsites = Website::where(function ($q) use ($user, $userTeamIds) {
            $q->where('user_id', $user->id) // Personal websites
              ->orWhereIn('team_id', $userTeamIds); // Team websites
        })
        ->with(['team', 'user']) // Eager load relationships
        ->orderBy('updated_at', 'desc') // Most recently updated first
        ->get();

        // Calculate SSL statistics with caching
        $sslStatistics = $this->calculateSslStatistics($allUserWebsites, $cacheService);

        // Calculate uptime statistics with caching
        $uptimeStatistics = $this->calculateUptimeStatistics($allUserWebsites, $cacheService);

        // Get recent SSL activity from Spatie monitors with caching
        $recentSslActivity = $this->getRecentSslActivityFromSpatie($allUserWebsites, $cacheService);

        // Get recent uptime activity with caching
        $recentUptimeActivity = $this->getRecentUptimeActivity($allUserWebsites, $cacheService);

        // Get critical SSL alerts
        $criticalAlerts = $this->getCriticalSslAlerts($allUserWebsites);

        // Get team transfer suggestions
        $transferSuggestions = $this->getTeamTransferSuggestions($user, $allUserWebsites);

        // Get certificate expiration timeline
        $expirationTimeline = $this->getCertificateExpirationTimeline($allUserWebsites);

        return Inertia::render('Dashboard', [
            'sslStatistics' => $sslStatistics,
            'uptimeStatistics' => $uptimeStatistics,
            'recentSslActivity' => $recentSslActivity,
            'recentUptimeActivity' => $recentUptimeActivity,
            'criticalAlerts' => $criticalAlerts,
            'transferSuggestions' => $transferSuggestions,
            'expirationTimeline' => $expirationTimeline,
        ]);
    }

    private function calculateSslStatistics($websites, SslMonitoringCacheService $cacheService): array
    {
        $totalWebsites = $websites->count();
        $websiteUrls = $websites->pluck('url')->toArray();

        if (empty($websiteUrls)) {
            return [
                'total_websites' => 0,
                'valid_certificates' => 0,
                'expiring_soon' => 0,
                'expired_certificates' => 0,
                'avg_response_time' => 0,
            ];
        }

        return $cacheService->cacheSslStatistics($websiteUrls, function () use ($websiteUrls) {
            // Get SSL statistics from Spatie monitors with optimized query
            $monitors = Monitor::whereIn('url', $websiteUrls)
                ->where('certificate_check_enabled', true)
                ->select(['url', 'certificate_status', 'certificate_expiration_date', 'uptime_check_response_time_in_ms'])
                ->get();

            $validCertificates = $monitors->where('certificate_status', 'valid')->count();
            $expiredCertificates = $monitors->where('certificate_status', 'invalid')->count();

            // Check for certificates expiring soon (within 10 days)
            $expiringSoon = $monitors->filter(function ($monitor) {
                if ($monitor->certificate_status === 'valid' && $monitor->certificate_expiration_date) {
                    $expirationDate = \Carbon\Carbon::parse($monitor->certificate_expiration_date);
                    $daysUntilExpiry = (int) now()->diffInDays($expirationDate, false);
                    return $daysUntilExpiry <= 10 && $daysUntilExpiry > 0;
                }
                return false;
            })->count();

            return [
                'total_websites' => count($websiteUrls),
                'valid_certificates' => $validCertificates,
                'expiring_soon' => $expiringSoon,
                'expired_certificates' => $expiredCertificates,
                'avg_response_time' => 0, // Spatie doesn't store SSL response time by default
            ];
        });
    }


    private function getCriticalSslAlerts($websites): array
    {
        $websiteUrls = $websites->pluck('url')->toArray();
        $alerts = [];

        if (empty($websiteUrls)) {
            return $alerts;
        }

        $monitors = Monitor::whereIn('url', $websiteUrls)
            ->where('certificate_check_enabled', true)
            ->where(function ($query) {
                $query->where('certificate_status', 'invalid')
                      ->orWhere(function ($q) {
                          $q->where('certificate_status', 'valid')
                            ->whereNotNull('certificate_expiration_date');
                      });
            })
            ->get();

        foreach ($monitors as $monitor) {
            $urlParts = parse_url($monitor->url);
            $websiteName = $urlParts['host'] ?? $monitor->url;

            if ($monitor->certificate_status === 'invalid') {
                $alerts[] = [
                    'type' => 'ssl_expired',
                    'website_name' => $websiteName,
                    'message' => "SSL certificate expired or invalid for {$websiteName}",
                    'expires_at' => $monitor->certificate_expiration_date,
                ];
            } elseif ($monitor->certificate_status === 'valid' && $monitor->certificate_expiration_date) {
                $expirationDate = \Carbon\Carbon::parse($monitor->certificate_expiration_date);
                $daysUntilExpiry = (int) now()->diffInDays($expirationDate, false);
                if ($daysUntilExpiry <= 10 && $daysUntilExpiry > 0) {
                    $alerts[] = [
                        'type' => 'ssl_expiring_soon',
                        'website_name' => $websiteName,
                        'message' => "SSL certificate expiring soon for {$websiteName}",
                        'expires_at' => $monitor->certificate_expiration_date,
                    ];
                }
            }
        }

        return $alerts;
    }

    private function calculateUptimeStatistics($websites): array
    {
        $totalUptimeMonitors = $websites->where('uptime_monitoring_enabled', true)->count();

        if ($totalUptimeMonitors === 0) {
            return [
                'total_monitors' => 0,
                'healthy_monitors' => 0,
                'down_monitors' => 0,
                'avg_response_time' => 0,
                'uptime_percentage' => 0,
            ];
        }

        $websiteUrls = $websites->where('uptime_monitoring_enabled', true)->pluck('url')->toArray();

        $monitors = Monitor::whereIn('url', $websiteUrls)->get();

        $healthyMonitors = $monitors->where('uptime_status', 'up')->count();
        $downMonitors = $monitors->where('uptime_status', 'down')->count();

        // Calculate average response time from recent checks
        $totalResponseTime = 0;
        $responseTimeCount = 0;

        foreach ($monitors as $monitor) {
            if ($monitor->uptime_check_response_time_in_ms) {
                $totalResponseTime += $monitor->uptime_check_response_time_in_ms;
                $responseTimeCount++;
            }
        }

        $avgResponseTime = $responseTimeCount > 0
            ? round($totalResponseTime / $responseTimeCount)
            : 0;

        $uptimePercentage = $totalUptimeMonitors > 0
            ? round(($healthyMonitors / $totalUptimeMonitors) * 100, 1)
            : 0;

        return [
            'total_monitors' => $totalUptimeMonitors,
            'healthy_monitors' => $healthyMonitors,
            'down_monitors' => $downMonitors,
            'avg_response_time' => $avgResponseTime,
            'uptime_percentage' => $uptimePercentage,
        ];
    }

    private function getRecentUptimeActivity($websites): array
    {
        $websiteUrls = $websites->where('uptime_monitoring_enabled', true)->pluck('url')->toArray();

        if (empty($websiteUrls)) {
            return [];
        }

        $monitors = Monitor::whereIn('url', $websiteUrls)
            ->whereNotNull('uptime_last_check_date')
            ->orderBy('uptime_last_check_date', 'desc')
            ->take(10)
            ->get();

        return $monitors->map(function ($monitor) use ($websites) {
            // Extract domain name from URL for cleaner display
            $urlParts = parse_url($monitor->url);
            $websiteName = $urlParts['host'] ?? $monitor->url;

            // Get website ID for edit link
            $website = $websites->firstWhere('url', $monitor->url);

            return [
                'id' => $monitor->id,
                'website_id' => $website?->id,
                'website_name' => $websiteName,
                'status' => $monitor->uptime_status,
                'checked_at' => $monitor->uptime_last_check_date,
                'time_ago' => $monitor->uptime_last_check_date->diffForHumans(),
                'response_time' => $monitor->uptime_check_response_time_in_ms,
                'failure_reason' => $monitor->uptime_check_failure_reason,
                'content_failure_reason' => $monitor->content_validation_failure_reason,
            ];
        })->toArray();
    }

    private function getRecentSslActivityFromSpatie($websites): array
    {
        $websiteUrls = $websites->pluck('url')->toArray();

        if (empty($websiteUrls)) {
            return [];
        }

        $monitors = Monitor::whereIn('url', $websiteUrls)
            ->where('certificate_check_enabled', true)
            ->whereNotNull('certificate_status')
            ->where('certificate_status', '!=', 'not yet checked')
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        return $monitors->map(function ($monitor) use ($websites) {
            // Extract domain name from URL for cleaner display
            $urlParts = parse_url($monitor->url);
            $websiteName = $urlParts['host'] ?? $monitor->url;

            // Get website ID for edit link
            $website = $websites->firstWhere('url', $monitor->url);

            return [
                'id' => $monitor->id,
                'website_id' => $website?->id,
                'website_name' => $websiteName,
                'status' => $monitor->certificate_status,
                'checked_at' => $monitor->updated_at,
                'time_ago' => $monitor->updated_at->diffForHumans(),
                'failure_reason' => $monitor->certificate_check_failure_reason,
            ];
        })->toArray();
    }

    private function getTeamTransferSuggestions($user, $allWebsites): array
    {
        // Get personal websites that can be transferred
        $personalWebsites = $allWebsites->where('user_id', $user->id)->whereNull('team_id');

        // Get user's teams where they can transfer websites (OWNER, ADMIN roles)
        $availableTeams = $user->teams()
            ->wherePivotIn('role', ['OWNER', 'ADMIN'])
            ->limit(5) // Show only top 5 for quick actions
            ->get(['teams.id', 'teams.name']);

        // Calculate suggestions
        $suggestions = [
            'personal_websites_count' => $personalWebsites->count(),
            'available_teams_count' => $availableTeams->count(),
            'quick_transfer_teams' => $availableTeams->map(fn($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'member_count' => $team->members()->count(),
            ])->toArray(),
            'should_show_suggestion' => $personalWebsites->count() > 0 && $availableTeams->count() > 0,
        ];

        return $suggestions;
    }

    private function getCertificateExpirationTimeline($websites): array
    {
        $websiteUrls = $websites->pluck('url')->toArray();

        if (empty($websiteUrls)) {
            return [
                'expiring_7_days' => [],
                'expiring_30_days' => [],
                'expiring_90_days' => [],
            ];
        }

        $monitors = Monitor::whereIn('url', $websiteUrls)
            ->where('certificate_check_enabled', true)
            ->where('certificate_status', 'valid')
            ->whereNotNull('certificate_expiration_date')
            ->orderBy('certificate_expiration_date', 'asc')
            ->get();

        $now = now();
        $expiring7Days = [];
        $expiring30Days = [];
        $expiring90Days = [];

        foreach ($monitors as $monitor) {
            $expirationDate = \Carbon\Carbon::parse($monitor->certificate_expiration_date);
            $daysUntilExpiry = (int) $now->diffInDays($expirationDate, false);

            // Get website for additional info
            $website = $websites->firstWhere('url', $monitor->url);
            $urlParts = parse_url($monitor->url);
            $websiteName = $urlParts['host'] ?? $monitor->url;

            $item = [
                'website_id' => $website?->id,
                'website_name' => $websiteName,
                'expires_at' => $monitor->certificate_expiration_date,
                'days_until_expiry' => $daysUntilExpiry,
            ];

            if ($daysUntilExpiry <= 7 && $daysUntilExpiry > 0) {
                $expiring7Days[] = $item;
            } elseif ($daysUntilExpiry <= 30 && $daysUntilExpiry > 7) {
                $expiring30Days[] = $item;
            } elseif ($daysUntilExpiry <= 90 && $daysUntilExpiry > 30) {
                $expiring90Days[] = $item;
            }
        }

        return [
            'expiring_7_days' => $expiring7Days,
            'expiring_30_days' => $expiring30Days,
            'expiring_90_days' => $expiring90Days,
        ];
    }
}
