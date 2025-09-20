<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptInvitationRequest;
use App\Services\TeamInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function __construct(
        private TeamInvitationService $invitationService
    ) {
        //
    }

    /**
     * Show the invitation acceptance page
     */
    public function show(string $token): View|RedirectResponse
    {
        $invitation = $this->invitationService->getInvitationByToken($token);

        if (! $invitation) {
            return redirect()->route('login')->with('error', 'Invalid invitation link.');
        }

        if (! $invitation->isPending()) {
            $message = $invitation->isAccepted()
                ? 'This invitation has already been accepted.'
                : 'This invitation has expired or is no longer valid.';

            return redirect()->route('login')->with('error', $message);
        }

        // If user is already logged in and is already a team member, redirect to dashboard
        if (Auth::check() && $invitation->team->hasMember(Auth::user())) {
            return redirect()->route('dashboard')->with('success', 'You are already a member of this team.');
        }

        return view('invitations.show', compact('invitation'));
    }

    /**
     * Accept the invitation and create/update user account
     */
    public function accept(AcceptInvitationRequest $request, string $token): RedirectResponse
    {
        try {
            $invitation = $this->invitationService->getInvitationByToken($token);

            if (! $invitation || ! $invitation->isPending()) {
                return redirect()->route('login')->with('error', 'Invalid or expired invitation.');
            }

            $userData = $request->validated();
            $user = $this->invitationService->acceptInvitation($token, $userData);

            // Log the user in
            Auth::login($user);

            return redirect()->route('dashboard')->with('success', "Welcome to {$invitation->team->name}! Your account has been set up successfully.");

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
