<?php

namespace Tests\Feature\Auth;

use App\Models\DepartmentHeadInvite;
use App\Models\User;
use App\Notifications\DepartmentHeadInviteNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DepartmentHeadInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_department_head_can_create_an_invite(): void
    {
        Notification::fake();

        $admin = User::factory()->departmentHead()->create();

        $this->actingAs($admin)
            ->post(route('admin.department-head-invites.store'), [
                'invited_email' => 'invitee@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('department_head_invites', [
            'invited_email' => 'invitee@example.com',
            'invited_by_user_id' => $admin->id,
        ]);
        Notification::assertSentOnDemand(DepartmentHeadInviteNotification::class);
    }

    public function test_staff_can_not_create_department_head_invites(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->post(route('admin.department-head-invites.store'), [
                'invited_email' => 'blocked@example.com',
            ])
            ->assertForbidden();
    }

    public function test_reveal_link_shows_the_code_only_once_and_successful_registration_creates_an_unverified_department_head(): void
    {
        Notification::fake();

        $admin = User::factory()->departmentHead()->create();

        $this->actingAs($admin)->post(route('admin.department-head-invites.store'), [
            'invited_email' => 'invitee@example.com',
        ]);
        $this->post(route('logout'));

        $invite = DepartmentHeadInvite::query()->firstOrFail();
        $revealUrl = $this->captureRevealUrl();

        $revealResponse = $this->get($this->relativePath($revealUrl));
        $revealResponse->assertOk();
        $revealResponse->assertSee('Authorization code revealed');

        preg_match('/[A-Z0-9]{10}/', $revealResponse->getContent(), $matches);
        $authorizationCode = $matches[0] ?? null;

        $this->assertNotNull($authorizationCode);

        $this->get($this->relativePath($revealUrl))
            ->assertSee('This code has already been revealed.');

        $registerResponse = $this->post(route('department-head.invites.store', $invite), [
            'email' => 'invitee@example.com',
            'name' => 'Invited Head',
            'department' => 'Security',
            'phone_number' => '+213 555 44 33 22',
            'authorization_code' => $authorizationCode,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $registerResponse->assertRedirect(route('login', absolute: false));
        $user = User::query()->where('email', 'invitee@example.com')->firstOrFail();
        $this->assertSame('department_head', $user->role);
        $this->assertSame('approved', $user->status);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->post(route('department-head.invites.store', $invite), [
            'email' => 'invitee@example.com',
            'name' => 'Reuse Attempt',
            'department' => 'Security',
            'phone_number' => '+213 555 44 33 22',
            'authorization_code' => $authorizationCode,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, User::query()->where('email', 'invitee@example.com')->count());
    }

    public function test_expired_or_revoked_invites_can_not_be_revealed_or_used(): void
    {
        $expiredInvite = DepartmentHeadInvite::factory()->expired()->create();
        $revokedInvite = DepartmentHeadInvite::factory()->revealed()->revoked()->create();

        $this->get(route('department-head.invites.reveal', [
            'departmentHeadInvite' => $expiredInvite,
            'token' => 'reveal-token-123',
        ]))->assertSee('expired');

        $this->get(route('department-head.invites.register', $revokedInvite))
            ->assertSee('revoked');
    }

    public function test_invite_code_is_bound_to_the_invited_email(): void
    {
        Notification::fake();

        $admin = User::factory()->departmentHead()->create();

        $this->actingAs($admin)->post(route('admin.department-head-invites.store'), [
            'invited_email' => 'invitee@example.com',
        ]);
        $this->post(route('logout'));

        $invite = DepartmentHeadInvite::query()->firstOrFail();
        $revealUrl = $this->captureRevealUrl();
        $revealResponse = $this->get($this->relativePath($revealUrl));
        preg_match('/[A-Z0-9]{10}/', $revealResponse->getContent(), $matches);
        $authorizationCode = $matches[0] ?? null;

        $this->post(route('department-head.invites.store', $invite), [
            'email' => 'other@example.com',
            'name' => 'Wrong Email',
            'department' => 'Systems',
            'phone_number' => '+213 555 77 66 55',
            'authorization_code' => $authorizationCode,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');
    }

    private function captureRevealUrl(): string
    {
        $revealUrl = '';

        Notification::assertSentOnDemand(DepartmentHeadInviteNotification::class, function ($notification, array $channels, $notifiable) use (&$revealUrl) {
            $revealUrl = $notification->toMail($notifiable)->actionUrl;

            return true;
        });

        return $revealUrl;
    }

    private function relativePath(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = (string) parse_url($url, PHP_URL_QUERY);

        return $query !== '' ? $path.'?'.$query : $path;
    }
}
