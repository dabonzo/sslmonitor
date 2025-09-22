<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TeamInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'email',
        'role',
        'token',
        'expires_at',
        'accepted_at',
        'invited_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    /**
     * Generate a unique invitation token
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /**
     * Create a new invitation
     */
    public static function createInvitation(Team $team, string $email, string $role, User $invitedBy): self
    {
        return static::create([
            'team_id' => $team->id,
            'email' => $email,
            'role' => $role,
            'token' => static::generateToken(),
            'expires_at' => now()->addDays(7), // Expires in 7 days
            'invited_by_user_id' => $invitedBy->id,
        ]);
    }

    /**
     * Check if invitation is valid (not expired and not accepted)
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isAccepted();
    }

    /**
     * Check if invitation is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if invitation has been accepted
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Accept the invitation
     */
    public function accept(User $user): TeamMember
    {
        if (!$this->isValid()) {
            throw new \Exception('Invitation is no longer valid');
        }

        // Check if user email matches invitation email
        if ($user->email !== $this->email) {
            throw new \Exception('Email does not match invitation');
        }

        // Create team membership
        $teamMember = TeamMember::create([
            'team_id' => $this->team_id,
            'user_id' => $user->id,
            'role' => $this->role,
            'joined_at' => now(),
            'invited_by_user_id' => $this->invited_by_user_id,
        ]);

        // Mark invitation as accepted
        $this->update(['accepted_at' => now()]);

        return $teamMember;
    }

    /**
     * Get invitation by token
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('token', $token)->first();
    }

    /**
     * Resend invitation (extend expiry)
     */
    public function resend(): void
    {
        $this->update([
            'expires_at' => now()->addDays(7),
            'token' => static::generateToken(), // New token for security
        ]);
    }

    /**
     * Check if user already has pending invitation for this team
     */
    public static function hasPendingInvitation(Team $team, string $email): bool
    {
        return static::where('team_id', $team->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Get invitation URL
     */
    public function getInvitationUrl(): string
    {
        return route('team.invitations.accept', ['token' => $this->token]);
    }

    /**
     * Scope for pending invitations
     */
    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    /**
     * Scope for expired invitations
     */
    public function scopeExpired($query)
    {
        return $query->whereNull('accepted_at')->where('expires_at', '<=', now());
    }
}
