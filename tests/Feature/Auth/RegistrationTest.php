<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@draxmailer',
            'department' => 'Systems',
            'role' => 'it_staff',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'test@draxmailer',
            'department' => 'Systems',
            'role' => 'it_staff',
            'is_approved' => true,
        ]);
    }

    public function test_department_head_registration_starts_pending_approval(): void
    {
        $response = $this->post('/register', [
            'name' => 'Pending Head',
            'email' => 'manager@draxmailer',
            'department' => 'Network',
            'role' => 'department_head',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'manager@draxmailer')->first();

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertFalse((bool) $user?->is_approved);
    }
}
