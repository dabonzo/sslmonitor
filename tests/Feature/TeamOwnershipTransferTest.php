<?php

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

test('team owner can transfer ownership to another member', function () {
    $owner = User::factory()->create();
    $newOwner = User::factory()->create();
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
        'user_id' => $newOwner->id,
        'role' => TeamMember::ROLE_ADMIN,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)
        ->post("/settings/team/{$team->id}/transfer-ownership", [
            'new_owner_id' => $newOwner->id,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Verify ownership transfer
    $team->refresh();
    expect($team->created_by_user_id)->toBe($newOwner->id);

    // Verify new owner has OWNER role
    $newOwnerMembership = TeamMember::where('team_id', $team->id)
        ->where('user_id', $newOwner->id)
        ->first();
    expect($newOwnerMembership->role)->toBe(TeamMember::ROLE_OWNER);

    // Verify old owner was downgraded to ADMIN
    $oldOwnerMembership = TeamMember::where('team_id', $team->id)
        ->where('user_id', $owner->id)
        ->first();
    expect($oldOwnerMembership->role)->toBe(TeamMember::ROLE_ADMIN);
});

test('non-owner cannot transfer ownership', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $newOwner = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    // Add users to team
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
        'user_id' => $newOwner->id,
        'role' => TeamMember::ROLE_ADMIN,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($admin)
        ->post("/settings/team/{$team->id}/transfer-ownership", [
            'new_owner_id' => $newOwner->id,
        ]);

    $response->assertForbidden();
});

test('cannot transfer ownership to non-member', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)
        ->post("/settings/team/{$team->id}/transfer-ownership", [
            'new_owner_id' => $nonMember->id,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'The new owner must be a team member.');
});

test('cannot transfer ownership to self', function () {
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
        ->post("/settings/team/{$team->id}/transfer-ownership", [
            'new_owner_id' => $owner->id,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'You are already the team owner.');
});

test('ownership transfer requires valid user id', function () {
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
        ->post("/settings/team/{$team->id}/transfer-ownership", [
            'new_owner_id' => 99999,
        ]);

    $response->assertSessionHasErrors('new_owner_id');
});
