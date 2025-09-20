<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id',
        'status',
        'checked_at',
        'expires_at',
        'issuer',
        'subject',
        'serial_number',
        'signature_algorithm',
        'is_valid',
        'days_until_expiry',
        'error_message',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_valid' => 'boolean',
        'website_id' => 'integer',
        'days_until_expiry' => 'integer',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('status', 'valid');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeExpiringSoon($query)
    {
        return $query->where('status', 'expiring_soon');
    }

    public function scopeInvalid($query)
    {
        return $query->where('status', 'invalid');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'error');
    }

    public function scopeForWebsite($query, int $websiteId)
    {
        return $query->where('website_id', $websiteId);
    }

    public function scopeLatestChecked($query)
    {
        return $query->orderBy('checked_at', 'desc');
    }
}
