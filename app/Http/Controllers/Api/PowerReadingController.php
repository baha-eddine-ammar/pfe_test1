<?php

namespace App\Http\Controllers\Api;

use App\Events\SensorTelemetryUpdated;
use App\Http\Controllers\Controller;
use App\Models\PowerReading;
use App\Services\AuditLogService;
use App\Services\EnvironmentalTelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PowerReadingController extends Controller
{
    public function __construct(
        private readonly EnvironmentalTelemetryService $environmentalTelemetryService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $configuredToken = (string) config('sensors.api_token');
        $providedToken = (string) $request->header('X-Sensor-Token');

        if ($configuredToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'message' => 'Invalid sensor credentials.',
            ], 401);
        }

        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:255'],
            'voltage_v' => ['required', 'numeric', 'between:0,500'],
            'current_a' => ['required', 'numeric', 'min:0'],
            'power_w' => ['required', 'numeric', 'min:0'],
            'energy_kwh' => ['nullable', 'numeric', 'min:0'],
            'frequency_hz' => ['nullable', 'numeric', 'between:0,100'],
            'power_factor' => ['nullable', 'numeric', 'between:0,1'],
        ]);

        $reading = PowerReading::query()->create([
            'device_id' => $validated['device_id'],
            'voltage_v' => round((float) $validated['voltage_v'], 2),
            'current_a' => round((float) $validated['current_a'], 3),
            'power_w' => round((float) $validated['power_w'], 2),
            'energy_kwh' => isset($validated['energy_kwh']) ? round((float) $validated['energy_kwh'], 4) : null,
            'frequency_hz' => isset($validated['frequency_hz']) ? round((float) $validated['frequency_hz'], 2) : null,
            'power_factor' => isset($validated['power_factor']) ? round((float) $validated['power_factor'], 3) : null,
        ]);

        $this->auditLogService->record('power.reading.ingested', $reading, [
            'device_id' => $reading->device_id,
            'voltage_v' => $reading->voltage_v,
            'current_a' => $reading->current_a,
            'power_w' => $reading->power_w,
            'created_at' => optional($reading->created_at)->toIso8601String(),
        ]);

        SensorTelemetryUpdated::dispatch(
            $this->environmentalTelemetryService->dashboardTrend()
        );

        return response()->json([
            'ok' => true,
            'reading' => [
                'device_id' => $reading->device_id,
                'voltage_v' => $reading->voltage_v,
                'current_a' => $reading->current_a,
                'power_w' => $reading->power_w,
                'energy_kwh' => $reading->energy_kwh,
                'frequency_hz' => $reading->frequency_hz,
                'power_factor' => $reading->power_factor,
                'created_at' => optional($reading->created_at)->toIso8601String(),
            ],
        ], 201);
    }
}
