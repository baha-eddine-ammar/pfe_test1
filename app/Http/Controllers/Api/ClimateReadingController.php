<?php

namespace App\Http\Controllers\Api;

use App\Events\SensorTelemetryUpdated;
use App\Http\Controllers\Controller;
use App\Models\SensorReading;
use App\Services\AuditLogService;
use App\Services\EnvironmentalTelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClimateReadingController extends Controller
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
            'temperature_c' => ['required', 'numeric', 'between:-20,80'],
            'humidity_percent' => ['required', 'numeric', 'between:0,100'],
        ]);

        $reading = SensorReading::query()->create([
            'device_id' => $validated['device_id'],
            'temperature' => round((float) $validated['temperature_c'], 2),
            'humidity' => round((float) $validated['humidity_percent'], 2),
            'recorded_at' => now(),
        ]);

        $this->auditLogService->record('climate.reading.ingested', $reading, [
            'device_id' => $reading->device_id,
            'temperature_c' => $reading->temperature,
            'humidity_percent' => $reading->humidity,
            'recorded_at' => optional($reading->recorded_at)->toIso8601String(),
        ]);

        SensorTelemetryUpdated::dispatch(
            $this->environmentalTelemetryService->dashboardTrend()
        );

        return response()->json([
            'ok' => true,
            'reading' => $this->serializeReading($reading),
        ], 201);
    }

    public function latest(): JsonResponse
    {
        $reading = SensorReading::query()
            ->latest('recorded_at')
            ->first();

        return response()->json([
            'ok' => true,
            'reading' => $reading ? $this->serializeReading($reading) : null,
        ]);
    }

    private function serializeReading(SensorReading $reading): array
    {
        return [
            'device_id' => $reading->device_id,
            'temperature_c' => round((float) $reading->temperature, 2),
            'humidity_percent' => $reading->humidity === null ? null : round((float) $reading->humidity, 2),
            'recorded_at' => optional($reading->recorded_at)->toIso8601String(),
        ];
    }
}
