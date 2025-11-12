<?php
use App\Models\User;
describe('Login', function () {
    test('login page renders successfully', function () {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
        );
    });
    test('login page has correct elements', function () {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('Email')
            ->assertSee('Password')
            ->assertSee('Remember me')
            ->assertSee('Log in');
    });
    test('user can login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $response = $this->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    });
    test('login fails with invalid password', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $response = $this->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
        $this->assertGuest();
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->has('errors')
        );
    });
    test('login fails with non-existent email', function () {
        $response = $this->post(route('login.store'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);
        $this->assertGuest();
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->has('errors')
        );
    });
    test('login requires email field', function () {
        $response = $this->post(route('login.store'), [
            'email' => '',
            'password' => 'password123',
        ]);
        $this->assertGuest();
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->has('errors.email')
        );
    });
    test('login requires password field', function () {
        $response = $this->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => '',
        ]);
        $this->assertGuest();
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
            ->has('errors.password')
        );
    });
    test('login redirects authenticated users to dashboard', function () {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->get(route('login'));
        $response->assertRedirect(route('dashboard'));
    });
    test('remember me functionality is available', function () {
        $response = $this->get(route('login'));
        $response->assertSee('Remember me');
    });
    test('forgot password link is available', function () {
        $response = $this->get(route('login'));
        $response->assertSee('Forgot password')
            ->assertSee(route('password.request'));
    });
    test('registration link is available', function () {
        $response = $this->get(route('login'));
        $response->assertSee('Register')
            ->assertSee(route('register'));
    });
    test('login persists user session', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $response = $this->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        // Check that user is authenticated
        $this->assertAuthenticated();
        // Verify we can access protected routes
        $dashboardResponse = $this->get(route('dashboard'));
        $dashboardResponse->assertStatus(200);
    });
})->group('auth', 'login');
