<?php

namespace Tests\Feature\Servers;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_head_can_create_a_server(): void
    {
        $admin = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('servers.store'), [
            'name' => 'SRV-APP-01',
            'identifier' => 'srv-app-01',
        ]);

        $server = Server::query()->first();

        $response->assertRedirect(route('servers.show', $server));
        $this->assertNotNull($server);
        $this->assertNotEmpty($server->api_token);
        $this->assertSame('srv-app-01', $server->identifier);
    }

    public function test_staff_cannot_access_server_create_page(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('servers.create'))
            ->assertForbidden();
    }

    public function test_verified_user_can_view_server_list(): void
    {
        $user = User::factory()->create();

        Server::query()->create([
            'name' => 'SRV-DB-01',
            'identifier' => 'srv-db-01',
            'api_token' => 'token-123',
        ]);

        $this->actingAs($user)
            ->get(route('servers.index'))
            ->assertOk()
            ->assertSeeText('Servers')
            ->assertSeeText('SRV-DB-01');
    }
}
