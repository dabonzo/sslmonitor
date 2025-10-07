<?php

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Website;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

test('owner can view their personal website', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    expect($user->can('view', $website))->toBeTrue();
});

test('owner can update their personal website', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    expect($user->can('update', $website))->toBeTrue();
});

test('owner can delete their personal website', function () {
    $user = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $user->id]);

    expect($user->can('delete', $website))->toBeTrue();
});

test('non owner cannot view personal website', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $website = Website::factory()->create(['user_id' => $owner->id]);

    expect($otherUser->can('view', $website))->toBeFalse();
});

test('team member with admin role can view team website', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $admin->id,
        'role' => TeamMember::ROLE_ADMIN,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $website = Website::factory()->create([
        'user_id' => $owner->id,
        'team_id' => $team->id,
    ]);

    expect($admin->can('view', $website))->toBeTrue();
});

test('team member with admin role can update team website', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $admin->id,
        'role' => TeamMember::ROLE_ADMIN,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $website = Website::factory()->create([
        'user_id' => $owner->id,
        'team_id' => $team->id,
    ]);

    expect($admin->can('update', $website))->toBeTrue();
});

test('team member with admin role can delete team website', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $admin->id,
        'role' => TeamMember::ROLE_ADMIN,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $website = Website::factory()->create([
        'user_id' => $owner->id,
        'team_id' => $team->id,
    ]);

    expect($admin->can('delete', $website))->toBeTrue();
});

test('team member with viewer role can view team website', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $viewer->id,
        'role' => TeamMember::ROLE_VIEWER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $website = Website::factory()->create([
        'user_id' => $owner->id,
        'team_id' => $team->id,
    ]);

    expect($viewer->can('view', $website))->toBeTrue();
});

test('team member with viewer role cannot update team website', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $viewer->id,
        'role' => TeamMember::ROLE_VIEWER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $website = Website::factory()->create([
        'user_id' => $owner->id,
        'team_id' => $team->id,
    ]);

    expect($viewer->can('update', $website))->toBeFalse();
});

test('team member with viewer role cannot delete team website', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $viewer->id,
        'role' => TeamMember::ROLE_VIEWER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $website = Website::factory()->create([
        'user_id' => $owner->id,
        'team_id' => $team->id,
    ]);

    expect($viewer->can('delete', $website))->toBeFalse();
});

test('non team member cannot view team website', function () {
    $owner = User::factory()->create();
    $nonMember = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    $website = Website::factory()->create([
        'user_id' => $owner->id,
        'team_id' => $team->id,
    ]);

    expect($nonMember->can('view', $website))->toBeFalse();
});

test('team owner can manage team website', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $owner->id,
        'role' => TeamMember::ROLE_OWNER,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $website = Website::factory()->create([
        'user_id' => $owner->id,
        'team_id' => $team->id,
    ]);

    expect($owner->can('view', $website))->toBeTrue()
        ->and($owner->can('update', $website))->toBeTrue()
        ->and($owner->can('delete', $website))->toBeTrue();
});

test('team admin can manage team website', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $admin->id,
        'role' => TeamMember::ROLE_ADMIN,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $website = Website::factory()->create([
        'user_id' => $owner->id,
        'team_id' => $team->id,
    ]);

    expect($admin->can('view', $website))->toBeTrue()
        ->and($admin->can('update', $website))->toBeTrue()
        ->and($admin->can('delete', $website))->toBeTrue();
});
