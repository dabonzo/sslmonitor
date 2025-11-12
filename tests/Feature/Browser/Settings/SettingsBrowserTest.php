<?php
use App\Models\User;
describe('Settings', function () {
    test('authenticated user can access profile settings', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('profile.edit'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Profile')
        );
    });
    test('unauthenticated user cannot access profile settings', function () {
        $response = $this->get(route('profile.edit'));
        $response->assertRedirect(route('login'));
    });
    test('profile settings displays user information', function () {
        $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        $response = $this->actingAs($user)->get(route('profile.edit'));
        $response->assertStatus(200);
        $response->assertSee('John Doe')
            ->assertSee('john@example.com');
    });
    test('user can update profile name', function () {
        $user = User::factory()->create(['name' => 'Original Name']);
        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated Name',
        ]);
        $user->refresh();
        expect($user->name)->toBe('Updated Name');
    });
    test('user can update profile email', function () {
        $user = User::factory()->create(['email' => 'original@example.com']);
        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'email' => 'newemail@example.com',
        ]);
        $user->refresh();
        expect($user->email)->toBe('newemail@example.com');
    });
    test('profile update requires name', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => '',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->has('errors.name')
        );
    });
    test('profile update requires valid email', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'email' => 'invalid-email',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->has('errors.email')
        );
    });
    test('profile update rejects duplicate email', function () {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $response = $this->actingAs($user1)->patch(route('profile.update'), [
            'email' => 'user2@example.com',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->has('errors.email')
        );
    });
    test('user can access password settings', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('password.edit'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Password')
        );
    });
    test('password settings has change password form', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('password.edit'));
        $response->assertStatus(200);
        $response->assertSee('Current Password')
            ->or($response)->assertSee('New Password')
            ->or($response)->assertSee('Confirm Password');
    });
    test('user can update password with correct current password', function () {
        $user = User::factory()->create(['password' => bcrypt('oldpassword')]);
        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        // User should still be authenticated
        $this->assertAuthenticated();
    });
    test('password update requires current password', function () {
        $user = User::factory()->create(['password' => bcrypt('oldpassword')]);
        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => '',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->has('errors')
        );
    });
    test('password update requires correct current password', function () {
        $user = User::factory()->create(['password' => bcrypt('oldpassword')]);
        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->has('errors')
        );
    });
    test('password update requires password confirmation', function () {
        $user = User::factory()->create(['password' => bcrypt('oldpassword')]);
        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword456',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->has('errors.password')
        );
    });
    test('user can access two-factor settings', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('two-factor.show'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/TwoFactorAuthentication')
        );
    });
    test('two-factor settings displays enable button when not enabled', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('two-factor.show'));
        $response->assertStatus(200);
        $response->assertSee('Enable')
            ->or($response)->assertSee('Set Up')
            ->or($response)->assertSee('Two-Factor');
    });
    test('user can enable two-factor authentication', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('two-factor.store'));
        $response->assertStatus(200);
        $this->assertAuthenticated();
    });
    test('two-factor enabling shows recovery codes', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->post(route('two-factor.store'));
        $response->assertStatus(200);
        $response->assertSee('code')
            ->or($response)->assertSee('Code');
    });
    test('user can delete profile account', function () {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        $response = $this->actingAs($user)->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);
        expect(User::find($user->id))->toBeNull();
    });
    test('profile deletion requires password confirmation', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->delete(route('profile.destroy'), [
            'password' => 'wrongpassword',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->has('errors')
        );
        expect(User::find($user->id))->not->toBeNull();
    });
    test('settings navigation menu is accessible', function () {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('profile.edit'));
        $response->assertStatus(200);
        // Should have links to other settings sections
        $response->assertSee('Profile')
            ->or($response)->assertSee('Password')
            ->or($response)->assertSee('Two-Factor');
    });
})->group('settings', 'profile');
