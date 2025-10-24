<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Monitor;
use App\Models\Website;
use App\Services\MonitoringHistoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitorHistoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private MonitoringHistoryService $historyService
    ) {}

    /**
     * Get monitoring history for a monitor.
     */
    public function history(Request $request, Monitor $monitor): JsonResponse
    {
        $this->authorizeMonitorAccess($monitor);

        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:1000',
            'check_type' => 'nullable|string|in:combined,ssl,uptime,content',
            'status' => 'nullable|string|in:success,failed',
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $query = $monitor->monitoringResults()->orderBy('started_at', 'desc');

        // Apply filters
        if (isset($validated['check_type'])) {
            $query->where('check_type', $validated['check_type']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['days'])) {
            $query->where('started_at', '>=', now()->subDays($validated['days']));
        }

        // Apply pagination
        $limit = $validated['limit'] ?? 50;
        $results = $query->limit($limit)->get();

        return response()->json([
            'data' => $results->map(function ($result) {
                return [
                    'id' => $result->id,
                    'uuid' => $result->uuid,
                    'monitor_id' => $result->monitor_id,
                    'website_id' => $result->website_id,
                    'check_type' => $result->check_type,
                    'status' => $result->status,
                    'started_at' => $result->started_at,
                    'completed_at' => $result->completed_at,
                    'duration_ms' => $result->duration_ms,
                    'uptime_status' => $result->uptime_status,
                    'response_time_ms' => $result->response_time_ms,
                    'ssl_status' => $result->ssl_status,
                    'http_status_code' => $result->http_status_code,
                    'error_message' => $result->error_message,
                ];
            }),
            'meta' => [
                'total' => $query->count(),
                'per_page' => $limit,
                'current_page' => 1,
            ],
        ]);
    }

    /**
     * Get trend data for a monitor.
     */
    public function trends(Request $request, Monitor $monitor): JsonResponse
    {
        $this->authorizeMonitorAccess($monitor);

        $validated = $request->validate([
            'period' => 'nullable|string|in:7d,30d,90d',
        ]);

        $period = $validated['period'] ?? '7d';
        $trendData = $this->historyService->getResponseTimeTrend($monitor, $period);

        return response()->json($trendData);
    }

    /**
     * Get summary statistics for a monitor.
     */
    public function summary(Request $request, Monitor $monitor): JsonResponse
    {
        $this->authorizeMonitorAccess($monitor);

        $validated = $request->validate([
            'period' => 'nullable|string|in:7d,30d,90d',
        ]);

        $period = $validated['period'] ?? '30d';
        $summary = $this->historyService->getSummaryStats($monitor, $period);

        return response()->json($summary);
    }

    /**
     * Get uptime statistics for a monitor.
     */
    public function uptimeStats(Request $request, Monitor $monitor): JsonResponse
    {
        $this->authorizeMonitorAccess($monitor);

        $validated = $request->validate([
            'period' => 'nullable|string|in:7d,30d,90d',
        ]);

        $period = $validated['period'] ?? '7d';
        $summary = $this->historyService->getSummaryStats($monitor, $period);
        $uptimePercentage = $this->historyService->getUptimePercentage($monitor, $period);

        // Calculate success rate
        $successRate = $summary['total_checks'] > 0
            ? ($summary['successful_checks'] / $summary['total_checks']) * 100
            : 0;

        // Get last check status
        $lastCheck = $monitor->monitoringResults()
            ->orderBy('started_at', 'desc')
            ->first();
        $lastCheckStatus = $lastCheck ? ucfirst($lastCheck->uptime_status ?? 'unknown') : 'Unknown';

        return response()->json([
            'total_checks' => $summary['total_checks'],
            'successful_checks' => $summary['successful_checks'],
            'failed_checks' => $summary['failed_checks'],
            'uptime_percentage' => $uptimePercentage,
            'avg_response_time' => $summary['avg_response_time'],
            'success_rate' => round($successRate, 1),
            'last_check_status' => $lastCheckStatus,
            'period' => $period,
        ]);
    }

    /**
     * Get SSL certificate information for a monitor.
     */
    public function sslInfo(Request $request, Monitor $monitor): JsonResponse
    {
        $this->authorizeMonitorAccess($monitor);

        // Get the most recent SSL certificate result
        $latestSslResult = $monitor->monitoringResults()
            ->whereIn('check_type', ['ssl', 'both'])
            ->whereNotNull('ssl_status')
            ->orderBy('started_at', 'desc')
            ->first();

        if (! $latestSslResult) {
            return response()->json([
                'certificate_status' => 'not_checked',
                'certificate_issuer' => null,
                'certificate_subject' => null,
                'certificate_expiration_date' => null,
                'days_until_expiration' => null,
                'is_valid' => false,
                'last_checked' => null,
            ]);
        }

        return response()->json([
            'certificate_status' => $latestSslResult->ssl_status,
            'certificate_issuer' => $latestSslResult->certificate_issuer ?? 'Unknown',
            'certificate_subject' => $latestSslResult->certificate_subject ?? (string) $monitor->url,
            'certificate_expiration_date' => $latestSslResult->certificate_expiration_date,
            'days_until_expiration' => $latestSslResult->days_until_expiration,
            'is_valid' => $latestSslResult->ssl_status === 'valid',
            'last_checked' => $latestSslResult->started_at,
        ]);
    }

    /**
     * Get recent checks for a monitor with pagination.
     */
    public function recentChecks(Request $request, Monitor $monitor): JsonResponse
    {
        $this->authorizeMonitorAccess($monitor);

        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
        ]);

        $limit = $validated['limit'] ?? 20;
        $offset = $validated['offset'] ?? 0;

        $checks = $monitor->monitoringResults()
            ->with(['triggeredBy:id,name'])
            ->orderBy('started_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        return response()->json([
            'data' => $checks->map(function ($check) {
                return [
                    'id' => $check->id,
                    'uuid' => $check->uuid,
                    'check_type' => $check->check_type,
                    'status' => $check->status,
                    'started_at' => $check->started_at,
                    'response_time_ms' => $check->response_time_ms,
                    'uptime_status' => $check->uptime_status,
                    'http_status_code' => $check->http_status_code,
                    'ssl_status' => $check->ssl_status,
                    'days_until_expiration' => $check->days_until_expiration,
                    'error_message' => $check->error_message,
                    'trigger_type' => $check->trigger_type,
                    'triggered_by_user' => $check->triggeredBy ? [
                        'id' => $check->triggeredBy->id,
                        'name' => $check->triggeredBy->name,
                    ] : null,
                ];
            }),
            'meta' => [
                'total' => $monitor->monitoringResults()->count(),
                'per_page' => $limit,
                'current_page' => floor($offset / $limit) + 1,
                'has_more' => ($offset + $limit) < $monitor->monitoringResults()->count(),
            ],
        ]);
    }

    /**
     * Get SSL expiration trends for a monitor.
     */
    public function sslExpirationTrends(Request $request, Monitor $monitor): JsonResponse
    {
        $this->authorizeMonitorAccess($monitor);

        $validated = $request->validate([
            'days' => 'nullable|integer|min:7|max:365',
        ]);

        $days = $validated['days'] ?? 30;

        // Get SSL results from monitoring history
        $sslResults = $monitor->monitoringResults()
            ->whereIn('check_type', ['ssl', 'both'])
            ->whereNotNull('days_until_expiration')
            ->where('started_at', '>=', now()->subDays($days))
            ->orderBy('started_at', 'asc')
            ->get(['started_at', 'days_until_expiration']);

        // Group by date and get the latest reading per day
        $dailyData = $sslResults->groupBy(function ($result) {
            return $result->started_at->format('Y-m-d');
        })->map(function ($dayResults) {
            return $dayResults->last(); // Get the latest reading for each day
        });

        // Fill missing dates with last known values
        $trendData = [];
        $lastKnownDays = null;
        $currentDate = now()->subDays($days - 1)->startOfDay();

        for ($i = 0; $i < $days; $i++) {
            $dateStr = $currentDate->format('Y-m-d');

            if (isset($dailyData[$dateStr])) {
                $lastKnownDays = $dailyData[$dateStr]->days_until_expiration;
            }

            $trendData[] = [
                'date' => $dateStr,
                'days_until_expiration' => $lastKnownDays,
            ];

            $currentDate->addDay();
        }

        return response()->json([
            'data' => $trendData,
            'period_days' => $days,
            'current_days_until_expiration' => $lastKnownDays,
        ]);
    }

    /**
     * Authorize access to monitor based on website ownership.
     */
    private function authorizeMonitorAccess(Monitor $monitor): void
    {
        // Find the website associated with this monitor by checking URLs
        $website = \App\Models\Website::where('url', $monitor->url)->first();

        if (! $website) {
            abort(404, 'Monitor not found or not associated with any website');
        }

        // Verify this monitor belongs to the website
        $websiteMonitor = $website->getSpatieMonitor();
        if (! $websiteMonitor || $websiteMonitor->id !== $monitor->id) {
            abort(403, 'Access denied to this monitor');
        }

        // Use the existing Website policy for authorization
        $this->authorize('view', $website);
    }
}
