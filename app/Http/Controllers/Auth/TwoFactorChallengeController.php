<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        protected TwoFactorAuthService $twoFactorService
    ) {}

    /**
     * Show the two-factor authentication challenge form
     */
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    /**
     * Verify the two-factor authentication code
     */
    public function store(Request $request): RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => 'required|string',
            'recovery_code' => 'nullable|string',
        ]);

        $user = \App\Models\User::findOrFail(
            $request->session()->pull('login.id')
        );

        $authenticated = false;

        // Try recovery code first if provided
        if ($request->filled('recovery_code')) {
            $authenticated = $this->twoFactorService->verifyRecoveryCode(
                $user,
                $request->recovery_code
            );
        }
        // Otherwise try regular 2FA code
        elseif ($request->filled('code')) {
            $authenticated = $this->twoFactorService->verifyKey(
                $user->two_factor_secret,
                $request->code
            );
        }

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code was invalid.'],
            ]);
        }

        // Remember device if requested
        $remember = $request->session()->pull('login.remember', false);

        Auth::login($user, $remember);

        $request->session()->regenerate();

        return redirect()->intended('/');
    }
}
