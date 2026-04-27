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
            'description' => 'Primary application workstation',
            'ip_address' => '192.168.1.24',
            'server_type' => 'Workstation',
        ]);

        $server = Server::query()->first();

        $response->assertRedirect(route('servers.show', $server));
        $this->assertNotNull($server);
        $this->assertNotEmpty($server->api_token);
        $this->assertSame('srv-app-01', $server->identifier);
        $this->assertSame('Primary application workstation', $server->description);
        $this->assertSame('192.168.1.24', $server->ip_address);
        $this->assertSame('Workstation', $server->server_type);
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

    public function test_verified_user_sees_an_empty_server_section_until_a_server_is_registered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('servers.index'))
            ->assertOk()
            ->assertSeeText('Server section')
            ->assertSeeText('Monitoring servers')
            ->assertDontSeeText('LOCAL-WORKSTATION')
            ->assertDontSeeText('No servers registered yet');
    }

    public function test_registered_server_appears_in_the_server_section_and_exposes_a_live_feed(): void
    {
        $user = User::factory()->create();

        $server = Server::query()->create([
            'name' => 'SRV-DB-01',
            'identifier' => 'srv-db-01',
            'description' => 'Primary database node',
            'ip_address' => '10.10.10.15',
            'server_type' => 'Database',
            'api_token' => 'token-123',
            'last_seen_at' => now(),
        ]);

        $server->metrics()->create([
            'cpu_percent' => 42.5,
            'ram_used_mb' => 8192,
            'ram_total_mb' => 16384,
            'disk_used_gb' => 320.4,
            'disk_total_gb' => 1000,
            'storage_free_gb' => 679.6,
            'storage_total_gb' => 1000,
            'temperature_c' => 58.3,
            'network_connected' => true,
            'network_name' => 'Ethernet',
            'network_speed_mbps' => 1000,
            'network_ipv4' => '10.10.10.15',
            'net_rx_mbps' => 12.8,
            'net_tx_mbps' => 4.2,
            'uptime_seconds' => 86400,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('servers.index'))
            ->assertOk()
            ->assertSeeText('SRV-DB-01')
            ->assertDontSeeText('LOCAL-WORKSTATION');

        $feed = $this->actingAs($user)->getJson(route('servers.feed', $server));

        $feed->assertOk()
            ->assertJsonPath('name', 'SRV-DB-01')
            ->assertJsonPath('identifier', 'srv-db-01')
            ->assertJsonPath('status', 'Live')
            ->assertJsonCount(6, 'metrics');
    }

    public function test_server_identifier_is_normalized_before_validation_and_storage(): void
    {
        $admin = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('servers.store'), [
            'name' => '  SRV-APP-02  ',
            'identifier' => '  SRV-APP-02  ',
        ]);

        $server = Server::query()->first();

        $response->assertRedirect(route('servers.show', $server));
        $this->assertSame('SRV-APP-02', $server->name);
        $this->assertSame('srv-app-02', $server->identifier);
    }

    public function test_server_identifier_validation_rejects_case_only_duplicates(): void
    {
        $admin = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        Server::query()->create([
            'name' => 'SRV-APP-01',
            'identifier' => 'srv-app-01',
            'api_token' => 'token-123',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('servers.create'))
            ->post(route('servers.store'), [
                'name' => 'SRV-APP-01 Duplicate',
                'identifier' => 'SRV-APP-01',
            ]);

        $response->assertRedirect(route('servers.create'));
        $response->assertSessionHasErrors('identifier');
        $this->assertDatabaseCount('servers', 1);
    }

    public function test_server_api_token_is_hidden_from_default_serialization(): void
    {
        $server = Server::query()->create([
            'name' => 'SRV-DB-01',
            'identifier' => 'srv-db-01',
            'api_token' => 'token-123',
        ]);

        $this->assertArrayNotHasKey('api_token', $server->toArray());
    }
}
