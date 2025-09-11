<?php

use App\Livewire\Settings\TeamManagement;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Website;
use Livewire\Livewire;

describe('Team Management Component', function () {
    test('user can access team management page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings/team')
            ->assertStatus(200)
            ->assertSeeLivewire(TeamManagement::class);
    });

    test('guest cannot access team management page', function () {
        $this->get('/settings/team')
            ->assertRedirect('/login');
    });

    test('user without team sees create team option', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TeamManagement::class)
            ->assertSee('Individual Mode')
            ->assertSee('Create Team')
            ->assertDontSee('Team Members');
    });

    test('user can create a team', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TeamManagement::class)
            ->set('teamName', 'SSL Monitor Team')
            ->call('createTeam')
            ->assertHasNoErrors()
            ->assertSee('SSL Monitor Team')
            ->assertSee('Team Members');

        $team = Team::where('name', 'SSL Monitor Team')->first();
        expect($team)->not->toBeNull();
        expect($team->owner_id)->toBe($user->id);

        // Owner should be automatically added as team member
        expect($team->hasMember($user))->toBeTrue();
        expect($team->getUserRole($user))->toBe(TeamMember::ROLE_OWNER);
    });

    test('team name is required for creation', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TeamManagement::class)
            ->set('teamName', '')
            ->call('createTeam')
            ->assertHasErrors(['teamName']);
    });

    test('user with team sees team information', function () {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Existing Team', 'owner_id' => $user->id]);
        
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);

        Livewire::actingAs($user)
            ->test(TeamManagement::class)
            ->assertSee('Existing Team')
            ->assertSee('Team Members')
            ->assertSee($user->email)
            ->assertSee('Owner')
            ->assertDontSee('Create Team');
    });

    test('team owner can invite users', function () {
        $owner = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $owner->id]);
        
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);

        Livewire::actingAs($owner)
            ->test(TeamManagement::class)
            ->set('inviteEmail', 'office@intermedien.at')
            ->set('inviteRole', 'admin')
            ->call('inviteUser')
            ->assertHasNoErrors()
            ->assertSee('User invited successfully');

        // Should create user if doesn't exist and add to team
        $invitedUser = User::where('email', 'office@intermedien.at')->first();
        expect($invitedUser)->not->toBeNull();
        expect($team->hasMember($invitedUser))->toBeTrue();
        expect($team->getUserRole($invitedUser))->toBe(TeamMember::ROLE_ADMIN);
    });

    test('non-owner cannot invite users', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $owner->id]);
        
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => TeamMember::ROLE_VIEWER,
        ]);

        Livewire::actingAs($member)
            ->test(TeamManagement::class)
            ->set('inviteEmail', 'someone@example.com')
            ->set('inviteRole', 'admin')
            ->call('inviteUser')
            ->assertForbidden();
    });

    test('user can transfer personal websites to team during creation', function () {
        $user = User::factory()->create();
        
        // Create personal websites
        $website1 = Website::factory()->create(['user_id' => $user->id, 'url' => 'https://site1.com']);
        $website2 = Website::factory()->create(['user_id' => $user->id, 'url' => 'https://site2.com']);

        Livewire::actingAs($user)
            ->test(TeamManagement::class)
            ->set('teamName', 'My Team')
            ->set('transferWebsites', [$website1->id]) // Transfer only first website
            ->call('createTeam')
            ->assertHasNoErrors();

        $team = Team::where('name', 'My Team')->first();
        
        // First website should be transferred to team
        expect($website1->fresh()->team_id)->toBe($team->id);
        expect($website1->fresh()->added_by)->toBe($user->id);
        
        // Second website should remain personal
        expect($website2->fresh()->team_id)->toBeNull();
    });
});

describe('Team Member Management', function () {
    test('team owner can remove team members', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $owner->id]);
        
        TeamMember::create(['team_id' => $team->id, 'user_id' => $owner->id, 'role' => TeamMember::ROLE_OWNER]);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => TeamMember::ROLE_ADMIN]);

        Livewire::actingAs($owner)
            ->test(TeamManagement::class)
            ->call('removeMember', $member->id)
            ->assertHasNoErrors();

        expect($team->hasMember($member))->toBeFalse();
    });

    test('team owner cannot remove themselves', function () {
        $owner = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $owner->id]);
        
        TeamMember::create(['team_id' => $team->id, 'user_id' => $owner->id, 'role' => TeamMember::ROLE_OWNER]);

        Livewire::actingAs($owner)
            ->test(TeamManagement::class)
            ->call('removeMember', $owner->id)
            ->assertHasErrors();

        expect($team->hasMember($owner))->toBeTrue();
    });

    test('team owner can change member roles', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $owner->id]);
        
        TeamMember::create(['team_id' => $team->id, 'user_id' => $owner->id, 'role' => TeamMember::ROLE_OWNER]);
        TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => TeamMember::ROLE_VIEWER]);

        Livewire::actingAs($owner)
            ->test(TeamManagement::class)
            ->call('changeMemberRole', $member->id, 'admin')
            ->assertHasNoErrors();

        expect($team->getUserRole($member))->toBe(TeamMember::ROLE_ADMIN);
    });
});
