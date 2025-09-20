<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class EmailSettings extends Model
{
    use HasFactory;

    protected $fillable = [
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
        'user_id',
        'team_id',
        'notification_emails',
        'notification_phones',
    ];

    protected $casts = [
        'port' => 'integer',
        'timeout' => 'integer',
        'verify_peer' => 'boolean',
        'is_active' => 'boolean',
        'test_passed' => 'boolean',
        'last_tested_at' => 'datetime',
        'user_id' => 'integer',
        'team_id' => 'integer',
        'notification_emails' => 'array',
        'notification_phones' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Encrypt password when storing
     */
    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Decrypt password when retrieving
     */
    public function getPasswordAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Get the user this email setting belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team this email setting belongs to
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the active email settings (personal)
     */
    public static function active(): ?self
    {
        return self::where('is_active', true)->whereNull('team_id')->first();
    }

    /**
     * Get active email settings for a team
     */
    public static function activeForTeam(Team $team): ?self
    {
        return self::where('is_active', true)->where('team_id', $team->id)->first();
    }

    /**
     * Get active email settings for a user (personal settings)
     */
    public static function activeForUser(User $user): ?self
    {
        return self::where('is_active', true)->where('user_id', $user->id)->whereNull('team_id')->first();
    }

    /**
     * Check if this is a personal email setting
     */
    public function isPersonal(): bool
    {
        return $this->team_id === null;
    }

    /**
     * Check if this is a team email setting
     */
    public function isTeam(): bool
    {
        return $this->team_id !== null;
    }

    /**
     * Get mail configuration array for Laravel
     */
    public function toMailConfig(): array
    {
        $mailer = $this->mailer ?? 'smtp';

        return [
            'default' => $mailer,
            'mailers' => [
                $mailer => [
                    'transport' => 'smtp',
                    'host' => $this->host,
                    'port' => $this->port,
                    'encryption' => $this->encryption,
                    'username' => $this->username,
                    'password' => $this->password,
                    'timeout' => $this->timeout,
                    'verify_peer' => $this->verify_peer,
                ],
            ],
            'from' => [
                'address' => $this->from_address,
                'name' => $this->from_name,
            ],
        ];
    }

    /**
     * Test the email configuration
     */
    public function test(?string $recipientEmail = null): bool
    {
        try {
            // Use provided recipient email or fall back to the current authenticated user
            $recipient = $recipientEmail ?: auth()->user()->email;

            // Temporarily configure mail with these settings
            $originalConfig = config('mail');
            config($this->toMailConfig());

            // Send test email
            \Mail::raw('This is a test email from SSL Monitor to verify your email configuration is working correctly.', function ($message) use ($recipient) {
                $message->to($recipient)
                    ->subject('SSL Monitor - Email Configuration Test');
            });

            // Restore original config
            config(['mail' => $originalConfig]);

            // Update test results
            $this->update([
                'last_tested_at' => now(),
                'test_passed' => true,
                'test_error' => null,
            ]);

            return true;

        } catch (\Exception $e) {
            // Restore original config
            if (isset($originalConfig)) {
                config(['mail' => $originalConfig]);
            }

            // Update test results
            $this->update([
                'last_tested_at' => now(),
                'test_passed' => false,
                'test_error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
