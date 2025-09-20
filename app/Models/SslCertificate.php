<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
        'certificate_chain',
        'security_metrics',
        'certificate_hash',
        'plugin_analysis',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_valid' => 'boolean',
            'certificate_chain' => 'array',
            'security_metrics' => 'array',
            'plugin_analysis' => 'array',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function getDaysUntilExpiry(): int
    {
        return Carbon::now()->diffInDays($this->expires_at, false);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isExpiringSoon(int $days = 14): bool
    {
        return !$this->isExpired() && $this->getDaysUntilExpiry() <= $days;
    }

    public function getStatus(): string
    {
        if (!$this->is_valid) {
            return 'invalid';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->isExpiringSoon()) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    // Query scopes from old_docs
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('is_valid', true);
    }

    public function scopeInvalid(Builder $query): Builder
    {
        return $query->where('is_valid', false);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', Carbon::now());
    }

    public function scopeExpiringSoon(Builder $query, int $days = 14): Builder
    {
        return $query->where('expires_at', '>', Carbon::now())
            ->where('expires_at', '<=', Carbon::now()->addDays($days))
            ->where('is_valid', true);
    }

    // Plugin-ready: Methods for future agent integration
    public function getSecurityMetric(string $key): mixed
    {
        return $this->security_metrics[$key] ?? null;
    }

    public function setSecurityMetric(string $key, mixed $value): void
    {
        $metrics = $this->security_metrics ?? [];
        $metrics[$key] = $value;
        $this->security_metrics = $metrics;
    }

    public function getPluginAnalysis(string $pluginName, string $key = null): mixed
    {
        $analysis = $this->plugin_analysis[$pluginName] ?? [];

        return $key ? ($analysis[$key] ?? null) : $analysis;
    }

    public function setPluginAnalysis(string $pluginName, string $key, mixed $value): void
    {
        $analysis = $this->plugin_analysis ?? [];
        $analysis[$pluginName][$key] = $value;
        $this->plugin_analysis = $analysis;
    }

    public function getCertificateChain(): array
    {
        return $this->certificate_chain ?? [];
    }

    public function setCertificateChain(array $chain): void
    {
        $this->certificate_chain = $chain;
    }

    public function generateCertificateHash(): string
    {
        $data = $this->subject . $this->serial_number . $this->expires_at->toString();

        return hash('sha256', $data);
    }

    public function updateCertificateHash(): void
    {
        $this->certificate_hash = $this->generateCertificateHash();
    }
}
