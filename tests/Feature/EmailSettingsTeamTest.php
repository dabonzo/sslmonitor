<?php

use App\Livewire\Settings\EmailSettings;
use App\Models\EmailSettings as EmailSettingsModel;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Livewire\Livewire;

describe('Email Settings Team Support', function () {
    test('individual user can save personal email settings', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EmailSettings::class)
            ->set('host', 'smtp.example.com')
            ->set('port', 587)
            ->set('from_address', 'test@example.com')
            ->set('from_name', 'Test Sender')
            ->call('save')
            ->assertHasNoErrors();

        $settings = EmailSettingsModel::activeForUser($user);
        expect($settings)->not->toBeNull();
        expect($settings->host)->toBe('smtp.example.com');
        expect($settings->user_id)->toBe($user->id);
        expect($settings->team_id)->toBeNull();
    });

    test('team member can save team email settings', function () {
        $owner = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);

        Livewire::actingAs($owner)
            ->test(EmailSettings::class)
            ->set('host', 'team.smtp.com')
            ->set('port', 587)
            ->set('from_address', 'team@company.com')
            ->set('from_name', 'Team Alerts')
            ->call('save')
            ->assertHasNoErrors();

        $settings = EmailSettingsModel::activeForTeam($team);
        expect($settings)->not->toBeNull();
        expect($settings->host)->toBe('team.smtp.com');
        expect($settings->from_address)->toBe('team@company.com');
        expect($settings->team_id)->toBe($team->id);
        expect($settings->user_id)->toBeNull();
    });

    test('team settings override personal settings for team members', function () {
        $owner = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);

        // Create personal settings first
        EmailSettingsModel::create([
            'user_id' => $owner->id,
            'host' => 'personal.smtp.com',
            'port' => 587,
            'from_address' => 'personal@example.com',
            'from_name' => 'Personal',
            'is_active' => true,
        ]);

        // Create team settings
        EmailSettingsModel::create([
            'team_id' => $team->id,
            'host' => 'team.smtp.com',
            'port' => 587,
            'from_address' => 'team@company.com',
            'from_name' => 'Team',
            'is_active' => true,
        ]);

        // Team settings should be loaded for team member
        $component = Livewire::actingAs($owner)
            ->test(EmailSettings::class);

        expect($component->host)->toBe('team.smtp.com');
        expect($component->from_address)->toBe('team@company.com');
    });

    test('individual user loads personal settings when no team', function () {
        $user = User::factory()->create();

        // Create personal settings
        EmailSettingsModel::create([
            'user_id' => $user->id,
            'host' => 'personal.smtp.com',
            'port' => 587,
            'from_address' => 'personal@example.com',
            'from_name' => 'Personal',
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($user)
            ->test(EmailSettings::class);

        expect($component->host)->toBe('personal.smtp.com');
        expect($component->from_address)->toBe('personal@example.com');
    });

    test('component shows team context in view', function () {
        $owner = User::factory()->create();
        $team = Team::create(['name' => 'SSL Team', 'owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);

        Livewire::actingAs($owner)
            ->test(EmailSettings::class)
            ->assertViewHas('team', function ($viewTeam) use ($team) {
                return $viewTeam && $viewTeam->id === $team->id;
            });
    });

    test('saving team settings deactivates previous team settings', function () {
        $owner = User::factory()->create();
        $team = Team::create(['name' => 'Test Team', 'owner_id' => $owner->id]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
            'role' => TeamMember::ROLE_OWNER,
        ]);

        // Create initial team settings
        $oldSettings = EmailSettingsModel::create([
            'team_id' => $team->id,
            'host' => 'old.smtp.com',
            'port' => 587,
            'from_address' => 'old@company.com',
            'from_name' => 'Old Settings',
            'is_active' => true,
        ]);

        // Save new settings
        Livewire::actingAs($owner)
            ->test(EmailSettings::class)
            ->set('host', 'new.smtp.com')
            ->set('port', 587)
            ->set('from_address', 'new@company.com')
            ->set('from_name', 'New Settings')
            ->call('save');

        // Old settings should be deactivated
        expect($oldSettings->fresh()->is_active)->toBeFalse();

        // New settings should be active
        $newSettings = EmailSettingsModel::activeForTeam($team);
        expect($newSettings->host)->toBe('new.smtp.com');
        expect($newSettings->is_active)->toBeTrue();
    });
});
