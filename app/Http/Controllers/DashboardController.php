<?php

namespace App\Http\Controllers;

use App\Services\EnvironmentalTelemetryService;
use App\Services\LocalSystemStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly LocalSystemStatusService $localSystemStatusService,
        private readonly EnvironmentalTelemetryService $environmentalTelemetryService,
    ) {
    }

    public function __invoke(Request $request): View
    {
        $temperature = $this->resolveTemperature($request);
        $humidity = $this->resolveHumidity($request);

        return view('dashboard', [
            'user' => $request->user(),
            'temperatureData' => $this->buildTemperaturePayload($temperature),
            'humidityData' => $this->buildHumidityPayload($humidity),
            'trendData' => $this->resolveTrendData(),
            'servers' => $this->serverCards(),
        ]);
    }

    public function temperatureFeed(Request $request): JsonResponse
    {
        return response()->json(
            $this->buildTemperaturePayload($this->resolveTemperature($request))
        );
    }

    public function humidityFeed(Request $request): JsonResponse
    {
        return response()->json(
            $this->buildHumidityPayload($this->resolveHumidity($request))
        );
    }

    public function trendFeed(): JsonResponse
    {
        return response()->json($this->resolveTrendData());
    }

    public function serverFeed(): JsonResponse
    {
        return response()->json($this->localSystemStatusService->dashboardCard());
    }

    private function resolveTemperature(Request $request): float
    {
        if ($request->filled('temp')) {
            return round((float) $request->query('temp'), 1);
        }

        return $this->environmentalTelemetryService->latestTemperature();
    }

    private function resolveHumidity(Request $request): float
    {
        if ($request->filled('humidity')) {
            return round((float) $request->query('humidity'), 1);
        }

        return $this->environmentalTelemetryService->latestHumidity();
    }

    private function buildTemperaturePayload(float $temperature): array
    {
        $status = match (true) {
            $temperature >= 30 => 'Critical',
            $temperature >= 25 => 'Warning',
            default => 'Stable',
        };

        $progress = max(0, min(100, (($temperature - 10) / 30) * 100));

        return [
            'value' => $temperature,
            'status' => $status,
            'ringDegrees' => round(($progress / 100) * 360),
        ];
    }

    private function buildHumidityPayload(float $humidity): array
    {
        $status = match (true) {
            $humidity >= 70 || $humidity <= 30 => 'Critical',
            $humidity >= 60 || $humidity <= 35 => 'Warning',
            default => 'Stable',
        };

        $progress = max(0, min(100, $humidity));

        return [
            'value' => $humidity,
            'status' => $status,
            'ringDegrees' => round(($progress / 100) * 360),
        ];
    }

    private function resolveTrendData(): array
    {
        return $this->environmentalTelemetryService->dashboardTrend();
    }

    private function serverCards(): array
    {
        return [
            $this->localSystemStatusService->dashboardCard(),
        ];
    }
}
