<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'check_type' => $this->check_type,
            'trigger_type' => $this->trigger_type,
            'status' => $this->status,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'duration_ms' => $this->duration_ms,
            'error_message' => $this->error_message,

            // Uptime data
            'uptime_status' => $this->uptime_status,
            'http_status_code' => $this->http_status_code,
            'response_time_ms' => $this->response_time_ms,
            'response_body_size_bytes' => $this->response_body_size_bytes,
            'redirect_count' => $this->redirect_count,
            'final_url' => $this->final_url,

            // SSL data
            'ssl_status' => $this->ssl_status,
            'certificate_issuer' => $this->certificate_issuer,
            'certificate_subject' => $this->certificate_subject,
            'certificate_expiration_date' => $this->certificate_expiration_date?->toIso8601String(),
            'certificate_valid_from_date' => $this->certificate_valid_from_date?->toIso8601String(),
            'days_until_expiration' => $this->days_until_expiration,

            // Content validation
            'content_validation_enabled' => $this->content_validation_enabled,
            'content_validation_status' => $this->content_validation_status,
            'expected_strings_found' => $this->expected_strings_found,
            'forbidden_strings_found' => $this->forbidden_strings_found,
            'regex_matches' => $this->regex_matches,
            'javascript_rendered' => $this->javascript_rendered,
            'javascript_wait_seconds' => $this->javascript_wait_seconds,
            'content_hash' => $this->content_hash,

            // Metadata
            'check_method' => $this->check_method,
            'user_agent' => $this->user_agent,
            'ip_address' => $this->ip_address,
            'server_software' => $this->server_software,

            // Triggered by user
            'triggered_by_user_id' => $this->triggered_by_user_id,
            'triggered_by' => $this->whenLoaded('triggeredBy', function () {
                return [
                    'id' => $this->triggeredBy->id,
                    'name' => $this->triggeredBy->name,
                    'email' => $this->triggeredBy->email,
                ];
            }),
        ];
    }
}
