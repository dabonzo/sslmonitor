<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\SslCertificate;
use App\Models\SslCheck;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SslDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get user's websites for SSL monitoring
        $userWebsites = Website::where('user_id', $user->id)
            ->with(['sslCertificates', 'sslChecks'])
            ->get();

        // Calculate SSL statistics
        $sslStatistics = $this->calculateSslStatistics($userWebsites);

        // Get recent SSL activity
        $recentSslActivity = $this->getRecentSslActivity($userWebsites);

        // Get critical SSL alerts
        $criticalAlerts = $this->getCriticalSslAlerts($userWebsites);

        return Inertia::render('Dashboard', [
            'sslStatistics' => $sslStatistics,
            'recentSslActivity' => $recentSslActivity,
            'criticalAlerts' => $criticalAlerts,
        ]);
    }

    private function calculateSslStatistics($websites): array
    {
        $totalWebsites = $websites->count();

        $validCertificates = 0;
        $expiringSoon = 0;
        $expiredCertificates = 0;
        $totalResponseTime = 0;
        $checksWithResponseTime = 0;

        foreach ($websites as $website) {
            $latestCertificate = $website->sslCertificates()->latest()->first();

            if ($latestCertificate) {
                switch ($latestCertificate->status) {
                    case 'valid':
                        $validCertificates++;
                        break;
                    case 'expiring':
                        $expiringSoon++;
                        break;
                    case 'expired':
                        $expiredCertificates++;
                        break;
                }
            }

            // Calculate average response time from recent checks
            $recentChecks = $website->sslChecks()
                ->whereNotNull('response_time')
                ->latest()
                ->take(10)
                ->get();

            foreach ($recentChecks as $check) {
                $totalResponseTime += $check->response_time;
                $checksWithResponseTime++;
            }
        }

        $avgResponseTime = $checksWithResponseTime > 0
            ? round($totalResponseTime / $checksWithResponseTime)
            : 0;

        return [
            'total_websites' => $totalWebsites,
            'valid_certificates' => $validCertificates,
            'expiring_soon' => $expiringSoon,
            'expired_certificates' => $expiredCertificates,
            'avg_response_time' => $avgResponseTime,
        ];
    }

    private function getRecentSslActivity($websites): array
    {
        $websiteIds = $websites->pluck('id')->toArray();

        if (empty($websiteIds)) {
            return [];
        }

        $recentChecks = SslCheck::whereIn('website_id', $websiteIds)
            ->with('website')
            ->latest('checked_at')
            ->take(10)
            ->get();

        return $recentChecks->map(function ($check) {
            return [
                'id' => $check->id,
                'website_name' => $check->website->name,
                'status' => $check->status,
                'checked_at' => $check->checked_at,
                'time_ago' => $check->checked_at->diffForHumans(),
            ];
        })->toArray();
    }

    private function getCriticalSslAlerts($websites): array
    {
        $alerts = [];

        foreach ($websites as $website) {
            $latestCertificate = $website->sslCertificates()->latest()->first();

            if ($latestCertificate && $latestCertificate->status === 'expired') {
                $alerts[] = [
                    'type' => 'ssl_expired',
                    'website_name' => $website->name,
                    'message' => "SSL certificate expired for {$website->name}",
                    'expires_at' => $latestCertificate->expires_at,
                ];
            }
        }

        return $alerts;
    }
}
