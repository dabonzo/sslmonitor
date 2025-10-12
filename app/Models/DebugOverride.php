<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DebugOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'module_type',
        'targetable_type',
        'targetable_id',
        'override_data',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'override_data' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the parent targetable model (website, monitor, etc.).
     */
    public function targetable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that owns the debug override.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active overrides.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include non-expired overrides.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope a query to only include overrides for a specific module.
     */
    public function scopeForModule($query, string $moduleType)
    {
        return $query->where('module_type', $moduleType);
    }

    /**
     * Check if the override is currently effective.
     */
    public function isEffective(): bool
    {
        return $this->is_active &&
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Mark the override as inactive.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }
}