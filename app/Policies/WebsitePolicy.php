<?php

namespace App\Policies;

use App\Models\TeamMember;
use App\Models\User;
use App\Models\Website;
use Illuminate\Auth\Access\Response;

class WebsitePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Website $website): bool
    {
        // Allow if user owns the website directly
        if ($user->id === $website->user_id) {
            return true;
        }

        // Allow if website belongs to a team and user is a member (any role can view)
        if ($website->team_id) {
            return TeamMember::where('team_id', $website->team_id)
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Website $website): bool
    {
        // Allow if user owns the website directly
        if ($user->id === $website->user_id) {
            return true;
        }

        // Allow if website belongs to a team and user has management permissions
        if ($website->team_id) {
            $teamMember = TeamMember::where('team_id', $website->team_id)
                ->where('user_id', $user->id)
                ->first();

            return $teamMember && $teamMember->canManageWebsites();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Website $website): bool
    {
        // Allow if user owns the website directly
        if ($user->id === $website->user_id) {
            return true;
        }

        // Allow if website belongs to a team and user has management permissions
        if ($website->team_id) {
            $teamMember = TeamMember::where('team_id', $website->team_id)
                ->where('user_id', $user->id)
                ->first();

            return $teamMember && $teamMember->canManageWebsites();
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Website $website): bool
    {
        // Allow if user owns the website directly
        if ($user->id === $website->user_id) {
            return true;
        }

        // Allow if website belongs to a team and user has management permissions
        if ($website->team_id) {
            $teamMember = TeamMember::where('team_id', $website->team_id)
                ->where('user_id', $user->id)
                ->first();

            return $teamMember && $teamMember->canManageWebsites();
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Website $website): bool
    {
        // Allow if user owns the website directly
        if ($user->id === $website->user_id) {
            return true;
        }

        // Allow if website belongs to a team and user has management permissions
        if ($website->team_id) {
            $teamMember = TeamMember::where('team_id', $website->team_id)
                ->where('user_id', $user->id)
                ->first();

            return $teamMember && $teamMember->canManageWebsites();
        }

        return false;
    }
}
