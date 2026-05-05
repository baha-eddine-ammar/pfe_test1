<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\NewStaffRegistrationNotification;
use App\Notifications\PendingApprovalNotification;
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

    public function test_new_users_can_register_with_any_valid_email_as_pending_staff_by_default(): void
    {
        Notification::fake();

        $admin = User::factory()->departmentHead()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test.user@gmail.com',
            'department' => 'Systems',
            'phone_number' => '0612345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('register.pending', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'test.user@gmail.com',
            'department' => 'Systems',
            'phone_number' => '0612345678',
            'role' => 'staff',
            'status' => 'pending',
            'is_approved' => false,
        ]);
        $this->assertFalse(User::query()->where('email', 'test.user@gmail.com')->first()->hasVerifiedEmail());
        Notification::assertSentTo(User::query()->where('email', 'test.user@gmail.com')->first(), PendingApprovalNotification::class);
        Notification::assertSentTo($admin, NewStaffRegistrationNotification::class);
    }

    public function test_pending_staff_can_not_access_dashboard_until_approved_and_verified(): void
    {
        $pendingStaff = User::factory()->pending()->create([
            'role' => 'staff',
        ]);

        $this->actingAs($pendingStaff)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_approved_but_unverified_staff_are_redirected_to_email_verification_notice(): void
    {
        $user = User::factory()->unverified()->create([
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }
}
