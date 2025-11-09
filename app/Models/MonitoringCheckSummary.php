<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringCheckSummary extends Model
{
    use HasFactory;
    protected $fillable = [
        'monitor_id', 'website_id', 'summary_period', 'period_start', 'period_end',
        'total_uptime_checks', 'successful_uptime_checks', 'failed_uptime_checks',
        'uptime_percentage', 'average_response_time_ms', 'min_response_time_ms',
        'max_response_time_ms', 'p95_response_time_ms', 'p99_response_time_ms',
        'total_ssl_checks', 'successful_ssl_checks', 'failed_ssl_checks',
        'certificates_expiring', 'certificates_expired', 'total_checks',
        'total_check_duration_ms', 'average_check_duration_ms',
        'total_content_validations', 'successful_content_validations',
        'failed_content_validations',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'uptime_percentage' => 'decimal:2',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    // Scopes
    public function scopeDaily($query)
    {
        return $query->where('summary_period', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('summary_period', 'weekly');
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('summary_period', $period);
    }
}
