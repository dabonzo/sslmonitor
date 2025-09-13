<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_enabled',
        'email_address',
        'expiry_days_notice',
        'error_alerts',
        'uptime_alerts',
        'downtime_recovery_alerts',
        'slow_response_alerts',
        'content_mismatch_alerts',
        'daily_digest',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'error_alerts' => 'boolean',
        'uptime_alerts' => 'boolean',
        'downtime_recovery_alerts' => 'boolean',
        'slow_response_alerts' => 'boolean',
        'content_mismatch_alerts' => 'boolean',
        'daily_digest' => 'boolean',
        'expiry_days_notice' => 'array',
    ];

    protected $attributes = [
        'email_enabled' => true,
        'error_alerts' => true,
        'uptime_alerts' => true,
        'downtime_recovery_alerts' => true,
        'slow_response_alerts' => true,
        'content_mismatch_alerts' => true,
        'daily_digest' => false,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->expiry_days_notice) {
                $model->expiry_days_notice = [7, 14, 30];
            }

            // Validate email format
            if ($model->email_address && ! filter_var($model->email_address, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('The email address format is invalid.');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shouldSendExpiryNotification(int $daysUntilExpiry): bool
    {
        if (! $this->email_enabled) {
            return false;
        }

        return in_array($daysUntilExpiry, $this->expiry_days_notice ?? []);
    }

    public function shouldSendUptimeNotification(string $notificationType): bool
    {
        if (! $this->email_enabled) {
            return false;
        }

        return match ($notificationType) {
            'downtime', 'down' => $this->uptime_alerts,
            'recovery', 'up' => $this->downtime_recovery_alerts,
            'slow' => $this->slow_response_alerts,
            'content_mismatch' => $this->content_mismatch_alerts,
            default => false,
        };
    }
}
