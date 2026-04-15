{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| Reusable partial for one server card in the dashboard fleet grid.
|
| Data source:
| Each $server array is prepared by ServerMonitoringService (or fallback demo
| data in DashboardController when no servers exist yet).
|--------------------------------------------------------------------------
--}}
@php
    // Maps server status values to badge color classes.
    $statusClasses = [
        'Online' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        'Warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        'Critical' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
    ];

    // Maps metric color names to text/progress-bar styles.
    $metricStyles = [
        'cyan' => [
            'text' => 'text-sky-600 dark:text-sky-300',
            'bar' => 'from-sky-400 via-cyan-400 to-sky-500',
        ],
        'violet' => [
            'text' => 'text-violet-600 dark:text-violet-300',
            'bar' => 'from-violet-400 via-fuchsia-400 to-violet-500',
        ],
        'pink' => [
            'text' => 'text-pink-600 dark:text-pink-300',
            'bar' => 'from-pink-400 via-rose-400 to-pink-500',
        ],
        'emerald' => [
            'text' => 'text-emerald-600 dark:text-emerald-300',
            'bar' => 'from-emerald-400 via-lime-400 to-emerald-500',
        ],
    ];

    // Final classes and summary text used by the card.
    $statusClass = $statusClasses[$server['status']] ?? 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300';
    $serverNarrative = match ($server['status']) {
        'Critical' => 'Immediate investigation recommended. Resource saturation or stale telemetry is pushing this node into a high-risk state.',
        'Warning' => 'Load pressure is climbing. Keep this server under observation before it escalates to a critical threshold.',
        default => 'Telemetry is flowing and the system is operating within expected parameters.',
    };
@endphp

<article class="dashboard-panel dashboard-panel-hover group relative overflow-hidden px-6 py-6 sm:px-7">
    {{--
        Top section:
        server identity, status, and "last seen" information.
    --}}
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(70,95,255,0.12),_transparent_30%),radial-gradient(circle_at_bottom_left,_rgba(34,211,238,0.08),_transparent_28%)]"></div>
    <div class="relative z-[1]">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/80 text-slate-700 shadow-lg shadow-slate-200/60 backdrop-blur dark:bg-white/10 dark:text-white dark:shadow-none">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="4" y="4" width="16" height="6" rx="2"></rect>
                        <rect x="4" y="14" width="16" height="6" rx="2"></rect>
                        <path d="M8 7h.01M8 17h.01"></path>
                    </svg>
                </div>

                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h3 class="font-display text-2xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white">{{ $server['name'] }}</h3>
                        <span class="app-pill {{ $statusClass }}">{{ $server['status'] }}</span>
                    </div>

                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                        {{ $server['identifier'] ?? 'Monitoring node' }}
                    </p>
                </div>
            </div>

            <div class="dashboard-surface-glass rounded-[24px] px-4 py-3 text-right">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Last seen</p>
                <p class="mt-2 font-display text-xl font-semibold text-slate-950 dark:text-white">{{ $server['lastSeenLabel'] ?? 'Live sample' }}</p>
            </div>
        </div>

        {{--
            Metric blocks:
            CPU, RAM, disk, and network are rendered from the $server['metrics'] array.
        --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach ($server['metrics'] as $metric)
                @php
                    $metricStyle = $metricStyles[$metric['color']] ?? $metricStyles['cyan'];
                @endphp

                <div class="dashboard-surface-glass rounded-[24px] px-4 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $metric['label'] }}</span>
                        <span class="text-sm font-semibold {{ $metricStyle['text'] }}">{{ $metric['value'] }}</span>
                    </div>

                    <div class="mt-4 metric-progress-track h-2.5 bg-slate-100/80 dark:bg-white/[0.06]">
                        <div
                            class="h-full rounded-full bg-gradient-to-r {{ $metricStyle['bar'] }}"
                            style="width: {{ $metric['progress'] }}%;"
                        ></div>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                        <span>Utilization</span>
                        <span>{{ $metric['progress'] }}%</span>
                    </div>
                </div>
            @endforeach
        </div>

        {{--
            Operational note:
            A short narrative based on the overall server status.
        --}}
        <div class="dashboard-surface-glass mt-5 rounded-[24px] px-4 py-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Operational note</p>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-500 dark:text-slate-400">{{ $serverNarrative }}</p>
                </div>

                <div class="flex items-center gap-2">
                    @foreach (array_slice($server['metrics'], 0, 3) as $metric)
                        <span class="inline-flex h-2.5 w-10 overflow-hidden rounded-full bg-slate-200 dark:bg-white/10">
                            <span class="h-full rounded-full bg-slate-900 dark:bg-white" style="width: {{ $metric['progress'] }}%;"></span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</article>
