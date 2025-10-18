<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user() ? array_merge($request->user()->toArray(), [
                    'primary_role' => $this->getUserPrimaryRole($request->user()),
                ]) : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
                'message' => $request->session()->get('message'),
            ],
            'config' => [
                'debug' => [
                    'menu_enabled' => config('debug_menu.menu_enabled', false),
                    'menu_users' => config('debug_menu.menu_users', ''),
                    'menu_roles' => config('debug_menu.menu_roles', 'OWNER,ADMIN'),
                    'menu_audit' => config('debug_menu.menu_audit', true),
                    'overrides_expire_hours' => config('debug_menu.overrides_expire_hours', 24),
                ],
            ],
        ];
    }

    /**
     * Get the user's primary role from their team memberships
     */
    private function getUserPrimaryRole($user): ?string
    {
        if (! $user) {
            return null;
        }

        // Get the user's highest priority role from all team memberships
        $teamMemberships = $user->teams()->withPivot('role')->get();

        if ($teamMemberships->isEmpty()) {
            // If no team memberships, check if they're a super admin or return default
            return 'User'; // Default role for users without teams
        }

        // Define role priority hierarchy (ADMIN and MANAGER merged)
        $rolePriority = [
            'OWNER' => 3,
            'ADMIN' => 2,  // Includes former MANAGER role
            'VIEWER' => 1,
        ];

        // Find the highest priority role
        $highestRole = 'VIEWER';
        $highestPriority = 0;

        foreach ($teamMemberships as $team) {
            $role = $team->pivot->role;
            $priority = $rolePriority[$role] ?? 0;

            if ($priority > $highestPriority) {
                $highestPriority = $priority;
                $highestRole = $role;
            }
        }

        return $highestRole;
    }
}
