<?php

namespace App\Services;

use App\Models\SslCertificate;
use Carbon\Carbon;

class SslStatusCalculator
{
    public const STATUS_VALID = 'valid';

    public const STATUS_EXPIRING_SOON = 'expiring_soon';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_ERROR = 'error';

    private const DEFAULT_EXPIRING_SOON_THRESHOLD = 14;

    private const STATUS_PRIORITIES = [
        self::STATUS_ERROR => 1,
        self::STATUS_EXPIRED => 2,
        self::STATUS_EXPIRING_SOON => 3,
        self::STATUS_INVALID => 4,
        self::STATUS_VALID => 5,
    ];

    public function calculateStatus(
        Carbon $expirationDate,
        bool $isValid,
        int $daysUntilExpiry,
        int $expiringSoonThreshold = self::DEFAULT_EXPIRING_SOON_THRESHOLD
    ): string {
        if ($expirationDate->isPast()) {
            return self::STATUS_EXPIRED;
        }

        if (! $isValid) {
            return self::STATUS_INVALID;
        }

        if ($daysUntilExpiry <= $expiringSoonThreshold) {
            return self::STATUS_EXPIRING_SOON;
        }

        return self::STATUS_VALID;
    }

    public function calculateDaysUntilExpiry(Carbon $expirationDate, ?Carbon $fromDate = null): int
    {
        $fromDate = $fromDate ?? now();

        return (int) $fromDate->diffInDays($expirationDate, false);
    }

    public function calculateStatusFromCertificate(
        SslCertificate $certificate,
        int $expiringSoonThreshold = self::DEFAULT_EXPIRING_SOON_THRESHOLD
    ): string {
        $daysUntilExpiry = $this->calculateDaysUntilExpiry($certificate->expires_at);

        return $this->calculateStatus(
            $certificate->expires_at,
            $certificate->is_valid,
            $daysUntilExpiry,
            $expiringSoonThreshold
        );
    }

    public function isValidStatus(?string $status): bool
    {
        if ($status === null) {
            return false;
        }

        return in_array($status, [
            self::STATUS_VALID,
            self::STATUS_EXPIRING_SOON,
            self::STATUS_EXPIRED,
            self::STATUS_INVALID,
            self::STATUS_ERROR,
        ]);
    }

    public function getStatusPriority(string $status): int
    {
        return self::STATUS_PRIORITIES[$status] ?? 999;
    }

    public function getAllStatuses(): array
    {
        return [
            self::STATUS_VALID,
            self::STATUS_EXPIRING_SOON,
            self::STATUS_EXPIRED,
            self::STATUS_INVALID,
            self::STATUS_ERROR,
        ];
    }

    public function getStatusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_VALID => 'Valid',
            self::STATUS_EXPIRING_SOON => 'Expiring Soon',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_INVALID => 'Invalid',
            self::STATUS_ERROR => 'Error',
            default => 'Unknown',
        };
    }

    public function getStatusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_VALID => 'green',
            self::STATUS_EXPIRING_SOON => 'yellow',
            self::STATUS_EXPIRED => 'red',
            self::STATUS_INVALID => 'red',
            self::STATUS_ERROR => 'gray',
            default => 'gray',
        };
    }
}