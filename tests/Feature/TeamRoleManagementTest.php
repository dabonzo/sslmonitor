<?php

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

// OWNER ROLE MANAGEMENT TESTS

test('owner can promote viewer to admin', function () {
    [$owner, $viewer, $team] = createTeamWithMembers();

    $response = $this->actingAs($owner)
        ->patch("/settings/team/{$team->id}/members/{$viewer->id}/role", [
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $viewer->refresh();
    expect($viewer->getRoleInTeam($team))->toBe(TeamMember::ROLE_ADMIN);
});

test('owner can demote admin to viewer', function () {
    [$owner, $admin, $team] = createTeamWithMembers(adminRole: TeamMember::ROLE_ADMIN);

    $response = $this->actingAs($owner)
        ->patch("/settings/team/{$team->id}/members/{$admin->id}/role", [
            'role' => TeamMember::ROLE_VIEWER,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $admin->refresh();
    expect($admin->getRoleInTeam($team))->toBe(TeamMember::ROLE_VIEWER);
});

test('owner can promote admin to owner', function () {
    [$owner, $admin, $team] = createTeamWithMembers(adminRole: TeamMember::ROLE_ADMIN);

    $response = $this->actingAs($owner)
        ->patch("/settings/team/{$team->id}/members/{$admin->id}/role", [
            'role' => TeamMember::ROLE_OWNER,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $admin->refresh();
    expect($admin->getRoleInTeam($team))->toBe(TeamMember::ROLE_OWNER);
});

test('owner cannot demote themselves if they are the last owner', function () {
    [$owner, $viewer, $team] = createTeamWithMembers();

    $response = $this->actingAs($owner)
        ->patch("/settings/team/{$team->id}/members/{$owner->id}/role", [
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');

    $owner->refresh();
    expect($owner->getRoleInTeam($team))->toBe(TeamMember::ROLE_OWNER);
});

test('owner can demote themselves if there is another owner', function () {
    $owner1 = User::factory()->create();
    $owner2 = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner1->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner1->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner1->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner2->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner1->id,
    ]);

    $response = $this->actingAs($owner2)
        ->patch("/settings/team/{$team->id}/members/{$owner1->id}/role", [
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $owner1->refresh();
    expect($owner1->getRoleInTeam($team))->toBe(TeamMember::ROLE_ADMIN);
});

// ADMIN ROLE MANAGEMENT TESTS

test('admin can promote viewer to admin', function () {
    [$owner, $admin, $viewer, $team] = createTeamWithThreeMembers();

    $response = $this->actingAs($admin)
        ->patch("/settings/team/{$team->id}/members/{$viewer->id}/role", [
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $viewer->refresh();
    expect($viewer->getRoleInTeam($team))->toBe(TeamMember::ROLE_ADMIN);
});

test('admin can demote admin to viewer', function () {
    [$owner, $admin1, $admin2, $team] = createTeamWithThreeMembers(
        member2Role: TeamMember::ROLE_ADMIN,
        member3Role: TeamMember::ROLE_ADMIN
    );

    $response = $this->actingAs($admin1)
        ->patch("/settings/team/{$team->id}/members/{$admin2->id}/role", [
            'role' => TeamMember::ROLE_VIEWER,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $admin2->refresh();
    expect($admin2->getRoleInTeam($team))->toBe(TeamMember::ROLE_VIEWER);
});

test('admin cannot promote anyone to owner', function () {
    [$owner, $admin, $viewer, $team] = createTeamWithThreeMembers();

    $response = $this->actingAs($admin)
        ->patch("/settings/team/{$team->id}/members/{$viewer->id}/role", [
            'role' => TeamMember::ROLE_OWNER,
        ]);

    $response->assertForbidden();

    $viewer->refresh();
    expect($viewer->getRoleInTeam($team))->toBe(TeamMember::ROLE_VIEWER);
});

test('admin cannot demote owner', function () {
    [$owner, $admin, $viewer, $team] = createTeamWithThreeMembers();

    $response = $this->actingAs($admin)
        ->patch("/settings/team/{$team->id}/members/{$owner->id}/role", [
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertForbidden();

    $owner->refresh();
    expect($owner->getRoleInTeam($team))->toBe(TeamMember::ROLE_OWNER);
});

// VIEWER ROLE MANAGEMENT TESTS

test('viewer cannot change any roles', function () {
    [$owner, $viewer1, $viewer2, $team] = createTeamWithThreeMembers(
        member2Role: TeamMember::ROLE_VIEWER,
        member3Role: TeamMember::ROLE_VIEWER
    );

    $response = $this->actingAs($viewer1)
        ->patch("/settings/team/{$team->id}/members/{$viewer2->id}/role", [
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertForbidden();
});

// SELF-ROLE CHANGE TESTS

test('owner cannot change their own role', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    // Add another owner so demoting would be allowed (if not self)
    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $admin->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)
        ->patch("/settings/team/{$team->id}/members/{$owner->id}/role", [
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'You cannot change your own role. Ask another team member to change it.');
});

test('admin cannot change their own role', function () {
    [$owner, $admin, $team] = createTeamWithMembers(adminRole: TeamMember::ROLE_ADMIN);

    $response = $this->actingAs($admin)
        ->patch("/settings/team/{$team->id}/members/{$admin->id}/role", [
            'role' => TeamMember::ROLE_VIEWER,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'You cannot change your own role. Ask another team member to change it.');
});

test('viewer cannot change their own role', function () {
    [$owner, $viewer, $team] = createTeamWithMembers();

    $response = $this->actingAs($viewer)
        ->patch("/settings/team/{$team->id}/members/{$viewer->id}/role", [
            'role' => TeamMember::ROLE_ADMIN,
        ]);

    $response->assertForbidden();
});

// VALIDATION TESTS

test('role change requires valid role', function () {
    [$owner, $viewer, $team] = createTeamWithMembers();

    $response = $this->actingAs($owner)
        ->patch("/settings/team/{$team->id}/members/{$viewer->id}/role", [
            'role' => 'INVALID_ROLE',
        ]);

    $response->assertSessionHasErrors('role');
});

test('role change requires role parameter', function () {
    [$owner, $viewer, $team] = createTeamWithMembers();

    $response = $this->actingAs($owner)
        ->patch("/settings/team/{$team->id}/members/{$viewer->id}/role", []);

    $response->assertSessionHasErrors('role');
});

// Helper functions
function createTeamWithMembers(string $adminRole = TeamMember::ROLE_VIEWER): array
{
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

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
        'role' => $adminRole,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    return [$owner, $member, $team];
}

function createTeamWithThreeMembers(
    string $member2Role = TeamMember::ROLE_ADMIN,
    string $member3Role = TeamMember::ROLE_VIEWER
): array {
    $owner = User::factory()->create();
    $member2 = User::factory()->create();
    $member3 = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $member2->id,
        'role' => $member2Role,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $member3->id,
        'role' => $member3Role,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    return [$owner, $member2, $member3, $team];
}
