{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| Reusable dashboard metric card component.
|
| What it shows:
| - one environmental metric (temperature, humidity, airflow, power)
| - its status
| - its mini sparkline
| - its progress bar
|
| Data source:
| Values are passed from resources/views/dashboard.blade.php, which receives
| them from DashboardController.
--}}
@props([
    'title',
    'subtitle',
    'value',
    'status',
    'ringDegrees',
    'unit',
    'feedUrl',
    'stableColor' => '#465FFF',
    'icon' => 'temperature',
    'target' => null,
    'sparkline' => [],
    'trend' => ['percent' => 0, 'direction' => 'flat'],
])

<article
    {{--
        This component starts its own Alpine helper (liveMetricCard).
        The helper is defined inline in dashboard.blade.php and refreshes the card
        using the metric's individual feed URL.
    --}}
    x-data="liveMetricCard({
        initialValue: {{ json_encode($value) }},
        initialStatus: {{ json_encode($status) }},
        initialRingDegrees: {{ json_encode($ringDegrees) }},
        unit: {{ json_encode($unit) }},
        feedUrl: {{ json_encode($feedUrl) }},
        stableColor: {{ json_encode($stableColor) }},
        sparkline: @js($sparkline),
        initialTrendPercent: {{ json_encode($trend['percent'] ?? 0) }},
        initialTrendDirection: {{ json_encode($trend['direction'] ?? 'flat') }},
    })"
    class="dashboard-panel dashboard-panel-hover group relative overflow-hidden px-6 py-6 sm:px-7"
    :class="{ 'scale-[1.01] shadow-[0_28px_90px_rgba(70,95,255,0.12)]': refreshPulse }"
>
    <div class="absolute inset-0 transition-opacity duration-500" :style="glowStyle()"></div>
    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/80 to-transparent dark:via-white/20"></div>

    <div class="relative z-[1]">
        {{--
            Card header:
            Left = metric icon
            Right = current status badge
        --}}
        <div class="flex items-start justify-between gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/80 text-slate-700 shadow-lg shadow-slate-200/60 backdrop-blur dark:bg-white/10 dark:text-white dark:shadow-none">
                @if ($icon === 'temperature')
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M12 4a2 2 0 00-2 2v7.6a4 4 0 104 0V6a2 2 0 00-2-2z"></path>
                        <path d="M12 11v5" stroke-linecap="round"></path>
                    </svg>
                @elseif ($icon === 'humidity')
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M12 4c3.5 4.2 5.5 7 5.5 9.5A5.5 5.5 0 116.5 13.5C6.5 11 8.5 8.2 12 4z"></path>
                    </svg>
                @elseif ($icon === 'airflow')
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

            <div class="flex items-center gap-2">
                <span class="dashboard-live-badge" :class="statusClasses()">
                    <span class="dashboard-live-dot"></span>
                    <span x-text="status"></span>
                </span>
            </div>
        </div>

        {{--
            Main metric content:
            Shows title, subtitle, numeric value, and the current percentage.
        --}}
        <div class="mt-6">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $subtitle }}</p>
            <div class="mt-2 flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-display text-[1.9rem] font-semibold tracking-[-0.04em] text-slate-950 dark:text-white">{{ $title }}</h2>
                    <div class="mt-4 flex items-end gap-2">
                        <p class="font-display text-5xl font-semibold leading-none text-slate-950 dark:text-white">
                            <span class="tabular-nums" x-text="displayValue()"></span>
                        </p>
                        <span class="pb-1 text-lg font-medium text-slate-400 dark:text-slate-500">{{ $unit }}</span>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Current load</p>
                    <p class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white" x-text="displayPercent()"></p>
                </div>
            </div>
        </div>

        {{--
            Trend area:
            Shows trend direction and the mini sparkline chart.
        --}}
        <div class="dashboard-surface-glass mt-6 rounded-[24px] p-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Trend delta</p>
                    <div class="mt-2 flex items-center gap-2" :class="trendClasses()">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M5 12l5-5 5 5" stroke-linecap="round" stroke-linejoin="round" x-show="trendDirection === 'up'"></path>
                            <path d="M5 8l5 5 5-5" stroke-linecap="round" stroke-linejoin="round" x-show="trendDirection === 'down'"></path>
                            <path d="M5 10h10" stroke-linecap="round" x-show="trendDirection === 'flat'"></path>
                        </svg>
                        <span class="text-sm font-semibold" x-text="displayTrend()"></span>
                    </div>
                </div>

                <div class="min-w-[7rem] text-right">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Live trace</p>
                    <div x-ref="sparkline" class="mt-2 h-[88px]"></div>
                </div>
            </div>
        </div>

        @if ($target)
            <p class="mt-5 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $target }}</p>
        @endif

        {{--
            Bottom progress bar:
            Visualizes the normalized metric load derived in DashboardController.
        --}}
        <div class="mt-5">
            <div class="metric-progress-track h-2.5 bg-slate-100/80 dark:bg-white/[0.06]">
                <div class="h-full rounded-full transition-all duration-500" :style="progressStyle()"></div>
            </div>
            <div class="mt-3 flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                <span>Auto-refreshing feed</span>
                <span x-text="status"></span>
            </div>
        </div>
    </div>
</article>
