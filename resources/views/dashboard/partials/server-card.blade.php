{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| Live dashboard panel for the dedicated Server Section.
|
| Data source:
| The initial $server payload comes from DashboardController. The panel can
| also refresh itself through the dashboard server-feed endpoint.
|--------------------------------------------------------------------------
--}}
@php
    $feedUrl = $feedUrl ?? null;
    $reactServerCardProps = [
        'server' => $server,
        'feedUrl' => $feedUrl,
    ];
@endphp

<div
    data-react-server-live-card
    data-props='{{ json_encode($reactServerCardProps, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT) }}'
></div>

<article
    x-data="liveServerPanel({
        initialServer: @js($server),
        feedUrl: @js($feedUrl),
    })"
    x-init="init()"
    data-react-fallback
    class="dashboard-panel dashboard-panel-hover group relative overflow-hidden px-6 py-6 sm:px-7"
>
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
                        <h3 class="font-display text-2xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white" x-text="server.name">{{ $server['name'] }}</h3>
                        <span class="app-pill" :class="statusPillClass(server.status)" x-text="server.status">{{ $server['status'] }}</span>
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-brand-100 bg-brand-50 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-brand-600 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-300"
                            :class="refreshing ? 'shadow-lg shadow-brand-500/10 dark:shadow-brand-500/20' : ''"
                        >
                            <span class="dashboard-live-dot"></span>
                            <span x-text="refreshing ? 'Refreshing' : 'Live'"></span>
                        </span>
                    </div>

                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400" x-text="server.identifier || 'Monitoring node'">{{ $server['identifier'] ?? 'Monitoring node' }}</p>
                </div>
            </div>

            <div class="dashboard-surface-glass rounded-[24px] px-4 py-3 text-right">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Last seen</p>
                <p class="mt-2 font-display text-xl font-semibold text-slate-950 dark:text-white" x-text="server.lastSeenLabel || 'Live sample'">{{ $server['lastSeenLabel'] ?? 'Live sample' }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($server['metrics'] as $index => $metric)
                <div class="dashboard-surface-glass rounded-[24px] px-4 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400" x-text="server.metrics[{{ $index }}]?.label || @js($metric['label'])">{{ $metric['label'] }}</span>
                        <span class="text-sm font-semibold" :class="metricTextClass(server.metrics[{{ $index }}]?.color || @js($metric['color']))" x-text="server.metrics[{{ $index }}]?.value || @js($metric['value'])">{{ $metric['value'] }}</span>
                    </div>

                    <div class="mt-4 metric-progress-track h-2.5 bg-slate-100/80 dark:bg-white/[0.06]">
                        <div
                            class="h-full rounded-full bg-gradient-to-r"
                            :class="metricBarClass(server.metrics[{{ $index }}]?.color || @js($metric['color']))"
                            :style="progressStyle(server.metrics[{{ $index }}]?.progress ?? {{ $metric['progress'] }})"
                            style="width: {{ $metric['progress'] }}%;"
                        ></div>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                        <span x-text="server.metrics[{{ $index }}]?.footerLabel || @js($metric['footerLabel'] ?? 'Utilization')">{{ $metric['footerLabel'] ?? 'Utilization' }}</span>
                        <span x-text="server.metrics[{{ $index }}]?.progressLabel || @js($metric['progressLabel'] ?? ($metric['progress'].'%'))">{{ $metric['progressLabel'] ?? ($metric['progress'].'%') }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="dashboard-surface-glass mt-5 rounded-[24px] px-4 py-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Operational note</p>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-500 dark:text-slate-400" x-text="server.narrative">{{ $server['narrative'] ?? 'Telemetry is flowing and the system is operating within expected parameters.' }}</p>
                </div>

                <div class="flex items-center gap-2">
                    @foreach (array_slice($server['metrics'], 0, 3) as $index => $metric)
                        <span class="inline-flex h-2.5 w-10 overflow-hidden rounded-full bg-slate-200 dark:bg-white/10">
                            <span class="h-full rounded-full bg-slate-900 dark:bg-white" :style="progressStyle(server.metrics[{{ $index }}]?.progress ?? {{ $metric['progress'] }})" style="width: {{ $metric['progress'] }}%;"></span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</article>
