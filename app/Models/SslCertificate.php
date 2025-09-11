<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_id',
        'issuer',
        'expires_at',
        'subject',
        'serial_number',
        'signature_algorithm',
        'is_valid',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_valid' => 'boolean',
        'website_id' => 'integer',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function getDaysUntilExpiry(): int
    {
        return (int) Carbon::now()->startOfDay()->diffInDays($this->expires_at->startOfDay(), false);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isExpiringSoon(int $days = 14): bool
    {
        return ! $this->isExpired() && $this->getDaysUntilExpiry() <= $days;
    }

    public function getStatus(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }

        if (! $this->is_valid) {
            return 'invalid';
        }

        if ($this->isExpiringSoon()) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    public function scopeInvalid($query)
    {
        return $query->where('is_valid', false);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', Carbon::now());
    }

    public function scopeExpiringSoon($query, int $days = 14)
    {
        return $query->where('expires_at', '>=', Carbon::now())
            ->where('expires_at', '<=', Carbon::now()->addDays($days))
            ->where('is_valid', true);
    }
}
