<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebsiteRequest;
use App\Http\Requests\UpdateWebsiteRequest;
use App\Jobs\ImmediateWebsiteCheckJob;
use App\Models\Website;
use App\Models\SslCertificate;
use App\Models\SslCheck;
use App\Services\MonitorIntegrationService;
use App\Services\SslCertificateAnalysisService;
use App\Support\AutomationLogger;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\UptimeMonitor\Models\Monitor;

class WebsiteController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, MonitorIntegrationService $monitorService): Response
    {
        $user = $request->user();

        // Cache user's team IDs to avoid repeated queries
        $userTeamIds = cache()->remember(
            "user_team_ids_{$user->id}",
            now()->addMinutes(10),
            fn() => $user->teams()->pluck('teams.id')
        );

        // Query websites owned personally OR assigned to user's teams
        $query = Website::where(function ($q) use ($user, $userTeamIds) {
            $q->where('user_id', $user->id) // Personal websites
              ->orWhereIn('team_id', $userTeamIds); // Team websites
        });

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            });
        }

        // Filter functionality for unified monitoring hub
        $filter = $request->get('filter', 'all');
        $teamFilter = $request->get('team', 'all'); // all, personal, team

        // Apply team filtering
        if ($teamFilter === 'personal') {
            $query->whereNull('team_id');
        } elseif ($teamFilter === 'team') {
            $query->whereNotNull('team_id');
        }

        $websites = $query->with(['team', 'user'])->orderBy('created_at', 'desc')->paginate(15);

        // Bulk fetch all monitors in a single query to avoid N+1
        $websiteUrls = $websites->pluck('url')->toArray();
        $monitors = Monitor::whereIn('url', $websiteUrls)
            ->select(['url', 'certificate_status', 'certificate_expiration_date', 'certificate_issuer',
                     'uptime_status', 'uptime_last_check_date', 'uptime_check_failure_reason',
                     'uptime_check_times_failed_in_a_row', 'uptime_check_response_time_in_ms', 'updated_at'])
            ->get()
            ->keyBy('url');

        // Transform websites with enhanced SSL and uptime data for unified monitoring hub
        $websites->through(function ($website) use ($monitors) {
            // Get monitor from our bulk collection instead of individual queries
            $monitor = $monitors->get($website->url);

            $sslData = null;
            $daysRemaining = null;
            $urgencyLevel = 'safe';

            // Extract enhanced SSL data from Spatie monitor
            if ($monitor && $monitor->certificate_status !== 'not yet checked') {
                if ($monitor->certificate_expiration_date) {
                    $expirationDate = \Carbon\Carbon::parse($monitor->certificate_expiration_date);
                    $daysRemaining = (int) $expirationDate->diffInDays(now(), false);
                    $daysRemaining = $daysRemaining < 0 ? abs($daysRemaining) : $daysRemaining;

                    // Calculate urgency level for Let's Encrypt focus
                    if ($daysRemaining <= 0) {
                        $urgencyLevel = 'expired';
                    } elseif ($daysRemaining <= 3) {
                        $urgencyLevel = 'critical';
                    } elseif ($daysRemaining <= 7) {
                        $urgencyLevel = 'urgent';
                    } elseif ($daysRemaining <= 30) {
                        $urgencyLevel = 'warning';
                    } else {
                        $urgencyLevel = 'safe';
                    }
                }

                $sslData = [
                    'status' => $monitor->certificate_status,
                    'expires_at' => $monitor->certificate_expiration_date,
                    'days_remaining' => $daysRemaining ? (int)$daysRemaining : null,
                    'urgency_level' => $urgencyLevel,
                    'issuer' => $monitor->certificate_issuer,
                    'is_valid' => $monitor->certificate_status === 'valid',
                    'last_checked' => $monitor->updated_at,
                ];
            }

            // Use Spatie monitor status
            $sslStatus = $monitor?->certificate_status ?? 'not yet checked';
            $uptimeStatus = $monitor?->uptime_status ?? 'not yet checked';
            $responseTime = $monitor?->uptime_check_response_time_in_ms;

            // Enhanced uptime data
            $uptimeData = [
                'status' => $uptimeStatus,
                'response_time' => $responseTime,
                'response_time_display' => $responseTime ? $responseTime . 'ms' : null,
                'last_checked' => $monitor?->uptime_last_check_date,
                'failure_reason' => $monitor?->uptime_check_failure_reason,
                'times_failed' => $monitor?->uptime_check_times_failed_in_a_row ?? 0,
            ];

            // Calculate overall status for display
            $overallStatus = $this->calculateOverallStatus($sslStatus, $uptimeStatus, $website);

            // Add filtering support flags
            $filterFlags = [
                'has_ssl_issues' => in_array($sslStatus, ['invalid', 'expired']) || $urgencyLevel === 'critical',
                'has_uptime_issues' => in_array($uptimeStatus, ['down', 'slow']),
                'is_expiring_soon' => in_array($urgencyLevel, ['warning', 'urgent', 'critical']),
                'is_healthy' => $sslStatus === 'valid' && $uptimeStatus === 'up',
                'is_team_website' => false, // Placeholder for team feature
            ];

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
                'ssl_urgency_level' => $urgencyLevel,
                'ssl_data' => $sslData,
                'uptime_data' => $uptimeData,
                'filter_flags' => $filterFlags,
                'monitor_sync_status' => $monitor !== null,
                'team_badge' => $website->team_id ? [
                    'type' => 'team',
                    'name' => $website->team->name,
                    'color' => 'green',
                ] : [
                    'type' => 'personal',
                    'name' => null,
                    'color' => 'gray',
                ],
                'created_at' => $website->created_at,
                'updated_at' => $website->updated_at,
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

        // Calculate filter statistics with caching for better performance
        $cacheKey = "filter_stats_user_{$user->id}_" . md5(implode(',', $userTeamIds->toArray()));
        $filterStats = cache()->remember($cacheKey, now()->addMinutes(5), function () use ($user, $userTeamIds) {
            $allWebsites = Website::where(function ($q) use ($user, $userTeamIds) {
                $q->where('user_id', $user->id) // Personal websites
                  ->orWhereIn('team_id', $userTeamIds); // Team websites
            })->select(['id', 'url'])->get(); // Only select needed columns

            return $this->calculateFilterStatistics($allWebsites);
        });

        // Get available teams for transfer operations
        $availableTeams = $user->teams()
            ->wherePivotIn('role', ['OWNER', 'ADMIN', 'MANAGER'])
            ->get(['teams.id', 'teams.name', 'teams.description'])
            ->map(function ($team) use ($user) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'description' => $team->description,
                    'member_count' => $team->members()->count(),
                    'user_role' => $user->teams()->where('teams.id', $team->id)->first()->pivot->role ?? null,
                ];
            });

        return Inertia::render('Ssl/Websites/Index', [
            'websites' => $websitesArray,
            'filters' => $request->only(['search', 'filter', 'team']),
            'filterStats' => $filterStats,
            'current_filter' => $filter,
            'current_team_filter' => $teamFilter,
            'availableTeams' => $availableTeams,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Ssl/Websites/Create');
    }

    public function store(StoreWebsiteRequest $request): RedirectResponse
    {
        $user = $request->user();

        AutomationLogger::info("Creating new website", [
            'user_id' => $user->id,
            'url' => $request->validated('url'),
            'name' => $request->validated('name'),
            'ssl_monitoring_enabled' => $request->validated('ssl_monitoring_enabled', false),
            'uptime_monitoring_enabled' => $request->validated('uptime_monitoring_enabled', false),
            'immediate_check_requested' => $request->validated('immediate_check', false),
        ]);

        $website = Website::create([
            'user_id' => $user->id,
            'name' => $request->validated('name'),
            'url' => $request->validated('url'),
            'ssl_monitoring_enabled' => $request->validated('ssl_monitoring_enabled', false),
            'uptime_monitoring_enabled' => $request->validated('uptime_monitoring_enabled', false),
            'monitoring_config' => $request->validated('monitoring_config', []),
        ]);

        AutomationLogger::info("Website created successfully", [
            'website_id' => $website->id,
            'user_id' => $user->id,
            'url' => $website->url,
            'name' => $website->name,
        ]);

        // Handle immediate checks if requested
        if ($request->validated('immediate_check', false)) {
            $dispatched = $this->dispatchImmediateCheck($website);

            AutomationLogger::info("Immediate check requested for new website", [
                'website_id' => $website->id,
                'dispatched' => $dispatched,
                'user_id' => $user->id,
            ]);
        }

        $message = "Website '{$website->name}' has been added successfully.";

        if ($request->validated('immediate_check', false)) {
            $message .= " Initial checks are being performed in the background.";
        }

        return redirect()
            ->route('ssl.websites.index')
            ->with('success', $message);
    }

    private function dispatchImmediateCheck(Website $website): bool
    {
        try {
            // Log the immediate check request
            AutomationLogger::immediateCheck(
                "Dispatching immediate check for newly created website: {$website->url}",
                ['website_id' => $website->id, 'triggered_by' => 'website_creation']
            );

            // Dispatch the immediate check job to high-priority queue
            ImmediateWebsiteCheckJob::dispatch($website)
                ->onQueue(env('QUEUE_IMMEDIATE', 'immediate'));

            AutomationLogger::immediateCheck(
                "Immediate check job dispatched successfully",
                ['website_id' => $website->id, 'queue' => env('QUEUE_IMMEDIATE', 'immediate')]
            );

            return true;

        } catch (\Exception $e) {
            AutomationLogger::error(
                "Failed to dispatch immediate check for website {$website->id}",
                ['website_id' => $website->id, 'url' => $website->url],
                $e
            );

            return false;
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

    /**
     * Trigger immediate check for existing website (new queue-based approach)
     */
    public function immediateCheck(Request $request, Website $website): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $website);

        $user = $request->user();

        AutomationLogger::info("API immediate check requested", [
            'website_id' => $website->id,
            'website_url' => $website->url,
            'user_id' => $user->id,
            'ssl_monitoring_enabled' => $website->ssl_monitoring_enabled,
            'uptime_monitoring_enabled' => $website->uptime_monitoring_enabled,
        ]);

        try {
            // Ensure at least one monitoring type is enabled
            if (!$website->ssl_monitoring_enabled && !$website->uptime_monitoring_enabled) {
                AutomationLogger::warning("Immediate check blocked - no monitoring enabled", [
                    'website_id' => $website->id,
                    'website_url' => $website->url,
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No monitoring is enabled for this website.',
                ], 400);
            }

            // Dispatch the immediate check job
            $dispatched = $this->dispatchImmediateCheck($website);

            if (!$dispatched) {
                AutomationLogger::error("Failed to dispatch immediate check", [
                    'website_id' => $website->id,
                    'website_url' => $website->url,
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to schedule immediate check.',
                ], 500);
            }

            AutomationLogger::info("API immediate check job dispatched successfully", [
                'website_id' => $website->id,
                'dispatched' => $dispatched,
                'user_id' => $user->id,
                'queue' => env('QUEUE_IMMEDIATE', 'immediate'),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Immediate check queued for {$website->name}",
                'website_id' => $website->id,
                'estimated_completion' => now()->addSeconds(30)->toISOString(),
            ]);

        } catch (\Exception $e) {
            AutomationLogger::error(
                "Failed to trigger immediate check via API",
                ['website_id' => $website->id, 'url' => $website->url, 'user_id' => $user->id],
                $e
            );

            return response()->json([
                'success' => false,
                'message' => 'Immediate check failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get job status for immediate check (for real-time UI feedback)
     */
    public function checkStatus(Request $request, Website $website): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $website);

        $user = $request->user();

        AutomationLogger::debug("Check status API requested", [
            'website_id' => $website->id,
            'website_url' => $website->url,
            'user_id' => $user->id,
        ]);

        try {
            // Get the latest status from the website's updated_at timestamp
            $website->refresh();

            // Get monitor data for current status
            $monitor = $website->getSpatieMonitor();

            $response = [
                'website_id' => $website->id,
                'last_updated' => $website->updated_at,
                'ssl_status' => $monitor?->certificate_status ?? 'not yet checked',
                'uptime_status' => $monitor?->uptime_status ?? 'not yet checked',
                'ssl_monitoring_enabled' => $website->ssl_monitoring_enabled,
                'uptime_monitoring_enabled' => $website->uptime_monitoring_enabled,
                'checked_at' => now()->toISOString(),
            ];

            AutomationLogger::debug("Check status API response", [
                'website_id' => $website->id,
                'ssl_status' => $response['ssl_status'],
                'uptime_status' => $response['uptime_status'],
                'last_updated' => $response['last_updated'],
                'user_id' => $user->id,
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            AutomationLogger::error(
                "Failed to get check status via API",
                ['website_id' => $website->id, 'url' => $website->url, 'user_id' => $user->id],
                $e
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to get check status: ' . $e->getMessage(),
            ], 500);
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
            $daysRemaining = (int) $expirationDate->diffInDays(now(), false);
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

    public function certificateAnalysis(Website $website, SslCertificateAnalysisService $analysisService): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $website);

        try {
            $analysis = $analysisService->analyzeCertificate($website->url);

            return response()->json([
                'website' => [
                    'id' => $website->id,
                    'name' => $website->name,
                    'url' => $website->url,
                ],
                'analysis' => $analysis,
                'analyzed_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Certificate analysis failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transfer website to team
     */
    public function transferToTeam(Request $request, Website $website): RedirectResponse
    {
        $this->authorize('update', $website);

        $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);

        $user = $request->user();
        $team = \App\Models\Team::findOrFail($request->team_id);

        // Check if user is a member of the team with appropriate permissions
        if (!$user->isMemberOf($team)) {
            abort(403, 'You are not a member of this team.');
        }

        $userRole = $user->getRoleInTeam($team);
        if (!in_array($userRole, ['OWNER', 'ADMIN', 'MANAGER'])) {
            abort(403, 'You do not have permission to transfer websites to this team.');
        }

        try {
            $website->transferToTeam($team, $user);

            return redirect()->back()->with('success', "Website transferred to {$team->name} successfully!");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to transfer website: ' . $e->getMessage());
        }
    }

    /**
     * Transfer website back to personal ownership
     */
    public function transferToPersonal(Request $request, Website $website): RedirectResponse
    {
        // Check if user has permission to transfer from team
        if ($website->team_id) {
            $team = $website->team;
            $user = $request->user();

            if (!$user->isMemberOf($team)) {
                abort(403, 'You are not a member of this team.');
            }

            $userRole = $user->getRoleInTeam($team);
            if (!in_array($userRole, ['OWNER', 'ADMIN'])) {
                abort(403, 'You do not have permission to transfer team websites.');
            }
        } else {
            $this->authorize('update', $website);
        }

        try {
            $website->transferToPersonal();

            return redirect()->back()->with('success', 'Website transferred to personal ownership successfully!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to transfer website: ' . $e->getMessage());
        }
    }

    /**
     * Get available teams for website transfer
     */
    public function getTransferOptions(Request $request, Website $website): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $website);

        $user = $request->user();

        // Get teams where user can manage websites
        $availableTeams = $user->teams()
            ->whereIn('team_members.role', ['OWNER', 'ADMIN', 'MANAGER'])
            ->get()
            ->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'description' => $team->description,
                ];
            });

        return response()->json([
            'teams' => $availableTeams,
            'current_owner' => $website->team_id ? [
                'type' => 'team',
                'id' => $website->team_id,
                'name' => $website->team->name,
            ] : [
                'type' => 'personal',
                'id' => $website->user_id,
                'name' => $website->user->name,
            ],
        ]);
    }

    /**
     * Calculate filter statistics for the filter bar with optimized bulk queries
     */
    private function calculateFilterStatistics($websites): array
    {
        $stats = [
            'all' => $websites->count(),
            'ssl_issues' => 0,
            'uptime_issues' => 0,
            'expiring_soon' => 0,
            'critical' => 0,
        ];

        if ($websites->isEmpty()) {
            return $stats;
        }

        // Bulk fetch all monitors in a single query
        $websiteUrls = $websites->pluck('url')->toArray();
        $monitors = Monitor::whereIn('url', $websiteUrls)
            ->select(['url', 'certificate_status', 'certificate_expiration_date', 'uptime_status'])
            ->get();

        foreach ($monitors as $monitor) {
            // SSL Issues
            $sslStatus = $monitor->certificate_status;
            if (in_array($sslStatus, ['invalid', 'expired'])) {
                $stats['ssl_issues']++;
            }

            // Uptime Issues
            $uptimeStatus = $monitor->uptime_status;
            if (in_array($uptimeStatus, ['down', 'slow'])) {
                $stats['uptime_issues']++;
            }

            // Calculate days remaining for SSL certificate
            if ($monitor->certificate_expiration_date) {
                $expirationDate = \Carbon\Carbon::parse($monitor->certificate_expiration_date);
                $daysRemaining = (int) $expirationDate->diffInDays(now(), false);
                $daysRemaining = $daysRemaining < 0 ? abs($daysRemaining) : $daysRemaining;

                // Expiring Soon (within 30 days)
                if ($daysRemaining <= 30 && $daysRemaining >= 0) {
                    $stats['expiring_soon']++;
                }

                // Critical (Let's Encrypt focus: 3 days or less)
                if ($daysRemaining <= 3) {
                    $stats['critical']++;
                }
            }

            // Critical also includes expired certificates and down sites
            if ($sslStatus === 'expired' || $uptimeStatus === 'down') {
                $stats['critical']++;
            }
        }

        return $stats;
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

    /**
     * Bulk transfer websites to team
     */
    public function bulkTransferToTeam(Request $request)
    {
        $request->validate([
            'website_ids' => 'required|array',
            'website_ids.*' => 'exists:websites,id',
            'team_id' => 'required|exists:teams,id',
        ]);

        $user = $request->user();

        // Verify user has permission to transfer to this team
        $team = $user->teams()
            ->wherePivotIn('role', ['OWNER', 'ADMIN', 'MANAGER'])
            ->where('teams.id', $request->team_id)
            ->first();

        if (!$team) {
            return redirect()->back()->with('error', 'You do not have permission to transfer websites to this team.');
        }

        // Get websites that belong to the user and are personal (not already in a team)
        $websites = Website::whereIn('id', $request->website_ids)
            ->where('user_id', $user->id)
            ->whereNull('team_id')
            ->get();

        if ($websites->isEmpty()) {
            return redirect()->back()->with('error', 'No personal websites found to transfer.');
        }

        // Transfer websites to team
        $websites->each(function ($website) use ($request) {
            $website->update(['team_id' => $request->team_id]);
        });

        return redirect()->back()->with('success', "Successfully transferred {$websites->count()} websites to {$team->name}.");
    }

    /**
     * Bulk transfer websites to personal
     */
    public function bulkTransferToPersonal(Request $request)
    {
        $request->validate([
            'website_ids' => 'required|array',
            'website_ids.*' => 'exists:websites,id',
        ]);

        $user = $request->user();

        // Get user's team IDs where they have transfer permissions
        $userTeamIds = $user->teams()
            ->wherePivotIn('role', ['OWNER', 'ADMIN', 'MANAGER'])
            ->pluck('teams.id');

        // Get websites that belong to the user's teams
        $websites = Website::whereIn('id', $request->website_ids)
            ->where(function ($q) use ($user, $userTeamIds) {
                $q->where('user_id', $user->id) // User owns the website
                  ->orWhereIn('team_id', $userTeamIds); // Or website is in user's team with permissions
            })
            ->whereNotNull('team_id')
            ->get();

        if ($websites->isEmpty()) {
            return redirect()->back()->with('error', 'No team websites found to transfer.');
        }

        // Transfer websites to personal (remove team_id)
        $websites->each(function ($website) use ($user) {
            $website->update([
                'team_id' => null,
                'user_id' => $user->id, // Ensure ownership is set to current user
            ]);
        });

        return redirect()->back()->with('success', "Successfully transferred {$websites->count()} websites to your personal account.");
    }
}
