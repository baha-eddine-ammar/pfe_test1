<?php

namespace Tests\Feature\Api;

use App\Models\PowerReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PowerReadingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_power_payload_is_stored(): void
    {
        config(['sensors.api_token' => 'secret-token']);

        $response = $this->withHeaders([
            'X-Sensor-Token' => 'secret-token',
        ])->postJson(route('api.power-readings.store'), [
            'device_id' => 'ttgo-pzem-01',
            'voltage_v' => 233.9,
            'current_a' => 0.00,
            'power_w' => 0.8,
            'energy_kwh' => 0.0,
            'frequency_hz' => 50.0,
            'power_factor' => 0.0,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reading.device_id', 'ttgo-pzem-01')
            ->assertJsonPath('reading.voltage_v', 233.9)
            ->assertJsonPath('reading.current_a', 0)
            ->assertJsonPath('reading.power_w', 0.8);

        $this->assertDatabaseHas('power_readings', [
            'device_id' => 'ttgo-pzem-01',
            'voltage_v' => 233.9,
            'current_a' => 0,
            'power_w' => 0.8,
        ]);
    }

    public function test_invalid_sensor_token_is_rejected(): void
    {
        config(['sensors.api_token' => 'secret-token']);

        $response = $this->withHeaders([
            'X-Sensor-Token' => 'wrong-token',
        ])->postJson(route('api.power-readings.store'), [
            'device_id' => 'ttgo-pzem-01',
            'voltage_v' => 233.9,
            'current_a' => 0.00,
            'power_w' => 0.8,
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid sensor credentials.');

        $this->assertDatabaseCount('power_readings', 0);
    }

    public function test_dashboard_telemetry_includes_latest_power_reading(): void
    {
        $user = User::factory()->create();

        PowerReading::query()->create([
            'device_id' => 'ttgo-pzem-01',
            'voltage_v' => 233.9,
            'current_a' => 0.00,
            'power_w' => 0.8,
            'energy_kwh' => 0.0,
            'frequency_hz' => 50.0,
            'power_factor' => 0.0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.telemetry'));

        $response
            ->assertOk()
            ->assertJsonPath('latest.power', 0.8)
            ->assertJsonPath('latest.voltage', 233.9)
            ->assertJsonPath('latest.current', 0)
            ->assertJsonPath('latest.power_reading.device_id', 'ttgo-pzem-01')
            ->assertJsonStructure([
                'temperature',
                'humidity',
                'power',
                'powerLabels',
                'powerTimestamps',
                'latest' => [
                    'temperature',
                    'humidity',
                    'power',
                    'voltage',
                    'current',
                    'power_reading',
                ],
            ]);
    }
}
