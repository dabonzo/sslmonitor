<?php

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\Website;
use App\Mail\TeamInvitationMail;
use Illuminate\Support\Facades\Mail;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

// Team Creation Tests
test('user can create a new team', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/settings/team', [
            'name' => 'Test Team',
            'description' => 'A test team for SSL monitoring',
        ]);

    $response->assertRedirect('/settings/team');
    $response->assertSessionHas('success', 'Team created successfully!');

    $this->assertDatabaseHas('teams', [
        'name' => 'Test Team',
        'description' => 'A test team for SSL monitoring',
        'created_by_user_id' => $user->id,
    ]);

    // Creator should be automatically added as owner
    $team = Team::where('name', 'Test Team')->first();
    $this->assertDatabaseHas('team_members', [
        'team_id' => $team->id,
        'user_id' => $user->id,
        'role' => TeamMember::ROLE_OWNER,
    ]);
});

test('team creation requires valid name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/settings/team', [
            'name' => '',
            'description' => 'Test description',
        ]);

    $response->assertSessionHasErrors(['name']);
});

test('team creation description is optional', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/settings/team', [
            'name' => 'Test Team Without Description',
        ]);

    $response->assertRedirect('/settings/team');
    $this->assertDatabaseHas('teams', [
        'name' => 'Test Team Without Description',
        'description' => null,
    ]);
});

// Team Invitation Tests
test('team owner can invite new members', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    // Add owner to team
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)
        ->post("/settings/team/{$team->id}/invite", [
            'email' => 'newmember@example.com',
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Invitation sent successfully!');

    $this->assertDatabaseHas('team_invitations', [
        'team_id' => $team->id,
        'email' => 'newmember@example.com',
        'role' => TeamMember::ROLE_ADMIN,
        'invited_by_user_id' => $owner->id,
    ]);
});

test('team admin can invite new members', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    // Add admin to team
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $admin->id,
        'role' => TeamMember::ROLE_ADMIN,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($admin)
        ->post("/settings/team/{$team->id}/invite", [
            'email' => 'newmember@example.com',
            'role' => TeamMember::ROLE_MANAGER,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Invitation sent successfully!');
});

test('team manager cannot invite new members', function () {
    $owner = User::factory()->create();
    $manager = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    // Add manager to team
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $manager->id,
        'role' => TeamMember::ROLE_MANAGER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($manager)
        ->post("/settings/team/{$team->id}/invite", [
            'email' => 'newmember@example.com',
            'role' => TeamMember::ROLE_VIEWER,
        ]);

    $response->assertForbidden();
});

test('cannot invite existing team member', function () {
    $owner = User::factory()->create();
    $existingMember = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    // Add both users to team
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $existingMember->id,
        'role' => TeamMember::ROLE_VIEWER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)
        ->post("/settings/team/{$team->id}/invite", [
            'email' => $existingMember->email,
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertSessionHasErrors(['email']);
});

test('cannot send duplicate pending invitations', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    // Create existing invitation
    TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'pending@example.com',
        'role' => TeamMember::ROLE_VIEWER,
        'token' => 'existing-token',
        'expires_at' => now()->addDays(7),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)
        ->post("/settings/team/{$team->id}/invite", [
            'email' => 'pending@example.com',
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertSessionHasErrors(['email']);
});

// Team Member Management Tests
test('team owner can remove members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    // Add both users to team
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $member->id,
        'role' => TeamMember::ROLE_VIEWER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)
        ->delete("/settings/team/{$team->id}/members/{$member->id}");

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member removed successfully!');

    $this->assertDatabaseMissing('team_members', [
        'team_id' => $team->id,
        'user_id' => $member->id,
    ]);
});

test('team owner can update member roles', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    // Add both users to team
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $member->id,
        'role' => TeamMember::ROLE_VIEWER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)
        ->patch("/settings/team/{$team->id}/members/{$member->id}/role", [
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Member role updated successfully!');

    $this->assertDatabaseHas('team_members', [
        'team_id' => $team->id,
        'user_id' => $member->id,
        'role' => TeamMember::ROLE_ADMIN,
    ]);
});

test('last owner cannot remove themselves', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)
        ->delete("/settings/team/{$team->id}/members/{$owner->id}");

    $response->assertSessionHas('error');
});

test('last owner cannot change their own role', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)
        ->patch("/settings/team/{$team->id}/members/{$owner->id}/role", [
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertSessionHas('error');
});

// Website Transfer Tests
test('website can be transferred to team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $user->id]);
    $website = Website::factory()->create(['user_id' => $user->id]);

    expect($website->isPersonal())->toBeTrue();
    expect($website->isTeam())->toBeFalse();

    $website->transferToTeam($team, $user);

    $website->refresh();
    expect($website->isPersonal())->toBeFalse();
    expect($website->isTeam())->toBeTrue();
    expect($website->team_id)->toBe($team->id);
    expect($website->assigned_by_user_id)->toBe($user->id);
    expect($website->assigned_at)->not->toBeNull();
});

test('website can be transferred back to personal', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $user->id]);
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'assigned_by_user_id' => $user->id,
        'assigned_at' => now(),
    ]);

    expect($website->isTeam())->toBeTrue();

    $website->transferToPersonal();

    $website->refresh();
    expect($website->isPersonal())->toBeTrue();
    expect($website->team_id)->toBeNull();
    expect($website->assigned_by_user_id)->toBeNull();
    expect($website->assigned_at)->toBeNull();
});

// Enhanced Website Transfer System Tests
test('website transfer respects team member permissions', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $manager = User::factory()->create();
    $viewer = User::factory()->create();

    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
    $website = Website::factory()->create(['user_id' => $admin->id]);

    // Add members to team
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $admin->id,
        'role' => TeamMember::ROLE_ADMIN,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $manager->id,
        'role' => TeamMember::ROLE_MANAGER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $viewer->id,
        'role' => TeamMember::ROLE_VIEWER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    // Test transfer to team permissions
    $response = $this->actingAs($admin)
        ->post("/ssl/websites/{$website->id}/transfer-to-team", [
            'team_id' => $team->id,
        ]);
    $response->assertRedirect();

    $website->refresh();
    expect($website->team_id)->toBe($team->id);

    // Test that manager can transfer
    $website2 = Website::factory()->create(['user_id' => $manager->id]);
    $response = $this->actingAs($manager)
        ->post("/ssl/websites/{$website2->id}/transfer-to-team", [
            'team_id' => $team->id,
        ]);
    $response->assertRedirect();

    // Test that viewer cannot transfer
    $website3 = Website::factory()->create(['user_id' => $viewer->id]);
    $response = $this->actingAs($viewer)
        ->post("/ssl/websites/{$website3->id}/transfer-to-team", [
            'team_id' => $team->id,
        ]);
    $response->assertForbidden();
});

test('website transfer to personal requires admin or owner permissions', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $manager = User::factory()->create();

    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
    $website = Website::factory()->create([
        'user_id' => $owner->id,
        'team_id' => $team->id,
        'assigned_by_user_id' => $owner->id,
        'assigned_at' => now(),
    ]);

    // Add members to team
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $admin->id,
        'role' => TeamMember::ROLE_ADMIN,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $manager->id,
        'role' => TeamMember::ROLE_MANAGER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    // Owner can transfer to personal
    $response = $this->actingAs($owner)
        ->post("/ssl/websites/{$website->id}/transfer-to-personal");
    $response->assertRedirect();

    $website->refresh();
    expect($website->team_id)->toBeNull();

    // Transfer back to team
    $website->transferToTeam($team, $owner);

    // Admin can transfer to personal
    $response = $this->actingAs($admin)
        ->post("/ssl/websites/{$website->id}/transfer-to-personal");
    $response->assertRedirect();

    $website->refresh();
    expect($website->team_id)->toBeNull();

    // Transfer back to team
    $website->transferToTeam($team, $admin);

    // Manager cannot transfer to personal
    $response = $this->actingAs($manager)
        ->post("/ssl/websites/{$website->id}/transfer-to-personal");
    $response->assertForbidden();
});

test('website transfer fails when user is not team member', function () {
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
    $website = Website::factory()->create(['user_id' => $outsider->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    // Outsider cannot transfer website to team
    $response = $this->actingAs($outsider)
        ->post("/ssl/websites/{$website->id}/transfer-to-team", [
            'team_id' => $team->id,
        ]);
    $response->assertForbidden();
});

test('website transfer validates team exists', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->post("/ssl/websites/{$website->id}/transfer-to-team", [
            'team_id' => 99999, // Non-existent team
        ]);

    $response->assertSessionHasErrors(['team_id']);
});

test('website transfer tracks assignment metadata', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $user->id]);
    $website = Website::factory()->create(['user_id' => $user->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->post("/ssl/websites/{$website->id}/transfer-to-team", [
            'team_id' => $team->id,
        ]);

    $response->assertRedirect();

    $website->refresh();
    expect($website->team_id)->toBe($team->id);
    expect($website->assigned_by_user_id)->toBe($user->id);
    expect($website->assigned_at)->not->toBeNull();

    // Check that assigned_at is recent (within last minute)
    expect($website->assigned_at)->toBeGreaterThanOrEqual(now()->subMinute());
});

test('website transfer options returns available teams', function () {
    $user = User::factory()->create();
    $team1 = Team::factory()->create(['created_by_user_id' => $user->id]);
    $team2 = Team::factory()->create(['created_by_user_id' => User::factory()->create()->id]);
    $website = Website::factory()->create(['user_id' => $user->id]);

    // Add user to team1 as owner and team2 as admin
    TeamMember::create([
        'team_id' => $team1->id,
        'user_id' => $user->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $user->id,
    ]);

    TeamMember::create([
        'team_id' => $team2->id,
        'user_id' => $user->id,
        'role' => TeamMember::ROLE_ADMIN,
        'joined_at' => now(),
        'invited_by_user_id' => $team2->created_by_user_id,
    ]);

    $response = $this->actingAs($user)
        ->get("/ssl/websites/{$website->id}/transfer-options");

    $response->assertSuccessful();
    $responseData = $response->json();

    expect($responseData['teams'])->toHaveCount(2);

    $teamIds = collect($responseData['teams'])->pluck('id')->toArray();
    expect($teamIds)->toContain($team1->id);
    expect($teamIds)->toContain($team2->id);
});

test('website transfer clears assignment data when transferring to personal', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $user->id]);
    $website = Website::factory()->create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'assigned_by_user_id' => $user->id,
        'assigned_at' => now(),
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->post("/ssl/websites/{$website->id}/transfer-to-personal");

    $response->assertRedirect();

    $website->refresh();
    expect($website->team_id)->toBeNull();
    expect($website->assigned_by_user_id)->toBeNull();
    expect($website->assigned_at)->toBeNull();
});

// Team Role Permission Tests
test('team member role permissions work correctly', function () {
    $teamMember = new TeamMember(['role' => TeamMember::ROLE_OWNER]);
    expect($teamMember->canManageTeam())->toBeTrue();
    expect($teamMember->canManageWebsites())->toBeTrue();
    expect($teamMember->canManageEmailSettings())->toBeTrue();
    expect($teamMember->canInviteMembers())->toBeTrue();
    expect($teamMember->canRemoveMembers())->toBeTrue();

    $teamMember->role = TeamMember::ROLE_ADMIN;
    expect($teamMember->canManageTeam())->toBeFalse();
    expect($teamMember->canManageWebsites())->toBeTrue();
    expect($teamMember->canManageEmailSettings())->toBeTrue();
    expect($teamMember->canInviteMembers())->toBeTrue();
    expect($teamMember->canRemoveMembers())->toBeFalse();

    $teamMember->role = TeamMember::ROLE_MANAGER;
    expect($teamMember->canManageTeam())->toBeFalse();
    expect($teamMember->canManageWebsites())->toBeTrue();
    expect($teamMember->canManageEmailSettings())->toBeFalse();
    expect($teamMember->canInviteMembers())->toBeFalse();
    expect($teamMember->canRemoveMembers())->toBeFalse();

    $teamMember->role = TeamMember::ROLE_VIEWER;
    expect($teamMember->canManageTeam())->toBeFalse();
    expect($teamMember->canManageWebsites())->toBeFalse();
    expect($teamMember->canManageEmailSettings())->toBeFalse();
    expect($teamMember->canInviteMembers())->toBeFalse();
    expect($teamMember->canRemoveMembers())->toBeFalse();
    expect($teamMember->canViewWebsites())->toBeTrue();
    expect($teamMember->canViewSettings())->toBeTrue();
});

// Team Invitation Model Tests
test('team invitation token generation is unique', function () {
    $invitation1 = new TeamInvitation();
    $invitation2 = new TeamInvitation();

    $token1 = TeamInvitation::generateToken();
    $token2 = TeamInvitation::generateToken();

    expect($token1)->not->toBe($token2);
    expect(strlen($token1))->toBe(64);
    expect(strlen($token2))->toBe(64);
});

test('team invitation validity checks work correctly', function () {
    $invitation = TeamInvitation::factory()->create([
        'expires_at' => now()->addDays(7),
        'accepted_at' => null,
    ]);

    expect($invitation->isValid())->toBeTrue();
    expect($invitation->isExpired())->toBeFalse();
    expect($invitation->isAccepted())->toBeFalse();

    // Test expired invitation
    $invitation->update(['expires_at' => now()->subDays(1)]);
    expect($invitation->isValid())->toBeFalse();
    expect($invitation->isExpired())->toBeTrue();

    // Test accepted invitation
    $invitation->update([
        'expires_at' => now()->addDays(7),
        'accepted_at' => now(),
    ]);
    expect($invitation->isValid())->toBeFalse();
    expect($invitation->isAccepted())->toBeTrue();
});

// Team Settings Page Access Tests
test('user can access team settings page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/settings/team');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Settings/Team')
        ->has('teams')
        ->has('roleDescriptions')
        ->has('availableRoles')
    );
});

test('team member can view team details', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $member->id,
        'role' => TeamMember::ROLE_VIEWER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($member)->get("/settings/team/{$team->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Settings/Team')
        ->has('team')
        ->has('members')
        ->has('pendingInvitations')
        ->has('websites')
    );
});

test('non-team member cannot view team details', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    $response = $this->actingAs($user)->get("/settings/team/{$team->id}");

    $response->assertForbidden();
});

// Team Invitation Email Tests
test('team invitation sends email when invitation is created', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'invited_by_user_id' => $owner->id,
        'joined_at' => now(),
    ]);

    $response = $this->actingAs($owner)
        ->post("/settings/team/{$team->id}/invite", [
            'email' => 'newmember@example.com',
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Invitation sent successfully!');

    // Verify email was queued (since TeamInvitationMail implements ShouldQueue)
    Mail::assertQueued(TeamInvitationMail::class, function ($mail) {
        return $mail->hasTo('newmember@example.com');
    });
});

test('team invitation email contains correct content', function () {
    Mail::fake();

    $owner = User::factory()->create(['name' => 'John Owner']);
    $team = Team::factory()->create([
        'created_by_user_id' => $owner->id,
        'name' => 'Test Team',
        'description' => 'A test team for SSL monitoring'
    ]);
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'invited_by_user_id' => $owner->id,
        'joined_at' => now(),
    ]);

    $this->actingAs($owner)
        ->post("/settings/team/{$team->id}/invite", [
            'email' => 'newmember@example.com',
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    Mail::assertQueued(TeamInvitationMail::class, function ($mail) use ($team, $owner) {
        // Check that the mail object has correct data
        return $mail->invitation->team->name === 'Test Team' &&
               $mail->invitation->role === TeamMember::ROLE_ADMIN &&
               $mail->invitation->invitedBy->name === 'John Owner';
    });
});

test('team invitation email has both html and text versions', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'test@example.com',
        'role' => TeamMember::ROLE_ADMIN,
        'invited_by_user_id' => $owner->id,
    ]);

    $mail = new TeamInvitationMail($invitation);
    $content = $mail->content();

    expect($content->html)->toBe('emails.team-invitation');
    expect($content->text)->toBe('emails.team-invitation-text');
});

test('team invitation email subject contains team name and role', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Test Team']);
    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'test@example.com',
        'role' => TeamMember::ROLE_ADMIN,
        'invited_by_user_id' => $owner->id,
    ]);

    $mail = new TeamInvitationMail($invitation);
    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain('Test Team');
    expect($envelope->subject)->toContain('SSL Monitor');
});

test('invitation emails are queued for background processing', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);
    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'test@example.com',
        'role' => TeamMember::ROLE_ADMIN,
        'invited_by_user_id' => $owner->id,
    ]);

    $mail = new TeamInvitationMail($invitation);

    // TeamInvitationMail should implement ShouldQueue
    expect($mail)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

test('expired invitation does not send email', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    // Create an expired invitation
    $expiredInvitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'expired@example.com',
        'role' => TeamMember::ROLE_ADMIN,
        'invited_by_user_id' => $owner->id,
        'expires_at' => now()->subDay(), // Expired yesterday
    ]);

    // Try to access the expired invitation
    $response = $this->get("/team/invitations/{$expiredInvitation->token}");

    $response->assertRedirect('/');
    $response->assertSessionHas('error', 'This invitation is invalid or has expired.');

    // No additional emails should be sent for expired invitations
    Mail::assertNothingSent();
});

test('team invitation acceptance flow works for existing users', function () {
    $owner = User::factory()->create();
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'existing@example.com',
        'role' => TeamMember::ROLE_ADMIN,
        'invited_by_user_id' => $owner->id,
    ]);

    // Existing user accepts invitation
    $response = $this->actingAs($existingUser)
        ->post("/team/invitations/{$invitation->token}/accept");

    $response->assertRedirect('/settings/team');
    $response->assertSessionHas('success');

    // Verify user was added to team
    $this->assertDatabaseHas('team_members', [
        'team_id' => $team->id,
        'user_id' => $existingUser->id,
        'role' => TeamMember::ROLE_ADMIN,
    ]);

    // Verify invitation was accepted
    $invitation->refresh();
    expect($invitation->accepted_at)->not->toBeNull();
});

test('team invitation acceptance flow works for new users', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'newuser@example.com',
        'role' => TeamMember::ROLE_ADMIN,
        'invited_by_user_id' => $owner->id,
    ]);

    // New user registers and accepts invitation
    $response = $this->post("/team/invitations/{$invitation->token}/register", [
        'name' => 'New User',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/dashboard');
    $response->assertSessionHas('success');

    // Verify new user was created
    $newUser = User::where('email', 'newuser@example.com')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->name)->toBe('New User');
    expect($newUser->email_verified_at)->not->toBeNull(); // Auto-verified since they have valid invitation

    // Verify user was added to team
    $this->assertDatabaseHas('team_members', [
        'team_id' => $team->id,
        'user_id' => $newUser->id,
        'role' => TeamMember::ROLE_ADMIN,
    ]);

    // Verify invitation was accepted
    $invitation->refresh();
    expect($invitation->accepted_at)->not->toBeNull();
});

test('team invitation can be declined', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'declined@example.com',
        'role' => TeamMember::ROLE_ADMIN,
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->post("/team/invitations/{$invitation->token}/decline");

    $response->assertRedirect('/');
    $response->assertSessionHas('success');

    // Verify invitation was deleted
    $this->assertDatabaseMissing('team_invitations', [
        'id' => $invitation->id,
    ]);
});
