# SSL Monitor v4 - Technical Specifications

## 📊 Architecture Overview

SSL Monitor v4 combines the proven backend architecture from v3 (analyzed from `old_docs/`) with a modern Vue 3 + Inertia.js frontend. This document provides comprehensive technical specifications for all system components.

### **Technology Stack**
- **Backend**: Laravel 12 + PHP 8.4 + MariaDB + Redis
- **Frontend**: Vue 3 + Inertia.js + VRISTO Template + TailwindCSS v4
- **Testing**: Pest v4 + Browser Testing
- **SSL Monitoring**: Spatie SSL Certificate Package
- **Background Processing**: Laravel Jobs + Redis Queues

---

## 🗄️ Database Schema Specifications

### **Core Tables** (Migrated from old_docs)

#### **users** (Enhanced existing table)
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    last_login TIMESTAMP NULL,                    -- New: Track user activity
    preferences JSON NULL,                        -- New: UI preferences, timezone
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX users_email_index (email),
    INDEX users_last_login_index (last_login)
);
```

#### **websites** (Core SSL monitoring entity)
```sql
CREATE TABLE websites (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    check_interval INTEGER DEFAULT 1440,          -- Minutes between checks (default: daily)
    is_active BOOLEAN DEFAULT TRUE,
    last_checked_at TIMESTAMP NULL,
    next_check_at TIMESTAMP NULL,
    notes TEXT NULL,                              -- User notes about the website
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY websites_user_url_unique (user_id, url(191)),
    INDEX websites_user_id_index (user_id),
    INDEX websites_last_checked_at_index (last_checked_at),
    INDEX websites_next_check_at_index (next_check_at),
    INDEX websites_is_active_index (is_active)
);
```

#### **ssl_certificates** (Certificate details and history)
```sql
CREATE TABLE ssl_certificates (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    website_id BIGINT UNSIGNED NOT NULL,
    issuer VARCHAR(255) NULL,
    subject VARCHAR(255) NULL,
    serial_number VARCHAR(255) NULL,
    signature_algorithm VARCHAR(100) NULL,
    expires_at TIMESTAMP NULL,
    issued_at TIMESTAMP NULL,
    is_valid BOOLEAN DEFAULT FALSE,
    is_self_signed BOOLEAN DEFAULT FALSE,
    chain_length INTEGER NULL,                    -- Certificate chain depth
    key_length INTEGER NULL,                      -- Public key length
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    INDEX ssl_certificates_website_id_index (website_id),
    INDEX ssl_certificates_expires_at_index (expires_at),
    INDEX ssl_certificates_is_valid_index (is_valid),
    INDEX ssl_certificates_created_at_index (created_at)
);
```

#### **ssl_checks** (Check results and status history)
```sql
CREATE TABLE ssl_checks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    website_id BIGINT UNSIGNED NOT NULL,
    status ENUM('valid', 'expiring_soon', 'expired', 'invalid', 'error', 'pending') NOT NULL,
    checked_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NULL,
    days_until_expiry INTEGER NULL,
    error_message TEXT NULL,
    response_time INTEGER NULL,                   -- Check duration in milliseconds
    certificate_changed BOOLEAN DEFAULT FALSE,   -- Did certificate change since last check?
    issuer VARCHAR(255) NULL,                    -- Quick access to issuer info
    created_at TIMESTAMP NULL,

    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    INDEX ssl_checks_website_id_index (website_id),
    INDEX ssl_checks_status_index (status),
    INDEX ssl_checks_checked_at_index (checked_at),
    INDEX ssl_checks_expires_at_index (expires_at),
    INDEX ssl_checks_website_status_checked (website_id, status, checked_at)
);
```

#### **email_settings** (In-app SMTP configuration)
```sql
CREATE TABLE email_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    smtp_host VARCHAR(255) NOT NULL,
    smtp_port INTEGER NOT NULL DEFAULT 587,
    smtp_username VARCHAR(255) NULL,
    smtp_password TEXT NULL,                      -- Encrypted with Laravel Crypt
    smtp_encryption ENUM('tls', 'ssl', 'none') DEFAULT 'tls',
    from_address VARCHAR(255) NOT NULL,
    from_name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    test_email_sent_at TIMESTAMP NULL,           -- Last successful test
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX email_settings_is_active_index (is_active)
);
```

#### **notification_preferences** (User alert preferences)
```sql
CREATE TABLE notification_preferences (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    website_id BIGINT UNSIGNED NULL,             -- NULL = global preference
    notification_type ENUM('expiring_14', 'expiring_7', 'expiring_1', 'expired', 'error') NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    email_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    UNIQUE KEY notification_preferences_unique (user_id, website_id, notification_type),
    INDEX notification_preferences_user_id_index (user_id),
    INDEX notification_preferences_website_id_index (website_id)
);
```

---

## 📦 Model Specifications

### **User Model** (Enhanced for SSL monitoring)
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name', 'email', 'password', 'last_login', 'preferences'
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_recovery_codes', 'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login' => 'datetime',
            'preferences' => 'array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // Relationships
    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    // Business Logic
    public function updateLastLogin(): void
    {
        $this->update(['last_login' => now()]);
    }

    public function getPreference(string $key, $default = null)
    {
        return data_get($this->preferences, $key, $default);
    }

    public function setPreference(string $key, $value): void
    {
        $preferences = $this->preferences ?? [];
        data_set($preferences, $key, $value);
        $this->update(['preferences' => $preferences]);
    }

    // SSL Monitoring Aggregations
    public function getSslStatusCounts(): array
    {
        return [
            'total' => $this->websites()->count(),
            'valid' => $this->getWebsiteCountByStatus('valid'),
            'expiring_soon' => $this->getWebsiteCountByStatus('expiring_soon'),
            'expired' => $this->getWebsiteCountByStatus('expired'),
            'error' => $this->getWebsiteCountByStatus('error'),
            'pending' => $this->getWebsiteCountByStatus('pending'),
        ];
    }

    private function getWebsiteCountByStatus(string $status): int
    {
        return $this->websites()
            ->whereHas('sslChecks', function ($query) use ($status) {
                $query->where('status', $status)
                      ->whereRaw('checked_at = (SELECT MAX(checked_at) FROM ssl_checks WHERE website_id = websites.id)');
            })
            ->count();
    }
}
```

### **Website Model** (Core SSL monitoring entity)
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'url', 'check_interval', 'is_active',
        'last_checked_at', 'next_check_at', 'notes'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
            'next_check_at' => 'datetime',
            'check_interval' => 'integer',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sslCertificates(): HasMany
    {
        return $this->hasMany(SslCertificate::class);
    }

    public function sslChecks(): HasMany
    {
        return $this->hasMany(SslCheck::class);
    }

    // URL Sanitization (proven logic from old_docs)
    protected function setUrlAttribute(string $value): void
    {
        $this->attributes['url'] = $this->sanitizeUrl($value);
    }

    private function sanitizeUrl(string $url): string
    {
        // Ensure protocol
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }

        // Parse and rebuild URL
        $parsed = parse_url(strtolower($url));

        if (!$parsed || !isset($parsed['host'])) {
            return $url; // Return original if parsing fails
        }

        // Force HTTPS for security
        $scheme = 'https';
        $host = $parsed['host'];
        $path = isset($parsed['path']) ? rtrim($parsed['path'], '/') : '';

        // Remove redundant path elements
        $path = preg_replace('/\/+/', '/', $path);
        $path = rtrim($path, '/');

        return $scheme . '://' . $host . $path;
    }

    // Business Logic Methods
    public function getLatestSslCertificate(): ?SslCertificate
    {
        return $this->sslCertificates()->latest()->first();
    }

    public function getLatestSslCheck(): ?SslCheck
    {
        return $this->sslChecks()->latest('checked_at')->first();
    }

    public function getCurrentSslStatus(): string
    {
        $latestCheck = $this->getLatestSslCheck();
        return $latestCheck?->status ?? 'pending';
    }

    public function getDaysUntilExpiry(): ?int
    {
        $latestCheck = $this->getLatestSslCheck();
        return $latestCheck?->days_until_expiry;
    }

    public function isDueForCheck(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->last_checked_at) {
            return true; // Never checked
        }

        return $this->last_checked_at->addMinutes($this->check_interval)->isPast();
    }

    public function scheduleNextCheck(): void
    {
        $this->update([
            'next_check_at' => now()->addMinutes($this->check_interval)
        ]);
    }

    // Query Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueForCheck($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($query) {
                        $query->whereNull('last_checked_at')
                              ->orWhereRaw('last_checked_at + INTERVAL check_interval MINUTE < NOW()');
                    });
    }

    public function scopeWithLatestSslCheck($query)
    {
        return $query->with(['sslChecks' => function ($query) {
            $query->latest('checked_at')->limit(1);
        }]);
    }

    // Domain Extraction
    public function getDomain(): string
    {
        return parse_url($this->url, PHP_URL_HOST) ?? $this->url;
    }

    public function getDisplayUrl(): string
    {
        return str_replace(['http://', 'https://'], '', $this->url);
    }
}
```

### **SslCertificate Model** (Certificate details and validation)
```php
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
        'website_id', 'issuer', 'subject', 'serial_number',
        'signature_algorithm', 'expires_at', 'issued_at',
        'is_valid', 'is_self_signed', 'chain_length', 'key_length'
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'issued_at' => 'datetime',
            'is_valid' => 'boolean',
            'is_self_signed' => 'boolean',
            'chain_length' => 'integer',
            'key_length' => 'integer',
        ];
    }

    // Relationships
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    // Business Logic (proven from old_docs)
    public function isValid(): bool
    {
        return $this->is_valid && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isExpiringSoon(int $days = 14): bool
    {
        if (!$this->expires_at || $this->isExpired()) {
            return false;
        }

        return $this->expires_at->diffInDays(now()) <= $days;
    }

    public function getDaysUntilExpiry(): int
    {
        if (!$this->expires_at) {
            return 0;
        }

        if ($this->isExpired()) {
            return -$this->expires_at->diffInDays(now());
        }

        return $this->expires_at->diffInDays(now());
    }

    public function getValidityPeriodDays(): int
    {
        if (!$this->issued_at || !$this->expires_at) {
            return 0;
        }

        return $this->issued_at->diffInDays($this->expires_at);
    }

    // Status Calculation
    public function getStatus(): string
    {
        if (!$this->is_valid) {
            return $this->isExpired() ? 'expired' : 'invalid';
        }

        if ($this->isExpiringSoon(7)) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    // Query Scopes
    public function scopeValid($query)
    {
        return $query->where('is_valid', true)
                    ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeExpiringSoon($query, int $days = 14)
    {
        return $query->where('is_valid', true)
                    ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function scopeForWebsite($query, Website $website)
    {
        return $query->where('website_id', $website->id);
    }

    // Display Methods
    public function getFormattedIssuer(): string
    {
        if (!$this->issuer) {
            return 'Unknown Issuer';
        }

        // Clean up common issuer names
        $cleanIssuer = str_replace(['Inc.', 'Inc', 'LLC', 'Ltd.', 'Ltd'], '', $this->issuer);
        return trim($cleanIssuer);
    }

    public function getExpiryStatus(): array
    {
        $daysUntilExpiry = $this->getDaysUntilExpiry();

        if ($daysUntilExpiry < 0) {
            return [
                'status' => 'expired',
                'color' => 'red',
                'message' => 'Expired ' . abs($daysUntilExpiry) . ' days ago'
            ];
        }

        if ($daysUntilExpiry <= 7) {
            return [
                'status' => 'critical',
                'color' => 'red',
                'message' => 'Expires in ' . $daysUntilExpiry . ' days'
            ];
        }

        if ($daysUntilExpiry <= 14) {
            return [
                'status' => 'warning',
                'color' => 'yellow',
                'message' => 'Expires in ' . $daysUntilExpiry . ' days'
            ];
        }

        return [
            'status' => 'good',
            'color' => 'green',
            'message' => 'Expires in ' . $daysUntilExpiry . ' days'
        ];
    }
}
```

### **SslCheck Model** (Check results and status)
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslCheck extends Model
{
    use HasFactory;

    // Status Constants (proven from old_docs)
    const STATUS_VALID = 'valid';
    const STATUS_EXPIRING_SOON = 'expiring_soon';
    const STATUS_EXPIRED = 'expired';
    const STATUS_INVALID = 'invalid';
    const STATUS_ERROR = 'error';
    const STATUS_PENDING = 'pending';

    protected $fillable = [
        'website_id', 'status', 'checked_at', 'expires_at',
        'days_until_expiry', 'error_message', 'response_time',
        'certificate_changed', 'issuer'
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'expires_at' => 'datetime',
            'days_until_expiry' => 'integer',
            'response_time' => 'integer',
            'certificate_changed' => 'boolean',
        ];
    }

    // Relationships
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    // Business Logic
    public function isSuccessful(): bool
    {
        return in_array($this->status, [self::STATUS_VALID, self::STATUS_EXPIRING_SOON]);
    }

    public function isFailed(): bool
    {
        return in_array($this->status, [self::STATUS_ERROR, self::STATUS_INVALID, self::STATUS_EXPIRED]);
    }

    public function requiresAction(): bool
    {
        return in_array($this->status, [self::STATUS_EXPIRING_SOON, self::STATUS_EXPIRED, self::STATUS_ERROR]);
    }

    // Status Display
    public function getStatusDisplay(): array
    {
        $statusConfig = [
            self::STATUS_VALID => [
                'label' => 'Valid',
                'color' => 'green',
                'icon' => 'shield-check',
                'priority' => 1
            ],
            self::STATUS_EXPIRING_SOON => [
                'label' => 'Expiring Soon',
                'color' => 'yellow',
                'icon' => 'exclamation-triangle',
                'priority' => 2
            ],
            self::STATUS_EXPIRED => [
                'label' => 'Expired',
                'color' => 'red',
                'icon' => 'shield-exclamation',
                'priority' => 4
            ],
            self::STATUS_INVALID => [
                'label' => 'Invalid',
                'color' => 'red',
                'icon' => 'x-circle',
                'priority' => 3
            ],
            self::STATUS_ERROR => [
                'label' => 'Error',
                'color' => 'red',
                'icon' => 'exclamation-circle',
                'priority' => 5
            ],
            self::STATUS_PENDING => [
                'label' => 'Pending',
                'color' => 'gray',
                'icon' => 'clock',
                'priority' => 0
            ]
        ];

        return $statusConfig[$this->status] ?? $statusConfig[self::STATUS_PENDING];
    }

    // Query Scopes
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_VALID);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_ERROR, self::STATUS_INVALID, self::STATUS_EXPIRED]);
    }

    public function scopeExpiring($query)
    {
        return $query->where('status', self::STATUS_EXPIRING_SOON);
    }

    public function scopeForWebsite($query, Website $website)
    {
        return $query->where('website_id', $website->id);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('checked_at', '>', now()->subHours($hours));
    }

    public function scopeCritical($query)
    {
        return $query->whereIn('status', [self::STATUS_EXPIRED, self::STATUS_ERROR]);
    }

    // Status Analysis
    public static function getStatusPriority(string $status): int
    {
        $priorities = [
            self::STATUS_PENDING => 0,
            self::STATUS_VALID => 1,
            self::STATUS_EXPIRING_SOON => 2,
            self::STATUS_INVALID => 3,
            self::STATUS_EXPIRED => 4,
            self::STATUS_ERROR => 5,
        ];

        return $priorities[$status] ?? 0;
    }

    public static function getAllStatuses(): array
    {
        return [
            self::STATUS_VALID,
            self::STATUS_EXPIRING_SOON,
            self::STATUS_EXPIRED,
            self::STATUS_INVALID,
            self::STATUS_ERROR,
            self::STATUS_PENDING,
        ];
    }
}
```

---

## 🔧 Service Layer Specifications

### **SslCertificateChecker Service** (Core SSL monitoring)
```php
<?php

namespace App\Services;

use App\Models\Website;
use App\Models\SslCheck;
use App\Models\SslCertificate;
use Carbon\Carbon;
use Exception;
use Spatie\SslCertificate\SslCertificate as SpatieSslCertificate;

class SslCertificateChecker
{
    public function __construct(
        private SslStatusCalculator $statusCalculator
    ) {}

    /**
     * Check SSL certificate for a website
     */
    public function checkCertificate(Website $website, int $timeout = 30): array
    {
        $startTime = microtime(true);

        try {
            $domain = $website->getDomain();
            $certificate = SpatieSslCertificate::createForHostName($domain, $timeout);

            $result = $this->parseCertificateData($certificate);
            $result['response_time'] = round((microtime(true) - $startTime) * 1000);

            return $result;
        } catch (Exception $e) {
            return [
                'status' => SslCheck::STATUS_ERROR,
                'error_message' => $this->sanitizeErrorMessage($e->getMessage()),
                'is_valid' => false,
                'response_time' => round((microtime(true) - $startTime) * 1000),
                'expires_at' => null,
                'days_until_expiry' => null,
                'issuer' => null,
                'subject' => null,
                'serial_number' => null,
                'signature_algorithm' => null,
            ];
        }
    }

    /**
     * Parse certificate data from Spatie SSL Certificate
     */
    public function parseCertificateData(SpatieSslCertificate $certificate): array
    {
        $certificateFields = $certificate->getRawCertificateFields();
        $expiresAt = Carbon::createFromTimestamp($certificateFields['validTo_time_t']);
        $issuedAt = Carbon::createFromTimestamp($certificateFields['validFrom_time_t']);

        $daysUntilExpiry = $certificate->daysUntilExpirationDate();
        $isValid = $certificate->isValid();

        $status = $this->statusCalculator->calculateStatus($isValid, $daysUntilExpiry);

        return [
            'status' => $status,
            'issuer' => $certificate->getIssuer(),
            'subject' => $certificate->getDomain(),
            'serial_number' => $certificate->getSerialNumber(),
            'signature_algorithm' => $certificate->getSignatureAlgorithm(),
            'expires_at' => $expiresAt,
            'issued_at' => $issuedAt,
            'days_until_expiry' => $daysUntilExpiry,
            'is_valid' => $isValid,
            'is_self_signed' => $this->isSelfSigned($certificate),
            'chain_length' => $this->getChainLength($certificate),
            'key_length' => $this->getKeyLength($certificate),
            'error_message' => null,
        ];
    }

    /**
     * Check certificate and store results in database
     */
    public function checkAndStoreCertificate(Website $website): SslCheck
    {
        $result = $this->checkCertificate($website);

        // Store SSL certificate data if successful
        if ($result['status'] !== SslCheck::STATUS_ERROR && $result['expires_at']) {
            $this->storeCertificate($website, $result);
        }

        // Create SSL check record
        $sslCheck = SslCheck::create([
            'website_id' => $website->id,
            'status' => $result['status'],
            'checked_at' => now(),
            'expires_at' => $result['expires_at'],
            'days_until_expiry' => $result['days_until_expiry'],
            'error_message' => $result['error_message'],
            'response_time' => $result['response_time'],
            'issuer' => $result['issuer'],
            'certificate_changed' => $this->detectCertificateChange($website, $result),
        ]);

        // Update website last checked timestamp
        $website->update([
            'last_checked_at' => now(),
            'next_check_at' => now()->addMinutes($website->check_interval),
        ]);

        return $sslCheck;
    }

    /**
     * Store SSL certificate data
     */
    private function storeCertificate(Website $website, array $certificateData): SslCertificate
    {
        return SslCertificate::create([
            'website_id' => $website->id,
            'issuer' => $certificateData['issuer'],
            'subject' => $certificateData['subject'],
            'serial_number' => $certificateData['serial_number'],
            'signature_algorithm' => $certificateData['signature_algorithm'],
            'expires_at' => $certificateData['expires_at'],
            'issued_at' => $certificateData['issued_at'],
            'is_valid' => $certificateData['is_valid'],
            'is_self_signed' => $certificateData['is_self_signed'],
            'chain_length' => $certificateData['chain_length'],
            'key_length' => $certificateData['key_length'],
        ]);
    }

    /**
     * Detect if certificate has changed since last check
     */
    private function detectCertificateChange(Website $website, array $newCertData): bool
    {
        $lastCertificate = $website->sslCertificates()->latest()->first();

        if (!$lastCertificate) {
            return false; // First certificate
        }

        return $lastCertificate->serial_number !== $newCertData['serial_number'];
    }

    /**
     * Sanitize error messages for user display
     */
    private function sanitizeErrorMessage(string $message): string
    {
        // Common error message simplifications
        $replacements = [
            'cURL error 28: Connection timed out' => 'Connection timeout - website may be unreachable',
            'cURL error 7: Failed to connect to' => 'Connection failed - check if website is online',
            'cURL error 6: Could not resolve host' => 'Domain name could not be resolved',
            'SSL certificate problem: unable to get local issuer certificate' => 'SSL certificate validation failed',
        ];

        foreach ($replacements as $pattern => $replacement) {
            if (str_contains($message, $pattern)) {
                return $replacement;
            }
        }

        return $message;
    }

    /**
     * Check if certificate is self-signed
     */
    private function isSelfSigned(SpatieSslCertificate $certificate): bool
    {
        return $certificate->getIssuer() === $certificate->getDomain();
    }

    /**
     * Get certificate chain length
     */
    private function getChainLength(SpatieSslCertificate $certificate): int
    {
        // This would require additional analysis of the certificate chain
        // For now, return a default value
        return 1;
    }

    /**
     * Get public key length
     */
    private function getKeyLength(SpatieSslCertificate $certificate): ?int
    {
        // This would require parsing the certificate's public key
        // For now, return null - can be enhanced later
        return null;
    }
}
```

### **SslStatusCalculator Service** (Status determination)
```php
<?php

namespace App\Services;

use App\Models\SslCheck;

class SslStatusCalculator
{
    /**
     * Calculate SSL status based on validity and expiry
     */
    public function calculateStatus(bool $isValid, int $daysUntilExpiry): string
    {
        if (!$isValid) {
            return $daysUntilExpiry < 0 ? SslCheck::STATUS_EXPIRED : SslCheck::STATUS_INVALID;
        }

        if ($daysUntilExpiry <= 0) {
            return SslCheck::STATUS_EXPIRED;
        }

        if ($daysUntilExpiry <= 14) {
            return SslCheck::STATUS_EXPIRING_SOON;
        }

        return SslCheck::STATUS_VALID;
    }

    /**
     * Get color for status display
     */
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            SslCheck::STATUS_VALID => 'green',
            SslCheck::STATUS_EXPIRING_SOON => 'yellow',
            SslCheck::STATUS_EXPIRED => 'red',
            SslCheck::STATUS_INVALID => 'red',
            SslCheck::STATUS_ERROR => 'red',
            SslCheck::STATUS_PENDING => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get icon for status display
     */
    public function getStatusIcon(string $status): string
    {
        return match ($status) {
            SslCheck::STATUS_VALID => 'shield-check',
            SslCheck::STATUS_EXPIRING_SOON => 'exclamation-triangle',
            SslCheck::STATUS_EXPIRED => 'shield-exclamation',
            SslCheck::STATUS_INVALID => 'x-circle',
            SslCheck::STATUS_ERROR => 'exclamation-circle',
            SslCheck::STATUS_PENDING => 'clock',
            default => 'question-mark-circle',
        };
    }

    /**
     * Calculate priority for sorting (higher = more urgent)
     */
    public function calculatePriority(string $status): int
    {
        return match ($status) {
            SslCheck::STATUS_ERROR => 5,
            SslCheck::STATUS_EXPIRED => 4,
            SslCheck::STATUS_INVALID => 3,
            SslCheck::STATUS_EXPIRING_SOON => 2,
            SslCheck::STATUS_VALID => 1,
            SslCheck::STATUS_PENDING => 0,
            default => 0,
        };
    }

    /**
     * Get percentage distribution of statuses
     */
    public function calculateStatusPercentages(array $statusCounts): array
    {
        $total = array_sum($statusCounts);

        if ($total === 0) {
            return array_fill_keys(array_keys($statusCounts), 0);
        }

        $percentages = [];
        foreach ($statusCounts as $status => $count) {
            $percentages[$status] = round(($count / $total) * 100, 1);
        }

        return $percentages;
    }
}
```

---

## 🎮 Controller Specifications (Inertia.js API)

### **DashboardController** - SSL status overview
```php
<?php

namespace App\Http\Controllers;

use App\Models\SslCheck;
use App\Services\SslStatusCalculator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private SslStatusCalculator $statusCalculator
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get SSL status counts
        $statusCounts = $user->getSslStatusCounts();
        $statusPercentages = $this->statusCalculator->calculateStatusPercentages($statusCounts);

        // Get critical issues (expired or error status)
        $criticalIssues = SslCheck::whereHas('website', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->critical()
        ->with('website')
        ->latest('checked_at')
        ->limit(5)
        ->get();

        // Get recent SSL checks
        $recentChecks = SslCheck::whereHas('website', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with('website')
        ->latest('checked_at')
        ->limit(10)
        ->get();

        return Inertia::render('Dashboard/Index', [
            'statusCounts' => $statusCounts,
            'statusPercentages' => $statusPercentages,
            'criticalIssues' => $criticalIssues,
            'recentChecks' => $recentChecks,
        ]);
    }

    public function refresh(Request $request): Response
    {
        // Force refresh of dashboard data
        return $this->index($request);
    }
}
```

### **WebsiteController** - Website management
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWebsiteRequest;
use App\Http\Requests\UpdateWebsiteRequest;
use App\Jobs\CheckSslCertificateJob;
use App\Models\Website;
use App\Services\SslCertificateChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    public function __construct(
        private SslCertificateChecker $sslChecker
    ) {}

    public function index(Request $request): Response
    {
        $websites = $request->user()
            ->websites()
            ->withLatestSslCheck()
            ->latest()
            ->get();

        return Inertia::render('Websites/Index', [
            'websites' => $websites,
        ]);
    }

    public function store(StoreWebsiteRequest $request): RedirectResponse
    {
        $website = $request->user()->websites()->create($request->validated());

        // Queue SSL check for new website
        CheckSslCertificateJob::dispatch($website);

        return redirect()->route('websites.index')
            ->with('success', 'Website added successfully. SSL check is in progress.');
    }

    public function show(Request $request, Website $website): Response
    {
        $this->authorize('view', $website);

        $website->load([
            'sslCertificates' => fn($query) => $query->latest()->limit(5),
            'sslChecks' => fn($query) => $query->latest()->limit(20)
        ]);

        return Inertia::render('Websites/Show', [
            'website' => $website,
        ]);
    }

    public function update(UpdateWebsiteRequest $request, Website $website): RedirectResponse
    {
        $this->authorize('update', $website);

        $website->update($request->validated());

        return redirect()->route('websites.index')
            ->with('success', 'Website updated successfully.');
    }

    public function destroy(Request $request, Website $website): RedirectResponse
    {
        $this->authorize('delete', $website);

        $website->delete();

        return redirect()->route('websites.index')
            ->with('success', 'Website deleted successfully.');
    }

    /**
     * Preview SSL certificate for a URL (for add/edit forms)
     */
    public function previewSsl(Request $request): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'url', 'max:255'],
        ]);

        // Create temporary website model for checking
        $tempWebsite = Website::make(['url' => $request->url]);

        // Quick SSL check with short timeout for preview
        $result = $this->sslChecker->checkCertificate($tempWebsite, 10);

        return response()->json($result);
    }

    /**
     * Manually trigger SSL check for a website
     */
    public function checkSsl(Request $request, Website $website): RedirectResponse
    {
        $this->authorize('update', $website);

        CheckSslCertificateJob::dispatch($website);

        return redirect()->back()
            ->with('success', 'SSL check queued for ' . $website->name);
    }

    /**
     * Bulk actions on websites
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => ['required', 'in:check,delete,activate,deactivate'],
            'website_ids' => ['required', 'array', 'min:1'],
            'website_ids.*' => ['integer', 'exists:websites,id'],
        ]);

        $websites = $request->user()
            ->websites()
            ->whereIn('id', $request->website_ids)
            ->get();

        $count = $websites->count();

        switch ($request->action) {
            case 'check':
                foreach ($websites as $website) {
                    CheckSslCertificateJob::dispatch($website);
                }
                $message = "SSL checks queued for {$count} websites.";
                break;

            case 'delete':
                $websites->each->delete();
                $message = "{$count} websites deleted successfully.";
                break;

            case 'activate':
                $websites->each->update(['is_active' => true]);
                $message = "{$count} websites activated.";
                break;

            case 'deactivate':
                $websites->each->update(['is_active' => false]);
                $message = "{$count} websites deactivated.";
                break;
        }

        return redirect()->route('websites.index')
            ->with('success', $message);
    }
}
```

---

## 📚 Related Documentation

- **[SSL_MONITOR_V4_IMPLEMENTATION_PLAN.md](SSL_MONITOR_V4_IMPLEMENTATION_PLAN.md)** - Complete 8-week development plan
- **[MIGRATION_FROM_V3.md](MIGRATION_FROM_V3.md)** - Component reuse and migration strategy
- **[V4_DEVELOPMENT_WORKFLOW.md](V4_DEVELOPMENT_WORKFLOW.md)** - TDD process and VRISTO integration

**This technical specification ensures SSL Monitor v4 maintains the proven reliability of v3 while providing a modern, scalable architecture for enhanced user experience.**