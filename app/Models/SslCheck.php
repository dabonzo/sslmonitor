<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'response_time',
        'check_metrics',
        'check_source',
        'agent_data',
        'security_analysis',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_valid' => 'boolean',
            'days_until_expiry' => 'integer',
            'check_metrics' => 'array',
            'agent_data' => 'array',
            'security_analysis' => 'array',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    // Virtual attributes for test compatibility
    public function getPluginMetricsAttribute(): ?array
    {
        return $this->check_metrics;
    }

    public function setPluginMetricsAttribute(array $value): void
    {
        $this->check_metrics = $value;
    }

    public function getProtocolVersionAttribute(): ?string
    {
        return $this->getCheckMetric('protocol_version');
    }

    public function setProtocolVersionAttribute(string $value): void
    {
        $this->setCheckMetric('protocol_version', $value);
    }

    public function getCipherSuiteAttribute(): ?string
    {
        return $this->getCheckMetric('cipher_suite');
    }

    public function setCipherSuiteAttribute(string $value): void
    {
        $this->setCheckMetric('cipher_suite', $value);
    }

    public function getKeySizeAttribute(): ?int
    {
        return $this->getCheckMetric('key_size');
    }

    public function setKeySizeAttribute(int $value): void
    {
        $this->setCheckMetric('key_size', $value);
    }

    public function getOcspStatusAttribute(): ?string
    {
        return $this->getSecurityAnalysis('ocsp_status');
    }

    public function setOcspStatusAttribute(string $value): void
    {
        $this->setSecurityAnalysis('ocsp_status', $value);
    }

    public function getCertificateChainLengthAttribute(): ?int
    {
        return $this->getCheckMetric('certificate_chain_length');
    }

    public function setCertificateChainLengthAttribute(int $value): void
    {
        $this->setCheckMetric('certificate_chain_length', $value);
    }

    // Query scopes from old_docs tests
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', 'valid');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'expired');
    }

    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->where('status', 'expiring_soon');
    }

    public function scopeInvalid(Builder $query): Builder
    {
        return $query->where('status', 'invalid');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'error');
    }

    public function scopeLatestChecked(Builder $query): Builder
    {
        return $query->orderBy('checked_at', 'desc');
    }

    public function scopeForWebsite(Builder $query, int $websiteId): Builder
    {
        return $query->where('website_id', $websiteId);
    }

    // Plugin-ready: Methods for future agent integration
    public function getCheckMetric(string $key): mixed
    {
        return $this->check_metrics[$key] ?? null;
    }

    public function setCheckMetric(string $key, mixed $value): void
    {
        $metrics = $this->check_metrics ?? [];
        $metrics[$key] = $value;
        $this->check_metrics = $metrics;
    }

    public function getAgentData(string $agentName, string $key = null): mixed
    {
        $data = $this->agent_data[$agentName] ?? [];

        return $key ? ($data[$key] ?? null) : $data;
    }

    public function setAgentData(string $agentName, string $key, mixed $value): void
    {
        $data = $this->agent_data ?? [];
        $data[$agentName][$key] = $value;
        $this->agent_data = $data;
    }

    public function getSecurityAnalysis(string $analysisType): mixed
    {
        return $this->security_analysis[$analysisType] ?? null;
    }

    public function setSecurityAnalysis(string $analysisType, mixed $value): void
    {
        $analysis = $this->security_analysis ?? [];
        $analysis[$analysisType] = $value;
        $this->security_analysis = $analysis;
    }

    public function isFromAgent(): bool
    {
        return $this->check_source === 'agent';
    }

    public function isFromExternalService(): bool
    {
        return $this->check_source === 'external_service';
    }

    public function isFromSslMonitor(): bool
    {
        return $this->check_source === 'ssl_monitor';
    }

    public function hasError(): bool
    {
        return $this->status === 'error' && !empty($this->error_message);
    }

    public function isSuccessful(): bool
    {
        return in_array($this->status, ['valid', 'expiring_soon']);
    }
}
