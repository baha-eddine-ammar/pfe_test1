<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_approved_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_approved_users_can_authenticate_with_mixed_case_email_input(): void
    {
        $user = User::factory()->create([
            'email' => 'mixed.case@draxmailer',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $response = $this->post('/login', [
            'email' => '  Mixed.Case@Draxmailer  ',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_pending_users_can_not_authenticate(): void
    {
        $user = User::factory()->create([
            'status' => 'pending',
            'is_approved' => false,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'Your account is pending approval.',
        ]);
    }

    public function test_rejected_users_can_not_authenticate(): void
    {
        $user = User::factory()->create([
            'status' => 'rejected',
            'is_approved' => false,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'Your account does not currently have access.',
        ]);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
