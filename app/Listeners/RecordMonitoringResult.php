<?php

namespace App\Listeners;

use App\Events\MonitoringCheckCompleted;
use App\Models\MonitoringResult;
use App\Services\AlertCorrelationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordMonitoringResult implements ShouldQueue
{
    public string $queue = 'monitoring-history';

    public function __construct(
        protected AlertCorrelationService $alertService
    ) {}

    public function handle(MonitoringCheckCompleted $event): void
    {
        $monitor = $event->monitor;
        $results = $event->checkResults;

        $result = MonitoringResult::create([
            'monitor_id' => $monitor->id,
            'website_id' => $this->getWebsiteIdFromMonitor($monitor),
            'check_type' => $results['check_type'] ?? 'both',
            'trigger_type' => $event->triggerType,
            'triggered_by_user_id' => $event->triggeredByUserId,

            // Timing
            'started_at' => $event->startedAt,
            'completed_at' => $event->completedAt,
            'duration_ms' => $event->startedAt->diffInMilliseconds($event->completedAt),

            // Overall status
            'status' => $results['status'] ?? 'success',
            'error_message' => $results['error_message'] ?? null,

            // Uptime data
            'uptime_status' => $results['uptime_status'] ?? null,
            'http_status_code' => $results['http_status_code'] ?? null,
            'response_time_ms' => $monitor->uptime_check_response_time_in_ms,
            'response_body_size_bytes' => $results['response_body_size_bytes'] ?? null,
            'redirect_count' => $results['redirect_count'] ?? 0,
            'final_url' => $results['final_url'] ?? null,

            // SSL data
            'ssl_status' => $results['ssl_status'] ?? null,
            'certificate_issuer' => $monitor->certificate_issuer,
            'certificate_subject' => $results['certificate_subject'] ?? null,
            'certificate_expiration_date' => $monitor->certificate_expiration_date,
            'certificate_valid_from_date' => $results['certificate_valid_from_date'] ?? null,
            'days_until_expiration' => $results['days_until_expiration'] ?? null,
            'certificate_chain' => $results['certificate_chain'] ?? null,

            // Content validation
            'content_validation_enabled' => $results['content_validation_enabled'] ?? false,
            'content_validation_status' => $results['content_validation_status'] ?? null,
            'expected_strings_found' => $results['expected_strings_found'] ?? null,
            'forbidden_strings_found' => $results['forbidden_strings_found'] ?? null,
            'regex_matches' => $results['regex_matches'] ?? null,
            'javascript_rendered' => $results['javascript_rendered'] ?? false,
            'javascript_wait_seconds' => $results['javascript_wait_seconds'] ?? null,
            'content_hash' => $results['content_hash'] ?? null,

            // Technical details
            'check_method' => $results['check_method'] ?? 'GET',
            'user_agent' => $results['user_agent'] ?? null,
            'request_headers' => $results['request_headers'] ?? null,
            'response_headers' => $results['response_headers'] ?? null,
            'ip_address' => $results['ip_address'] ?? null,
            'server_software' => $results['server_software'] ?? null,

            // Monitoring context
            'monitor_config' => [
                'uptime_check_interval' => $monitor->uptime_check_interval_in_minutes,
                'look_for_string' => $monitor->look_for_string,
            ],
            'check_interval_minutes' => $monitor->uptime_check_interval_in_minutes,
        ]);

        // Check and create alerts
        $this->alertService->checkAndCreateAlerts($result);
        $this->alertService->autoResolveAlerts($result);
    }

    private function getWebsiteIdFromMonitor($monitor): ?int
    {
        // Get website_id from monitor's URL
        $website = \App\Models\Website::where('url', (string) $monitor->url)->first();

        return $website?->id;
    }
}
