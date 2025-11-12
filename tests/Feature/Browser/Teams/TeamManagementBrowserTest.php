<?php
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
describe('Team Management', function () {
    test('authenticated user can access team settings', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('settings.team.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Team/Index')
        );
    });
    test('unauthenticated user cannot access team settings', function () {
        $response = $this->get(route('settings.team.index'));
        $response->assertRedirect(route('login'));
    });
    test('user can create team', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('settings.team.store'), [
            'name' => 'Development Team',
            'description' => 'Our development team',
        ]);
        $team = Team::where('name', 'Development Team')->first();
        expect($team)->not->toBeNull();
        expect($team->created_by_user_id)->toBe($user->id);
    });
    test('team name is required', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('settings.team.store'), [
            'name' => '',
            'description' => 'Test',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->has('errors.name')
        );
    });
    test('user can view team details', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $user->id]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);
        $response = $this->actingAs($user)->get(route('settings.team.show', $team));
        $response->assertStatus(200);
        $response->assertSee($team->name);
    });
    test('team owner can invite team member', function () {
        $owner = User::factory()->create();
        $newUser = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);
        $response = $this->actingAs($owner)->post(route('settings.team.invite', $team), [
            'email' => $newUser->email,
            'role' => TeamMember::ROLE_ADMIN,
        ]);
        $member = TeamMember::where('team_id', $team->id)
            ->where('user_id', $newUser->id)
            ->first();
        expect($member)->not->toBeNull();
        expect($member->role)->toBe(TeamMember::ROLE_ADMIN);
    });
    test('invited user can accept team invitation', function () {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);
        // Invite user
        $this->actingAs($owner)->post(route('settings.team.invite', $team), [
            'email' => $invitedUser->email,
            'role' => TeamMember::ROLE_VIEWER,
        ]);
        // User accepts invitation
        $response = $this->actingAs($invitedUser)->post(
            route('team.invitations.accept.existing', ['token' => 'fake-token']),
            ['team_id' => $team->id]
        );
        // Verify user is now team member
        $member = TeamMember::where('team_id', $team->id)
            ->where('user_id', $invitedUser->id)
            ->first();
        expect($member)->not->toBeNull();
    });
    test('team owner can change member role', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => TeamMember::ROLE_VIEWER,
        ]);
        $response = $this->actingAs($owner)->patch(
            route('settings.team.members.role', [$team, $member]),
            ['role' => TeamMember::ROLE_ADMIN]
        );
        $teamMember = TeamMember::where('team_id', $team->id)
            ->where('user_id', $member->id)
            ->first();
        expect($teamMember->role)->toBe(TeamMember::ROLE_ADMIN);
    });
    test('team owner can remove team member', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);
        $teamMember = TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => TeamMember::ROLE_VIEWER,
        ]);
        $response = $this->actingAs($owner)->delete(
            route('settings.team.members.remove', [$team, $member])
        );
        expect(TeamMember::find($teamMember->id))->toBeNull();
    });
    test('non-owner cannot manage team members', function () {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $admin->id,
            'role' => TeamMember::ROLE_ADMIN,
        ]);
        $newMember = User::factory()->create();
        $response = $this->actingAs($admin)->post(route('settings.team.invite', $team), [
            'email' => $newMember->email,
        ]);
        $response->assertStatus(403);
    });
    test('team owner can update team details', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create([
            'created_by_user_id' => $user->id,
            'name' => 'Original Name',
        ]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);
        $response = $this->actingAs($user)->put(route('settings.team.update', $team), [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
        $team->refresh();
        expect($team->name)->toBe('Updated Name');
        expect($team->description)->toBe('Updated description');
    });
    test('team owner can delete team', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $user->id]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);
        $response = $this->actingAs($user)->delete(route('settings.team.destroy', $team));
        expect(Team::find($team->id))->toBeNull();
    });
    test('non-owner cannot delete team', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => TeamMember::ROLE_VIEWER,
        ]);
        $response = $this->actingAs($member)->delete(route('settings.team.destroy', $team));
        $response->assertStatus(403);
        expect(Team::find($team->id))->not->toBeNull();
    });
    test('team members list shows all members', function () {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);
        $response = $this->actingAs($owner)->get(route('settings.team.show', $team));
        $response->assertStatus(200);
        $response->assertSee($owner->email)
            ->or($response)->assertSee('Member');
    });
})->group('teams', 'management');
