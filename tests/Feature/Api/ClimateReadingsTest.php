<?php

namespace Tests\Feature\Api;

use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClimateReadingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_climate_payload_is_stored_in_sensor_readings(): void
    {
        config(['sensors.api_token' => 'secret-token']);

        $response = $this->withHeaders([
            'X-Sensor-Token' => 'secret-token',
        ])->postJson(route('api.climate-readings.store'), [
            'device_id' => 'esp32-sht-01',
            'temperature_c' => 22.4,
            'humidity_percent' => 45.0,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reading.device_id', 'esp32-sht-01')
            ->assertJsonPath('reading.temperature_c', 22.4)
            ->assertJsonPath('reading.humidity_percent', 45);

        $this->assertDatabaseHas('sensor_readings', [
            'device_id' => 'esp32-sht-01',
            'temperature' => 22.4,
            'humidity' => 45.0,
        ]);
    }

    public function test_invalid_sensor_token_is_rejected_for_climate_payloads(): void
    {
        config(['sensors.api_token' => 'secret-token']);

        $response = $this->withHeaders([
            'X-Sensor-Token' => 'wrong-token',
        ])->postJson(route('api.climate-readings.store'), [
            'device_id' => 'esp32-sht-01',
            'temperature_c' => 22.4,
            'humidity_percent' => 45.0,
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid sensor credentials.');

        $this->assertDatabaseCount('sensor_readings', 0);
    }

    public function test_latest_climate_reading_endpoint_returns_the_newest_sensor_reading(): void
    {
        SensorReading::query()->create([
            'device_id' => 'old-esp32-sht',
            'temperature' => 20.0,
            'humidity' => 40.0,
            'recorded_at' => now()->subMinute(),
        ]);

        SensorReading::query()->create([
            'device_id' => 'esp32-sht-01',
            'temperature' => 22.4,
            'humidity' => 45.0,
            'recorded_at' => now(),
        ]);

        $response = $this->getJson(route('api.climate-readings.latest'));

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reading.device_id', 'esp32-sht-01')
            ->assertJsonPath('reading.temperature_c', 22.4)
            ->assertJsonPath('reading.humidity_percent', 45);
    }

    public function test_dashboard_telemetry_uses_latest_climate_reading(): void
    {
        $user = User::factory()->create();

        SensorReading::query()->create([
            'device_id' => 'esp32-sht-01',
            'temperature' => 22.4,
            'humidity' => 45.0,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.telemetry'));

        $response
            ->assertOk()
            ->assertJsonPath('latest.temperature', 22.4)
            ->assertJsonPath('latest.humidity', 45);
    }
}
