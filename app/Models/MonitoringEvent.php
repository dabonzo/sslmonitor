<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringEvent extends Model
{
    const UPDATED_AT = null; // No updated_at column

    protected $fillable = [
        'monitor_id', 'website_id', 'user_id', 'event_type', 'event_name',
        'description', 'old_values', 'new_values', 'event_data', 'ip_address',
        'user_agent', 'source',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'event_data' => 'array',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUserActions($query)
    {
        return $query->where('source', 'user');
    }

    public function scopeSystemEvents($query)
    {
        return $query->where('source', 'system');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }
}
