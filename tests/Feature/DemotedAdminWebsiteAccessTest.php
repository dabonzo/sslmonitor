<?php

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Website;
use Tests\Traits\MocksSslCertificateAnalysis;
use Tests\Traits\UsesCleanDatabase;

uses(UsesCleanDatabase::class, MocksSslCertificateAnalysis::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
    $this->setUpMocksSslCertificateAnalysis();

    // Mock MonitorIntegrationService to prevent observer overhead
    $this->mock(\App\Services\MonitorIntegrationService::class, function ($mock) {
        $mock->shouldReceive('createOrUpdateMonitorForWebsite')->andReturn(null);
        $mock->shouldReceive('removeMonitorForWebsite')->andReturn(null);
    });
});

test('admin who created website and then demoted to viewer cannot delete it', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create(['created_by_user_id' => $owner->id]);

    // Create team with admin member
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

    // Admin creates a team website
    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'user_id' => $admin->id,
        'team_id' => $team->id,
        'assigned_by_user_id' => $admin->id,
        'assigned_at' => now(),
    ]));

    // Admin can delete before demotion
    expect($admin->can('delete', $website))->toBeTrue();

    // Owner demotes admin to viewer
    $adminMember = TeamMember::where('team_id', $team->id)
        ->where('user_id', $admin->id)
        ->first();
    $adminMember->role = TeamMember::ROLE_VIEWER;
    $adminMember->save();

    // After demotion: can view but NOT delete
    expect($admin->can('view', $website))->toBeTrue();
    expect($admin->can('delete', $website))->toBeFalse();
});

test('admin who created website and then demoted to viewer cannot edit it', function () {
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

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $admin->id,
        'role' => TeamMember::ROLE_ADMIN,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'user_id' => $admin->id,
        'team_id' => $team->id,
        'assigned_by_user_id' => $admin->id,
        'assigned_at' => now(),
    ]));

    // Admin can update before demotion
    expect($admin->can('update', $website))->toBeTrue();

    // Demote to viewer
    $adminMember = TeamMember::where('team_id', $team->id)
        ->where('user_id', $admin->id)
        ->first();
    $adminMember->role = TeamMember::ROLE_VIEWER;
    $adminMember->save();

    // After demotion: can view but NOT update
    expect($admin->can('view', $website))->toBeTrue();
    expect($admin->can('update', $website))->toBeFalse();
});

test('admin who created website and then demoted to viewer cannot transfer to personal', function () {
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

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $admin->id,
        'role' => TeamMember::ROLE_ADMIN,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'user_id' => $admin->id,
        'team_id' => $team->id,
        'assigned_by_user_id' => $admin->id,
        'assigned_at' => now(),
    ]));

    // Demote admin to viewer
    $adminMember = TeamMember::where('team_id', $team->id)
        ->where('user_id', $admin->id)
        ->first();
    $adminMember->role = TeamMember::ROLE_VIEWER;
    $adminMember->save();

    // Attempt to transfer to personal should fail with 403
    $response = $this->actingAs($admin)
        ->post("/ssl/websites/{$website->id}/transfer-to-personal");

    $response->assertForbidden();

    // Website should still be team website
    $website->refresh();
    expect($website->team_id)->toBe($team->id);
});

test('owner can still manage website created by demoted admin', function () {
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

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $admin->id,
        'role' => TeamMember::ROLE_ADMIN,
        'joined_at' => now(),
        'invited_by_user_id' => $owner->id,
    ]);

    $website = Website::withoutEvents(fn() => Website::factory()->create([
        'user_id' => $admin->id,
        'team_id' => $team->id,
        'assigned_by_user_id' => $admin->id,
        'assigned_at' => now(),
    ]));

    // Demote admin to viewer
    $adminMember = TeamMember::where('team_id', $team->id)
        ->where('user_id', $admin->id)
        ->first();
    $adminMember->role = TeamMember::ROLE_VIEWER;
    $adminMember->save();

    // Owner retains full access
    expect($owner->can('view', $website))->toBeTrue();
    expect($owner->can('update', $website))->toBeTrue();
    expect($owner->can('delete', $website))->toBeTrue();
});
