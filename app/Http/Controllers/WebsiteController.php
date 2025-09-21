<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebsiteRequest;
use App\Http\Requests\UpdateWebsiteRequest;
use App\Models\Website;
use App\Models\SslCertificate;
use App\Models\SslCheck;
use App\Services\MonitorIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, MonitorIntegrationService $monitorService): Response
    {
        $user = $request->user();

        $query = Website::where('user_id', $user->id);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $websites = $query->paginate(15);

        // Transform websites with real-time SSL and uptime status from Spatie monitors
        $websites->through(function ($website) use ($monitorService) {
            // Get real-time status from Spatie monitor
            $monitorStatus = $monitorService->getMonitoringStatusForWebsite($website);

            // Get Spatie monitor for this website
            $monitor = $website->getSpatieMonitor();

            $sslData = null;
            $daysRemaining = null;

            // Extract SSL data from Spatie monitor
            if ($monitor && $monitor->certificate_status !== 'not yet checked') {
                if ($monitor->certificate_expiration_date) {
                    $expirationDate = \Carbon\Carbon::parse($monitor->certificate_expiration_date);
                    $daysRemaining = $expirationDate->diffInDays(now(), false);
                    $daysRemaining = $daysRemaining < 0 ? abs($daysRemaining) : $daysRemaining;
                }

                $sslData = [
                    'status' => $monitor->certificate_status,
                    'expires_at' => $monitor->certificate_expiration_date,
                    'days_remaining' => $daysRemaining ? (int)$daysRemaining : null,
                    'issuer' => $monitor->certificate_issuer,
                    'subject' => null, // Not stored in Spatie monitor by default
                    'serial_number' => null, // Not stored in Spatie monitor by default
                    'signature_algorithm' => null, // Not stored in Spatie monitor by default
                    'is_valid' => $monitor->certificate_status === 'valid',
                    'last_checked' => $monitor->updated_at,
                    'response_time' => null, // SSL response time not tracked by Spatie
                ];
            }

            // Use Spatie monitor status
            $sslStatus = $monitor?->certificate_status ?? 'not yet checked';
            $uptimeStatus = $monitor?->uptime_status ?? 'not yet checked';

            // Calculate overall status for display
            $overallStatus = $this->calculateOverallStatus($sslStatus, $uptimeStatus, $website);

            return [
                'id' => $website->id,
                'name' => $website->name,
                'url' => $website->url,
                'ssl_monitoring_enabled' => $website->ssl_monitoring_enabled,
                'uptime_monitoring_enabled' => $website->uptime_monitoring_enabled,
                'ssl_status' => $sslStatus,
                'uptime_status' => $uptimeStatus,
                'overall_status' => $overallStatus,
                'ssl_days_remaining' => $daysRemaining ? (int)$daysRemaining : null,
                'latest_ssl_certificate' => $sslData,
                'monitor_sync_status' => $monitor !== null,
                'last_ssl_check' => $monitor?->updated_at,
                'last_uptime_check' => $monitor?->uptime_last_check_date,
                'failure_reason' => $monitor?->uptime_check_failure_reason,
                'created_at' => $website->created_at,
            ];
        });

        // Manually add meta data to match API resource format
        $websitesArray = $websites->toArray();
        $websitesArray['meta'] = [
            'current_page' => $websites->currentPage(),
            'from' => $websites->firstItem(),
            'last_page' => $websites->lastPage(),
            'path' => $websites->path(),
            'per_page' => $websites->perPage(),
            'to' => $websites->lastItem(),
            'total' => $websites->total(),
        ];

        return Inertia::render('Ssl/Websites/Index', [
            'websites' => $websitesArray,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Ssl/Websites/Create');
    }

    public function store(StoreWebsiteRequest $request): RedirectResponse
    {
        $user = $request->user();

        $website = Website::create([
            'user_id' => $user->id,
            'name' => $request->validated('name'),
            'url' => $request->validated('url'),
            'ssl_monitoring_enabled' => $request->validated('ssl_monitoring_enabled', false),
            'uptime_monitoring_enabled' => $request->validated('uptime_monitoring_enabled', false),
            'monitoring_config' => $request->validated('monitoring_config', []),
        ]);

        // Handle immediate checks if requested
        if ($request->validated('immediate_check', false)) {
            $this->performImmediateChecks($website);
        }

        $message = "Website '{$website->name}' has been added successfully.";

        if ($request->validated('immediate_check', false)) {
            $message .= " Initial checks are being performed.";
        }

        return redirect()
            ->route('ssl.websites.index')
            ->with('success', $message);
    }

    private function performImmediateChecks(Website $website): void
    {
        try {
            // Sync website with Spatie monitor for both uptime and SSL monitoring
            if ($website->ssl_monitoring_enabled || $website->uptime_monitoring_enabled) {
                Artisan::call('monitors:sync-websites');

                // Run both uptime and certificate checks via Spatie
                if ($website->uptime_monitoring_enabled) {
                    Artisan::call('monitor:check-uptime');
                }

                if ($website->ssl_monitoring_enabled) {
                    Artisan::call('monitor:check-certificate');
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the website creation
            \Log::error("Failed to perform immediate checks for website {$website->id}: " . $e->getMessage());
        }
    }

    public function show(Website $website): Response
    {
        $this->authorize('view', $website);

        // Get Spatie monitor data
        $monitor = $website->getSpatieMonitor();

        $websiteData = [
            'id' => $website->id,
            'name' => $website->name,
            'url' => $website->url,
            'ssl_monitoring_enabled' => $website->ssl_monitoring_enabled,
            'uptime_monitoring_enabled' => $website->uptime_monitoring_enabled,
            'ssl_status' => $website->getCurrentSslStatus(),
            'uptime_status' => $website->getCurrentUptimeStatus(),
            'monitor_data' => $monitor ? [
                'certificate_status' => $monitor->certificate_status,
                'certificate_expiration_date' => $monitor->certificate_expiration_date,
                'certificate_issuer' => $monitor->certificate_issuer,
                'uptime_status' => $monitor->uptime_status,
                'uptime_last_check_date' => $monitor->uptime_last_check_date,
                'uptime_check_failure_reason' => $monitor->uptime_check_failure_reason,
                'uptime_check_times_failed_in_a_row' => $monitor->uptime_check_times_failed_in_a_row,
                'updated_at' => $monitor->updated_at,
            ] : null,
            'ssl_certificates' => $monitor && $monitor->certificate_status !== 'not yet checked' ? [[
                'id' => $monitor->id,
                'status' => $monitor->certificate_status,
                'expires_at' => $monitor->certificate_expiration_date,
                'issuer' => $monitor->certificate_issuer,
                'subject' => null, // Not available in Spatie monitor
                'is_valid' => $monitor->certificate_status === 'valid',
                'created_at' => $monitor->created_at,
            ]] : [],
            'recent_ssl_checks' => $monitor ? [[
                'id' => $monitor->id,
                'status' => $monitor->certificate_status,
                'checked_at' => $monitor->updated_at,
                'response_time' => null, // Not tracked by Spatie for SSL
                'error_message' => $monitor->certificate_check_failure_reason,
            ]] : [],
            'created_at' => $website->created_at,
            'updated_at' => $website->updated_at,
        ];

        return Inertia::render('Ssl/Websites/Show', [
            'website' => $websiteData,
        ]);
    }

    public function edit(Website $website): Response
    {
        $this->authorize('update', $website);

        return Inertia::render('Ssl/Websites/Edit', [
            'website' => $website,
        ]);
    }

    public function update(UpdateWebsiteRequest $request, Website $website): RedirectResponse
    {
        $this->authorize('update', $website);

        $website->update([
            'name' => $request->validated('name'),
            'url' => $request->validated('url'),
            'ssl_monitoring_enabled' => $request->validated('ssl_monitoring_enabled', false),
            'uptime_monitoring_enabled' => $request->validated('uptime_monitoring_enabled', false),
        ]);

        return redirect()
            ->route('ssl.websites.show', $website)
            ->with('success', "Website '{$website->name}' has been updated.");
    }

    public function destroy(Website $website): RedirectResponse
    {
        $this->authorize('delete', $website);

        $websiteName = $website->name;

        // Delete related SSL data (handled by database cascade)
        $website->delete();

        return redirect()
            ->route('ssl.websites.index')
            ->with('success', "Website '{$websiteName}' and all related SSL data have been deleted.");
    }

    public function check(Request $request, Website $website, MonitorIntegrationService $monitorService): RedirectResponse
    {
        $this->authorize('update', $website);

        try {
            $messages = [];

            // Check SSL if enabled
            if ($website->ssl_monitoring_enabled) {
                $monitorService->createOrUpdateMonitorForWebsite($website);
                Artisan::call('monitor:check-certificate', ['--url' => $website->url]);
                $messages[] = 'SSL certificate check completed';
            }

            // Check uptime if enabled
            if ($website->uptime_monitoring_enabled) {
                $monitorService->createOrUpdateMonitorForWebsite($website);
                Artisan::call('monitor:check-uptime', ['--url' => $website->url]);
                $messages[] = 'Uptime check completed';
            }

            if (empty($messages)) {
                return redirect()
                    ->back()
                    ->with('error', 'No monitoring is enabled for this website.');
            }

            return redirect()
                ->back()
                ->with('success', implode(' and ', $messages) . ' for ' . $website->name);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Check failed: ' . $e->getMessage());
        }
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $request->validate([
            'website_ids' => 'required|array',
            'website_ids.*' => 'exists:websites,id',
        ]);

        $user = $request->user();
        $websiteIds = $request->get('website_ids');

        // Only delete websites owned by the current user
        $deletedCount = Website::where('user_id', $user->id)
            ->whereIn('id', $websiteIds)
            ->delete();

        return redirect()
            ->route('ssl.websites.index')
            ->with('success', "Successfully deleted {$deletedCount} websites.");
    }

    public function bulkCheck(Request $request, MonitorIntegrationService $monitorService): RedirectResponse
    {
        $request->validate([
            'website_ids' => 'required|array',
            'website_ids.*' => 'exists:websites,id',
        ]);

        $user = $request->user();
        $websiteIds = $request->get('website_ids');

        $websites = Website::where('user_id', $user->id)
            ->whereIn('id', $websiteIds)
            ->where(function ($query) {
                $query->where('ssl_monitoring_enabled', true)
                      ->orWhere('uptime_monitoring_enabled', true);
            })
            ->get();

        $sslChecked = 0;
        $uptimeChecked = 0;
        $errors = 0;

        foreach ($websites as $website) {
            try {
                $monitorService->createOrUpdateMonitorForWebsite($website);

                if ($website->ssl_monitoring_enabled) {
                    Artisan::call('monitor:check-certificate', ['--url' => $website->url]);
                    $sslChecked++;
                }

                if ($website->uptime_monitoring_enabled) {
                    Artisan::call('monitor:check-uptime', ['--url' => $website->url]);
                    $uptimeChecked++;
                }
            } catch (\Exception $e) {
                $errors++;
            }
        }

        $message = [];
        if ($sslChecked > 0) $message[] = "{$sslChecked} SSL checks";
        if ($uptimeChecked > 0) $message[] = "{$uptimeChecked} uptime checks";
        if ($errors > 0) $message[] = "{$errors} errors";

        return redirect()
            ->route('ssl.websites.index')
            ->with('success', "Completed: " . implode(', ', $message));
    }

    public function details(Website $website): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $website);

        // Get Spatie monitor data
        $monitor = $website->getSpatieMonitor();

        $daysRemaining = null;
        if ($monitor && $monitor->certificate_expiration_date) {
            $expirationDate = \Carbon\Carbon::parse($monitor->certificate_expiration_date);
            $daysRemaining = $expirationDate->diffInDays(now(), false);
            $daysRemaining = $daysRemaining < 0 ? abs($daysRemaining) : $daysRemaining;
        }

        $data = [
            'id' => $website->id,
            'name' => $website->name,
            'url' => $website->url,
            'ssl_monitoring_enabled' => $website->ssl_monitoring_enabled,
            'uptime_monitoring_enabled' => $website->uptime_monitoring_enabled,
            'created_at' => $website->created_at,
            'updated_at' => $website->updated_at,

            // SSL Information from Spatie monitor
            'ssl' => [
                'status' => $monitor?->certificate_status ?? 'not yet checked',
                'days_remaining' => $daysRemaining ? (int)$daysRemaining : null,
                'certificate' => $monitor && $monitor->certificate_status !== 'not yet checked' ? [
                    'issuer' => $monitor->certificate_issuer,
                    'subject' => null, // Not available in Spatie monitor
                    'serial_number' => null, // Not available in Spatie monitor
                    'signature_algorithm' => null, // Not available in Spatie monitor
                    'expires_at' => $monitor->certificate_expiration_date,
                    'is_valid' => $monitor->certificate_status === 'valid',
                    'security_metrics' => null, // Not available in Spatie monitor
                ] : null,
                'recent_checks' => $monitor ? [[
                    'status' => $monitor->certificate_status,
                    'checked_at' => $monitor->updated_at,
                    'response_time' => null, // Not tracked by Spatie for SSL
                    'error_message' => $monitor->certificate_check_failure_reason,
                    'check_details' => null, // Not available in Spatie monitor
                ]] : [],
            ],

            // Monitoring Configuration
            'monitoring' => [
                'config' => $website->monitoring_config,
                'ssl_enabled' => $website->ssl_monitoring_enabled,
                'uptime_enabled' => $website->uptime_monitoring_enabled,
            ],

            // Statistics from Spatie monitor
            'stats' => [
                'total_ssl_checks' => $monitor ? 1 : 0, // Spatie only shows current state
                'total_certificates' => $monitor && $monitor->certificate_status !== 'not yet checked' ? 1 : 0,
                'avg_response_time' => null, // Not tracked by Spatie for SSL
                'success_rate' => $monitor ? ($monitor->certificate_status === 'valid' ? 100 : 0) : 0,
            ],
        ];

        return response()->json($data);
    }

    /**
     * Calculate overall status badge for website display
     */
    private function calculateOverallStatus(string $sslStatus, string $uptimeStatus, Website $website): array
    {
        // Priority: Error > Warning > Success > Unknown
        $status = 'unknown';
        $label = 'Unknown';
        $color = 'gray';

        // Check for critical errors first
        if ($sslStatus === 'expired' || $uptimeStatus === 'down') {
            $status = 'error';
            $label = $sslStatus === 'expired' ? 'SSL Expired' : 'Down';
            $color = 'red';
        }
        // Check for warnings
        elseif ($sslStatus === 'expiring_soon' || $sslStatus === 'invalid' || $uptimeStatus === 'slow') {
            $status = 'warning';
            $label = $sslStatus === 'expiring_soon' ? 'Expiring Soon' :
                    ($sslStatus === 'invalid' ? 'SSL Invalid' : 'Slow');
            $color = 'yellow';
        }
        // Check for success states
        elseif ($sslStatus === 'valid' && ($uptimeStatus === 'up' || !$website->uptime_monitoring_enabled)) {
            $status = 'success';
            $label = 'Healthy';
            $color = 'green';
        }
        elseif ($uptimeStatus === 'up' && (!$website->ssl_monitoring_enabled || $sslStatus === 'valid')) {
            $status = 'success';
            $label = 'Up';
            $color = 'green';
        }
        // Content mismatch or other issues
        elseif ($uptimeStatus === 'content_mismatch') {
            $status = 'warning';
            $label = 'Content Issue';
            $color = 'yellow';
        }

        return [
            'status' => $status,
            'label' => $label,
            'color' => $color,
            'ssl_enabled' => $website->ssl_monitoring_enabled,
            'uptime_enabled' => $website->uptime_monitoring_enabled,
        ];
    }
}
