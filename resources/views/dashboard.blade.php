@php
    $metricCards = [
        [
            'title' => 'Temperature',
            'subtitle' => 'Room condition',
            'value' => $temperatureData['value'],
            'status' => $temperatureData['status'],
            'ringDegrees' => $temperatureData['ringDegrees'],
            'unit' => 'deg C',
            'feedUrl' => route('dashboard.temperature'),
            'stableColor' => '#38BDF8',
            'icon' => 'temperature',
            'iconClass' => 'bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300',
            'target' => 'Target 18-25 deg C',
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
            'iconClass' => 'bg-violet-100 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300',
            'target' => 'Comfort band 35-60%',
        ],
        [
            'title' => 'Air Flow',
            'subtitle' => 'Ventilation rate',
            'value' => $airFlowData['value'],
            'status' => $airFlowData['status'],
            'ringDegrees' => $airFlowData['ringDegrees'],
            'unit' => 'm/s',
            'feedUrl' => route('dashboard.airflow'),
            'stableColor' => '#34D399',
            'icon' => 'airflow',
            'iconClass' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
            'target' => 'Cooling route stable',
        ],
        [
            'title' => 'Power Usage',
            'subtitle' => 'Energy load',
            'value' => $powerUsageData['value'],
            'status' => $powerUsageData['status'],
            'ringDegrees' => $powerUsageData['ringDegrees'],
            'unit' => '%',
            'feedUrl' => route('dashboard.power'),
            'stableColor' => '#F59E0B',
            'icon' => 'power',
            'iconClass' => 'bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
            'target' => 'UPS load under 80%',
        ],
    ];

    $statusCounts = ['Stable' => 0, 'Warning' => 0, 'Critical' => 0];
    foreach ($metricCards as $metricCard) {
        $statusCounts[$metricCard['status']]++;
    }

    $totalStatuses = array_sum($statusCounts);
    $averageMetricLoad = count($metricCards) > 0
        ? round(array_sum(array_map(fn ($card) => $card['ringDegrees'] / 3.6, $metricCards)) / count($metricCards), 1)
        : 0;

    $attentionMetrics = $statusCounts['Warning'] + $statusCounts['Critical'];

    $serverStateCounts = ['Online' => 0, 'Warning' => 0, 'Critical' => 0];
    foreach ($servers as $server) {
        $serverStateCounts[$server['status']] = ($serverStateCounts[$server['status']] ?? 0) + 1;
    }

    $priorityServers = array_slice($servers, 0, 3);
    $targetGaugeDegrees = round(($averageMetricLoad / 100) * 360, 1);
    $onlineServers = $serverStateCounts['Online'] ?? 0;
    $summaryCards = [
        [
            'label' => 'Environment Sensors',
            'value' => count($metricCards),
            'note' => 'Live cards updating every 3 seconds',
            'pill' => 'Realtime',
            'pillClass' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        ],
        [
            'label' => 'Monitored Servers',
            'value' => count($servers),
            'note' => count($servers).' monitored nodes',
            'pill' => $onlineServers.' online',
            'pillClass' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300',
        ],
        [
            'label' => 'Attention Needed',
            'value' => $attentionMetrics,
            'note' => 'Warning or critical states',
            'pill' => $attentionMetrics > 0 ? 'Review' : 'Clear',
            'pillClass' => $attentionMetrics > 0
                ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'
                : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        ],
        [
            'label' => 'Room Target',
            'value' => number_format($averageMetricLoad, 0).'%',
            'note' => 'Average readiness across current metrics',
            'pill' => 'Capacity',
            'pillClass' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300',
        ],
    ];
@endphp

<x-app-layout>
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="app-section-title">Overview</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Server Room Analytics
                </h1>
                <p class="mt-3 max-w-3xl text-sm leading-7 text-gray-500 dark:text-gray-400">
                    Clean monitoring cards, live sensor movement, room health, and server activity in one dashboard.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="inline-flex items-center gap-1 rounded-2xl bg-gray-100 p-1 dark:bg-white/[0.03]">
                    <button type="button" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white">Overview</button>
                    <button type="button" class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-500 dark:text-gray-400">Today</button>
                    <button type="button" class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-500 dark:text-gray-400">7 Days</button>
                </div>
                <span class="app-pill bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                    Auto refresh 3s
                </span>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($summaryCards as $summaryCard)
                <div class="app-card px-5 py-5 md:px-6">
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $summaryCard['label'] }}</p>
                        <span class="app-pill {{ $summaryCard['pillClass'] }}">{{ $summaryCard['pill'] }}</span>
                    </div>
                    <p class="mt-4 font-display text-[2.25rem] font-semibold tracking-tight text-gray-900 dark:text-white">{{ $summaryCard['value'] }}</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $summaryCard['note'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-7xl">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($metricCards as $card)
                <article
                    x-data="{
                        value: {{ json_encode($card['value']) }},
                        status: {{ json_encode($card['status']) }},
                        ringDegrees: {{ json_encode($card['ringDegrees']) }},
                        unit: {{ json_encode($card['unit']) }},
                        feedUrl: {{ json_encode($card['feedUrl']) }},
                        stableColor: {{ json_encode($card['stableColor']) }},
                        statusClasses() {
                            return {
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300': this.status === 'Stable',
                                'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300': this.status === 'Warning',
                                'bg-pink-100 text-pink-700 dark:bg-pink-500/10 dark:text-pink-300': this.status === 'Critical',
                            };
                        },
                        valueClasses() {
                            return {
                                'text-emerald-600 dark:text-emerald-300': this.status === 'Stable',
                                'text-amber-600 dark:text-amber-300': this.status === 'Warning',
                                'text-pink-600 dark:text-pink-300': this.status === 'Critical',
                            };
                        },
                        barStyle() {
                            const color = this.status === 'Critical'
                                ? '#EC4899'
                                : (this.status === 'Warning' ? '#F59E0B' : this.stableColor);

                            const width = Math.max(10, Math.round(this.ringDegrees / 3.6));
                            return `width: ${width}%; background: linear-gradient(90deg, ${color}, ${color}cc)`;
                        },
                        displayValue() {
                            return Number(this.value).toFixed(1);
                        },
                        displayPercent() {
                            return `${Math.round(this.ringDegrees / 3.6)}%`;
                        },
                        async refreshCard() {
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
                            this.value = data.value;
                            this.status = data.status;
                            this.ringDegrees = data.ringDegrees;
                        },
                        init() {
                            setInterval(() => {
                                this.refreshCard();
                            }, 3000);
                        },
                    }"
                    class="app-card app-card-hover p-5 md:p-6"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl {{ $card['iconClass'] }}">
                            @if ($card['icon'] === 'temperature')
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <path d="M12 4a2 2 0 00-2 2v7.6a4 4 0 104 0V6a2 2 0 00-2-2z"></path>
                                    <path d="M12 11v5" stroke-linecap="round"></path>
                                </svg>
                            @elseif ($card['icon'] === 'humidity')
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <path d="M12 4c3.5 4.2 5.5 7 5.5 9.5A5.5 5.5 0 116.5 13.5C6.5 11 8.5 8.2 12 4z"></path>
                                </svg>
                            @elseif ($card['icon'] === 'airflow')
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <path d="M4 9h9a3 3 0 100-6" stroke-linecap="round"></path>
                                    <path d="M4 15h12a3 3 0 110 6" stroke-linecap="round"></path>
                                    <path d="M4 12h16" stroke-linecap="round"></path>
                                </svg>
                            @else
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <path d="M13 3L4 14h6l-1 7 9-11h-6l1-7z" stroke-linejoin="round"></path>
                                </svg>
                            @endif
                        </div>

                        <span class="app-pill px-3 py-1" :class="statusClasses()" x-text="status"></span>
                    </div>

                    <div class="mt-6">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['subtitle'] }}</p>
                        <h2 class="mt-2 font-display text-[1.8rem] font-semibold tracking-tight text-gray-900 dark:text-white">
                            {{ $card['title'] }}
                        </h2>
                    </div>

                    <div class="mt-7">
                        <div class="flex items-end justify-between gap-4">
                            <p class="font-display text-4xl font-semibold leading-none text-gray-900 dark:text-white">
                                <span x-text="displayValue()"></span>
                                <span class="ms-1 text-xl font-medium text-gray-400 dark:text-gray-500">{{ $card['unit'] }}</span>
                            </p>
                            <span class="text-sm font-medium text-gray-400 dark:text-gray-500" x-text="displayPercent()"></span>
                        </div>

                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ $card['target'] }}</p>
                    </div>

                    <div class="mt-6">
                        <div class="metric-progress-track">
                            <div class="h-full rounded-full transition-all duration-500" :style="barStyle()"></div>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                            <span>Live feed</span>
                            <span :class="valueClasses()" x-text="status"></span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-7xl">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(360px,1fr)]">
            <div
                x-data="{
                    trend: @js($trendData),
                    feedUrl: '{{ route('dashboard.trend') }}',
                    chart: null,
                    chartTheme() {
                        const dark = document.documentElement.classList.contains('dark');

                        return {
                            labelColor: dark ? '#94A3B8' : '#6B7280',
                            gridColor: dark ? 'rgba(71,85,105,0.22)' : 'rgba(226,232,240,1)',
                            tooltipTheme: dark ? 'dark' : 'light',
                        };
                    },
                    buildSeries(data) {
                        return [
                            { name: 'Temperature', data: data.temperature },
                            { name: 'Humidity', data: data.humidity },
                            { name: 'Air Flow', data: data.airFlow },
                            { name: 'Power Usage', data: data.powerUsage },
                        ];
                    },
                    buildAnnotations(data) {
                        return {
                            points: [
                                {
                                    x: data.labels[1],
                                    y: data.temperature[1],
                                    marker: { size: 4, fillColor: '#38BDF8', strokeColor: '#38BDF8' },
                                    label: { text: 'T', borderColor: 'rgba(56,189,248,0.18)', style: { background: '#ffffff', color: '#111827', fontSize: '11px' } },
                                },
                                {
                                    x: data.labels[3],
                                    y: data.humidity[3],
                                    marker: { size: 4, fillColor: '#8B5CF6', strokeColor: '#8B5CF6' },
                                    label: { text: 'H', borderColor: 'rgba(139,92,246,0.18)', style: { background: '#ffffff', color: '#111827', fontSize: '11px' } },
                                },
                                {
                                    x: data.labels[5],
                                    y: data.powerUsage[5],
                                    marker: { size: 4, fillColor: '#F59E0B', strokeColor: '#F59E0B' },
                                    label: { text: 'P', borderColor: 'rgba(245,158,11,0.18)', style: { background: '#ffffff', color: '#111827', fontSize: '11px' } },
                                },
                            ],
                        };
                    },
                    chartOptions(data) {
                        const theme = this.chartTheme();

                        return {
                            chart: {
                                type: 'area',
                                height: 340,
                                toolbar: { show: false },
                                background: 'transparent',
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 650,
                                },
                            },
                            series: this.buildSeries(data),
                            colors: ['#465FFF', '#8B5CF6', '#22C55E', '#F59E0B'],
                            stroke: {
                                curve: 'smooth',
                                width: 3,
                            },
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.16,
                                    opacityTo: 0.02,
                                    stops: [0, 85, 100],
                                },
                            },
                            dataLabels: {
                                enabled: false,
                            },
                            legend: {
                                show: true,
                                position: 'top',
                                horizontalAlign: 'left',
                                labels: {
                                    colors: theme.labelColor,
                                },
                            },
                            markers: {
                                size: 0,
                                hover: {
                                    size: 5,
                                },
                            },
                            xaxis: {
                                categories: data.labels,
                                labels: {
                                    style: {
                                        colors: theme.labelColor,
                                    },
                                },
                                axisBorder: {
                                    color: theme.gridColor,
                                },
                                axisTicks: {
                                    color: theme.gridColor,
                                },
                            },
                            yaxis: {
                                labels: {
                                    style: {
                                        colors: theme.labelColor,
                                    },
                                },
                            },
                            grid: {
                                borderColor: theme.gridColor,
                                strokeDashArray: 4,
                            },
                            tooltip: {
                                theme: theme.tooltipTheme,
                            },
                            annotations: this.buildAnnotations(data),
                        };
                    },
                    applyTheme() {
                        if (!this.chart) {
                            return;
                        }

                        const theme = this.chartTheme();

                        this.chart.updateOptions({
                            legend: { labels: { colors: theme.labelColor } },
                            xaxis: {
                                labels: { style: { colors: theme.labelColor } },
                                axisBorder: { color: theme.gridColor },
                                axisTicks: { color: theme.gridColor },
                            },
                            yaxis: {
                                labels: { style: { colors: theme.labelColor } },
                            },
                            grid: { borderColor: theme.gridColor },
                            tooltip: { theme: theme.tooltipTheme },
                        }, false, false);
                    },
                    async refreshTrend() {
                        const response = await fetch(this.feedUrl, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const data = await response.json();
                        this.trend = data;

                        if (!this.chart) {
                            return;
                        }

                        this.chart.updateOptions({
                            xaxis: { categories: data.labels },
                            annotations: this.buildAnnotations(data),
                        }, false, false);

                        this.chart.updateSeries(this.buildSeries(data), true);
                    },
                    init() {
                        if (!window.ApexCharts) {
                            return;
                        }

                        this.chart = new window.ApexCharts(this.$refs.chart, this.chartOptions(this.trend));
                        this.chart.render();

                        window.addEventListener('theme-changed', () => this.applyTheme());

                        setInterval(() => {
                            this.refreshTrend();
                        }, 3000);
                    },
                }"
                x-init="init()"
                class="app-card px-6 py-6 sm:px-7"
            >
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="app-section-title">Analytics</p>
                        <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">Sensor Trend</h2>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Live movement across temperature, humidity, air flow, and power usage.</p>
                    </div>

                    <div class="inline-flex items-center gap-1 rounded-xl bg-gray-100 p-1 dark:bg-white/[0.03]">
                        <button type="button" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white">30 min</button>
                        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400">2 h</button>
                        <button type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400">Today</button>
                    </div>
                </div>

                <div x-ref="chart" class="h-[340px]"></div>
            </div>

            <div class="space-y-6">
                <div class="app-card px-6 py-6 sm:px-7">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="app-section-title">Monthly Target</p>
                            <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">Room Health</h2>
                        </div>
                        <span class="app-pill bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                            {{ number_format($averageMetricLoad, 1) }}%
                        </span>
                    </div>

                    <div class="mt-6 flex items-center justify-center">
                        <div class="relative h-52 w-52 rounded-full" style="background: conic-gradient(#465FFF 0deg {{ $targetGaugeDegrees }}deg, rgba(226,232,240,1) {{ $targetGaugeDegrees }}deg 360deg);">
                            <div class="absolute inset-4 rounded-full bg-white dark:bg-gray-900"></div>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Average readiness</p>
                                <p class="mt-2 font-display text-5xl font-semibold text-gray-900 dark:text-white">{{ number_format($averageMetricLoad, 0) }}%</p>
                                <p class="mt-2 text-xs font-semibold uppercase tracking-[0.28em] text-brand-500">Operational target</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        @foreach ([
                            ['label' => 'Stable', 'count' => $statusCounts['Stable'], 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'],
                            ['label' => 'Warning', 'count' => $statusCounts['Warning'], 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'],
                            ['label' => 'Critical', 'count' => $statusCounts['Critical'], 'class' => 'bg-pink-100 text-pink-700 dark:bg-pink-500/10 dark:text-pink-300'],
                        ] as $row)
                            <div class="app-surface-muted flex items-center justify-between px-4 py-3">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $row['label'] }}</span>
                                <span class="app-pill {{ $row['class'] }}">{{ $row['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="app-card px-6 py-6 sm:px-7">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="app-section-title">Priority</p>
                            <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">Server Watch</h2>
                        </div>
                        <span class="app-pill bg-gray-100 text-gray-500 dark:bg-white/[0.03] dark:text-gray-300">
                            {{ count($priorityServers) }} live
                        </span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach ($priorityServers as $server)
                            @php
                                $watchStatusClasses = [
                                    'Online' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
                                    'Warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
                                    'Critical' => 'bg-pink-100 text-pink-700 dark:bg-pink-500/10 dark:text-pink-300',
                                ];
                            @endphp

                            <div class="app-surface-muted px-4 py-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-display text-lg font-semibold text-gray-900 dark:text-white">{{ $server['name'] }}</p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $server['metrics'][0]['value'] }} CPU / {{ $server['metrics'][1]['value'] }} RAM
                                        </p>
                                    </div>
                                    <span class="app-pill {{ $watchStatusClasses[$server['status']] ?? 'bg-gray-100 text-gray-500 dark:bg-white/[0.03] dark:text-gray-300' }}">
                                        {{ $server['status'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-3">
                        <div class="app-surface-muted px-4 py-3 text-center">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Online</p>
                            <p class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">{{ $serverStateCounts['Online'] }}</p>
                        </div>
                        <div class="app-surface-muted px-4 py-3 text-center">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Watch</p>
                            <p class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">{{ $attentionMetrics }}</p>
                        </div>
                        <div class="app-surface-muted px-4 py-3 text-center">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Critical</p>
                            <p class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">{{ $serverStateCounts['Critical'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="app-section-title">Infrastructure</p>
                <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">Servers</h2>
            </div>

            <p class="max-w-2xl text-sm leading-7 text-gray-500 dark:text-gray-400">
                CPU, RAM, disk, and network remain visible in one compact operational grid.
            </p>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            @foreach ($servers as $server)
                @include('dashboard.partials.server-card', ['server' => $server])
            @endforeach
        </div>
    </section>
</x-app-layout>
