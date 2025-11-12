<?php
use App\Models\User;
describe('Registration', function () {
    test('registration page renders successfully', function () {
        $response = $this->get(route('register'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
        );
    });
    test('registration page has correct form fields', function () {
        $response = $this->get(route('register'));
        $response->assertStatus(200);
        $response->assertSee('Name')
            ->assertSee('Email')
            ->assertSee('Password')
            ->assertSee('Confirm Password')
            ->assertSee('Create Account');
    });
    test('user can register with valid data', function () {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);
        $user = User::where('email', 'newuser@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->name)->toBe('Test User');
    });
    test('registration redirects to verification page', function () {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);
        $response->assertRedirect(route('registration.success'));
    });
    test('registration requires name field', function () {
        $response = $this->post(route('register.store'), [
            'name' => '',
            'email' => 'newuser@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->has('errors.name')
        );
    });
    test('registration requires valid email format', function () {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->has('errors.email')
        );
    });
    test('registration rejects duplicate email', function () {
        User::factory()->create(['email' => 'existing@example.com']);
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->has('errors.email')
        );
    });
    test('registration requires password confirmation match', function () {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'DifferentPassword456',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->has('errors.password')
        );
    });
    test('registration requires secure password', function () {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->has('errors.password')
        );
    });
    test('registered user is not authenticated until email verified', function () {
        $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'SecurePassword123',
            'password_confirmation' => 'SecurePassword123',
        ]);
        $this->assertGuest();
    });
    test('registration redirects authenticated users to dashboard', function () {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->get(route('register'));
        $response->assertRedirect(route('dashboard'));
    });
    test('login link is available on registration page', function () {
        $response = $this->get(route('register'));
        $response->assertSee('Log in')
            ->assertSee(route('login'));
    });
})->group('auth', 'registration');
