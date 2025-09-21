<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\UptimeMonitor\Models\Monitor;

class SslDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get user's websites for SSL monitoring
        $userWebsites = Website::where('user_id', $user->id)->get();

        // Calculate SSL statistics
        $sslStatistics = $this->calculateSslStatistics($userWebsites);

        // Calculate uptime statistics
        $uptimeStatistics = $this->calculateUptimeStatistics($userWebsites);

        // Get recent SSL activity from Spatie monitors
        $recentSslActivity = $this->getRecentSslActivityFromSpatie($userWebsites);

        // Get recent uptime activity
        $recentUptimeActivity = $this->getRecentUptimeActivity($userWebsites);

        // Get critical SSL alerts
        $criticalAlerts = $this->getCriticalSslAlerts($userWebsites);

        return Inertia::render('Dashboard', [
            'sslStatistics' => $sslStatistics,
            'uptimeStatistics' => $uptimeStatistics,
            'recentSslActivity' => $recentSslActivity,
            'recentUptimeActivity' => $recentUptimeActivity,
            'criticalAlerts' => $criticalAlerts,
        ]);
    }

    private function calculateSslStatistics($websites): array
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

        // Get SSL statistics from Spatie monitors
        $monitors = Monitor::whereIn('url', $websiteUrls)
            ->where('certificate_check_enabled', true)
            ->get();

        $validCertificates = $monitors->where('certificate_status', 'valid')->count();
        $expiredCertificates = $monitors->where('certificate_status', 'invalid')->count();

        // Check for certificates expiring soon (within 10 days)
        $expiringSoon = $monitors->filter(function ($monitor) {
            if ($monitor->certificate_status === 'valid' && $monitor->certificate_expiration_date) {
                $expirationDate = \Carbon\Carbon::parse($monitor->certificate_expiration_date);
                return $expirationDate->diffInDays(now()) <= 10;
            }
            return false;
        })->count();

        return [
            'total_websites' => $totalWebsites,
            'valid_certificates' => $validCertificates,
            'expiring_soon' => $expiringSoon,
            'expired_certificates' => $expiredCertificates,
            'avg_response_time' => 0, // Spatie doesn't store SSL response time by default
        ];
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
                            ->whereNotNull('certificate_expiration_date')
                            ->whereRaw('DATEDIFF(certificate_expiration_date, NOW()) <= 10');
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
                if ($expirationDate->diffInDays(now()) <= 10) {
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

        return $monitors->map(function ($monitor) {
            // Extract domain name from URL for cleaner display
            $urlParts = parse_url($monitor->url);
            $websiteName = $urlParts['host'] ?? $monitor->url;

            return [
                'id' => $monitor->id,
                'website_name' => $websiteName,
                'status' => $monitor->uptime_status,
                'checked_at' => $monitor->uptime_last_check_date,
                'time_ago' => $monitor->uptime_last_check_date->diffForHumans(),
                'response_time' => null, // Spatie doesn't store response time by default
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

        return $monitors->map(function ($monitor) {
            // Extract domain name from URL for cleaner display
            $urlParts = parse_url($monitor->url);
            $websiteName = $urlParts['host'] ?? $monitor->url;

            return [
                'id' => $monitor->id,
                'website_name' => $websiteName,
                'status' => $monitor->certificate_status,
                'checked_at' => $monitor->updated_at,
                'time_ago' => $monitor->updated_at->diffForHumans(),
            ];
        })->toArray();
    }
}
