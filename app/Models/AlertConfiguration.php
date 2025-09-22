<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'website_id',
        'team_id',
        'alert_type',
        'enabled',
        'threshold_days',
        'threshold_response_time',
        'notification_channels',
        'alert_level',
        'custom_message',
        'last_triggered_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'threshold_days' => 'integer',
        'threshold_response_time' => 'integer',
        'notification_channels' => 'array',
        'last_triggered_at' => 'datetime',
    ];

    // Alert types
    public const ALERT_SSL_EXPIRY = 'ssl_expiry';
    public const ALERT_SSL_INVALID = 'ssl_invalid';
    public const ALERT_UPTIME_DOWN = 'uptime_down';
    public const ALERT_RESPONSE_TIME = 'response_time';
    public const ALERT_LETS_ENCRYPT_RENEWAL = 'lets_encrypt_renewal';

    // Alert levels (Let's Encrypt focused)
    public const LEVEL_INFO = 'info';           // 30 days
    public const LEVEL_WARNING = 'warning';     // 14 days
    public const LEVEL_URGENT = 'urgent';       // 7 days (Let's Encrypt focus)
    public const LEVEL_CRITICAL = 'critical';   // 3 days (Let's Encrypt critical)

    // Notification channels
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_DASHBOARD = 'dashboard';
    public const CHANNEL_SLACK = 'slack';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public static function getDefaultConfigurations(): array
    {
        return [
            [
                'alert_type' => self::ALERT_SSL_EXPIRY,
                'enabled' => true,
                'threshold_days' => 7,
                'alert_level' => self::LEVEL_URGENT,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],
            [
                'alert_type' => self::ALERT_LETS_ENCRYPT_RENEWAL,
                'enabled' => true,
                'threshold_days' => 3,
                'alert_level' => self::LEVEL_CRITICAL,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],
            [
                'alert_type' => self::ALERT_SSL_INVALID,
                'enabled' => true,
                'threshold_days' => null,
                'alert_level' => self::LEVEL_CRITICAL,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],
            [
                'alert_type' => self::ALERT_UPTIME_DOWN,
                'enabled' => true,
                'threshold_days' => null,
                'alert_level' => self::LEVEL_CRITICAL,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],
            [
                'alert_type' => self::ALERT_RESPONSE_TIME,
                'enabled' => true,
                'threshold_days' => null,
                'threshold_response_time' => 5000, // 5 seconds
                'alert_level' => self::LEVEL_WARNING,
                'notification_channels' => [self::CHANNEL_DASHBOARD],
            ],
        ];
    }

    public function shouldTrigger(array $checkData): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Cooldown check - don't spam alerts (24 hour minimum)
        if ($this->last_triggered_at && $this->last_triggered_at->diffInHours(now()) < 24) {
            return false;
        }

        return match($this->alert_type) {
            self::ALERT_SSL_EXPIRY => $this->shouldTriggerSslExpiry($checkData),
            self::ALERT_LETS_ENCRYPT_RENEWAL => $this->shouldTriggerLetsEncryptRenewal($checkData),
            self::ALERT_SSL_INVALID => $this->shouldTriggerSslInvalid($checkData),
            self::ALERT_UPTIME_DOWN => $this->shouldTriggerUptimeDown($checkData),
            self::ALERT_RESPONSE_TIME => $this->shouldTriggerResponseTime($checkData),
            default => false,
        };
    }

    private function shouldTriggerSslExpiry(array $checkData): bool
    {
        $daysRemaining = $checkData['ssl_days_remaining'] ?? null;

        if ($daysRemaining === null) {
            return false;
        }

        return $daysRemaining <= $this->threshold_days && $daysRemaining >= 0;
    }

    private function shouldTriggerLetsEncryptRenewal(array $checkData): bool
    {
        $isLetsEncrypt = $checkData['is_lets_encrypt'] ?? false;
        $daysRemaining = $checkData['ssl_days_remaining'] ?? null;

        if (!$isLetsEncrypt || $daysRemaining === null) {
            return false;
        }

        return $daysRemaining <= $this->threshold_days && $daysRemaining >= 0;
    }

    private function shouldTriggerSslInvalid(array $checkData): bool
    {
        $sslStatus = $checkData['ssl_status'] ?? '';
        return in_array($sslStatus, ['invalid', 'expired', 'failed']);
    }

    private function shouldTriggerUptimeDown(array $checkData): bool
    {
        $uptimeStatus = $checkData['uptime_status'] ?? '';
        return in_array($uptimeStatus, ['down', 'failed']);
    }

    private function shouldTriggerResponseTime(array $checkData): bool
    {
        $responseTime = $checkData['response_time'] ?? null;

        if ($responseTime === null || $this->threshold_response_time === null) {
            return false;
        }

        return $responseTime > $this->threshold_response_time;
    }

    public function markTriggered(): void
    {
        $this->update(['last_triggered_at' => now()]);
    }

    public function getAlertLevelColor(): string
    {
        return match($this->alert_level) {
            self::LEVEL_CRITICAL => 'red',
            self::LEVEL_URGENT => 'orange',
            self::LEVEL_WARNING => 'yellow',
            self::LEVEL_INFO => 'blue',
            default => 'gray',
        };
    }

    public function getAlertTypeLabel(): string
    {
        return match($this->alert_type) {
            self::ALERT_SSL_EXPIRY => 'SSL Certificate Expiry',
            self::ALERT_LETS_ENCRYPT_RENEWAL => 'Let\'s Encrypt Renewal',
            self::ALERT_SSL_INVALID => 'SSL Certificate Invalid',
            self::ALERT_UPTIME_DOWN => 'Website Down',
            self::ALERT_RESPONSE_TIME => 'Slow Response Time',
            default => 'Unknown Alert',
        };
    }
}