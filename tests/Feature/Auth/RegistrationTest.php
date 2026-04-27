<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_register_as_pending_staff_by_default(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@draxmailer',
            'department' => 'Systems',
            'phone_number' => '0612345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionHas('status', 'Your account is pending approval.');
        $this->assertDatabaseHas('users', [
            'email' => 'test@draxmailer',
            'department' => 'Systems',
            'phone_number' => '0612345678',
            'role' => 'staff',
            'status' => 'pending',
            'is_approved' => false,
        ]);
        $this->assertFalse(User::query()->where('email', 'test@draxmailer')->first()->hasVerifiedEmail());
        Notification::assertNothingSent();
    }

    public function test_valid_department_head_key_creates_an_approved_department_head(): void
    {
        config()->set('services.registration.department_head_key', '123456789');
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Head User',
            'email' => 'head@draxmailer',
            'department' => 'Network',
            'phone_number' => '0698765432',
            'department_head_key' => '123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'head@draxmailer')->first();

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'head@draxmailer',
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Notification::assertNotSentTo($user, VerifyEmail::class);
    }
}
