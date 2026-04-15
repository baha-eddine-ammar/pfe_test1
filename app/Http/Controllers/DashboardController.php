<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This controller powers the main dashboard page and its live metric feeds.
| It gathers the data needed by the dashboard Blade view and also exposes
| small JSON endpoints that the frontend polls to refresh the UI.
|
| Why this file exists:
| The dashboard needs one place to assemble environmental telemetry
| (temperature, humidity, airflow, power usage), trend data, and server cards.
|
| When this file is used:
| - When a user opens /dashboard
| - When the frontend requests /dashboard/*-feed endpoints for live updates
|
| FILES TO READ (IN ORDER):
| 1. routes/web.php
| 2. app/Http/Controllers/DashboardController.php
| 3. app/Services/ServerMonitoringService.php
| 4. app/Models/Server.php and app/Models/ServerMetric.php
| 5. resources/views/dashboard.blade.php
| 6. resources/views/components/dashboard/*
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. The route points to this controller.
| 2. This controller resolves current values from the session or query string.
| 3. It converts raw values into UI-friendly payloads.
| 4. It loads server cards from the database or fallback demo data.
| 5. The dashboard Blade page renders those payloads into cards and charts.
*/

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\ServerMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // The service is injected by Laravel's container.
    // It converts real server + metric rows into card data for the UI.
    public function __construct(
        private readonly ServerMonitoringService $serverMonitoringService,
    ) {
    }

    /*
    |----------------------------------------------------------------------
    | Main dashboard page
    |----------------------------------------------------------------------
    | Flow:
    | Request -> session/query values -> payload builders -> view('dashboard')
    |
    | Important variables:
    | - $temperature / $humidity / $airFlow / $powerUsage:
    |   current metric values resolved from the session or URL query.
    | - temperatureData / humidityData / airFlowData / powerUsageData:
    |   arrays prepared specifically for the Blade UI.
    | - trendData:
    |   historical-looking chart data used by the telemetry graph.
    | - servers:
    |   server cards built from the database or fallback demo data.
    */
    public function __invoke(Request $request): View
    {
        $temperature = $this->resolveTemperature($request);
        $humidity = $this->resolveHumidity($request);
        $airFlow = $this->resolveAirFlow($request);
        $powerUsage = $this->resolvePowerUsage($request);

        return view('dashboard', [
            'user' => $request->user(),
            'temperatureData' => $this->buildTemperaturePayload($temperature),
            'humidityData' => $this->buildHumidityPayload($humidity),
            'airFlowData' => $this->buildAirFlowPayload($airFlow),
            'powerUsageData' => $this->buildPowerUsagePayload($powerUsage),
            // Trend data is used by the frontend chart to show movement over time.
            'trendData' => $this->resolveTrendData($request),
            // Server cards come from the database when available.
            // If no servers exist yet, fallback demo cards keep the dashboard useful.
            'servers' => $this->serverCards(),
        ]);
    }

    // JSON endpoint used by the frontend to refresh only the temperature card.
    public function temperatureFeed(Request $request): JsonResponse
    {
        $temperature = $this->resolveTemperature($request, true);

        return response()->json($this->buildTemperaturePayload($temperature));
    }

    public function humidityFeed(Request $request): JsonResponse
    {
        $humidity = $this->resolveHumidity($request, true);

        return response()->json($this->buildHumidityPayload($humidity));
    }

    public function airFlowFeed(Request $request): JsonResponse
    {
        $airFlow = $this->resolveAirFlow($request, true);

        return response()->json($this->buildAirFlowPayload($airFlow));
    }

    public function powerUsageFeed(Request $request): JsonResponse
    {
        $powerUsage = $this->resolvePowerUsage($request, true);

        return response()->json($this->buildPowerUsagePayload($powerUsage));
    }

    public function trendFeed(Request $request): JsonResponse
    {
        return response()->json($this->resolveTrendData($request, true));
    }

    // These resolve* methods support two dashboard modes:
    // 1. initial page load
    // 2. simulated next value for polling-based live updates
    //
    // If the query string contains an explicit value, that value wins.
    // Otherwise the current value is taken from the session.
    private function resolveTemperature(Request $request, bool $simulateNext = false): float
    {
        if ($request->filled('temp')) {
            return round((float) $request->query('temp'), 1);
        }

        $currentTemperature = (float) $request->session()->get('demo_temperature', 22.4);

        if (! $simulateNext) {
            return round($currentTemperature, 1);
        }

        $delta = mt_rand(-8, 8) / 10;
        $nextTemperature = max(18, min(34, $currentTemperature + $delta));

        $request->session()->put('demo_temperature', $nextTemperature);

        return round($nextTemperature, 1);
    }

    // Same idea as resolveTemperature(), but for humidity data.
    private function resolveHumidity(Request $request, bool $simulateNext = false): float
    {
        if ($request->filled('humidity')) {
            return round((float) $request->query('humidity'), 1);
        }

        $currentHumidity = (float) $request->session()->get('demo_humidity', 45.0);

        if (! $simulateNext) {
            return round($currentHumidity, 1);
        }

        $delta = mt_rand(-12, 12) / 10;
        $nextHumidity = max(25, min(80, $currentHumidity + $delta));

        $request->session()->put('demo_humidity', $nextHumidity);

        return round($nextHumidity, 1);
    }

    // Same pattern again for airflow data.
    private function resolveAirFlow(Request $request, bool $simulateNext = false): float
    {
        if ($request->filled('airflow')) {
            return round((float) $request->query('airflow'), 1);
        }

        $currentAirFlow = (float) $request->session()->get('demo_airflow', 5.8);

        if (! $simulateNext) {
            return round($currentAirFlow, 1);
        }

        $delta = mt_rand(-6, 6) / 10;
        $nextAirFlow = max(2.5, min(9.5, $currentAirFlow + $delta));

        $request->session()->put('demo_airflow', $nextAirFlow);

        return round($nextAirFlow, 1);
    }

    // Same pattern again for power usage data.
    private function resolvePowerUsage(Request $request, bool $simulateNext = false): float
    {
        if ($request->filled('power')) {
            return round((float) $request->query('power'), 1);
        }

        $currentPowerUsage = (float) $request->session()->get('demo_power_usage', 64.0);

        if (! $simulateNext) {
            return round($currentPowerUsage, 1);
        }

        $delta = mt_rand(-15, 15) / 10;
        $nextPowerUsage = max(25, min(98, $currentPowerUsage + $delta));

        $request->session()->put('demo_power_usage', $nextPowerUsage);

        return round($nextPowerUsage, 1);
    }

    // These payload builders convert raw numeric values into UI-friendly arrays.
    // Each payload contains:
    // - value: the number shown to the user
    // - status: Stable / Warning / Critical
    // - ringDegrees: how much of the circular indicator should be filled
    private function buildTemperaturePayload(float $temperature): array
    {
        if ($temperature >= 30) {
            $status = 'Critical';
        } elseif ($temperature >= 25) {
            $status = 'Warning';
        } else {
            $status = 'Stable';
        }

        $progress = max(0, min(100, (($temperature - 10) / 30) * 100));

        return [
            'value' => $temperature,
            'status' => $status,
            'ringDegrees' => round(($progress / 100) * 360),
        ];
    }

    // Humidity becomes critical if it is too high or too low.
    private function buildHumidityPayload(float $humidity): array
    {
        if ($humidity >= 70 || $humidity <= 30) {
            $status = 'Critical';
        } elseif ($humidity >= 60 || $humidity <= 35) {
            $status = 'Warning';
        } else {
            $status = 'Stable';
        }

        $progress = max(0, min(100, $humidity));

        return [
            'value' => $humidity,
            'status' => $status,
            'ringDegrees' => round(($progress / 100) * 360),
        ];
    }

    // Airflow uses a lower-is-bad interpretation because poor airflow can
    // indicate cooling problems in the server room.
    private function buildAirFlowPayload(float $airFlow): array
    {
        if ($airFlow < 3.5) {
            $status = 'Critical';
        } elseif ($airFlow < 4.5) {
            $status = 'Warning';
        } else {
            $status = 'Stable';
        }

        $progress = max(0, min(100, (($airFlow - 2) / 8) * 100));

        return [
            'value' => $airFlow,
            'status' => $status,
            'ringDegrees' => round(($progress / 100) * 360),
        ];
    }

    // Higher power usage is treated as riskier because it can indicate heavier load.
    private function buildPowerUsagePayload(float $powerUsage): array
    {
        if ($powerUsage >= 85) {
            $status = 'Critical';
        } elseif ($powerUsage >= 70) {
            $status = 'Warning';
        } else {
            $status = 'Stable';
        }

        $progress = max(0, min(100, $powerUsage));

        return [
            'value' => $powerUsage,
            'status' => $status,
            'ringDegrees' => round(($progress / 100) * 360),
        ];
    }

    /*
    |----------------------------------------------------------------------
    | Trend data for charts
    |----------------------------------------------------------------------
    | This method stores chart history in the session so repeated refreshes can
    | show a moving graph without needing a real telemetry source yet.
    */
    private function resolveTrendData(Request $request, bool $simulateNext = false): array
    {
        $trendData = $request->session()->get('demo_trend_data', [
            'labels' => ['09:00', '09:05', '09:10', '09:15', '09:20', '09:25', '09:30'],
            'temperature' => [22.1, 22.4, 22.0, 23.2, 24.1, 23.6, 24.0],
            'humidity' => [45.0, 45.8, 46.1, 47.0, 46.5, 47.2, 46.8],
            'airFlow' => [5.4, 5.7, 5.5, 5.9, 6.1, 5.8, 6.0],
            'powerUsage' => [61.0, 63.0, 62.4, 65.8, 67.2, 66.1, 68.0],
        ]);

        if (! $simulateNext) {
            return $trendData;
        }

        $trendData['labels'][] = now()->format('H:i');
        array_shift($trendData['labels']);

        $trendData['temperature'][] = $this->nextMetricValue(last($trendData['temperature']), 18, 34, 0.8);
        $trendData['humidity'][] = $this->nextMetricValue(last($trendData['humidity']), 25, 80, 1.2);
        $trendData['airFlow'][] = $this->nextMetricValue(last($trendData['airFlow']), 2.5, 9.5, 0.4);
        $trendData['powerUsage'][] = $this->nextMetricValue(last($trendData['powerUsage']), 25, 98, 1.6);

        array_shift($trendData['temperature']);
        array_shift($trendData['humidity']);
        array_shift($trendData['airFlow']);
        array_shift($trendData['powerUsage']);

        $request->session()->put('demo_trend_data', $trendData);

        return $trendData;
    }

    // Generates the next point inside a safe numeric range.
    private function nextMetricValue(float $current, float $min, float $max, float $step): float
    {
        $delta = mt_rand((int) (-10 * $step), (int) (10 * $step)) / 10;

        return round(max($min, min($max, $current + $delta)), 1);
    }

    /*
    |----------------------------------------------------------------------
    | Server cards
    |----------------------------------------------------------------------
    | Flow:
    | Database -> Server model -> latestMetric relation -> ServerMonitoringService
    |
    | If there are no real servers yet, fallback cards keep the dashboard useful
    | during demos or early development.
    */
    private function serverCards(): array
    {
        $servers = Server::query()
            ->with('latestMetric')
            ->orderBy('name')
            ->get();

        if ($servers->isNotEmpty()) {
            return $this->serverMonitoringService->buildCards($servers);
        }

        return $this->fallbackServerCards();
    }

    // Demo-only cards used when the servers table is still empty.
    private function fallbackServerCards(): array
    {
        return [
            [
                'name' => 'SRV-APP-01',
                'status' => 'Online',
                'statusColor' => 'emerald',
                'metrics' => [
                    ['label' => 'CPU', 'value' => '45%', 'progress' => 45, 'color' => 'cyan'],
                    ['label' => 'RAM', 'value' => '12/32 GB', 'progress' => 38, 'color' => 'violet'],
                    ['label' => 'Disk', 'value' => '420/1000 GB', 'progress' => 42, 'color' => 'pink'],
                    ['label' => 'Network', 'value' => '↓ 35 Mbps ↑ 12 Mbps', 'progress' => 36, 'color' => 'emerald'],
                ],
            ],
            [
                'name' => 'SRV-DB-01',
                'status' => 'Warning',
                'statusColor' => 'amber',
                'metrics' => [
                    ['label' => 'CPU', 'value' => '68%', 'progress' => 68, 'color' => 'cyan'],
                    ['label' => 'RAM', 'value' => '25/32 GB', 'progress' => 78, 'color' => 'violet'],
                    ['label' => 'Disk', 'value' => '812/1000 GB', 'progress' => 81, 'color' => 'pink'],
                    ['label' => 'Network', 'value' => '↓ 52 Mbps ↑ 21 Mbps', 'progress' => 52, 'color' => 'emerald'],
                ],
            ],
            [
                'name' => 'SRV-BACKUP-01',
                'status' => 'Online',
                'statusColor' => 'emerald',
                'metrics' => [
                    ['label' => 'CPU', 'value' => '22%', 'progress' => 22, 'color' => 'cyan'],
                    ['label' => 'RAM', 'value' => '10/64 GB', 'progress' => 16, 'color' => 'violet'],
                    ['label' => 'Disk', 'value' => '640/2000 GB', 'progress' => 32, 'color' => 'pink'],
                    ['label' => 'Network', 'value' => '↓ 18 Mbps ↑ 9 Mbps', 'progress' => 19, 'color' => 'emerald'],
                ],
            ],
            [
                'name' => 'SRV-WEB-EDGE',
                'status' => 'Critical',
                'statusColor' => 'pink',
                'metrics' => [
                    ['label' => 'CPU', 'value' => '91%', 'progress' => 91, 'color' => 'cyan'],
                    ['label' => 'RAM', 'value' => '29/32 GB', 'progress' => 91, 'color' => 'violet'],
                    ['label' => 'Disk', 'value' => '910/1000 GB', 'progress' => 91, 'color' => 'pink'],
                    ['label' => 'Network', 'value' => '↓ 83 Mbps ↑ 47 Mbps', 'progress' => 83, 'color' => 'emerald'],
                ],
            ],
        ];
    }
}
