<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mailer',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_address',
        'from_name',
        'timeout',
        'verify_peer',
        'is_active',
        'last_tested_at',
        'test_passed',
        'test_error',
        'notification_channels',
        'notification_rules',
        'template_config',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'timeout' => 'integer',
            'verify_peer' => 'boolean',
            'is_active' => 'boolean',
            'last_tested_at' => 'datetime',
            'test_passed' => 'boolean',
            'notification_channels' => 'array',
            'notification_rules' => 'array',
            'template_config' => 'array',
            'password' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Plugin-ready: Methods for future notification plugins
    public function getNotificationChannel(string $channelType): mixed
    {
        return $this->notification_channels[$channelType] ?? null;
    }

    public function setNotificationChannel(string $channelType, array $config): void
    {
        $channels = $this->notification_channels ?? [];
        $channels[$channelType] = $config;
        $this->notification_channels = $channels;
    }

    public function hasNotificationChannel(string $channelType): bool
    {
        return isset($this->notification_channels[$channelType]);
    }

    public function getNotificationRule(string $ruleType): mixed
    {
        return $this->notification_rules[$ruleType] ?? null;
    }

    public function setNotificationRule(string $ruleType, mixed $rule): void
    {
        $rules = $this->notification_rules ?? [];
        $rules[$ruleType] = $rule;
        $this->notification_rules = $rules;
    }

    public function getTemplateConfig(string $templateType): mixed
    {
        return $this->template_config[$templateType] ?? null;
    }

    public function setTemplateConfig(string $templateType, array $config): void
    {
        $templates = $this->template_config ?? [];
        $templates[$templateType] = $config;
        $this->template_config = $templates;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->host) && ! empty($this->from_address);
    }

    public function isSmtp(): bool
    {
        return $this->mailer === 'smtp';
    }

    public function requiresAuthentication(): bool
    {
        return ! empty($this->username);
    }

    public function getMailConfig(): array
    {
        return [
            'transport' => $this->mailer,
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption,
            'username' => $this->username,
            'password' => $this->password,
            'timeout' => $this->timeout,
            'local_domain' => '['.($_SERVER['SERVER_ADDR'] ?? '127.0.0.1').']',
            'verify_peer' => $this->verify_peer,
        ];
    }
}
