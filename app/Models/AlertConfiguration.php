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

    public const ALERT_UPTIME_UP = 'uptime_up';

    public const ALERT_RESPONSE_TIME = 'response_time';

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
            // SSL Certificate Expiry - Multiple severity levels
            [
                'alert_type' => self::ALERT_SSL_EXPIRY,
                'enabled' => false, // Disabled by default - users usually don't want 30-day warnings
                'threshold_days' => 30,
                'alert_level' => self::LEVEL_INFO,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],
            [
                'alert_type' => self::ALERT_SSL_EXPIRY,
                'enabled' => false, // Disabled by default
                'threshold_days' => 14,
                'alert_level' => self::LEVEL_WARNING,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],
            [
                'alert_type' => self::ALERT_SSL_EXPIRY,
                'enabled' => true, // Enabled - most users want 7-day warnings
                'threshold_days' => 7,
                'alert_level' => self::LEVEL_URGENT,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],
            [
                'alert_type' => self::ALERT_SSL_EXPIRY,
                'enabled' => true, // Enabled - critical 3-day alert
                'threshold_days' => 3,
                'alert_level' => self::LEVEL_CRITICAL,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],
            [
                'alert_type' => self::ALERT_SSL_EXPIRY,
                'enabled' => true, // Enabled - expired certificate alert
                'threshold_days' => 0,
                'alert_level' => self::LEVEL_CRITICAL,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],

            // SSL Certificate Invalid
            [
                'alert_type' => self::ALERT_SSL_INVALID,
                'enabled' => true,
                'threshold_days' => null,
                'alert_level' => self::LEVEL_CRITICAL,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],

            // Uptime Monitoring
            [
                'alert_type' => self::ALERT_UPTIME_DOWN,
                'enabled' => true,
                'threshold_days' => null,
                'alert_level' => self::LEVEL_CRITICAL,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],
            [
                'alert_type' => self::ALERT_UPTIME_UP,
                'enabled' => true,
                'threshold_days' => null,
                'alert_level' => self::LEVEL_INFO,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],

            // Response Time - Disabled by default per your specifications
            [
                'alert_type' => self::ALERT_RESPONSE_TIME,
                'enabled' => false, // Disabled by default
                'threshold_days' => null,
                'threshold_response_time' => 5000, // 5 seconds - Warning level
                'alert_level' => self::LEVEL_WARNING,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],
            [
                'alert_type' => self::ALERT_RESPONSE_TIME,
                'enabled' => false, // Disabled by default
                'threshold_days' => null,
                'threshold_response_time' => 10000, // 10 seconds - Critical level
                'alert_level' => self::LEVEL_CRITICAL,
                'notification_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DASHBOARD],
            ],
        ];
    }

    public function shouldTrigger(array $checkData, bool $bypassCooldown = false, bool $bypassEnabledCheck = false): bool
    {
        // For debug testing, we can bypass the enabled check to test disabled alerts
        if (! $bypassEnabledCheck && ! $this->enabled) {
            return false;
        }

        // Hybrid alert logic: immediate critical alerts, daily warnings
        if (! $bypassCooldown) {
            // Check if we should trigger based on alert urgency
            if ($this->isImmediateAlert($checkData)) {
                // Critical alerts - no cooldown (expired SSL, uptime down)
                // Always trigger these immediately
            } else {
                // Daily warnings - check if already sent today
                if ($this->alreadySentToday()) {
                    return false;
                }
            }
        }

        return match ($this->alert_type) {
            self::ALERT_SSL_EXPIRY => $this->shouldTriggerSslExpiry($checkData),
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

        // For 0-day threshold (expired certificates), trigger when days <= 0
        if ($this->threshold_days === 0) {
            return $daysRemaining <= 0;
        }

        // For positive thresholds, trigger when days <= threshold and still valid
        return $daysRemaining <= $this->threshold_days && $daysRemaining >= 0;
    }

    private function shouldTriggerSslInvalid(array $checkData): bool
    {
        $sslStatus = $checkData['ssl_status'] ?? '';

        // Only trigger for invalid certificates, not expired ones
        // Expired certificates are handled by SSL Expiry alerts
        return in_array($sslStatus, ['invalid', 'failed']);
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

        return $responseTime >= $this->threshold_response_time;
    }

    public function markTriggered(): void
    {
        $this->update(['last_triggered_at' => now()]);
    }

    public function getAlertLevelColor(): string
    {
        return match ($this->alert_level) {
            self::LEVEL_CRITICAL => 'red',
            self::LEVEL_URGENT => 'orange',
            self::LEVEL_WARNING => 'yellow',
            self::LEVEL_INFO => 'blue',
            default => 'gray',
        };
    }

    public function getAlertTypeLabel(): string
    {
        return match ($this->alert_type) {
            self::ALERT_SSL_EXPIRY => 'SSL Certificate Expiry',
            self::ALERT_SSL_INVALID => 'SSL Certificate Invalid',
            self::ALERT_UPTIME_DOWN => 'Website Down',
            self::ALERT_UPTIME_UP => 'Website Recovered',
            self::ALERT_RESPONSE_TIME => 'Slow Response Time',
            default => 'Unknown Alert',
        };
    }

    /**
     * Check if this alert should be sent immediately (critical alerts)
     */
    private function isImmediateAlert(array $checkData): bool
    {
        return match ($this->alert_type) {
            self::ALERT_UPTIME_DOWN => true, // Always immediate
            self::ALERT_SSL_INVALID => true, // Always immediate
            self::ALERT_SSL_EXPIRY => $this->shouldTriggerSslExpiry($checkData) &&
                                      ($checkData['ssl_days_remaining'] ?? null) <= 0, // Only immediate if expired
            self::ALERT_RESPONSE_TIME => false, // Never immediate (warnings only)
            default => false,
        };
    }

    /**
     * Check if this alert was already sent within the last 24 hours
     */
    private function alreadySentToday(): bool
    {
        if (! $this->last_triggered_at) {
            return false;
        }

        // Check if last trigger was within the last 24 hours
        return $this->last_triggered_at->gt(now()->subHours(24));
    }
}
