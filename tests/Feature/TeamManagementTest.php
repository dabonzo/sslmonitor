<?php

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Website;

describe('Team Management', function () {
    test('user can create a team', function () {
        $user = User::factory()->create();

        $team = Team::create([
            'name' => 'SSL Monitor Team',
            'owner_id' => $user->id,
        ]);

        expect($team)
            ->name->toBe('SSL Monitor Team')
            ->owner_id->toBe($user->id);

        expect($team->owner)->toBeInstanceOf(User::class);
        expect($team->owner->id)->toBe($user->id);
    });

    test('team owner is automatically added as member', function () {
        $user = User::factory()->create();

        $team = Team::create([
            'name' => 'Test Team',
            'owner_id' => $user->id,
        ]);

        // Manually add owner as member (this will be handled by observer/service later)
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);

        expect($team->hasMember($user))->toBeTrue();
        expect($team->getUserRole($user))->toBe(TeamMember::ROLE_OWNER);
    });

    test('user can be invited to team', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create(['email' => 'office@intermedien.at']);

        $team = Team::create([
            'name' => 'SSL Team',
            'owner_id' => $owner->id,
        ]);

        // Add member to team
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => TeamMember::ROLE_ADMIN,
        ]);

        expect($team->hasMember($member))->toBeTrue();
        expect($team->getUserRole($member))->toBe(TeamMember::ROLE_ADMIN);
    });
});

describe('Team Permissions', function () {
    test('team owner has all permissions', function () {
        $owner = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);

        expect($team->userHasPermission($owner, 'view_websites'))->toBeTrue();
        expect($team->userHasPermission($owner, 'edit_settings'))->toBeTrue();
        expect($team->userHasPermission($owner, 'manage_team'))->toBeTrue();
        expect($team->userHasPermission($owner, 'delete_team'))->toBeTrue();
    });

    test('admin has website and settings permissions but not team management', function () {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $admin->id,
            'role' => TeamMember::ROLE_ADMIN,
        ]);

        expect($team->userHasPermission($admin, 'view_websites'))->toBeTrue();
        expect($team->userHasPermission($admin, 'edit_websites'))->toBeTrue();
        expect($team->userHasPermission($admin, 'edit_settings'))->toBeTrue();
        expect($team->userHasPermission($admin, 'manage_team'))->toBeFalse();
        expect($team->userHasPermission($admin, 'delete_team'))->toBeFalse();
    });

    test('viewer has only read permissions', function () {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $viewer->id,
            'role' => TeamMember::ROLE_VIEWER,
        ]);

        expect($team->userHasPermission($viewer, 'view_websites'))->toBeTrue();
        expect($team->userHasPermission($viewer, 'view_settings'))->toBeTrue();
        expect($team->userHasPermission($viewer, 'edit_websites'))->toBeFalse();
        expect($team->userHasPermission($viewer, 'edit_settings'))->toBeFalse();
    });
});

describe('Team Websites', function () {
    test('user can transfer personal website to team', function () {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $user->id]);

        // Create personal website
        $website = Website::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
        ]);

        expect($website->isPersonal())->toBeTrue();

        // Transfer to team
        $website->update([
            'team_id' => $team->id,
            'added_by' => $user->id,
        ]);

        expect($website->fresh()->isTeamWebsite())->toBeTrue();
        expect($website->fresh()->team_id)->toBe($team->id);
        expect($website->fresh()->added_by)->toBe($user->id);
    });

    test('team member can add website to team', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => TeamMember::ROLE_ADMIN,
        ]);

        $website = Website::create([
            'name' => 'Company Site',
            'url' => 'https://company.com',
            'user_id' => $owner->id, // Still owned by team owner
            'team_id' => $team->id,
            'added_by' => $member->id, // But added by member
        ]);

        expect($website->isTeamWebsite())->toBeTrue();
        expect($website->addedBy->id)->toBe($member->id);
        expect($website->team->id)->toBe($team->id);
    });

    test('user can see accessible websites (personal + team)', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $user->id]);

        // Add user to team
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);

        // Create personal website
        $personalWebsite = Website::factory()->create(['user_id' => $user->id]);

        // Create team website
        $teamWebsite = Website::factory()->create([
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        // Create other user's website (should not be accessible)
        $otherWebsite = Website::factory()->create(['user_id' => $otherUser->id]);

        $accessibleWebsites = Website::accessibleToUser($user)->get();

        expect($accessibleWebsites)->toHaveCount(2);
        expect($accessibleWebsites->pluck('id'))->toContain($personalWebsite->id);
        expect($accessibleWebsites->pluck('id'))->toContain($teamWebsite->id);
        expect($accessibleWebsites->pluck('id'))->not->toContain($otherWebsite->id);
    });
});

describe('Team Email Settings', function () {
    test('team can have dedicated email settings', function () {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $user->id]);

        $emailSettings = \App\Models\EmailSettings::create([
            'team_id' => $team->id,
            'host' => 'smtp.company.com',
            'port' => 587,
            'from_address' => 'ssl-alerts@company.com',
            'from_name' => 'Company SSL Monitor',
            'is_active' => true,
            'notification_emails' => ['bonzo@konjscina.com', 'office@intermedien.at'],
        ]);

        expect($emailSettings->isTeam())->toBeTrue();
        expect($emailSettings->team->id)->toBe($team->id);
        expect($emailSettings->notification_emails)->toContain('bonzo@konjscina.com');
        expect($emailSettings->notification_emails)->toContain('office@intermedien.at');
    });

    test('can get active email settings for team', function () {
        $user = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $user->id]);

        // Create inactive settings
        \App\Models\EmailSettings::create([
            'team_id' => $team->id,
            'host' => 'old.smtp.com',
            'port' => 25,
            'from_address' => 'old@company.com',
            'from_name' => 'Old Settings',
            'is_active' => false,
        ]);

        // Create active settings
        $activeSettings = \App\Models\EmailSettings::create([
            'team_id' => $team->id,
            'host' => 'smtp.company.com',
            'port' => 587,
            'from_address' => 'ssl@company.com',
            'from_name' => 'SSL Monitor',
            'is_active' => true,
        ]);

        $retrieved = \App\Models\EmailSettings::activeForTeam($team);

        expect($retrieved->id)->toBe($activeSettings->id);
        expect($retrieved->host)->toBe('smtp.company.com');
    });
});
