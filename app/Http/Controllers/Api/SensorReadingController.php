<?php

namespace App\Http\Controllers\Api;

use App\Events\SensorTelemetryUpdated;
use App\Http\Controllers\Controller;
use App\Models\SensorReading;
use App\Services\AuditLogService;
use App\Services\EnvironmentalTelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorReadingController extends Controller
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
            'temperature' => ['required', 'numeric', 'between:-20,80'],
            'humidity' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $reading = SensorReading::create([
            'device_id' => $validated['device_id'],
            'temperature' => round((float) $validated['temperature'], 2),
            'humidity' => isset($validated['humidity']) ? round((float) $validated['humidity'], 2) : null,
            'recorded_at' => now(),
        ]);

        $this->auditLogService->record('sensor.reading.ingested', $reading, [
            'device_id' => $reading->device_id,
            'temperature' => $reading->temperature,
            'humidity' => $reading->humidity,
            'recorded_at' => optional($reading->recorded_at)->toIso8601String(),
        ]);

        SensorTelemetryUpdated::dispatch(
            $this->environmentalTelemetryService->dashboardTrend()
        );

        return response()->json([
            'message' => 'Sensor reading stored successfully.',
            'reading_id' => $reading->id,
        ], 201);
    }
}
