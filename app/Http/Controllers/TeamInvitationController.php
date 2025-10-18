<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class TeamInvitationController extends Controller
{
    /**
     * Show the invitation acceptance page
     */
    public function show(string $token): Response|RedirectResponse
    {
        $invitation = TeamInvitation::findByToken($token);

        if (! $invitation || ! $invitation->isValid()) {
            return redirect('/')->with('error', 'This invitation is invalid or has expired.');
        }

        // Load team and inviter information
        $invitation->load(['team', 'invitedBy']);

        return Inertia::render('auth/AcceptInvitation', [
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'expires_at' => $invitation->expires_at,
                'team' => [
                    'id' => $invitation->team->id,
                    'name' => $invitation->team->name,
                    'description' => $invitation->team->description,
                ],
                'invited_by' => $invitation->invitedBy->name,
            ],
            'existing_user' => User::where('email', $invitation->email)->exists(),
        ]);
    }

    /**
     * Accept the invitation for existing users
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = TeamInvitation::findByToken($token);

        if (! $invitation || ! $invitation->isValid()) {
            return redirect('/')->with('error', 'This invitation is invalid or has expired.');
        }

        $user = Auth::user();

        // Check if user email matches invitation email
        if (! $user || $user->email !== $invitation->email) {
            return redirect()->back()->with('error', 'You must be logged in with the invited email address.');
        }

        try {
            DB::transaction(function () use ($invitation, $user) {
                $teamMember = $invitation->accept($user);
            });

            return redirect('/settings/team')
                ->with('success', "You've successfully joined the {$invitation->team->name} team!");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Accept invitation and create new user account
     */
    public function acceptWithRegistration(Request $request, string $token): RedirectResponse
    {
        $invitation = TeamInvitation::findByToken($token);

        if (! $invitation || ! $invitation->isValid()) {
            return redirect('/')->with('error', 'This invitation is invalid or has expired.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Check if user already exists
        if (User::where('email', $invitation->email)->exists()) {
            return redirect()->back()->with('error', 'An account with this email already exists. Please log in instead.');
        }

        try {
            DB::transaction(function () use ($request, $invitation) {
                // Create new user
                $user = User::create([
                    'name' => $request->name,
                    'email' => $invitation->email,
                    'password' => Hash::make($request->password),
                    'email_verified_at' => now(), // Auto-verify since they have a valid invitation
                ]);

                // Accept the invitation
                $teamMember = $invitation->accept($user);

                // Log the user in
                Auth::login($user);
            });

            return redirect('/dashboard')
                ->with('success', "Welcome! You've successfully joined the {$invitation->team->name} team.");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create account: '.$e->getMessage());
        }
    }

    /**
     * Cancel/decline an invitation
     */
    public function decline(string $token): RedirectResponse
    {
        $invitation = TeamInvitation::findByToken($token);

        if (! $invitation) {
            return redirect('/')->with('error', 'Invitation not found.');
        }

        $teamName = $invitation->team->name;
        $invitation->delete();

        return redirect('/')
            ->with('success', "You've declined the invitation to join {$teamName}.");
    }
}
