<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DowntimeIncident extends Model
{
    /** @use HasFactory<\Database\Factories\DowntimeIncidentFactory> */
    use HasFactory;

    protected $fillable = [
        'website_id',
        'started_at',
        'ended_at',
        'duration_minutes',
        'max_response_time_ms',
        'incident_type',
        'error_details',
        'resolved_automatically',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'resolved_automatically' => 'boolean',
    ];

    protected $attributes = [
        'resolved_automatically' => false,
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($incident) {
            // Auto-calculate duration when both started_at and ended_at are set
            if ($incident->started_at && $incident->ended_at) {
                $incident->duration_minutes = $incident->started_at->diffInMinutes($incident->ended_at);
            }
        });
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function isOngoing(): bool
    {
        return $this->ended_at === null;
    }

    public function resolve(bool $automatically = false): void
    {
        $this->ended_at = now();
        $this->resolved_automatically = $automatically;
        $this->duration_minutes = $this->started_at->diffInMinutes($this->ended_at);
        $this->save();
    }
}
