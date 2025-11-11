<?php

use App\Models\User;
use App\Models\Team;
use App\Models\TeamInvitation;
use Tests\Traits\UsesCleanDatabase;
use Illuminate\Support\Str;

uses(UsesCleanDatabase::class);

beforeEach(function () {
    $this->setUpCleanDatabase();
});

// Scenario 1: New User Registration Flow
test('new user can register via invitation and auto-accept happens after registration', function () {
    $owner = User::factory()->create(['email' => 'owner-scenario1@test.com']);
    $team = Team::factory()->create(['created_by_user_id' => $owner->id, 'name' => 'Test Team 1']);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'newuser-scenario1@test.com',
        'role' => 'viewer',
        'invited_by_user_id' => $owner->id,
        'token' => Str::random(32),
        'expires_at' => now()->addDays(7),
    ]);

    // Access invitation page (not logged in)
    $response = $this->get("/team/invitations/{$invitation->token}");
    $response->assertStatus(200);
    $response->assertInertia(
        fn ($page) => $page
            ->component('auth/AcceptInvitation')
            ->has('invitation')
            ->where('invitation.email', 'newuser-scenario1@test.com')
            ->where('invitation.team.name', 'Test Team 1')
            ->where('existing_user', false)
    );

    // Register via invitation
    $registerResponse = $this->post("/team/invitations/{$invitation->token}/register", [
        'name' => 'New User Scenario 1',
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
    ]);

    // Should redirect to dashboard
    $registerResponse->assertRedirect('/dashboard');
    $registerResponse->assertSessionHas('success', "Welcome! You've successfully joined the Test Team 1 team.");

    // Verify user was created and auto-accepted
    $newUser = User::where('email', 'newuser-scenario1@test.com')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->teams->contains($team->id))->toBeTrue();

    // Verify invitation was marked as accepted (not deleted)
    $acceptedInvitation = TeamInvitation::find($invitation->id);
    expect($acceptedInvitation)->not->toBeNull();
    expect($acceptedInvitation->accepted_at)->not->toBeNull();
});

// Scenario 2: Existing User Login Flow
test('existing user can log in via invitation and auto-accept happens', function () {
    $owner = User::factory()->create(['email' => 'owner-scenario2@test.com']);
    $team = Team::factory()->create(['created_by_user_id' => $owner->id, 'name' => 'Test Team 2']);
    $existingUser = User::factory()->create(['email' => 'existing-scenario2@test.com']);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'existing-scenario2@test.com',
        'role' => 'admin',
        'invited_by_user_id' => $owner->id,
        'token' => Str::random(32),
        'expires_at' => now()->addDays(7),
    ]);

    // Access invitation page (not logged in)
    $response = $this->get("/team/invitations/{$invitation->token}");
    $response->assertStatus(200);
    $response->assertInertia(
        fn ($page) => $page
            ->component('auth/AcceptInvitation')
            ->where('existing_user', true)
            ->where('invitation.email', 'existing-scenario2@test.com')
    );

    // Log in (simulating user login)
    $this->actingAs($existingUser);

    // Access invitation again - should auto-accept
    $autoAcceptResponse = $this->get("/team/invitations/{$invitation->token}");

    // Should redirect to team settings with success message
    $autoAcceptResponse->assertRedirect('/settings/team');
    $autoAcceptResponse->assertSessionHas('success', "You've successfully joined the Test Team 2 team!");

    // Verify user was added to team
    expect($existingUser->teams->contains($team->id))->toBeTrue();

    // Verify invitation was marked as accepted (not deleted)
    $acceptedInvitation = TeamInvitation::find($invitation->id);
    expect($acceptedInvitation)->not->toBeNull();
    expect($acceptedInvitation->accepted_at)->not->toBeNull();
});

// Scenario 3: Already Logged-In User Flow
test('logged-in user with matching email auto-accepts immediately', function () {
    $owner = User::factory()->create(['email' => 'owner-scenario3@test.com']);
    $team = Team::factory()->create(['created_by_user_id' => $owner->id, 'name' => 'Test Team 3']);
    $loggedUser = User::factory()->create(['email' => 'loggedin-scenario3@test.com']);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'loggedin-scenario3@test.com',
        'role' => 'admin',
        'invited_by_user_id' => $owner->id,
        'token' => Str::random(32),
        'expires_at' => now()->addDays(7),
    ]);

    // Log in as the invited user
    $this->actingAs($loggedUser);

    // Access invitation - should auto-accept immediately
    $response = $this->get("/team/invitations/{$invitation->token}");

    // Should redirect to team settings
    $response->assertRedirect('/settings/team');
    $response->assertSessionHas('success', "You've successfully joined the Test Team 3 team!");

    // Verify user was added to team
    expect($loggedUser->teams->contains($team->id))->toBeTrue();

    // Verify invitation was marked as accepted (not deleted)
    $acceptedInvitation = TeamInvitation::find($invitation->id);
    expect($acceptedInvitation)->not->toBeNull();
    expect($acceptedInvitation->accepted_at)->not->toBeNull();
});

// Scenario 4: Wrong User Logged In
test('logged-in user with different email sees invitation page without auto-accept', function () {
    $owner = User::factory()->create(['email' => 'owner-scenario4@test.com']);
    $team = Team::factory()->create(['created_by_user_id' => $owner->id, 'name' => 'Test Team 4']);
    $wrongUser = User::factory()->create(['email' => 'wronguser-scenario4@test.com']);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'differentemail-scenario4@test.com',
        'role' => 'viewer',
        'invited_by_user_id' => $owner->id,
        'token' => Str::random(32),
        'expires_at' => now()->addDays(7),
    ]);

    // Log in as different user
    $this->actingAs($wrongUser);

    // Access invitation - should show invitation page, not auto-accept
    $response = $this->get("/team/invitations/{$invitation->token}");

    // Should render the invitation page
    $response->assertStatus(200);
    $response->assertInertia(
        fn ($page) => $page
            ->component('auth/AcceptInvitation')
            ->where('invitation.email', 'differentemail-scenario4@test.com')
            ->where('existing_user', false)
    );

    // Verify user was NOT added to team
    expect($wrongUser->teams->contains($team->id))->toBeFalse();

    // Verify invitation still exists
    expect(TeamInvitation::find($invitation->id))->not->toBeNull();
});

// Scenario 5: Expired Invitation
test('accessing expired invitation shows error message', function () {
    $owner = User::factory()->create(['email' => 'owner-scenario5@test.com']);
    $team = Team::factory()->create(['created_by_user_id' => $owner->id, 'name' => 'Test Team 5']);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'expireduser@test.com',
        'role' => 'viewer',
        'invited_by_user_id' => $owner->id,
        'token' => Str::random(32),
        'expires_at' => now()->subDay(), // Already expired
    ]);

    // Access invitation
    $response = $this->get("/team/invitations/{$invitation->token}");

    // Should redirect to home with error
    $response->assertRedirect('/');
    $response->assertSessionHas('error', 'This invitation is invalid or has expired.');
});

// Scenario 6: Invalid Token
test('accessing invalid token shows error message', function () {
    $response = $this->get('/team/invitations/invalid-token-12345');

    $response->assertRedirect('/');
    $response->assertSessionHas('error', 'This invitation is invalid or has expired.');
});

// Scenario 7: Verify team members list after acceptance
test('new team member appears in team members list after acceptance', function () {
    $owner = User::factory()->create(['email' => 'owner-scenario7@test.com']);
    $team = Team::factory()->create(['created_by_user_id' => $owner->id, 'name' => 'Test Team 7']);
    $newUser = User::factory()->create(['email' => 'newmember-scenario7@test.com']);

    $invitation = TeamInvitation::create([
        'team_id' => $team->id,
        'email' => 'newmember-scenario7@test.com',
        'role' => 'admin',
        'invited_by_user_id' => $owner->id,
        'token' => Str::random(32),
        'expires_at' => now()->addDays(7),
    ]);

    // Log in and access invitation to trigger auto-accept
    $this->actingAs($newUser);
    $this->get("/team/invitations/{$invitation->token}");

    // Visit team details page
    $response = $this->get("/settings/team/{$team->id}");

    $response->assertStatus(200);
    $response->assertInertia(
        fn ($page) => $page
            ->component('Settings/Team')
            ->has('members')
    );
});
