<?php

namespace App\Http\Controllers\Debug;

use App\Http\Controllers\Controller;
use App\Models\DebugOverride;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SslOverridesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get user's websites with SSL monitoring enabled
        $websites = Website::with(['debugOverrides' => function ($query) use ($user) {
            $query->where('module_type', 'ssl_expiry')
                ->where('user_id', $user->id)
                ->active()
                ->notExpired();
        }])
            ->where('user_id', $user->id)
            ->where('ssl_monitoring_enabled', true)
            ->get();

        // Format website data for frontend
        $formattedWebsites = $websites->map(function ($website) use ($user) {
            $monitor = $website->getSpatieMonitor();
            $override = $website->getDebugOverride('ssl_expiry', $user->id);

            return [
                'id' => $website->id,
                'name' => $website->name,
                'url' => $website->url,
                'real_expiry_date' => $monitor?->certificate_expiration_date,
                'override' => $override,
                'effective_expiry' => $website->getEffectiveSslExpiryDate($user->id),
                'days_remaining' => $website->getDaysRemaining($user->id),
                'monitor_status' => $monitor?->certificate_status,
                'can_override' => $monitor && $website->ssl_monitoring_enabled,
            ];
        });

        return Inertia::render('Debug/SslOverrides', [
            'websites' => $formattedWebsites,
            'stats' => [
                'total_websites' => $formattedWebsites->count(),
                'active_overrides' => $formattedWebsites->where('override')->count(),
                'urgent_alerts' => $formattedWebsites->filter(fn ($w) => $w['days_remaining'] <= 7)->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'website_id' => 'required|exists:websites,id',
            'expiry_date' => 'required|date|after:now',
            'reason' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $website = Website::where('user_id', $user->id)
            ->findOrFail($request->website_id);

        // Create or update SSL override
        $override = DebugOverride::updateOrCreate([
            'user_id' => $user->id,
            'module_type' => 'ssl_expiry',
            'targetable_type' => Website::class,
            'targetable_id' => $website->id,
        ], [
            'override_data' => [
                'expiry_date' => $request->expiry_date,
                'original_expiry' => $website->getSpatieMonitor()?->certificate_expiration_date,
                'reason' => $request->reason ?? 'Manual override for testing',
            ],
            'is_active' => true,
            'expires_at' => now()->addHours(24), // Auto-expire after 24 hours
        ]);

        return response()->json([
            'success' => true,
            'override' => $override,
            'effective_expiry' => $website->getEffectiveSslExpiryDate($user->id),
            'days_remaining' => $website->getDaysRemaining($user->id),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $override = DebugOverride::where('id', $id)
            ->where('user_id', $user->id)
            ->where('module_type', 'ssl_expiry')
            ->firstOrFail();

        $override->deactivate();

        return response()->json([
            'success' => true,
            'message' => 'SSL override removed successfully',
        ]);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'website_ids' => 'required|array',
            'website_ids.*' => 'exists:websites,id',
            'days_ahead' => 'required|integer|min:0|max:365',
            'reason' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $expiryDate = now()->addDays($request->days_ahead);

        $websites = Website::where('user_id', $user->id)
            ->whereIn('id', $request->website_ids)
            ->get();

        $overrides = [];
        foreach ($websites as $website) {
            $override = DebugOverride::updateOrCreate([
                'user_id' => $user->id,
                'module_type' => 'ssl_expiry',
                'targetable_type' => Website::class,
                'targetable_id' => $website->id,
            ], [
                'override_data' => [
                    'expiry_date' => $expiryDate->format('Y-m-d H:i:s'),
                    'original_expiry' => $website->getSpatieMonitor()?->certificate_expiration_date,
                    'reason' => $request->reason ?? "Bulk override: {$request->days_ahead} days",
                ],
                'is_active' => true,
                'expires_at' => now()->addHours(24),
            ]);

            $overrides[] = $override;
        }

        return response()->json([
            'success' => true,
            'message' => "Created SSL overrides for {$overrides->count()} websites",
            'overrides_count' => count($overrides),
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'website_ids' => 'required|array',
            'website_ids.*' => 'exists:websites,id',
        ]);

        $user = $request->user();

        $deletedCount = DebugOverride::where('user_id', $user->id)
            ->where('module_type', 'ssl_expiry')
            ->whereIn('targetable_id', $request->website_ids)
            ->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => "Removed SSL overrides for {$deletedCount} websites",
            'deleted_count' => $deletedCount,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'expiry_date' => 'required|date',
            'reason' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        $override = DebugOverride::where('id', $id)
            ->where('user_id', $user->id)
            ->where('module_type', 'ssl_expiry')
            ->firstOrFail();

        $website = $override->targetable;

        $override->update([
            'override_data' => [
                'expiry_date' => $request->expiry_date,
                'original_expiry' => $website->getSpatieMonitor()?->certificate_expiration_date,
                'reason' => $request->reason ?? $override->override_data['reason'] ?? 'Updated override',
            ],
            'is_active' => true,
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json([
            'success' => true,
            'override' => $override->fresh(),
            'effective_expiry' => $website->getEffectiveSslExpiryDate($user->id),
            'days_remaining' => $website->getDaysRemaining($user->id),
        ]);
    }

    public function testAlerts(Request $request): JsonResponse
    {
        $request->validate([
            'website_id' => 'required|exists:websites,id',
        ]);

        $user = $request->user();
        $website = Website::where('user_id', $user->id)
            ->findOrFail($request->website_id);

        // Get alert service for testing
        $alertService = app(\App\Services\AlertService::class);

        try {
            // Test alerts with current effective expiry (including overrides)
            // Bypass cooldown for debug testing
            $triggeredAlerts = $alertService->checkAndTriggerAlerts($website, true);

            return response()->json([
                'success' => true,
                'message' => 'Alert test completed successfully',
                'triggered_alerts' => $triggeredAlerts,
                'effective_expiry' => $website->getEffectiveSslExpiryDate($user->id),
                'days_remaining' => $website->getDaysRemaining($user->id),
                'monitor_status' => $website->getSpatieMonitor()?->certificate_status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Alert test failed: '.$e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
