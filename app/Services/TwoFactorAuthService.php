<?php

namespace App\Services;

use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Models\User;
use Illuminate\Support\Collection;

class TwoFactorAuthService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a secret key for 2FA
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Generate QR Code URL for setup
     */
    public function getQRCodeUrl(string $companyName, string $email, string $secretKey): string
    {
        return $this->google2fa->getQRCodeUrl(
            $companyName,
            $email,
            $secretKey
        );
    }

    /**
     * Generate QR Code as base64 image
     */
    public function getQRCodeImage(string $companyName, string $email, string $secretKey): string
    {
        $qrCodeUrl = $this->getQRCodeUrl($companyName, $email, $secretKey);

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(200),
                new ImagickImageBackEnd()
            )
        );

        return base64_encode($writer->writeString($qrCodeUrl));
    }

    /**
     * Verify a 2FA code
     */
    public function verifyKey(string $secretKey, string $code, int $window = 2): bool
    {
        return $this->google2fa->verifyKey($secretKey, $code, $window);
    }

    /**
     * Verify a 2FA code and prevent replay attacks
     */
    public function verifyKeyNewer(string $secretKey, string $code, ?int $oldTimestamp = null): int|false
    {
        return $this->google2fa->verifyKeyNewer($secretKey, $code, $oldTimestamp);
    }

    /**
     * Generate recovery codes
     */
    public function generateRecoveryCodes(int $count = 8): Collection
    {
        $codes = collect();

        for ($i = 0; $i < $count; $i++) {
            $codes->push(strtoupper(substr(md5(random_bytes(32)), 0, 8)));
        }

        return $codes;
    }

    /**
     * Hash recovery codes for storage
     */
    public function hashRecoveryCodes(Collection $codes): array
    {
        return $codes->map(fn($code) => bcrypt($code))->toArray();
    }

    /**
     * Enable 2FA for a user
     */
    public function enableTwoFactorAuth(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        // Verify the code before enabling
        if (!$this->verifyKey($user->two_factor_secret, $code)) {
            return false;
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();
        $hashedCodes = $this->hashRecoveryCodes($recoveryCodes);

        // Enable 2FA
        $user->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => json_encode($hashedCodes),
        ]);

        return true;
    }

    /**
     * Disable 2FA for a user
     */
    public function disableTwoFactorAuth(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Check if user has 2FA enabled
     */
    public function isEnabled(User $user): bool
    {
        return !is_null($user->two_factor_confirmed_at) && !is_null($user->two_factor_secret);
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (!$user->two_factor_recovery_codes) {
            return false;
        }

        $recoveryCodes = json_decode($user->two_factor_recovery_codes, true);

        if (!is_array($recoveryCodes)) {
            return false;
        }

        $code = strtoupper($code);

        foreach ($recoveryCodes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                // Remove used recovery code
                unset($recoveryCodes[$index]);
                $user->update([
                    'two_factor_recovery_codes' => json_encode(array_values($recoveryCodes))
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Get unused recovery codes (for display)
     */
    public function getRecoveryCodesCount(User $user): int
    {
        if (!$user->two_factor_recovery_codes) {
            return 0;
        }

        $codes = json_decode($user->two_factor_recovery_codes, true);
        return is_array($codes) ? count($codes) : 0;
    }
}