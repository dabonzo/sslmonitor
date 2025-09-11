<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Validation\ValidationException;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'user_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
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

    public function getLatestSslCertificate()
    {
        return $this->sslCertificates()->latest()->first();
    }

    public function getCurrentSslStatus()
    {
        // For now, return 'unknown' until we implement SslCheck model
        return 'unknown';
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
        
        if (!$parsed || !isset($parsed['host'])) {
            return $url;
        }

        // Force HTTPS
        $scheme = 'https';
        $host = $parsed['host'];
        
        return "{$scheme}://{$host}";
    }
}