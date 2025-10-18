<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringAlert extends Model
{
    protected $fillable = [
        'monitor_id', 'website_id', 'alert_type', 'alert_severity', 'alert_title',
        'alert_message', 'first_detected_at', 'last_occurred_at', 'acknowledged_at',
        'resolved_at', 'acknowledged_by_user_id', 'acknowledgment_note',
        'trigger_value', 'threshold_value', 'affected_check_result_id',
        'notifications_sent', 'notification_channels', 'notification_status',
        'suppressed', 'suppressed_until', 'occurrence_count',
    ];

    protected $casts = [
        'first_detected_at' => 'datetime',
        'last_occurred_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'suppressed_until' => 'datetime',
        'trigger_value' => 'array',
        'threshold_value' => 'array',
        'notifications_sent' => 'array',
        'suppressed' => 'boolean',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by_user_id');
    }

    public function affectedCheckResult(): BelongsTo
    {
        return $this->belongsTo(MonitoringResult::class, 'affected_check_result_id');
    }

    // Scopes
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_at');
    }

    public function scopeCritical($query)
    {
        return $query->where('alert_severity', 'critical');
    }

    // Methods
    public function acknowledge(User $user, ?string $note = null): void
    {
        $this->update([
            'acknowledged_at' => now(),
            'acknowledged_by_user_id' => $user->id,
            'acknowledgment_note' => $note,
        ]);
    }

    public function resolve(): void
    {
        $this->update([
            'resolved_at' => now(),
        ]);
    }
}
