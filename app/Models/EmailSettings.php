<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    protected $casts = [
        'port' => 'integer',
        'timeout' => 'integer',
        'verify_peer' => 'boolean',
        'is_active' => 'boolean',
        'test_passed' => 'boolean',
        'last_tested_at' => 'datetime',
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
     * Get the active email settings
     */
    public static function active(): ?self
    {
        return self::where('is_active', true)->first();
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
    public function test(): bool
    {
        try {
            // Temporarily configure mail with these settings
            $originalConfig = config('mail');
            config($this->toMailConfig());

            // Send test email
            \Mail::raw('This is a test email from SSL Monitor to verify email configuration.', function ($message) {
                $message->to($this->from_address)
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
