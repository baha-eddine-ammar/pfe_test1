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
            'value' => null,
            'status' => 'Awaiting Sensor',
            'ringDegrees' => 0,
            'unit' => 'm/s',
            'feedUrl' => null,
            'stableColor' => '#34D399',
            'icon' => 'airflow',
            'target' => 'Sensor feed not connected yet',
            'sparkline' => [],
            'trend' => ['percent' => 0, 'direction' => 'flat'],
            'empty' => true,
        ],
        [
            'title' => 'Power Usage',
            'subtitle' => 'UPS and rack draw',
            'value' => null,
            'status' => 'Awaiting Sensor',
            'ringDegrees' => 0,
            'unit' => '%',
            'feedUrl' => null,
            'stableColor' => '#F59E0B',
            'icon' => 'power',
            'target' => 'Sensor feed not connected yet',
            'sparkline' => [],
            'trend' => ['percent' => 0, 'direction' => 'flat'],
            'empty' => true,
        ],
    ];

    // Aggregate counts used by summary widgets and charts.
    $statusCounts = ['Stable' => 0, 'Warning' => 0, 'Critical' => 0];
    foreach ($metricCards as $metricCard) {
        if (($metricCard['empty'] ?? false) || ! array_key_exists($metricCard['status'], $statusCounts)) {
            continue;
        }

        $statusCounts[$metricCard['status']]++;
    }

    $attentionMetrics = $statusCounts['Warning'] + $statusCounts['Critical'];

    $serverStateCounts = ['Live' => 0, 'Warning' => 0, 'Critical' => 0, 'Offline' => 0];
    foreach ($servers as $server) {
        $serverStateCounts[$server['status']] = ($serverStateCounts[$server['status']] ?? 0) + 1;
    }

    $criticalServers = $serverStateCounts['Critical'] ?? 0;
    $warningServers = $serverStateCounts['Warning'] ?? 0;
    $offlineServers = $serverStateCounts['Offline'] ?? 0;
    $alertsTotal = $attentionMetrics + $criticalServers + $warningServers + $offlineServers;
    $serverPanel = $servers[0] ?? null;

    // Compact summary rows displayed below the main telemetry chart.
    $telemetryHighlights = collect($metricCards)
        ->filter(fn (array $card): bool => in_array($card['title'], ['Temperature', 'Humidity'], true))
        ->map(function (array $card): array {
            $statusClass = match ($card['status']) {
                'Stable' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                'Warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                'Critical' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
                default => 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300',
            };

            return [
                'label' => $card['title'],
                'metric' => $card['title'] === 'Temperature' ? 'temperature' : 'humidity',
                'color' => $card['title'] === 'Temperature' ? '#38BDF8' : '#8B5CF6',
                'value' => $card['value'],
                'unit' => $card['unit'] === 'deg C' ? '&deg;C' : $card['unit'],
                'status' => $card['status'],
                'statusClass' => $statusClass,
                'trend' => $card['trend']['label'] ?? 'Flat',
            ];
        })
        ->all();

    $reactDashboardChartProps = [
        'trend' => $trendData,
        'feedUrl' => route('dashboard.trend'),
    ];
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
            'feedUrl' => route('dashboard.trend'),
        ]))"
        x-init="init()"
        data-dashboard-page
        class="dashboard-shell relative isolate mx-auto max-w-[1600px] space-y-6 pb-10 sm:space-y-7"
    >
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[34rem] bg-[radial-gradient(circle_at_top_left,_rgba(70,95,255,0.22),_transparent_32%),radial-gradient(circle_at_top_right,_rgba(14,165,233,0.16),_transparent_28%),linear-gradient(180deg,_rgba(255,255,255,0.96),_rgba(248,250,252,0))] blur-2xl dark:bg-[radial-gradient(circle_at_top_left,_rgba(70,95,255,0.2),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(34,211,238,0.14),_transparent_24%),linear-gradient(180deg,_rgba(15,23,42,0.66),_rgba(2,6,23,0))]"></div>

        {{--
            Dashboard header:
            Introduces the page.
        --}}
        <section>
            <div>
                <p class="app-section-title">Realtime monitoring</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white sm:text-4xl">
                    Server room dashboard
                </h1>
                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500 dark:text-slate-400">
                    Temperature and humidity are now the first thing operators see, with the main telemetry graph directly underneath.
                </p>
                <div class="sr-only">
                    <p>Live PC telemetry has moved to the Server section.</p>
                    <a href="{{ route('servers.index') }}">Open Server Section</a>
                </div>
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
                    :empty="$card['empty'] ?? false"
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
                title="Environmental telemetry"
                description="Live ESP32 DHT22 readings from sensor_readings, displayed in Tunisia local time."
                height="h-[620px]"
            >
                <x-slot name="action">
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-[11px] font-bold uppercase tracking-[0.24em] shadow-sm transition-all duration-300"
                            :class="hasTelemetryData()
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 shadow-emerald-500/10 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300'
                                : 'border-amber-200 bg-amber-50 text-amber-700 shadow-amber-500/10 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300'"
                        >
                            <span class="h-2 w-2 rounded-full shadow-[0_0_0_5px_rgba(16,185,129,0.12)]"
                                :class="hasTelemetryData() ? 'bg-emerald-500' : 'bg-amber-500'"
                            ></span>
                            <span x-text="isSyncing ? 'Syncing sensor' : (hasTelemetryData() ? 'Streaming live' : 'Awaiting ESP32')"></span>
                        </span>
                        <span class="app-pill bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300" x-text="`${trend.labels.length} samples`">
                        </span>
                    </div>
                </x-slot>

                <div class="flex h-full flex-col">
                    <div class="mb-5 grid gap-3 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-stretch">
                        <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($telemetryHighlights as $telemetryHighlight)
                            <div class="dashboard-surface-glass group rounded-[26px] border border-white/70 px-5 py-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-slate-950/5 dark:border-white/10 dark:hover:shadow-black/20">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full shadow-[0_0_18px_currentColor]" style="background-color: {{ $telemetryHighlight['color'] }}; color: {{ $telemetryHighlight['color'] }}"></span>
                                            <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">{{ $telemetryHighlight['label'] }}</p>
                                        </div>
                                        <p
                                            class="mt-3 font-display text-4xl font-semibold tracking-[-0.05em] text-slate-950 dark:text-white"
                                            x-text="latestReadingLabel('{{ $telemetryHighlight['metric'] }}')"
                                        >
                                            @if ($trendData['hasData'] ?? false)
                                                {{ number_format((float) $telemetryHighlight['value'], 1) }}{!! $telemetryHighlight['unit'] !!}
                                            @else
                                                Awaiting sensor
                                            @endif
                                        </p>
                                    </div>

                                    <div>
                                        <span
                                            class="app-pill"
                                            :class="latestStatusClass('{{ $telemetryHighlight['metric'] }}')"
                                            x-text="latestStatus('{{ $telemetryHighlight['metric'] }}')"
                                        >{{ $telemetryHighlight['status'] }}</span>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                                    <span x-text="liveTrendLabel('{{ $telemetryHighlight['metric'] }}')">Trend {{ $telemetryHighlight['trend'] }}</span>
                                    <span>{{ $telemetryHighlight['metric'] === 'temperature' ? 'Blue line' : 'Purple line' }}</span>
                                </div>
                            </div>
                        @endforeach
                        </div>

                        <div class="dashboard-surface-glass rounded-[26px] border border-white/70 px-5 py-4 shadow-sm dark:border-white/10 xl:min-w-[250px]">
                            <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Last sensor update</p>
                            <p class="mt-3 font-display text-2xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white" x-text="latestTimestampLabel()"></p>
                            <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                Tunisia local time
                                <span class="font-semibold text-slate-700 dark:text-slate-200">(Africa/Tunis)</span>
                            </p>
                            <div class="mt-4 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                                <span class="h-2 w-2 rounded-full" :class="isSyncing ? 'bg-sky-400 animate-pulse' : 'bg-slate-300 dark:bg-slate-600'"></span>
                                <span x-text="isSyncing ? 'Syncing chart' : 'WebSocket live'"></span>
                            </div>
                        </div>
                    </div>

                    <div class="relative min-h-0 flex-1 overflow-hidden rounded-[32px] border border-slate-200/70 bg-[radial-gradient(circle_at_12%_0%,rgba(56,189,248,0.16),transparent_26%),radial-gradient(circle_at_88%_0%,rgba(139,92,246,0.16),transparent_26%),linear-gradient(180deg,rgba(255,255,255,0.92),rgba(248,250,252,0.72))] px-3 py-4 shadow-inner shadow-slate-950/[0.03] dark:border-white/10 dark:bg-[radial-gradient(circle_at_12%_0%,rgba(56,189,248,0.14),transparent_26%),radial-gradient(circle_at_88%_0%,rgba(139,92,246,0.15),transparent_26%),linear-gradient(180deg,rgba(15,23,42,0.72),rgba(2,6,23,0.48))] sm:px-5">
                        <div class="pointer-events-none absolute inset-x-8 top-8 h-px bg-gradient-to-r from-transparent via-sky-300/70 to-violet-300/70 dark:via-sky-400/30 dark:to-violet-400/30"></div>
                        <div
                            data-react-dashboard-telemetry-chart
                            data-props='{{ json_encode($reactDashboardChartProps, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT) }}'
                            class="h-full min-h-[330px]"
                        ></div>
                        <div
                            data-react-chart-fallback
                            x-show="!hasTelemetryData()"
                            x-cloak
                            class="absolute inset-5 z-10 grid place-items-center rounded-[26px] border border-dashed border-slate-300/70 bg-white/80 text-center backdrop-blur-sm dark:border-white/10 dark:bg-slate-950/70"
                        >
                            <div class="max-w-sm px-6">
                                <p class="font-display text-2xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white">Waiting for real ESP32 data</p>
                                <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">
                                    As soon as the DHT22 posts to Laravel, this chart will render the real temperature and humidity timeline.
                                </p>
                            </div>
                        </div>
                        <div data-react-chart-fallback x-ref="trendChart" class="h-full min-h-[330px]"></div>
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

            </div>
        </section>

        {{--
            Secondary charts:
            Smaller focused views for temperature, humidity, and server CPU.
        --}}
        <section class="grid gap-6 xl:grid-cols-2">
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
        </section>

    </div>

    <script>
        /*
         * Page-local frontend logic for the dashboard.
         *
         * What this script does:
         * - powers each live metric card
         * - renders ApexCharts charts
         * - applies Reverb/WebSocket telemetry events without periodic polling
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
                empty: Boolean(config.empty),
                refreshPulse: false,
                chart: null,
                intervalHandle: null,
                statusTone() {
                    if (this.empty) {
                        return '#94A3B8';
                    }

                    return this.status === 'Critical'
                        ? '#F43F5E'
                        : (this.status === 'Warning' ? '#F59E0B' : this.stableColor);
                },
                statusClasses() {
                    if (this.empty) {
                        return {
                            'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-slate-400': true,
                        };
                    }

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
                    if (this.empty) {
                        return '--';
                    }

                    return Number(this.value).toFixed(1);
                },
                displayPercent() {
                    if (this.empty) {
                        return '--';
                    }

                    return `${Math.round(this.ringDegrees / 3.6)}%`;
                },
                displayTrend() {
                    if (this.empty) {
                        return 'No data';
                    }

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
                    if (this.empty) {
                        return 'width:0%;background:transparent;';
                    }

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
                applyRealtimeTrendPayload(payload) {
                    if (!payload?.trend) {
                        return;
                    }

                    const metricKey = this.title.toLowerCase();
                    const series = payload.trend?.[metricKey];

                    if (!Array.isArray(series) || series.length === 0) {
                        return;
                    }

                    const nextValue = Number(series[series.length - 1]);
                    const previous = Number(this.value);
                    this.value = nextValue;

                    if (this.title === 'Temperature') {
                        this.status = nextValue >= 30 ? 'Critical' : (nextValue >= 25 ? 'Warning' : 'Stable');
                        this.ringDegrees = Math.round((Math.max(0, Math.min(100, ((nextValue - 10) / 30) * 100)) / 100) * 360);
                    }

                    if (this.title === 'Humidity') {
                        this.status = (nextValue >= 70 || nextValue <= 30)
                            ? 'Critical'
                            : ((nextValue >= 60 || nextValue <= 35) ? 'Warning' : 'Stable');
                        this.ringDegrees = Math.round((Math.max(0, Math.min(100, nextValue)) / 100) * 360);
                    }

                    this.sparkline = series.slice(-10);

                    const deltaPercent = previous !== 0 ? ((nextValue - previous) / previous) * 100 : 0;
                    this.trendPercent = Number(deltaPercent.toFixed(1));
                    this.trendDirection = deltaPercent > 0 ? 'up' : (deltaPercent < 0 ? 'down' : 'flat');

                    if (this.chart) {
                        this.chart.updateOptions({
                            colors: [this.statusTone()],
                            tooltip: {
                                theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                            },
                        }, false, false);
                        this.chart.updateSeries([{ data: this.sparkline }], true);
                    }

                    this.empty = false;
                    this.refreshPulse = true;
                    window.setTimeout(() => {
                        this.refreshPulse = false;
                    }, 700);
                },
                init() {
                    if (this.empty) {
                        return;
                    }

                    if (window.ApexCharts && this.$refs.sparkline) {
                        this.chart = new window.ApexCharts(this.$refs.sparkline, this.chartOptions());
                        this.chart.render();
                    }

                    window.addEventListener('sensor-telemetry-updated', (event) => {
                        this.applyRealtimeTrendPayload(event.detail);
                    });

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

        window.liveServerPanel = window.liveServerPanel || function (config) {
            return {
                server: JSON.parse(JSON.stringify(config.initialServer || {})),
                feedUrl: config.feedUrl,
                refreshing: false,
                intervalHandle: null,
                statusPillClass(status) {
                    return {
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300': status === 'Live',
                        'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300': status === 'Warning',
                        'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300': status === 'Critical',
                        'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-slate-300': status === 'Offline',
                    };
                },
                metricTextClass(color) {
                    return {
                        cyan: 'text-sky-600 dark:text-sky-300',
                        violet: 'text-violet-600 dark:text-violet-300',
                        pink: 'text-pink-600 dark:text-pink-300',
                        emerald: 'text-emerald-600 dark:text-emerald-300',
                        amber: 'text-amber-600 dark:text-amber-300',
                    }[color] || 'text-slate-600 dark:text-slate-300';
                },
                metricBarClass(color) {
                    return {
                        cyan: 'from-sky-400 via-cyan-400 to-sky-500',
                        violet: 'from-violet-400 via-fuchsia-400 to-violet-500',
                        pink: 'from-pink-400 via-rose-400 to-pink-500',
                        emerald: 'from-emerald-400 via-lime-400 to-emerald-500',
                        amber: 'from-amber-400 via-orange-400 to-amber-500',
                    }[color] || 'from-slate-400 via-slate-500 to-slate-600';
                },
                progressStyle(progress) {
                    return `width: ${Math.max(0, Math.min(100, Number(progress || 0)))}%;`;
                },
                summaryMetrics() {
                    return Array.isArray(this.server.metrics)
                        ? this.server.metrics.slice(0, 3)
                        : [];
                },
                applyRealtimePayload(payload) {
                    if (!payload?.server) {
                        return;
                    }

                    if (String(payload.server.id) !== String(this.server.id)) {
                        return;
                    }

                    this.server = payload.server;
                    this.refreshing = false;
                },
                init() {
                    if (!this.feedUrl) {
                        return;
                    }

                    window.addEventListener('server-metric-stored', (event) => {
                        this.applyRealtimePayload(event.detail);
                    });
                },
            };
        };

        window.serverRoomDashboard = window.serverRoomDashboard || function (config) {
            return {
                trend: JSON.parse(JSON.stringify(config.trend)),
                sensorStates: { ...config.sensorStates },
                feedUrl: config.feedUrl,
                charts: {},
                isSyncing: false,
                lastRefreshLabel: config.trend.lastUpdatedLabel || 'Waiting for ESP32 readings',
                chartTheme() {
                    const dark = document.documentElement.classList.contains('dark');

                    return {
                        dark,
                        text: dark ? '#94A3B8' : '#64748B',
                        mutedText: dark ? '#CBD5E1' : '#0F172A',
                        panel: dark ? '#020617' : '#FFFFFF',
                        grid: dark ? 'rgba(148, 163, 184, 0.12)' : 'rgba(148, 163, 184, 0.22)',
                        tooltip: dark ? 'dark' : 'light',
                    };
                },
                hasTelemetryData() {
                    return Boolean(this.trend?.hasData)
                        && (this.trend.temperature?.length || 0) > 0
                        && (this.trend.humidity?.length || 0) > 0;
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

                    const temperatureScore = 100 - Math.min(100, Math.abs(temperature - 22) * 9);
                    const humidityScore = 100 - Math.min(100, Math.abs(humidity - 47.5) * 3.2);

                    return Math.round(Math.max(0, Math.min(100, (
                        temperatureScore + humidityScore
                    ) / 2)));
                },
                formatTelemetryValue(seriesName, value) {
                    const numericValue = Number(value);

                    if (seriesName === 'Temperature') {
                        return `${numericValue.toFixed(1)}\u00B0C`;
                    }

                    if (seriesName === 'Humidity') {
                        return `${numericValue.toFixed(1)}%`;
                    }

                    return `${numericValue.toFixed(1)}%`;
                },
                latestReading(metric) {
                    const values = this.trend?.[metric] || [];
                    const rawValue = values[values.length - 1];

                    if (rawValue === null || rawValue === undefined) {
                        return null;
                    }

                    const value = Number(rawValue);

                    return Number.isFinite(value) ? value : null;
                },
                latestTimestampLabel() {
                    return this.trend?.latest?.fullLabel || 'No sensor reading yet';
                },
                latestReadingLabel(metric) {
                    const value = this.latestReading(metric);

                    if (value === null) {
                        return 'Awaiting sensor';
                    }

                    return metric === 'temperature'
                        ? `${value.toFixed(1)}\u00B0C`
                        : `${value.toFixed(1)}%`;
                },
                latestStatus(metric) {
                    const value = this.latestReading(metric);

                    if (value === null) {
                        return 'Awaiting Sensor';
                    }

                    return this.metricStatus(metric, value);
                },
                latestStatusClass(metric) {
                    const status = this.latestStatus(metric);

                    return {
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300': status === 'Stable',
                        'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300': status === 'Warning',
                        'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300': status === 'Critical',
                        'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300': status === 'Awaiting Sensor',
                    };
                },
                liveTrendLabel(metric) {
                    const values = this.trend?.[metric] || [];

                    if (values.length < 2) {
                        return 'Trend awaiting data';
                    }

                    const latest = Number(values[values.length - 1]);
                    const previous = Number(values[values.length - 2]);
                    const delta = latest - previous;
                    const direction = delta > 0 ? 'rising' : (delta < 0 ? 'falling' : 'steady');
                    const unit = metric === 'temperature' ? '\u00B0C' : '%';
                    const sign = delta > 0 ? '+' : '';

                    return `Trend ${direction} ${sign}${delta.toFixed(1)}${unit}`;
                },
                combinedSeries() {
                    return [
                        { name: 'Temperature', data: this.trend.temperature },
                        { name: 'Humidity', data: this.trend.humidity },
                    ];
                },
                metricStatus(metric, value) {
                    if (metric === 'temperature') {
                        return value >= 30 ? 'Critical' : (value >= 25 ? 'Warning' : 'Stable');
                    }

                    if (metric === 'humidity') {
                        return value >= 70 || value <= 30 ? 'Critical' : (value >= 60 || value <= 35 ? 'Warning' : 'Stable');
                    }

                    return 'Stable';
                },
                updateSensorStates() {
                    const nextCounts = { Stable: 0, Warning: 0, Critical: 0 };
                    const latestValues = {
                        temperature: this.trend.temperature[this.trend.temperature.length - 1] ?? 0,
                        humidity: this.trend.humidity[this.trend.humidity.length - 1] ?? 0,
                    };

                    Object.entries(latestValues).forEach(([metric, value]) => {
                        if (value === null || value === undefined) {
                            return;
                        }

                        nextCounts[this.metricStatus(metric, Number(value))]++;
                    });

                    this.sensorStates = nextCounts;
                },
                telemetryYRange() {
                    const values = [
                        ...(this.trend.temperature || []),
                        ...(this.trend.humidity || []),
                    ].filter((value) => value !== null && value !== undefined)
                        .map(Number)
                        .filter(Number.isFinite);

                    if (values.length === 0) {
                        return { min: 0, max: 100 };
                    }

                    const min = Math.min(...values);
                    const max = Math.max(...values);
                    const padding = Math.max(4, (max - min) * 0.16);

                    return {
                        min: Math.max(0, Math.floor(min - padding)),
                        max: Math.ceil(max + padding),
                    };
                },
                telemetryMarkers() {
                    const markers = [];
                    const temperature = this.trend.temperature || [];
                    const humidity = this.trend.humidity || [];
                    const latestIndex = Math.max(temperature.length, humidity.length) - 1;

                    temperature.forEach((value, index) => {
                        if (value === null || value === undefined) {
                            return;
                        }

                        if (Number(value) >= 25 || index === latestIndex) {
                            markers.push({
                                seriesIndex: 0,
                                dataPointIndex: index,
                                fillColor: Number(value) >= 30 ? '#F43F5E' : '#38BDF8',
                                strokeColor: '#FFFFFF',
                                size: index === latestIndex ? 6 : 4,
                            });
                        }
                    });

                    humidity.forEach((value, index) => {
                        if (value === null || value === undefined) {
                            return;
                        }

                        if (Number(value) >= 60 || Number(value) <= 35 || index === latestIndex) {
                            markers.push({
                                seriesIndex: 1,
                                dataPointIndex: index,
                                fillColor: Number(value) >= 70 || Number(value) <= 30 ? '#F43F5E' : '#8B5CF6',
                                strokeColor: '#FFFFFF',
                                size: index === latestIndex ? 6 : 4,
                            });
                        }
                    });

                    return markers.slice(-28);
                },
                telemetryTooltip({ series, dataPointIndex, w }) {
                    const theme = this.chartTheme();
                    const timestamp = this.trend.tooltipLabels?.[dataPointIndex]
                        || this.trend.labels?.[dataPointIndex]
                        || 'Tunisia time unavailable';
                    const temperature = Number(series[0]?.[dataPointIndex]);
                    const humidity = Number(series[1]?.[dataPointIndex]);
                    const temperatureLabel = Number.isFinite(temperature) ? `${temperature.toFixed(1)}\u00B0C` : 'No data';
                    const humidityLabel = Number.isFinite(humidity) ? `${humidity.toFixed(1)}%` : 'No data';

                    return `
                        <div style="min-width: 230px; padding: 14px 15px; border-radius: 18px; background: ${theme.dark ? 'rgba(2, 6, 23, 0.96)' : 'rgba(255, 255, 255, 0.98)'}; box-shadow: 0 22px 55px rgba(15, 23, 42, 0.18); border: 1px solid ${theme.dark ? 'rgba(255,255,255,0.1)' : 'rgba(148,163,184,0.22)'};">
                            <div style="font-size: 11px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: ${theme.text};">Tunisia local time</div>
                            <div style="margin-top: 5px; font-size: 13px; font-weight: 800; color: ${theme.dark ? '#F8FAFC' : '#0F172A'};">${timestamp}</div>
                            <div style="display: grid; gap: 9px; margin-top: 13px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                                    <span style="display:flex; align-items:center; gap:8px; color:${theme.text}; font-size:12px; font-weight:700;"><span style="width:9px;height:9px;border-radius:999px;background:#38BDF8;box-shadow:0 0 14px rgba(56,189,248,.55);"></span>${w.globals.seriesNames[0]}</span>
                                    <strong style="color:${theme.dark ? '#F8FAFC' : '#0F172A'}; font-size:16px;">${temperatureLabel}</strong>
                                </div>
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                                    <span style="display:flex; align-items:center; gap:8px; color:${theme.text}; font-size:12px; font-weight:700;"><span style="width:9px;height:9px;border-radius:999px;background:#8B5CF6;box-shadow:0 0 14px rgba(139,92,246,.55);"></span>${w.globals.seriesNames[1]}</span>
                                    <strong style="color:${theme.dark ? '#F8FAFC' : '#0F172A'}; font-size:16px;">${humidityLabel}</strong>
                                </div>
                            </div>
                        </div>
                    `;
                },
                combinedChartOptions() {
                    const theme = this.chartTheme();
                    const yRange = this.telemetryYRange();

                    return {
                        chart: {
                            type: 'line',
                            height: 360,
                            background: 'transparent',
                            toolbar: { show: false },
                            zoom: { enabled: false },
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 720,
                                animateGradually: {
                                    enabled: true,
                                    delay: 80,
                                },
                                dynamicAnimation: {
                                    enabled: true,
                                    speed: 520,
                                },
                            },
                            dropShadow: {
                                enabled: true,
                                top: 8,
                                left: 0,
                                blur: 14,
                                opacity: theme.dark ? 0.28 : 0.16,
                            },
                        },
                        series: this.combinedSeries(),
                        colors: ['#38BDF8', '#8B5CF6'],
                        stroke: {
                            curve: 'smooth',
                            width: [4.2, 4.2],
                            lineCap: 'round',
                        },
                        fill: {
                            type: 'solid',
                            opacity: 1,
                        },
                        legend: {
                            show: true,
                            position: 'top',
                            horizontalAlign: 'right',
                            fontSize: '13px',
                            fontWeight: 700,
                            labels: {
                                colors: theme.dark ? '#E2E8F0' : '#0F172A',
                            },
                            markers: {
                                width: 10,
                                height: 10,
                                radius: 999,
                            },
                            itemMargin: {
                                horizontal: 14,
                                vertical: 8,
                            },
                        },
                        markers: {
                            size: 0,
                            strokeWidth: 2,
                            strokeColors: theme.dark ? '#020617' : '#FFFFFF',
                            discrete: this.telemetryMarkers(),
                            hover: {
                                size: 7,
                            },
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        xaxis: {
                            categories: this.trend.labels,
                            tickAmount: Math.min(8, Math.max(2, this.trend.labels.length - 1)),
                            labels: {
                                style: {
                                    colors: theme.text,
                                    fontSize: '12px',
                                    fontWeight: 700,
                                },
                                trim: true,
                                hideOverlappingLabels: true,
                                rotate: 0,
                            },
                            axisBorder: {
                                color: theme.grid,
                            },
                            axisTicks: {
                                color: theme.grid,
                            },
                        },
                        yaxis: {
                            min: yRange.min,
                            max: yRange.max,
                            decimalsInFloat: 1,
                            forceNiceScale: true,
                            title: {
                                text: 'Sensor value',
                                style: {
                                    color: theme.text,
                                    fontSize: '12px',
                                    fontWeight: 800,
                                },
                            },
                            labels: {
                                style: {
                                    colors: theme.text,
                                    fontSize: '12px',
                                    fontWeight: 700,
                                },
                                formatter(value) {
                                    return Number(value).toFixed(1);
                                },
                            },
                        },
                        grid: {
                            borderColor: theme.grid,
                            strokeDashArray: 5,
                            padding: {
                                left: 18,
                                right: 20,
                                top: 10,
                                bottom: 6,
                            },
                        },
                        noData: {
                            text: 'Waiting for ESP32 sensor readings',
                            align: 'center',
                            verticalAlign: 'middle',
                            style: {
                                color: theme.text,
                                fontSize: '14px',
                            },
                        },
                        tooltip: {
                            theme: theme.tooltip,
                            shared: true,
                            intersect: false,
                            marker: { show: true },
                            custom: (context) => this.telemetryTooltip(context),
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
                    this.charts.distribution = new window.ApexCharts(this.$refs.distributionChart, this.distributionChartOptions());

                    Object.values(this.charts).forEach((chart) => chart.render());
                },
                refreshChartTheme() {
                    const theme = this.chartTheme();

                    if (this.charts.trend) {
                        const yRange = this.telemetryYRange();

                        this.charts.trend.updateOptions({
                            legend: { labels: { colors: theme.text } },
                            xaxis: {
                                labels: { style: { colors: theme.text, fontSize: '12px', fontWeight: 700 } },
                                axisBorder: { color: theme.grid },
                                axisTicks: { color: theme.grid },
                            },
                            yaxis: {
                                min: yRange.min,
                                max: yRange.max,
                                title: { style: { color: theme.text } },
                                labels: { style: { colors: theme.text, fontSize: '12px', fontWeight: 700 } },
                            },
                            markers: { discrete: this.telemetryMarkers() },
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
                        const yRange = this.telemetryYRange();

                        this.charts.trend.updateOptions({
                            xaxis: {
                                categories: this.trend.labels,
                                tickAmount: Math.min(8, Math.max(2, this.trend.labels.length - 1)),
                            },
                            yaxis: {
                                min: yRange.min,
                                max: yRange.max,
                            },
                            markers: {
                                discrete: this.telemetryMarkers(),
                            },
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
                applyRealtimeTrend(payload) {
                    if (!payload?.trend) {
                        return;
                    }

                    this.trend = payload.trend;
                    this.updateSensorStates();
                    this.updateChartsWithTrend();
                    this.lastRefreshLabel = this.trend.lastUpdatedLabel || 'Waiting for ESP32 readings';
                },
                init() {
                    this.renderCharts();
                    window.addEventListener('theme-changed', () => this.refreshChartTheme());
                    window.addEventListener('sensor-telemetry-updated', (event) => {
                        this.applyRealtimeTrend(event.detail);
                    });
                },
            };
        };
    </script>
</x-app-layout>
