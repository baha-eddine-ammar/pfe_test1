<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_department_head_can_view_the_users_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
    }

    public function test_staff_can_not_view_the_users_page(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($staff)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_department_head_can_approve_a_pending_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $pendingUser = User::factory()->create([
            'role' => 'staff',
            'status' => 'pending',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.users.approve', $pendingUser));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'status' => 'approved',
            'is_approved' => true,
        ]);
    }

    public function test_department_head_can_promote_and_demote_users(): void
    {
        $admin = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $staffUser = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $this->actingAs($admin)->patch(route('admin.users.promote', $staffUser))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $staffUser->id,
            'role' => 'department_head',
        ]);

        $this->actingAs($admin)->patch(route('admin.users.demote', $staffUser))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $staffUser->id,
            'role' => 'staff',
        ]);
    }
}
