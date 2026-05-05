<?php

namespace Tests\Feature\Auth;

use App\Models\LoginTwoFactorChallenge;
use App\Models\User;
use App\Notifications\LoginTwoFactorCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DepartmentHeadTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_staff_login_redirects_to_two_factor_challenge(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge', absolute: false));

        $this->assertGuest();
        $this->assertDatabaseHas('login_two_factor_challenges', [
            'user_id' => $user->id,
            'attempts' => 0,
        ]);
        Notification::assertSentTo($user, LoginTwoFactorCodeNotification::class);
    }

    public function test_approved_staff_can_not_access_dashboard_before_two_factor_is_completed(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge', absolute: false));

        $this->get(route('dashboard'))
            ->assertRedirect(route('login', absolute: false));

        $this->get('/login')
            ->assertRedirect(route('two-factor.challenge', absolute: false));

        $this->assertGuest();
    }

    public function test_valid_otp_completes_staff_login(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $code = $this->latestOtpFor($user);

        $response = $this->post(route('two-factor.verify'), [
            'code' => $code,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $user->id,
        ]);
    }

    public function test_staff_wrong_otp_fails(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->from(route('two-factor.challenge'))
            ->post(route('two-factor.verify'), [
                'code' => $this->wrongOtpFor($user),
            ])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseHas('login_two_factor_challenges', [
            'user_id' => $user->id,
            'attempts' => 1,
        ]);
    }

    public function test_staff_expired_otp_fails(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        LoginTwoFactorChallenge::query()
            ->where('user_id', $user->id)
            ->update(['expires_at' => now()->subMinute()]);

        $code = $this->latestOtpFor($user);

        $this->from(route('two-factor.challenge'))
            ->post(route('two-factor.verify'), [
                'code' => $code,
            ])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $user->id,
        ]);
    }

    public function test_department_head_can_not_access_dashboard_before_two_factor_is_completed(): void
    {
        Notification::fake();

        $user = User::factory()->departmentHead()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge', absolute: false));

        $this->get('/login')
            ->assertRedirect(route('two-factor.challenge', absolute: false));

        $this->assertGuest();
        Notification::assertSentTo($user, LoginTwoFactorCodeNotification::class);
    }

    public function test_valid_otp_completes_department_head_login(): void
    {
        Notification::fake();

        $user = User::factory()->departmentHead()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $code = $this->latestOtpFor($user);

        $response = $this->post(route('two-factor.verify'), [
            'code' => $code,
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $user->id,
        ]);
    }

    public function test_expired_otp_fails(): void
    {
        Notification::fake();

        $user = User::factory()->departmentHead()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        LoginTwoFactorChallenge::query()
            ->where('user_id', $user->id)
            ->update(['expires_at' => now()->subMinute()]);

        $code = $this->latestOtpFor($user);

        $this->from(route('two-factor.challenge'))
            ->post(route('two-factor.verify'), [
                'code' => $code,
            ])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_too_many_wrong_otp_attempts_clear_the_challenge(): void
    {
        Notification::fake();

        $user = User::factory()->departmentHead()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->from(route('two-factor.challenge'))
                ->post(route('two-factor.verify'), [
                    'code' => $this->wrongOtpFor($user),
                ]);
        }

        $this->assertGuest();
        $this->assertDatabaseMissing('login_two_factor_challenges', [
            'user_id' => $user->id,
        ]);
    }

    public function test_resend_otp_observes_a_cooldown_and_then_succeeds(): void
    {
        Notification::fake();

        $user = User::factory()->departmentHead()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->from(route('two-factor.challenge'))
            ->post(route('two-factor.resend'))
            ->assertSessionHasErrors('code');

        $this->travel(61)->seconds();

        $this->post(route('two-factor.resend'))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        Notification::assertSentToTimes($user, LoginTwoFactorCodeNotification::class, 2);
    }

    private function latestOtpFor(User $user): string
    {
        $codes = collect();

        Notification::assertSentTo($user, LoginTwoFactorCodeNotification::class, function ($notification, array $channels) use (&$codes, $user) {
            $mail = $notification->toMail($user);
            $code = collect($mail->introLines)
                ->first(fn (string $line) => preg_match('/^\d{6}$/', $line) === 1);

            if ($code) {
                $codes->push($code);
            }

            return true;
        });

        return (string) $codes->last();
    }

    private function wrongOtpFor(User $user): string
    {
        return $this->latestOtpFor($user) === '000000' ? '111111' : '000000';
    }
}
