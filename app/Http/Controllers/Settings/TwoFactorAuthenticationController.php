<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorAuthenticationController extends Controller
{
    public function __construct(
        protected TwoFactorAuthService $twoFactorService
    ) {}

    /**
     * Show the two-factor authentication settings page
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/TwoFactor', [
            'twoFactorEnabled' => $this->twoFactorService->isEnabled($user),
            'recoveryCodes' => $this->twoFactorService->getRecoveryCodesCount($user),
            'qrCodeSvg' => $user->two_factor_secret && !$this->twoFactorService->isEnabled($user)
                ? $this->generateQrCodeSvg($user)
                : null,
        ]);
    }

    /**
     * Generate a new 2FA secret key
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Generate new secret key
        $secretKey = $this->twoFactorService->generateSecretKey();

        $user->update([
            'two_factor_secret' => $secretKey,
            'two_factor_confirmed_at' => null, // Reset confirmation
            'two_factor_recovery_codes' => null, // Reset recovery codes
        ]);

        return back()->with('status', '2FA setup initiated. Please scan the QR code.');
    }

    /**
     * Confirm and enable 2FA
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if (!$user->two_factor_secret) {
            throw ValidationException::withMessages([
                'code' => ['Two-factor authentication is not set up.'],
            ]);
        }

        if ($this->twoFactorService->isEnabled($user)) {
            throw ValidationException::withMessages([
                'code' => ['Two-factor authentication is already enabled.'],
            ]);
        }

        if (!$this->twoFactorService->enableTwoFactorAuth($user, $request->code)) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code was invalid.'],
            ]);
        }

        return back()->with('status', 'Two-factor authentication has been enabled.');
    }

    /**
     * Disable 2FA
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $this->twoFactorService->disableTwoFactorAuth($user);

        return back()->with('status', 'Two-factor authentication has been disabled.');
    }

    /**
     * Get recovery codes
     */
    public function recoveryCodes(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$this->twoFactorService->isEnabled($user)) {
            return response()->json(['error' => 'Two-factor authentication is not enabled.'], 400);
        }

        // Generate new recovery codes
        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();
        $hashedCodes = $this->twoFactorService->hashRecoveryCodes($recoveryCodes);

        $user->update([
            'two_factor_recovery_codes' => json_encode($hashedCodes),
        ]);

        return response()->json([
            'recovery_codes' => $recoveryCodes->toArray(),
        ]);
    }

    /**
     * Generate QR code SVG for setup
     */
    protected function generateQrCodeSvg($user): string
    {
        $companyName = config('app.name', 'SSL Monitor');

        return $this->twoFactorService->getQRCodeImage(
            $companyName,
            $user->email,
            $user->two_factor_secret
        );
    }
}