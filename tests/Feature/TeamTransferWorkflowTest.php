<?php

use App\Models\User;
use App\Models\Website;
use App\Models\Team;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create();

    // Add user to team with ADMIN role
    $this->team->members()->attach($this->user->id, [
        'role' => 'ADMIN',
        'joined_at' => now(),
        'invited_by_user_id' => $this->user->id,
    ]);

    $this->personalWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => null,
    ]);

    $this->teamWebsite = Website::factory()->create([
        'user_id' => $this->user->id,
        'team_id' => $this->team->id,
    ]);
});

describe('Dashboard Transfer Suggestions', function () {
    it('provides transfer suggestions for users with personal websites and teams', function () {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->has('transferSuggestions')
                 ->where('transferSuggestions.personal_websites_count', 1)
                 ->where('transferSuggestions.available_teams_count', 1)
                 ->where('transferSuggestions.should_show_suggestion', true)
                 ->has('transferSuggestions.quick_transfer_teams.0', fn ($team) =>
                     $team->where('id', $this->team->id)
                          ->where('name', $this->team->name)
                          ->etc()
                 )
        );
    });

    it('does not show suggestions when user has no personal websites', function () {
        $this->personalWebsite->update(['team_id' => $this->team->id]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->where('transferSuggestions.personal_websites_count', 0)
                 ->where('transferSuggestions.should_show_suggestion', false)
        );
    });
});

describe('Website List Enhanced UX', function () {
    it('provides available teams data for transfer operations', function () {
        $response = $this->actingAs($this->user)->get('/ssl/websites');

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->has('availableTeams.0', fn ($team) =>
                $team->where('id', $this->team->id)
                     ->where('name', $this->team->name)
                     ->has('member_count')
                     ->has('user_role')
                     ->etc()
            )
        );
    });

    it('shows team badges correctly for personal and team websites', function () {
        $response = $this->actingAs($this->user)->get('/ssl/websites');

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $websites = collect($page->toArray()['props']['websites']['data']);

            $personalWebsite = $websites->firstWhere('id', $this->personalWebsite->id);
            expect($personalWebsite)->not->toBeNull();
            expect($personalWebsite['team_badge']['type'])->toBe('personal');
            expect($personalWebsite['team_badge']['name'])->toBeNull();
            expect($personalWebsite['team_badge']['color'])->toBe('gray');

            $teamWebsite = $websites->firstWhere('id', $this->teamWebsite->id);
            expect($teamWebsite)->not->toBeNull();
            expect($teamWebsite['team_badge']['type'])->toBe('team');
            expect($teamWebsite['team_badge']['name'])->toBe($this->team->name);
            expect($teamWebsite['team_badge']['color'])->toBe('green');
        });
    });
});

describe('Bulk Transfer Operations', function () {
    it('can bulk transfer personal websites to team', function () {
        $personalWebsite2 = Website::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/ssl/websites/bulk-transfer-to-team', [
                'website_ids' => [$this->personalWebsite->id, $personalWebsite2->id],
                'team_id' => $this->team->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', "Successfully transferred 2 websites to {$this->team->name}.");

        $this->assertDatabaseHas('websites', [
            'id' => $this->personalWebsite->id,
            'team_id' => $this->team->id,
        ]);

        $this->assertDatabaseHas('websites', [
            'id' => $personalWebsite2->id,
            'team_id' => $this->team->id,
        ]);
    });

    it('can bulk transfer team websites to personal', function () {
        $teamWebsite2 = Website::factory()->create([
            'user_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/ssl/websites/bulk-transfer-to-personal', [
                'website_ids' => [$this->teamWebsite->id, $teamWebsite2->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Successfully transferred 2 websites to your personal account.');

        $this->assertDatabaseHas('websites', [
            'id' => $this->teamWebsite->id,
            'team_id' => null,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('websites', [
            'id' => $teamWebsite2->id,
            'team_id' => null,
            'user_id' => $this->user->id,
        ]);
    });

    it('validates bulk transfer to team permissions', function () {
        $otherTeam = Team::factory()->create();

        $response = $this->actingAs($this->user)
            ->post('/ssl/websites/bulk-transfer-to-team', [
                'website_ids' => [$this->personalWebsite->id],
                'team_id' => $otherTeam->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You do not have permission to transfer websites to this team.');
    });

    it('only transfers personal websites in bulk transfer to team', function () {
        $response = $this->actingAs($this->user)
            ->post('/ssl/websites/bulk-transfer-to-team', [
                'website_ids' => [$this->personalWebsite->id, $this->teamWebsite->id],
                'team_id' => $this->team->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success'); // Should have success message for 1 transferred website

        // Personal website should be transferred
        $this->assertDatabaseHas('websites', [
            'id' => $this->personalWebsite->id,
            'team_id' => $this->team->id,
        ]);

        // Team website should remain unchanged
        $this->assertDatabaseHas('websites', [
            'id' => $this->teamWebsite->id,
            'team_id' => $this->team->id,
        ]);
    });
});

describe('Single Website Transfer Enhancement', function () {
    it('provides enhanced transfer options with team details', function () {
        $response = $this->actingAs($this->user)
            ->getJson("/ssl/websites/{$this->personalWebsite->id}/transfer-options");

        $response->assertOk();
        $response->assertJsonStructure([
            'teams' => [
                '*' => ['id', 'name', 'description']
            ],
            'current_owner' => ['type', 'id', 'name']
        ]);

        $response->assertJson([
            'current_owner' => [
                'type' => 'personal',
                'id' => $this->personalWebsite->user_id,
                'name' => $this->user->name,
            ]
        ]);
    });

    it('shows correct current owner for team websites', function () {
        $response = $this->actingAs($this->user)
            ->getJson("/ssl/websites/{$this->teamWebsite->id}/transfer-options");

        $response->assertOk();
        $response->assertJson([
            'current_owner' => [
                'type' => 'team',
                'id' => $this->team->id,
                'name' => $this->team->name,
            ]
        ]);
    });
});

describe('Permission-Based Transfer Access', function () {
    it('only shows teams where user has transfer permissions', function () {
        $viewOnlyTeam = Team::factory()->create();
        $viewOnlyTeam->members()->attach($this->user->id, [
            'role' => 'MEMBER',
            'joined_at' => now(),
            'invited_by_user_id' => $this->user->id,
        ]); // No transfer permissions

        $response = $this->actingAs($this->user)->get('/ssl/websites');

        $response->assertOk();
        $response->assertInertia(function ($page) use ($viewOnlyTeam) {
            $page->has('availableTeams', 1); // Only the team with ADMIN role
            $page->where('availableTeams.0.id', $this->team->id);

            // Should not include the view-only team
            $availableTeamIds = collect($page->toArray()['props']['availableTeams'])->pluck('id')->toArray();
            expect($availableTeamIds)->not->toContain($viewOnlyTeam->id);
        });
    });

    it('includes user role information in available teams', function () {
        $ownerTeam = Team::factory()->create();
        $ownerTeam->members()->attach($this->user->id, [
            'role' => 'OWNER',
            'joined_at' => now(),
            'invited_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get('/ssl/websites');

        $response->assertOk();
        $response->assertInertia(function ($page) use ($ownerTeam) {
            $page->has('availableTeams', 2); // Both teams

            $teams = collect($page->toArray()['props']['availableTeams']);

            $adminTeam = $teams->firstWhere('id', $this->team->id);
            expect($adminTeam['user_role'])->toBe('ADMIN');

            $ownerTeamData = $teams->firstWhere('id', $ownerTeam->id);
            expect($ownerTeamData['user_role'])->toBe('OWNER');
        });
    });
});

describe('Website Filtering by Team', function () {
    it('filters personal websites correctly', function () {
        $response = $this->actingAs($this->user)->get('/ssl/websites?team=personal');

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $page->has('websites.data', 1); // Only personal website
            $page->where('websites.data.0.id', $this->personalWebsite->id);
        });
    });

    it('filters team websites correctly', function () {
        $response = $this->actingAs($this->user)->get('/ssl/websites?team=team');

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $page->has('websites.data', 1); // Only team website
            $page->where('websites.data.0.id', $this->teamWebsite->id);
        });
    });

    it('shows all websites when no team filter is applied', function () {
        $response = $this->actingAs($this->user)->get('/ssl/websites');

        $response->assertOk();
        $response->assertInertia(function ($page) {
            $page->has('websites.data', 2); // Both websites
        });
    });
});