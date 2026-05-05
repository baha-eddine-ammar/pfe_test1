<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FirstDepartmentHeadSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_screen_shows_first_setup_link_when_no_department_head_exists(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('First time setup? Create the first Department Head')
            ->assertSee(route('department-head.setup.create', absolute: false));
    }

    public function test_register_screen_hides_first_setup_link_when_department_head_exists(): void
    {
        User::factory()->departmentHead()->create();

        $this->get(route('register'))
            ->assertOk()
            ->assertDontSee('First time setup? Create the first Department Head')
            ->assertDontSee(route('department-head.setup.create', absolute: false));
    }

    public function test_first_department_head_setup_screen_can_be_rendered_when_no_department_head_exists(): void
    {
        $this->get(route('department-head.setup.create'))
            ->assertOk();
    }

    public function test_first_department_head_can_be_created_only_when_no_department_head_exists(): void
    {
        Notification::fake();
        config()->set('services.registration.department_head_key', 'bootstrap-key');

        $response = $this->post(route('department-head.setup.store'), [
            'name' => 'Initial Head',
            'email' => 'head@example.com',
            'department' => 'Infrastructure',
            'phone_number' => '+213 555 12 34 56',
            'setup_key' => 'bootstrap-key',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('login', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'head@example.com',
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $user = User::query()->where('email', 'head@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_setup_key_becomes_useless_after_the_first_department_head_exists(): void
    {
        config()->set('services.registration.department_head_key', 'bootstrap-key');

        User::factory()->departmentHead()->create();

        $this->get(route('department-head.setup.create'))
            ->assertNotFound();

        $this->post(route('department-head.setup.store'), [
            'name' => 'Second Head',
            'email' => 'second-head@example.com',
            'department' => 'Infrastructure',
            'phone_number' => '+213 555 22 33 44',
            'setup_key' => 'bootstrap-key',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', [
            'email' => 'second-head@example.com',
            'role' => 'department_head',
        ]);
    }
}
