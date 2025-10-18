<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MonitoringResult extends Model
{
    protected $fillable = [
        'uuid', 'monitor_id', 'website_id', 'check_type', 'trigger_type',
        'triggered_by_user_id', 'started_at', 'completed_at', 'duration_ms',
        'status', 'error_message', 'uptime_status', 'http_status_code',
        'response_time_ms', 'response_body_size_bytes', 'redirect_count',
        'final_url', 'ssl_status', 'certificate_issuer', 'certificate_subject',
        'certificate_expiration_date', 'certificate_valid_from_date',
        'days_until_expiration', 'certificate_chain', 'content_validation_enabled',
        'content_validation_status', 'expected_strings_found', 'forbidden_strings_found',
        'regex_matches', 'javascript_rendered', 'javascript_wait_seconds',
        'content_hash', 'check_method', 'user_agent', 'request_headers',
        'response_headers', 'ip_address', 'server_software', 'monitor_config',
        'check_interval_minutes',
    ];

    protected $casts = [
        'started_at' => 'datetime:Y-m-d H:i:s.v',
        'completed_at' => 'datetime:Y-m-d H:i:s.v',
        'certificate_expiration_date' => 'datetime',
        'certificate_valid_from_date' => 'datetime',
        'certificate_chain' => 'array',
        'expected_strings_found' => 'array',
        'forbidden_strings_found' => 'array',
        'regex_matches' => 'array',
        'request_headers' => 'array',
        'response_headers' => 'array',
        'monitor_config' => 'array',
        'content_validation_enabled' => 'boolean',
        'javascript_rendered' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeManual($query)
    {
        return $query->whereIn('trigger_type', ['manual_immediate', 'manual_bulk']);
    }

    public function scopeScheduled($query)
    {
        return $query->where('trigger_type', 'scheduled');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }
}
