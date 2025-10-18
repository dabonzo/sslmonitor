<?php

namespace App\Http\Controllers\Debug;

use App\Http\Controllers\Controller;
use App\Mail\SlowResponseTimeAlert;
use App\Mail\SslCertificateExpiryAlert;
use App\Mail\SslCertificateInvalidAlert;
use App\Mail\UptimeDownAlert;
use App\Mail\UptimeRecoveredAlert;
use App\Models\AlertConfiguration;
use App\Models\Website;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class AlertTestingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get user's websites for testing
        $websites = Website::where('user_id', $user->id)->get();

        // Get available alert types
        $alertTypes = [
            'ssl_expiry' => [
                'name' => 'SSL Certificate Expiry',
                'description' => 'Test different SSL expiry urgency levels (30, 14, 7, 3, 0 days)',
                'levels' => [
                    ['days' => 30, 'level' => 'info', 'label' => 'INFO (30 days)'],
                    ['days' => 14, 'level' => 'warning', 'label' => 'WARNING (14 days)'],
                    ['days' => 7, 'level' => 'urgent', 'label' => 'URGENT (7 days)'],
                    ['days' => 3, 'level' => 'critical', 'label' => 'CRITICAL (3 days)'],
                    ['days' => 0, 'level' => 'expired', 'label' => 'EXPIRED (0 days)'],
                ],
            ],
            'ssl_invalid' => [
                'name' => 'SSL Certificate Invalid',
                'description' => 'Test invalid SSL certificate alerts',
                'levels' => [
                    ['level' => 'critical', 'label' => 'CRITICAL - Invalid Certificate'],
                ],
            ],
            'uptime_down' => [
                'name' => 'Website Uptime',
                'description' => 'Test website down and recovery alerts',
                'levels' => [
                    ['status' => 'down', 'level' => 'critical', 'label' => 'CRITICAL - Website Down'],
                    ['status' => 'recovered', 'level' => 'info', 'label' => 'INFO - Website Recovered'],
                ],
            ],
            'response_time' => [
                'name' => 'Response Time',
                'description' => 'Test slow response time alerts',
                'levels' => [
                    ['response_time' => 5000, 'level' => 'warning', 'label' => 'WARNING - 5 seconds'],
                    ['response_time' => 10000, 'level' => 'critical', 'label' => 'CRITICAL - 10 seconds'],
                ],
            ],
        ];

        return Inertia::render('Debug/AlertTesting', [
            'websites' => $websites,
            'alertTypes' => $alertTypes,
            'stats' => [
                'total_websites' => $websites->count(),
                'ssl_monitoring_enabled' => $websites->where('ssl_monitoring_enabled', true)->count(),
                'uptime_monitoring_enabled' => $websites->where('uptime_monitoring_enabled', true)->count(),
            ],
        ]);
    }

    public function testAllAlerts(Request $request): JsonResponse
    {
        $request->validate([
            'website_id' => 'required|exists:websites,id',
        ]);

        $user = $request->user();
        $website = Website::where('user_id', $user->id)
            ->findOrFail($request->website_id);

        $results = [];
        $totalSent = 0;

        try {
            // Test all SSL expiry levels
            $sslLevels = [30, 14, 7, 3, 0];
            foreach ($sslLevels as $days) {
                $result = $this->sendSslExpiryTest($website, $days);
                $results[] = $result;
                if ($result['success']) {
                    $totalSent++;
                }
            }

            // Test SSL invalid
            $result = $this->sendSslInvalidTest($website);
            $results[] = $result;
            if ($result['success']) {
                $totalSent++;
            }

            // Test uptime down
            $result = $this->sendUptimeDownTest($website);
            $results[] = $result;
            if ($result['success']) {
                $totalSent++;
            }

            // Test uptime recovered
            $result = $this->sendUptimeRecoveredTest($website);
            $results[] = $result;
            if ($result['success']) {
                $totalSent++;
            }

            // Test response time alerts
            $responseTimes = [5000, 10000];
            foreach ($responseTimes as $responseTime) {
                $result = $this->sendResponseTimeTest($website, $responseTime);
                $results[] = $result;
                if ($result['success']) {
                    $totalSent++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Sent {$totalSent} test alerts for {$website->name}",
                'total_sent' => $totalSent,
                'results' => $results,
                'mailpit_url' => 'http://localhost:8025',
            ]);

        } catch (\Exception $e) {
            Log::error('Alert testing failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Alert testing failed: '.$e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testSslAlerts(Request $request): JsonResponse
    {
        $request->validate([
            'website_id' => 'required|exists:websites,id',
            'days' => 'required|array',
            'days.*' => 'integer|min:0|max:365',
        ]);

        $user = $request->user();
        $website = Website::where('user_id', $user->id)
            ->findOrFail($request->website_id);

        $results = [];
        $totalSent = 0;

        try {
            foreach ($request->days as $days) {
                $result = $this->sendSslExpiryTest($website, $days);
                $results[] = $result;
                if ($result['success']) {
                    $totalSent++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Sent {$totalSent} SSL test alerts for {$website->name}",
                'total_sent' => $totalSent,
                'results' => $results,
                'mailpit_url' => 'http://localhost:8025',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SSL alert testing failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function testUptimeAlerts(Request $request): JsonResponse
    {
        $request->validate([
            'website_id' => 'required|exists:websites,id',
            'status' => 'required|array',
            'status.*' => 'in:down,recovered',
        ]);

        $user = $request->user();
        $website = Website::where('user_id', $user->id)
            ->findOrFail($request->website_id);

        $results = [];
        $totalSent = 0;

        try {
            foreach ($request->status as $status) {
                if ($status === 'down') {
                    $result = $this->sendUptimeDownTest($website);
                } else {
                    $result = $this->sendUptimeRecoveredTest($website);
                }

                $results[] = $result;
                if ($result['success']) {
                    $totalSent++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Sent {$totalSent} uptime test alerts for {$website->name}",
                'total_sent' => $totalSent,
                'results' => $results,
                'mailpit_url' => 'http://localhost:8025',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Uptime alert testing failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function testResponseTimeAlerts(Request $request): JsonResponse
    {
        $request->validate([
            'website_id' => 'required|exists:websites,id',
            'response_times' => 'required|array',
            'response_times.*' => 'integer|min:100|max:60000',
        ]);

        $user = $request->user();
        $website = Website::where('user_id', $user->id)
            ->findOrFail($request->website_id);

        $results = [];
        $totalSent = 0;

        try {
            foreach ($request->response_times as $responseTime) {
                $result = $this->sendResponseTimeTest($website, $responseTime);
                $results[] = $result;
                if ($result['success']) {
                    $totalSent++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Sent {$totalSent} response time test alerts for {$website->name}",
                'total_sent' => $totalSent,
                'results' => $results,
                'mailpit_url' => 'http://localhost:8025',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Response time alert testing failed: '.$e->getMessage(),
            ], 500);
        }
    }

    private function sendSslExpiryTest(Website $website, int $days): array
    {
        try {
            // Create a temporary debug override to simulate the SSL expiry scenario
            $overrideData = [
                'expiry_date' => now()->addDays($days)->format('Y-m-d H:i:s'),
                'original_expiry' => now()->addDays(30)->format('Y-m-d H:i:s'), // Simulate original 30-day expiry
                'reason' => "Debug test: Simulating {$days} days remaining",
            ];

            $override = \App\Models\DebugOverride::create([
                'user_id' => $website->user_id,
                'module_type' => 'ssl_expiry',
                'targetable_type' => Website::class,
                'targetable_id' => $website->id,
                'override_data' => $overrideData,
                'is_active' => true,
                'expires_at' => now()->addMinutes(30), // Auto-expire after 30 minutes
            ]);

            // Get only SSL expiry alert configurations for this specific website
            // Note: For debug testing, we DON'T filter by 'enabled' - users should be able to test disabled alerts
            $sslAlertConfigs = AlertConfiguration::where('user_id', $website->user_id)
                ->where('website_id', $website->id)
                ->where('alert_type', AlertConfiguration::ALERT_SSL_EXPIRY)
                ->where('threshold_days', '=', $days) // Only trigger the alert for this exact threshold
                ->get();

            $triggeredAlerts = 0;
            foreach ($sslAlertConfigs as $alertConfig) {
                $checkData = $this->prepareSslCheckData($website, $days);
                // For debug testing: bypass both cooldown AND enabled check
                if ($alertConfig->shouldTrigger($checkData, $bypassCooldown = true, $bypassEnabledCheck = true)) {
                    $this->triggerAlert($alertConfig, $website, $checkData);
                    $triggeredAlerts++;
                }
            }

            Log::info('SSL expiry test simulation completed', [
                'website_id' => $website->id,
                'simulated_days_remaining' => $days,
                'triggered_alerts' => $triggeredAlerts,
                'debug_override_id' => $override->id,
            ]);

            return [
                'success' => true,
                'type' => 'ssl_expiry',
                'days' => $days,
                'message' => "SSL expiry scenario simulated ({$days} days remaining)",
                'triggered_alerts' => $triggeredAlerts,
                'debug_override_id' => $override->id,
            ];

        } catch (\Exception $e) {
            Log::error('SSL expiry test simulation failed: '.$e->getMessage());

            return [
                'success' => false,
                'type' => 'ssl_expiry',
                'days' => $days,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function sendSslInvalidTest(Website $website): array
    {
        try {
            $checkData = [
                'ssl_status' => 'invalid',
                'ssl_days_remaining' => null,
                'is_lets_encrypt' => false,
            ];

            Mail::to($website->user->email)->send(
                new SslCertificateInvalidAlert($website, $checkData)
            );

            Log::info('SSL invalid test alert sent', [
                'website_id' => $website->id,
                'recipient' => $website->user->email,
            ]);

            return [
                'success' => true,
                'type' => 'ssl_invalid',
                'message' => 'SSL invalid alert sent',
            ];

        } catch (\Exception $e) {
            Log::error('SSL invalid test alert failed: '.$e->getMessage());

            return [
                'success' => false,
                'type' => 'ssl_invalid',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function sendUptimeDownTest(Website $website): array
    {
        try {
            // Create a mock AlertConfiguration for testing
            $alertConfig = $this->createTestAlertConfig($website, 'uptime_down');

            $uptimeData = [
                'uptime_status' => 'down',
                'response_time' => null,
                'failure_reason' => 'Connection timeout',
                'status_code' => null,
                'checked_at' => now(),
            ];

            Mail::to($website->user->email)->send(
                new UptimeDownAlert($website, $alertConfig, $uptimeData)
            );

            Log::info('Uptime down test alert sent', [
                'website_id' => $website->id,
                'recipient' => $website->user->email,
            ]);

            return [
                'success' => true,
                'type' => 'uptime_down',
                'message' => 'Website down alert sent',
            ];

        } catch (\Exception $e) {
            Log::error('Uptime down test alert failed: '.$e->getMessage());

            return [
                'success' => false,
                'type' => 'uptime_down',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function sendUptimeRecoveredTest(Website $website): array
    {
        try {
            // Create a mock AlertConfiguration for testing
            $alertConfig = $this->createTestAlertConfig($website, 'uptime_recovered');

            $uptimeData = [
                'uptime_status' => 'up',
                'response_time' => 250,
                'last_check' => now(),
                'downtime_duration' => '5 minutes',
                'downtime_started' => now()->subMinutes(5),
                'recovery_time' => now(),
                'status_code' => 200,
                'checked_at' => now(),
            ];

            Mail::to($website->user->email)->send(
                new UptimeRecoveredAlert($website, $alertConfig, $uptimeData, '5 minutes')
            );

            Log::info('Uptime recovered test alert sent', [
                'website_id' => $website->id,
                'recipient' => $website->user->email,
            ]);

            return [
                'success' => true,
                'type' => 'uptime_recovered',
                'message' => 'Website recovered alert sent',
            ];

        } catch (\Exception $e) {
            Log::error('Uptime recovered test alert failed: '.$e->getMessage());

            return [
                'success' => false,
                'type' => 'uptime_recovered',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function sendResponseTimeTest(Website $website, int $responseTime): array
    {
        try {
            // Create a debug override to simulate slow response time
            $overrideData = [
                'response_time' => $responseTime,
                'original_response_time' => 250, // Simulate normal response time
                'reason' => "Debug test: Simulating slow response time ({$responseTime}ms)",
                'threshold_exceeded' => $responseTime > ($website->response_time_threshold ?? 5000),
                'last_check' => now(),
            ];

            $override = \App\Models\DebugOverride::create([
                'user_id' => $website->user_id,
                'module_type' => 'response_time',
                'targetable_type' => Website::class,
                'targetable_id' => $website->id,
                'override_data' => $overrideData,
                'is_active' => true,
                'expires_at' => now()->addMinutes(30),
            ]);

            // Get only response time alert configurations for this specific website
            // Note: For debug testing, we DON'T filter by 'enabled' - users should be able to test disabled alerts
            $responseTimeAlertConfigs = AlertConfiguration::where('user_id', $website->user_id)
                ->where('website_id', $website->id)
                ->where('alert_type', AlertConfiguration::ALERT_RESPONSE_TIME)
                ->where('threshold_response_time', '=', $responseTime) // Only trigger the alert for this exact threshold
                ->get();

            $triggeredAlerts = 0;
            foreach ($responseTimeAlertConfigs as $alertConfig) {
                $checkData = $this->prepareResponseTimeCheckData($website, $responseTime);
                // For debug testing: bypass both cooldown AND enabled check
                if ($alertConfig->shouldTrigger($checkData, $bypassCooldown = true, $bypassEnabledCheck = true)) {
                    $this->triggerAlert($alertConfig, $website, $checkData);
                    $triggeredAlerts++;
                }
            }

            Log::info('Response time test simulation completed', [
                'website_id' => $website->id,
                'simulated_response_time' => $responseTime,
                'triggered_alerts' => $triggeredAlerts,
                'debug_override_id' => $override->id,
            ]);

            return [
                'success' => true,
                'type' => 'response_time',
                'response_time' => $responseTime,
                'message' => "Slow response time scenario simulated ({$responseTime}ms)",
                'triggered_alerts' => $triggeredAlerts,
                'debug_override_id' => $override->id,
            ];

        } catch (\Exception $e) {
            Log::error('Response time test simulation failed: '.$e->getMessage());

            return [
                'success' => false,
                'type' => 'response_time',
                'response_time' => $responseTime,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function createTestAlertConfig(Website $website, string $alertType, ?int $thresholdDays = null): AlertConfiguration
    {
        $config = new AlertConfiguration([
            'user_id' => $website->user_id,
            'website_id' => $website->id,
            'alert_type' => $alertType,
            'enabled' => true,
            'threshold_days' => $thresholdDays,
            'alert_level' => $this->getAlertLevel($thresholdDays),
            'notification_channels' => ['email'],
        ]);

        return $config;
    }

    private function prepareSslCheckData(Website $website, int $days): array
    {
        return [
            'ssl_status' => $days <= 0 ? 'expired' : 'valid',
            'ssl_days_remaining' => $days,
            'is_lets_encrypt' => false,
            'checked_at' => now(),
        ];
    }

    private function prepareResponseTimeCheckData(Website $website, int $responseTime): array
    {
        return [
            'response_time' => $responseTime,
            'threshold_exceeded' => $responseTime > ($website->response_time_threshold ?? 5000),
            'checked_at' => now(),
        ];
    }

    private function triggerAlert(AlertConfiguration $alertConfig, Website $website, array $checkData): void
    {
        Log::info('Triggering test alert', [
            'alert_type' => $alertConfig->alert_type,
            'website_id' => $website->id,
            'alert_level' => $alertConfig->alert_level,
        ]);

        // Send notifications based on configured channels
        foreach ($alertConfig->notification_channels as $channel) {
            match ($channel) {
                'email' => $this->sendEmailAlert($alertConfig, $website, $checkData),
                'dashboard' => $this->createDashboardNotification($alertConfig, $website, $checkData),
                'slack' => $this->sendSlackAlert($alertConfig, $website, $checkData),
                default => Log::warning("Unknown notification channel: {$channel}"),
            };
        }

        // Mark alert as triggered
        $alertConfig->markTriggered();
    }

    private function sendEmailAlert(AlertConfiguration $alertConfig, Website $website, array $checkData): void
    {
        try {
            $user = $website->user;

            match ($alertConfig->alert_type) {
                AlertConfiguration::ALERT_SSL_EXPIRY => Mail::to($user->email)->send(new SslCertificateExpiryAlert($website, $alertConfig, $checkData)),

                AlertConfiguration::ALERT_SSL_INVALID => Mail::to($user->email)->send(new SslCertificateInvalidAlert($website, $checkData)),

                AlertConfiguration::ALERT_UPTIME_DOWN => Mail::to($user->email)->send(new UptimeDownAlert($website, $alertConfig, $checkData)),

                AlertConfiguration::ALERT_RESPONSE_TIME => Mail::to($user->email)->send(new SlowResponseTimeAlert($website, $checkData)),

                default => Log::warning("No email template for alert type: {$alertConfig->alert_type}"),
            };

            Log::info('Email alert sent', [
                'alert_type' => $alertConfig->alert_type,
                'recipient' => $user->email,
                'website' => $website->name,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send email alert: '.$e->getMessage(), [
                'alert_config_id' => $alertConfig->id,
                'website_id' => $website->id,
            ]);
        }
    }

    private function createDashboardNotification(AlertConfiguration $alertConfig, Website $website, array $checkData): void
    {
        // TODO: Implement dashboard notifications in Phase 4
        Log::info('Dashboard notification created', [
            'alert_type' => $alertConfig->alert_type,
            'website_id' => $website->id,
        ]);
    }

    private function sendSlackAlert(AlertConfiguration $alertConfig, Website $website, array $checkData): void
    {
        // TODO: Implement Slack integration in Phase 5
        Log::info('Slack alert would be sent', [
            'alert_type' => $alertConfig->alert_type,
            'website_id' => $website->id,
        ]);
    }

    private function getAlertLevel(?int $thresholdDays): string
    {
        if ($thresholdDays === null) {
            return 'critical';
        }
        if ($thresholdDays === 0) {
            return 'critical';
        }
        if ($thresholdDays <= 3) {
            return 'critical';
        }
        if ($thresholdDays <= 7) {
            return 'urgent';
        }
        if ($thresholdDays <= 14) {
            return 'warning';
        }

        return 'info';
    }
}
