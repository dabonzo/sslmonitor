<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the websites owned by this user
     */
    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    /**
     * Get the user's notification preference
     */
    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    /**
     * Get teams owned by this user
     */
    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    /**
     * Get teams this user is a member of
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    /**
     * Get team member records for this user
     */
    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Get all websites accessible to this user (personal + team websites)
     */
    public function accessibleWebsites(): \Illuminate\Support\Collection
    {
        // Personal websites
        $personalWebsites = $this->websites()->get();

        // Team websites
        $teamWebsites = Website::whereIn('team_id', $this->teams()->pluck('teams.id'))->get();

        return $personalWebsites->concat($teamWebsites);
    }

    /**
     * Get accessible websites as a query builder (for eager loading)
     */
    public function accessibleWebsitesQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $teamIds = $this->teams()->pluck('teams.id')->toArray();

        return Website::where(function ($query) use ($teamIds) {
            $query->where('user_id', $this->id) // Personal websites
                ->orWhereIn('team_id', $teamIds); // Team websites
        });
    }

    /**
     * Check if user has team
     */
    public function hasTeam(): bool
    {
        return $this->teams()->exists() || $this->ownedTeams()->exists();
    }

    /**
     * Get user's primary team (first team they're owner of, or first team they're in)
     */
    public function primaryTeam(): ?Team
    {
        return $this->ownedTeams()->first() ?? $this->teams()->first();
    }
}
