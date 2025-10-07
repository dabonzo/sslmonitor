<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function websites(): HasMany
    {
        return $this->hasMany(\App\Models\Website::class);
    }

    public function personalWebsites(): HasMany
    {
        return $this->hasMany(\App\Models\Website::class)->whereNull('team_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot(['role', 'joined_at', 'invited_by_user_id'])
            ->withTimestamps();
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'created_by_user_id');
    }

    public function teamInvitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class, 'invited_by_user_id');
    }

    public function alertConfigurations(): HasMany
    {
        return $this->hasMany(AlertConfiguration::class);
    }

    /**
     * Check if user is member of a specific team
     */
    public function isMemberOf(Team $team): bool
    {
        return TeamMember::where('team_id', $team->id)->where('user_id', $this->id)->exists();
    }

    /**
     * Get user's role in a specific team
     */
    public function getRoleInTeam(Team $team): ?string
    {
        $membership = TeamMember::where('team_id', $team->id)->where('user_id', $this->id)->first();
        return $membership?->role;
    }

    /**
     * Check if user can manage a specific team
     */
    public function canManageTeam(Team $team): bool
    {
        $role = $this->getRoleInTeam($team);
        return $role === TeamMember::ROLE_OWNER;
    }
}
