<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'user_id',
        'team_id',
        'added_by',
        'uptime_monitoring',
        'javascript_enabled',
        'expected_status_code',
        'expected_content',
        'forbidden_content',
        'max_response_time',
        'follow_redirects',
        'max_redirects',
        'uptime_status',
        'last_uptime_check_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'team_id' => 'integer',
        'added_by' => 'integer',
        'uptime_monitoring' => 'boolean',
        'javascript_enabled' => 'boolean',
        'expected_status_code' => 'integer',
        'max_response_time' => 'integer',
        'follow_redirects' => 'boolean',
        'max_redirects' => 'integer',
        'last_uptime_check_at' => 'datetime',
    ];

    protected $attributes = [
        'uptime_monitoring' => false,
        'expected_status_code' => 200,
        'max_response_time' => 30000,
        'follow_redirects' => true,
        'max_redirects' => 3,
        'uptime_status' => 'unknown',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sslCertificates(): HasMany
    {
        return $this->hasMany(SslCertificate::class);
    }

    public function sslChecks(): HasMany
    {
        return $this->hasMany('App\Models\SslCheck');
    }

    public function uptimeChecks(): HasMany
    {
        return $this->hasMany(UptimeCheck::class);
    }

    public function downtimeIncidents(): HasMany
    {
        return $this->hasMany(DowntimeIncident::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function getLatestSslCertificate()
    {
        return $this->sslCertificates()->latest()->first();
    }

    public function getCurrentSslStatus()
    {
        // For now, return 'unknown' until we implement SslCheck model
        return 'unknown';
    }

    /**
     * Check if this is a personal website (not team)
     */
    public function isPersonal(): bool
    {
        return $this->team_id === null;
    }

    /**
     * Check if this is a team website
     */
    public function isTeamWebsite(): bool
    {
        return $this->team_id !== null;
    }

    /**
     * Get the owner of this website (either user or team owner)
     */
    public function getOwner(): User
    {
        return $this->isPersonal() ? $this->user : $this->team->owner;
    }

    /**
     * Scope to get personal websites for a user
     */
    public function scopePersonal($query, User $user)
    {
        return $query->where('user_id', $user->id)->whereNull('team_id');
    }

    /**
     * Scope to get team websites for teams user has access to
     */
    public function scopeAccessibleToUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            // Personal websites
            $q->where('user_id', $user->id)->whereNull('team_id');
        })->orWhere(function ($q) use ($user) {
            // Team websites where user is a member
            $q->whereIn('team_id', $user->teams()->pluck('teams.id'));
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($website) {
            // Sanitize URL before saving
            if ($website->url) {
                $website->url = static::sanitizeUrl($website->url);
            }
        });
    }

    protected static function sanitizeUrl(string $url): string
    {
        // Normalize the URL
        $url = strtolower($url);

        // Remove trailing slashes and paths like /../
        $parsed = parse_url($url);

        if (! $parsed || ! isset($parsed['host'])) {
            return $url;
        }

        // Force HTTPS
        $scheme = 'https';
        $host = $parsed['host'];

        return "{$scheme}://{$host}";
    }
}
