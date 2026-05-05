<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\LoginTwoFactorCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_approved_staff_are_redirected_to_the_two_factor_challenge_after_password_login(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.challenge', absolute: false));
        Notification::assertSentTo($user, LoginTwoFactorCodeNotification::class);
    }

    public function test_approved_users_can_start_two_factor_with_mixed_case_email_input(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'mixed.case@draxmailer',
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $response = $this->post('/login', [
            'email' => '  Mixed.Case@Draxmailer  ',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.challenge', absolute: false));
        Notification::assertSentTo($user, LoginTwoFactorCodeNotification::class);
    }

    public function test_pending_users_can_not_authenticate(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'staff',
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
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $user->id,
        ]);
        Notification::assertNotSentTo($user, LoginTwoFactorCodeNotification::class);
    }

    public function test_rejected_users_can_not_authenticate(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'staff',
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
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $user->id,
        ]);
        Notification::assertNotSentTo($user, LoginTwoFactorCodeNotification::class);
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

    public function test_department_heads_are_redirected_to_the_two_factor_challenge_after_password_login(): void
    {
        Notification::fake();

        $user = User::factory()->departmentHead()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.challenge', absolute: false));
        Notification::assertSentTo($user, LoginTwoFactorCodeNotification::class);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
