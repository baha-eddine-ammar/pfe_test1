<?php

namespace Tests\Feature\Api;

use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_metrics_payload_is_stored(): void
    {
        $server = Server::query()->create([
            'name' => 'SRV-APP-01',
            'identifier' => 'srv-app-01',
            'api_token' => 'secret-token',
        ]);

        $response = $this->withHeaders([
            'X-Server-Token' => 'secret-token',
        ])->postJson(route('api.server-metrics.store'), [
            'identifier' => 'srv-app-01',
            'cpu_percent' => 32.5,
            'ram_used_mb' => 4096,
            'ram_total_mb' => 8192,
            'disk_used_gb' => 200.5,
            'disk_total_gb' => 500,
            'net_rx_mbps' => 8.2,
            'net_tx_mbps' => 3.4,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('server.identifier', 'srv-app-01');

        $this->assertDatabaseHas('server_metrics', [
            'server_id' => $server->id,
            'ram_used_mb' => 4096,
        ]);

        $this->assertNotNull($server->fresh()->last_seen_at);
    }

    public function test_invalid_token_is_rejected(): void
    {
        Server::query()->create([
            'name' => 'SRV-APP-01',
            'identifier' => 'srv-app-01',
            'api_token' => 'secret-token',
        ]);

        $response = $this->withHeaders([
            'X-Server-Token' => 'wrong-token',
        ])->postJson(route('api.server-metrics.store'), [
            'identifier' => 'srv-app-01',
            'cpu_percent' => 32.5,
            'ram_used_mb' => 4096,
            'ram_total_mb' => 8192,
            'disk_used_gb' => 200.5,
            'disk_total_gb' => 500,
            'net_rx_mbps' => 8.2,
            'net_tx_mbps' => 3.4,
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid server credentials.');

        $this->assertDatabaseCount('server_metrics', 0);
    }
}
