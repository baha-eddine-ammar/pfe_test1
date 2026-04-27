<?php

namespace Tests\Feature\Dashboard;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServerCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_points_users_to_the_dedicated_server_section(): void
    {
        $user = User::factory()->create();

        $server = Server::query()->create([
            'name' => 'SRV-REAL-01',
            'identifier' => 'srv-real-01',
            'api_token' => 'secret-token',
            'last_seen_at' => now(),
        ]);

        $server->metrics()->create([
            'cpu_percent' => 56.4,
            'ram_used_mb' => 8192,
            'ram_total_mb' => 16384,
            'disk_used_gb' => 320,
            'disk_total_gb' => 1000,
            'net_rx_mbps' => 11.5,
            'net_tx_mbps' => 4.3,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Server section')
            ->assertSeeText('Live PC telemetry has moved')
            ->assertSeeText('Open Server Section')
            ->assertDontSeeText('Local workstation telemetry')
            ->assertDontSeeText('SRV-REAL-01');
    }

    public function test_server_section_feed_returns_the_live_registered_server_payload(): void
    {
        $user = User::factory()->create();

        $server = Server::query()->create([
            'name' => 'SRV-REAL-02',
            'identifier' => 'srv-real-02',
            'api_token' => 'secret-token',
            'last_seen_at' => now(),
        ]);

        $server->metrics()->create([
            'cpu_percent' => 48.2,
            'ram_used_mb' => 6144,
            'ram_total_mb' => 16384,
            'disk_used_gb' => 280,
            'disk_total_gb' => 1000,
            'storage_free_gb' => 720,
            'storage_total_gb' => 1000,
            'temperature_c' => 52.7,
            'network_connected' => true,
            'network_name' => 'Wi-Fi',
            'network_speed_mbps' => 300,
            'network_ipv4' => '192.168.1.44',
            'net_rx_mbps' => 0,
            'net_tx_mbps' => 0,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('servers.feed', $server));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'identifier',
                'status',
                'lastSeenLabel',
                'narrative',
                'metrics' => [
                    ['key', 'label', 'value', 'progress', 'color'],
                ],
            ]);

        $response->assertJsonPath('name', 'SRV-REAL-02');
        $this->assertCount(6, $response->json('metrics'));
    }
}
