{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This is the main realtime monitoring dashboard page.
|
| Why this file exists:
| It is the visual command center of the project. It displays environmental
| metrics, trend charts, and server cards using data prepared by DashboardController.
|
| When this file is used:
| After DashboardController builds metric payloads, trend data, and server cards.
|
| FILES TO READ (IN ORDER):
| 1. routes/web.php
| 2. app/Http/Controllers/DashboardController.php
| 3. app/Services/ServerMonitoringService.php
| 4. app/Models/Server.php and app/Models/ServerMetric.php
| 5. resources/views/dashboard.blade.php
| 6. resources/views/components/dashboard/*
| 7. resources/views/dashboard/partials/server-card.blade.php
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. Controller sends raw metric payloads and trend arrays to this view.
| 2. This view reshapes them into UI cards/charts.
| 3. Dashboard components render the reusable metric and chart containers.
| 4. Inline Alpine/ApexCharts code keeps the page feeling live.
--}}
@php
    // Helper closure used only inside this Blade file.
    // It computes the latest trend direction/percentage from a list of chart values.
    $buildTrendMeta = function (array $values): array {
        $values = array_values(array_map('floatval', $values));
        $current = count($values) > 0 ? $values[array_key_last($values)] : 0.0;
        $previous = count($values) > 1 ? $values[count($values) - 2] : $current;
        $delta = round($current - $previous, 1);
        $percent = $previous !== 0.0 ? round(($delta / $previous) * 100, 1) : 0.0;
        $direction = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
        $sign = $percent > 0 ? '+' : '';

        return [
            'delta' => $delta,
            'percent' => $percent,
            'direction' => $direction,
            'label' => $direction === 'flat' ? 'Flat' : $sign.$percent.'%',
        ];
    };

    // Trend metadata for each environmental metric.
    $temperatureTrend = $buildTrendMeta($trendData['temperature']);
    $humidityTrend = $buildTrendMeta($trendData['humidity']);
    $airFlowTrend = $buildTrendMeta($trendData['airFlow']);
    $powerUsageTrend = $buildTrendMeta($trendData['powerUsage']);

    // $metricCards is the main source used by the top KPI card grid.
    // Data comes from DashboardController payloads plus local view formatting.
    $metricCards = [
        [
            'title' => 'Temperature',
            'subtitle' => 'Rack climate stability',
            'value' => $temperatureData['value'],
            'status' => $temperatureData['status'],
            'ringDegrees' => $temperatureData['ringDegrees'],
            'unit' => 'deg C',
            'feedUrl' => route('dashboard.temperature'),
            'stableColor' => '#38BDF8',
            'icon' => 'temperature',
            'target' => 'Target band 18-25 deg C',
            'sparkline' => $trendData['temperature'],
            'trend' => $temperatureTrend,
        ],
        [
            'title' => 'Humidity',
            'subtitle' => 'Moisture balance',
            'value' => $humidityData['value'],
            'status' => $humidityData['status'],
            'ringDegrees' => $humidityData['ringDegrees'],
            'unit' => '%',
            'feedUrl' => route('dashboard.humidity'),
            'stableColor' => '#8B5CF6',
            'icon' => 'humidity',
            'target' => 'Target band 35-60%',
            'sparkline' => $trendData['humidity'],
            'trend' => $humidityTrend,
        ],
        [
            'title' => 'Air Flow',
            'subtitle' => 'Cooling route pressure',
            'value' => $airFlowData['value'],
            'status' => $airFlowData['status'],
            'ringDegrees' => $airFlowData['ringDegrees'],
            'unit' => 'm/s',
            'feedUrl' => route('dashboard.airflow'),
            'stableColor' => '#34D399',
            'icon' => 'airflow',
            'target' => 'Cooling route above 4.5 m/s',
            'sparkline' => $trendData['airFlow'],
            'trend' => $airFlowTrend,
        ],
        [
            'title' => 'Power Usage',
            'subtitle' => 'UPS and rack draw',
            'value' => $powerUsageData['value'],
            'status' => $powerUsageData['status'],
            'ringDegrees' => $powerUsageData['ringDegrees'],
            'unit' => '%',
            'feedUrl' => route('dashboard.power'),
            'stableColor' => '#F59E0B',
            'icon' => 'power',
            'target' => 'Target band below 80%',
            'sparkline' => $trendData['powerUsage'],
            'trend' => $powerUsageTrend,
        ],
    ];

    // Aggregate counts used by summary widgets and charts.
    $statusCounts = ['Stable' => 0, 'Warning' => 0, 'Critical' => 0];
    foreach ($metricCards as $metricCard) {
        $statusCounts[$metricCard['status']]++;
    }

    $averageMetricLoad = count($metricCards) > 0
        ? round(array_sum(array_map(fn ($card) => $card['ringDegrees'] / 3.6, $metricCards)) / count($metricCards), 1)
        : 0;

    $attentionMetrics = $statusCounts['Warning'] + $statusCounts['Critical'];

    $serverStateCounts = ['Online' => 0, 'Warning' => 0, 'Critical' => 0];
    foreach ($servers as $server) {
        $serverStateCounts[$server['status']] = ($serverStateCounts[$server['status']] ?? 0) + 1;
    }

    $onlineServers = $serverStateCounts['Online'] ?? 0;
    $criticalServers = $serverStateCounts['Critical'] ?? 0;
    $warningServers = $serverStateCounts['Warning'] ?? 0;
    $totalServers = count($servers);
    $healthyServerRatio = $totalServers > 0 ? (int) round(($onlineServers / $totalServers) * 100) : 0;
    $alertsTotal = $attentionMetrics + $criticalServers + $warningServers;

    // CPU load data prepared for the server distribution chart.
    $serverLoadSeries = collect($servers)
        ->take(6)
        ->map(fn ($server) => (int) ($server['metrics'][0]['progress'] ?? 0))
        ->values()
        ->all();
    $serverLabels = collect($servers)
        ->take(6)
        ->map(fn ($server) => $server['name'])
        ->values()
        ->all();

    if ($serverLoadSeries === []) {
        $serverLoadSeries = [0, 0, 0, 0];
        $serverLabels = ['SRV-A', 'SRV-B', 'SRV-C', 'SRV-D'];
    }

    $topCpuServer = collect($servers)
        ->sortByDesc(fn ($server) => (int) ($server['metrics'][0]['progress'] ?? 0))
        ->first();

    // Compact summary rows displayed below the main telemetry chart.
    $telemetryHighlights = collect($metricCards)
        ->map(function (array $card): array {
            $statusClass = match ($card['status']) {
                'Stable' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                'Warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                'Critical' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
                default => 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300',
            };

            return [
                'label' => $card['title'],
                'value' => in_array($card['unit'], ['%', 'deg C', 'm/s'], true)
                    ? $card['value'].($card['unit'] === '%' ? '%' : ' '.$card['unit'])
                    : $card['value'].' '.$card['unit'],
                'status' => $card['status'],
                'statusClass' => $statusClass,
                'trend' => $card['trend']['label'] ?? 'Flat',
            ];
        })
        ->all();
@endphp

<x-app-layout>
    {{--
        Root dashboard Alpine component.
        It receives chart series and feed URLs from the backend.
    --}}
    <div
        x-data="serverRoomDashboard(@js([
            'trend' => $trendData,
            'sensorStates' => $statusCounts,
            'serverLoads' => $serverLoadSeries,
            'serverLabels' => $serverLabels,
            'feedUrl' => route('dashboard.trend'),
        ]))"
        x-init="init()"
        data-dashboard-page
        class="dashboard-shell relative isolate mx-auto max-w-[1600px] space-y-6 pb-10 sm:space-y-7"
    >
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[34rem] bg-[radial-gradient(circle_at_top_left,_rgba(70,95,255,0.22),_transparent_32%),radial-gradient(circle_at_top_right,_rgba(14,165,233,0.16),_transparent_28%),linear-gradient(180deg,_rgba(255,255,255,0.96),_rgba(248,250,252,0))] blur-2xl dark:bg-[radial-gradient(circle_at_top_left,_rgba(70,95,255,0.2),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(34,211,238,0.14),_transparent_24%),linear-gradient(180deg,_rgba(15,23,42,0.66),_rgba(2,6,23,0))]"></div>

        {{--
            Dashboard header:
            Introduces the page and shows the last refresh status.
        --}}
        <section class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="app-section-title">Realtime monitoring</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white sm:text-4xl">
                    Server room dashboard
                </h1>
                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500 dark:text-slate-400">
                    Temperature, humidity, air flow, and power usage are now the first thing operators see, with the main telemetry graph directly underneath.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="dashboard-live-badge">
                    <span class="dashboard-live-dot"></span>
                    Live telemetry
                </span>
                <div class="dashboard-surface-glass rounded-full px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Last refresh</p>
                    <p class="mt-1 text-sm font-semibold text-slate-950 dark:text-white" x-text="lastRefreshLabel"></p>
                </div>
                <a href="{{ route('servers.index') }}" class="app-button-secondary">Servers</a>
                <a href="{{ route('reports.index') }}" class="app-button-secondary">Reports</a>
            </div>
        </section>

        {{--
            Primary KPI cards:
            These are rendered by <x-dashboard.live-metric-card>.
            Each card receives one item from $metricCards.
        --}}
        <section class="grid grid-cols-1 gap-5 xl:grid-cols-2 2xl:grid-cols-4">
            @foreach ($metricCards as $card)
                <x-dashboard.live-metric-card
                    :title="$card['title']"
                    :subtitle="$card['subtitle']"
                    :value="$card['value']"
                    :status="$card['status']"
                    :ring-degrees="$card['ringDegrees']"
                    :unit="$card['unit']"
                    :feed-url="$card['feedUrl']"
                    :stable-color="$card['stableColor']"
                    :icon="$card['icon']"
                    :target="$card['target']"
                    :sparkline="$card['sparkline']"
                    :trend="$card['trend']"
                />
            @endforeach
        </section>

        {{--
            Main analytics section:
            Left = large telemetry chart
            Right = summary widgets and status distribution
        --}}
        <section class="grid gap-6 2xl:grid-cols-[minmax(0,1.6fr)_minmax(340px,0.84fr)]">
            <x-dashboard.chart-card
                eyebrow="Realtime analytics"
                title="Telemetry stream"
                description="A cleaner live chart with stronger contrast, better spacing, and the latest readings attached directly below it."
                height="h-[500px]"
            >
                <x-slot name="action">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="dashboard-live-badge" :class="isSyncing ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300 dark:border-brand-500/20' : ''">
                            <span class="dashboard-live-dot"></span>
                            <span x-text="isSyncing ? 'Syncing now' : 'Streaming live'"></span>
                        </span>
                        <span class="app-pill bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300">
                            {{ $alertsTotal }} active alerts
                        </span>
                    </div>
                </x-slot>

                <div class="flex h-full flex-col">
                    <div x-ref="trendChart" class="min-h-0 flex-1"></div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($telemetryHighlights as $telemetryHighlight)
                            <div class="dashboard-surface-glass rounded-[22px] px-4 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">{{ $telemetryHighlight['label'] }}</p>
                                        <p class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">{{ $telemetryHighlight['value'] }}</p>
                                    </div>
                                    <span class="app-pill {{ $telemetryHighlight['statusClass'] }}">{{ $telemetryHighlight['status'] }}</span>
                                </div>

                                <p class="mt-3 text-xs font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                                    Trend {{ $telemetryHighlight['trend'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-dashboard.chart-card>

            <div class="space-y-6">
                <x-dashboard.chart-card
                    eyebrow="System distribution"
                    title="Health balance"
                    description="Current sensor-state distribution with room readiness at the center."
                    height="h-[320px]"
                >
                    <div class="grid h-full gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(140px,0.9fr)] lg:items-center">
                        <div x-ref="distributionChart" class="h-[260px]"></div>

                        <div class="space-y-3">
                            @foreach ([
                                ['label' => 'Stable', 'count' => $statusCounts['Stable'], 'class' => 'bg-emerald-500'],
                                ['label' => 'Warning', 'count' => $statusCounts['Warning'], 'class' => 'bg-amber-500'],
                                ['label' => 'Critical', 'count' => $statusCounts['Critical'], 'class' => 'bg-rose-500'],
                            ] as $distributionRow)
                                <div class="dashboard-mini-stat">
                                    <div class="flex items-center gap-3">
                                        <span class="h-3 w-3 rounded-full {{ $distributionRow['class'] }}"></span>
                                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $distributionRow['label'] }}</span>
                                    </div>
                                    <span class="font-display text-2xl font-semibold text-slate-950 dark:text-white">{{ $distributionRow['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-dashboard.chart-card>

                <article class="dashboard-panel px-6 py-6 sm:px-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="app-section-title">Live overview</p>
                            <h2 class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">Room snapshot</h2>
                        </div>
                        <span
                            class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-white shadow-lg transition-all duration-300"
                            :class="isSyncing ? 'bg-brand-500 shadow-brand-500/30' : 'bg-slate-950 shadow-slate-950/20 dark:bg-white dark:text-slate-950 dark:shadow-white/10'"
                        >
                            <span class="dashboard-live-dot" :class="isSyncing ? 'bg-white shadow-white/50' : ''"></span>
                            <span x-text="isSyncing ? 'Refreshing now' : 'Feed online'"></span>
                        </span>
                    </div>

                    <div class="mt-6 grid gap-3">
                        <div class="dashboard-mini-stat">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Room health</p>
                                <p class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">{{ number_format($averageMetricLoad, 0) }}%</p>
                            </div>
                            <p class="max-w-[10rem] text-right text-sm leading-6 text-slate-500 dark:text-slate-400">Average readiness across the four live environmental systems.</p>
                        </div>
                        <div class="dashboard-mini-stat">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Fleet health</p>
                                <p class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">{{ $healthyServerRatio }}%</p>
                            </div>
                            <p class="max-w-[10rem] text-right text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $onlineServers }} of {{ $totalServers }} servers are currently reporting healthy.</p>
                        </div>
                        <div class="dashboard-mini-stat">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Active alerts</p>
                                <p class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">{{ $alertsTotal }}</p>
                            </div>
                            <p class="max-w-[10rem] text-right text-sm leading-6 text-slate-500 dark:text-slate-400">Combined sensor and server conditions that still need operator attention.</p>
                        </div>
                        <div class="dashboard-mini-stat">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Peak CPU</p>
                                <p class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">{{ $topCpuServer['metrics'][0]['value'] ?? 'No data' }}</p>
                            </div>
                            <p class="max-w-[10rem] text-right text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $topCpuServer['name'] ?? 'Telemetry pending' }}</p>
                        </div>
                        <div class="dashboard-mini-stat">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Active operator</p>
                                <p class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">{{ $user->name }}</p>
                            </div>
                            <p class="max-w-[10rem] text-right text-sm leading-6 text-slate-500 dark:text-slate-400">The dashboard remains in live monitoring mode while you supervise the room.</p>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        {{--
            Secondary charts:
            Smaller focused views for temperature, humidity, and server CPU.
        --}}
        <section class="grid gap-6 xl:grid-cols-3">
            <x-dashboard.chart-card
                eyebrow="Thermal"
                title="Temperature over time"
                description="Smooth line movement for rack climate over the current sample window."
                height="h-[260px]"
            >
                <div x-ref="temperatureChart" class="h-full"></div>
            </x-dashboard.chart-card>

            <x-dashboard.chart-card
                eyebrow="Humidity"
                title="Humidity over time"
                description="Moisture trends stay visible as an independent line for faster issue detection."
                height="h-[260px]"
            >
                <div x-ref="humidityChart" class="h-full"></div>
            </x-dashboard.chart-card>

            <x-dashboard.chart-card
                eyebrow="Fleet load"
                title="Server CPU distribution"
                description="Bar view of the monitored servers currently carrying the highest CPU pressure."
                height="h-[260px]"
            >
                <div x-ref="serverLoadChart" class="h-full"></div>
            </x-dashboard.chart-card>
        </section>

        {{--
            Server fleet section:
            Server cards come from ServerMonitoringService or fallback demo data.
        --}}
        <section>
            <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="app-section-title">Infrastructure</p>
                    <h2 class="mt-2 font-display text-3xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white">Server fleet</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500 dark:text-slate-400">
                        CPU, RAM, disk, and network now sit inside richer operational cards with clearer hierarchy, stronger contrast, and faster scanability.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="app-pill bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ $onlineServers }} online</span>
                    <span class="app-pill bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">{{ $warningServers }} watch</span>
                    <span class="app-pill bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ $criticalServers }} critical</span>
                </div>
            </div>

            @if (count($servers) > 0)
                <div class="grid gap-5 xl:grid-cols-2">
                    @foreach ($servers as $server)
                        @include('dashboard.partials.server-card', ['server' => $server])
                    @endforeach
                </div>
            @else
                <article class="dashboard-panel px-6 py-8 sm:px-8">
                    <div class="max-w-3xl">
                        <p class="app-section-title">No live nodes yet</p>
                        <h3 class="mt-3 font-display text-3xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white">The fleet grid is ready for telemetry.</h3>
                        <p class="mt-4 text-sm leading-7 text-slate-500 dark:text-slate-400">
                            Once servers begin reporting through the monitoring API, each node will appear here with richer CPU, RAM, disk, and network diagnostics.
                        </p>
                    </div>
                </article>
            @endif
        </section>
    </div>

    <script>
        /*
         * Page-local frontend logic for the dashboard.
         *
         * What this script does:
         * - powers each live metric card
         * - renders ApexCharts charts
         * - polls the trend endpoint for refreshed telemetry
         *
         * Business logic still comes from DashboardController and the services/models.
         */
        window.liveMetricCard = window.liveMetricCard || function (config) {
            return {
                value: Number(config.initialValue),
                status: config.initialStatus,
                ringDegrees: Number(config.initialRingDegrees),
                unit: config.unit,
                feedUrl: config.feedUrl,
                stableColor: config.stableColor,
                sparkline: Array.isArray(config.sparkline) ? [...config.sparkline] : [],
                trendPercent: Number(config.initialTrendPercent || 0),
                trendDirection: config.initialTrendDirection || 'flat',
                refreshPulse: false,
                chart: null,
                intervalHandle: null,
                statusTone() {
                    return this.status === 'Critical'
                        ? '#F43F5E'
                        : (this.status === 'Warning' ? '#F59E0B' : this.stableColor);
                },
                statusClasses() {
                    return {
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300': this.status === 'Stable',
                        'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300': this.status === 'Warning',
                        'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300': this.status === 'Critical',
                    };
                },
                trendClasses() {
                    return {
                        'text-emerald-600 dark:text-emerald-300': this.trendDirection === 'up',
                        'text-rose-600 dark:text-rose-300': this.trendDirection === 'down',
                        'text-slate-500 dark:text-slate-400': this.trendDirection === 'flat',
                    };
                },
                displayValue() {
                    return Number(this.value).toFixed(1);
                },
                displayPercent() {
                    return `${Math.round(this.ringDegrees / 3.6)}%`;
                },
                displayTrend() {
                    const sign = this.trendPercent > 0 ? '+' : '';

                    return this.trendDirection === 'flat'
                        ? 'Flat'
                        : `${sign}${this.trendPercent.toFixed(1)}%`;
                },
                glowStyle() {
                    const color = this.statusTone();

                    return `background:
                        radial-gradient(circle at top right, ${color}1E, transparent 34%),
                        radial-gradient(circle at bottom left, ${color}18, transparent 30%);`;
                },
                progressStyle() {
                    const color = this.statusTone();
                    const width = Math.max(10, Math.round(this.ringDegrees / 3.6));

                    return `width:${width}%;background:linear-gradient(90deg, ${color}, ${color}B3);`;
                },
                chartOptions() {
                    const dark = document.documentElement.classList.contains('dark');
                    const color = this.statusTone();

                    return {
                        chart: {
                            type: 'area',
                            height: 88,
                            sparkline: { enabled: true },
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 420,
                            },
                            toolbar: { show: false },
                        },
                        series: [{ data: this.sparkline }],
                        stroke: {
                            curve: 'smooth',
                            width: 3,
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: dark ? 0.34 : 0.24,
                                opacityTo: 0.02,
                                stops: [0, 100],
                            },
                        },
                        colors: [color],
                        tooltip: {
                            theme: dark ? 'dark' : 'light',
                        },
                    };
                },
                async refreshCard() {
                    try {
                        const previous = Number(this.value);
                        const response = await fetch(this.feedUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const data = await response.json();
                        const nextValue = Number(data.value);
                        this.value = nextValue;
                        this.status = data.status;
                        this.ringDegrees = Number(data.ringDegrees);

                        const deltaPercent = previous !== 0 ? ((nextValue - previous) / previous) * 100 : 0;
                        this.trendPercent = Number(deltaPercent.toFixed(1));
                        this.trendDirection = deltaPercent > 0 ? 'up' : (deltaPercent < 0 ? 'down' : 'flat');

                        this.sparkline.push(nextValue);
                        if (this.sparkline.length > 10) {
                            this.sparkline.shift();
                        }

                        if (this.chart) {
                            this.chart.updateOptions({
                                colors: [this.statusTone()],
                                tooltip: {
                                    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                                },
                            }, false, false);
                            this.chart.updateSeries([{ data: this.sparkline }], true);
                        }

                        this.refreshPulse = true;
                        window.setTimeout(() => {
                            this.refreshPulse = false;
                        }, 700);
                    } catch (error) {
                        console.error('Failed to refresh live metric card', error);
                    }
                },
                init() {
                    if (window.ApexCharts && this.$refs.sparkline) {
                        this.chart = new window.ApexCharts(this.$refs.sparkline, this.chartOptions());
                        this.chart.render();
                    }

                    this.intervalHandle = window.setInterval(() => {
                        this.refreshCard();
                    }, 3000);

                    window.addEventListener('theme-changed', () => {
                        if (!this.chart) {
                            return;
                        }

                        this.chart.updateOptions({
                            tooltip: {
                                theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                            },
                        }, false, false);
                    });
                },
            };
        };

        window.serverRoomDashboard = window.serverRoomDashboard || function (config) {
            return {
                trend: JSON.parse(JSON.stringify(config.trend)),
                sensorStates: { ...config.sensorStates },
                serverLoads: [...config.serverLoads],
                serverLabels: [...config.serverLabels],
                feedUrl: config.feedUrl,
                charts: {},
                isSyncing: false,
                lastRefreshLabel: new Date().toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                }),
                chartTheme() {
                    const dark = document.documentElement.classList.contains('dark');

                    return {
                        dark,
                        text: dark ? '#94A3B8' : '#64748B',
                        mutedText: dark ? '#CBD5E1' : '#0F172A',
                        grid: dark ? 'rgba(148, 163, 184, 0.12)' : 'rgba(148, 163, 184, 0.22)',
                        tooltip: dark ? 'dark' : 'light',
                    };
                },
                clampPercent(value, min, max) {
                    if (max <= min) {
                        return 0;
                    }

                    return Math.max(0, Math.min(100, ((value - min) / (max - min)) * 100));
                },
                readinessScore() {
                    const lastIndex = Math.max((this.trend.labels?.length || 1) - 1, 0);
                    const temperature = Number(this.trend.temperature?.[lastIndex] ?? 22);
                    const humidity = Number(this.trend.humidity?.[lastIndex] ?? 47.5);
                    const airFlow = Number(this.trend.airFlow?.[lastIndex] ?? 5.8);
                    const powerUsage = Number(this.trend.powerUsage?.[lastIndex] ?? 64);

                    const temperatureScore = 100 - Math.min(100, Math.abs(temperature - 22) * 9);
                    const humidityScore = 100 - Math.min(100, Math.abs(humidity - 47.5) * 3.2);
                    const airFlowScore = this.clampPercent(airFlow, 2.5, 9.5);
                    const powerScore = 100 - this.clampPercent(powerUsage, 25, 98);

                    return Math.round(Math.max(0, Math.min(100, (
                        temperatureScore + humidityScore + airFlowScore + powerScore
                    ) / 4)));
                },
                formatTelemetryValue(seriesName, value) {
                    const numericValue = Number(value);

                    if (seriesName === 'Temperature') {
                        return `${numericValue.toFixed(1)} deg C`;
                    }

                    if (seriesName === 'Humidity') {
                        return `${numericValue.toFixed(1)}%`;
                    }

                    if (seriesName === 'Air Flow') {
                        return `${numericValue.toFixed(1)} m/s`;
                    }

                    return `${numericValue.toFixed(1)}%`;
                },
                combinedSeries() {
                    return [
                        { name: 'Temperature', data: this.trend.temperature },
                        { name: 'Humidity', data: this.trend.humidity },
                        { name: 'Air Flow', data: this.trend.airFlow },
                        { name: 'Power Usage', data: this.trend.powerUsage },
                    ];
                },
                metricStatus(metric, value) {
                    if (metric === 'temperature') {
                        return value >= 30 ? 'Critical' : (value >= 25 ? 'Warning' : 'Stable');
                    }

                    if (metric === 'humidity') {
                        return value >= 70 || value <= 30 ? 'Critical' : (value >= 60 || value <= 35 ? 'Warning' : 'Stable');
                    }

                    if (metric === 'airFlow') {
                        return value < 3.5 ? 'Critical' : (value < 4.5 ? 'Warning' : 'Stable');
                    }

                    return value >= 85 ? 'Critical' : (value >= 70 ? 'Warning' : 'Stable');
                },
                updateSensorStates() {
                    const nextCounts = { Stable: 0, Warning: 0, Critical: 0 };
                    const latestValues = {
                        temperature: this.trend.temperature[this.trend.temperature.length - 1] ?? 0,
                        humidity: this.trend.humidity[this.trend.humidity.length - 1] ?? 0,
                        airFlow: this.trend.airFlow[this.trend.airFlow.length - 1] ?? 0,
                        powerUsage: this.trend.powerUsage[this.trend.powerUsage.length - 1] ?? 0,
                    };

                    Object.entries(latestValues).forEach(([metric, value]) => {
                        nextCounts[this.metricStatus(metric, Number(value))]++;
                    });

                    this.sensorStates = nextCounts;
                },
                combinedChartOptions() {
                    const theme = this.chartTheme();

                    return {
                        chart: {
                            type: 'area',
                            height: 420,
                            background: 'transparent',
                            toolbar: { show: false },
                            zoom: { enabled: false },
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 620,
                            },
                            dropShadow: {
                                enabled: true,
                                top: 10,
                                left: 0,
                                blur: 18,
                                opacity: theme.dark ? 0.24 : 0.12,
                            },
                        },
                        series: this.combinedSeries(),
                        colors: ['#38BDF8', '#8B5CF6', '#34D399', '#F59E0B'],
                        stroke: {
                            curve: 'smooth',
                            width: [3.8, 3.6, 3.4, 3.4],
                            lineCap: 'round',
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: theme.dark ? 0.2 : 0.14,
                                opacityTo: 0.01,
                                stops: [0, 82, 100],
                            },
                        },
                        legend: {
                            show: false,
                        },
                        markers: {
                            size: 0,
                            strokeWidth: 0,
                            hover: {
                                size: 6,
                            },
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        xaxis: {
                            categories: this.trend.labels,
                            labels: {
                                style: {
                                    colors: theme.text,
                                },
                                trim: true,
                            },
                            axisBorder: {
                                color: theme.grid,
                            },
                            axisTicks: {
                                color: theme.grid,
                            },
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    colors: theme.text,
                                },
                                formatter(value) {
                                    return Math.round(value);
                                },
                            },
                        },
                        grid: {
                            borderColor: theme.grid,
                            strokeDashArray: 5,
                            padding: {
                                left: 8,
                                right: 8,
                                top: 8,
                                bottom: 0,
                            },
                        },
                        tooltip: {
                            theme: theme.tooltip,
                            shared: true,
                            intersect: false,
                            x: {
                                show: true,
                            },
                            y: {
                                formatter: (value, { seriesIndex, w }) => this.formatTelemetryValue(
                                    w.globals.seriesNames[seriesIndex],
                                    value
                                ),
                            },
                        },
                    };
                },
                detailLineChartOptions(metric, title, color) {
                    const theme = this.chartTheme();

                    return {
                        chart: {
                            type: 'line',
                            height: 260,
                            background: 'transparent',
                            toolbar: { show: false },
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 520,
                            },
                        },
                        series: [{ name: title, data: this.trend[metric] }],
                        colors: [color],
                        stroke: {
                            curve: 'smooth',
                            width: 3.5,
                        },
                        markers: {
                            size: 4,
                            strokeWidth: 0,
                            colors: [color],
                            hover: { size: 6 },
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        grid: {
                            borderColor: theme.grid,
                            strokeDashArray: 4,
                        },
                        xaxis: {
                            categories: this.trend.labels,
                            labels: {
                                style: {
                                    colors: theme.text,
                                },
                            },
                            axisBorder: {
                                color: theme.grid,
                            },
                            axisTicks: {
                                color: theme.grid,
                            },
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    colors: theme.text,
                                },
                            },
                        },
                        tooltip: {
                            theme: theme.tooltip,
                        },
                    };
                },
                serverLoadChartOptions() {
                    const theme = this.chartTheme();

                    return {
                        chart: {
                            type: 'bar',
                            height: 260,
                            background: 'transparent',
                            toolbar: { show: false },
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 540,
                            },
                        },
                        series: [{
                            name: 'CPU load',
                            data: this.serverLoads,
                        }],
                        colors: ['#465FFF'],
                        plotOptions: {
                            bar: {
                                columnWidth: '46%',
                                borderRadius: 10,
                                distributed: true,
                            },
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        legend: {
                            show: false,
                        },
                        xaxis: {
                            categories: this.serverLabels,
                            labels: {
                                style: {
                                    colors: theme.text,
                                    fontSize: '11px',
                                },
                            },
                            axisBorder: {
                                color: theme.grid,
                            },
                            axisTicks: {
                                color: theme.grid,
                            },
                        },
                        yaxis: {
                            max: 100,
                            labels: {
                                style: {
                                    colors: theme.text,
                                },
                                formatter(value) {
                                    return `${Math.round(value)}%`;
                                },
                            },
                        },
                        grid: {
                            borderColor: theme.grid,
                            strokeDashArray: 4,
                        },
                        tooltip: {
                            theme: theme.tooltip,
                            y: {
                                formatter(value) {
                                    return `${Math.round(value)}% CPU`;
                                },
                            },
                        },
                    };
                },
                distributionChartOptions() {
                    const theme = this.chartTheme();
                    const readiness = this.readinessScore();

                    return {
                        chart: {
                            type: 'donut',
                            height: 280,
                            background: 'transparent',
                        },
                        series: [
                            this.sensorStates.Stable || 0,
                            this.sensorStates.Warning || 0,
                            this.sensorStates.Critical || 0,
                        ],
                        labels: ['Stable', 'Warning', 'Critical'],
                        colors: ['#34D399', '#F59E0B', '#F43F5E'],
                        legend: {
                            show: false,
                        },
                        stroke: {
                            width: 0,
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '72%',
                                    labels: {
                                        show: true,
                                        name: {
                                            show: true,
                                            color: theme.text,
                                            offsetY: -8,
                                        },
                                        value: {
                                            show: true,
                                            color: theme.mutedText,
                                            fontSize: '28px',
                                            fontWeight: 600,
                                            offsetY: 10,
                                        },
                                        total: {
                                            show: true,
                                            label: 'Readiness',
                                            color: theme.text,
                                            formatter() {
                                                return `${readiness}%`;
                                            },
                                        },
                                    },
                                },
                            },
                        },
                        tooltip: {
                            theme: theme.tooltip,
                        },
                    };
                },
                renderCharts() {
                    if (!window.ApexCharts) {
                        return;
                    }

                    this.charts.trend = new window.ApexCharts(this.$refs.trendChart, this.combinedChartOptions());
                    this.charts.temperature = new window.ApexCharts(this.$refs.temperatureChart, this.detailLineChartOptions('temperature', 'Temperature', '#38BDF8'));
                    this.charts.humidity = new window.ApexCharts(this.$refs.humidityChart, this.detailLineChartOptions('humidity', 'Humidity', '#8B5CF6'));
                    this.charts.serverLoad = new window.ApexCharts(this.$refs.serverLoadChart, this.serverLoadChartOptions());
                    this.charts.distribution = new window.ApexCharts(this.$refs.distributionChart, this.distributionChartOptions());

                    Object.values(this.charts).forEach((chart) => chart.render());
                },
                refreshChartTheme() {
                    const theme = this.chartTheme();

                    if (this.charts.trend) {
                        this.charts.trend.updateOptions({
                            legend: { labels: { colors: theme.text } },
                            xaxis: {
                                labels: { style: { colors: theme.text } },
                                axisBorder: { color: theme.grid },
                                axisTicks: { color: theme.grid },
                            },
                            yaxis: {
                                labels: { style: { colors: theme.text } },
                            },
                            grid: { borderColor: theme.grid },
                            tooltip: { theme: theme.tooltip },
                        }, false, false);
                    }

                    if (this.charts.temperature) {
                        this.charts.temperature.updateOptions({
                            xaxis: {
                                labels: { style: { colors: theme.text } },
                                axisBorder: { color: theme.grid },
                                axisTicks: { color: theme.grid },
                            },
                            yaxis: {
                                labels: { style: { colors: theme.text } },
                            },
                            grid: { borderColor: theme.grid },
                            tooltip: { theme: theme.tooltip },
                        }, false, false);
                    }

                    if (this.charts.humidity) {
                        this.charts.humidity.updateOptions({
                            xaxis: {
                                labels: { style: { colors: theme.text } },
                                axisBorder: { color: theme.grid },
                                axisTicks: { color: theme.grid },
                            },
                            yaxis: {
                                labels: { style: { colors: theme.text } },
                            },
                            grid: { borderColor: theme.grid },
                            tooltip: { theme: theme.tooltip },
                        }, false, false);
                    }

                    if (this.charts.serverLoad) {
                        this.charts.serverLoad.updateOptions({
                            xaxis: {
                                labels: { style: { colors: theme.text, fontSize: '11px' } },
                                axisBorder: { color: theme.grid },
                                axisTicks: { color: theme.grid },
                            },
                            yaxis: {
                                labels: { style: { colors: theme.text } },
                            },
                            grid: { borderColor: theme.grid },
                            tooltip: { theme: theme.tooltip },
                        }, false, false);
                    }

                    if (this.charts.distribution) {
                        const readiness = this.readinessScore();

                        this.charts.distribution.updateOptions({
                            plotOptions: {
                                pie: {
                                    donut: {
                                        labels: {
                                            name: { color: theme.text },
                                            value: { color: theme.mutedText },
                                            total: {
                                                color: theme.text,
                                                formatter() {
                                                    return `${readiness}%`;
                                                },
                                            },
                                        },
                                    },
                                },
                            },
                            tooltip: { theme: theme.tooltip },
                        }, false, false);
                    }
                },
                updateChartsWithTrend() {
                    if (this.charts.trend) {
                        this.charts.trend.updateOptions({
                            xaxis: { categories: this.trend.labels },
                        }, false, false);
                        this.charts.trend.updateSeries(this.combinedSeries(), true);
                    }

                    if (this.charts.temperature) {
                        this.charts.temperature.updateOptions({
                            xaxis: { categories: this.trend.labels },
                        }, false, false);
                        this.charts.temperature.updateSeries([{ name: 'Temperature', data: this.trend.temperature }], true);
                    }

                    if (this.charts.humidity) {
                        this.charts.humidity.updateOptions({
                            xaxis: { categories: this.trend.labels },
                        }, false, false);
                        this.charts.humidity.updateSeries([{ name: 'Humidity', data: this.trend.humidity }], true);
                    }

                    if (this.charts.distribution) {
                        const readiness = this.readinessScore();

                        this.charts.distribution.updateSeries([
                            this.sensorStates.Stable || 0,
                            this.sensorStates.Warning || 0,
                            this.sensorStates.Critical || 0,
                        ]);
                        this.charts.distribution.updateOptions({
                            plotOptions: {
                                pie: {
                                    donut: {
                                        labels: {
                                            total: {
                                                formatter() {
                                                    return `${readiness}%`;
                                                },
                                            },
                                        },
                                    },
                                },
                            },
                        }, false, false);
                    }
                },
                async refreshTrend() {
                    try {
                        this.isSyncing = true;

                        const response = await fetch(this.feedUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        this.trend = await response.json();
                        this.updateSensorStates();
                        this.updateChartsWithTrend();
                        this.lastRefreshLabel = new Date().toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                        });
                    } catch (error) {
                        console.error('Failed to refresh dashboard trend feed', error);
                    } finally {
                        window.setTimeout(() => {
                            this.isSyncing = false;
                        }, 500);
                    }
                },
                init() {
                    this.renderCharts();
                    window.addEventListener('theme-changed', () => this.refreshChartTheme());
                    window.setInterval(() => {
                        this.refreshTrend();
                    }, 3000);
                },
            };
        };
    </script>
</x-app-layout>
