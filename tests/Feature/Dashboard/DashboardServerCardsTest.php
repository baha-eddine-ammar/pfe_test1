<?php

namespace Tests\Feature\Dashboard;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServerCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_database_backed_server_cards_when_servers_exist(): void
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
            ->assertSeeText('SRV-REAL-01')
            ->assertSeeText('srv-real-01')
            ->assertSeeText('56.4%');
    }
}
