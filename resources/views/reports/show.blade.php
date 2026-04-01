@php
    $snapshot = $report->metrics_snapshot ?? [];
    $overview = $snapshot['overview'] ?? [];
    $metrics = collect($snapshot['metrics'] ?? []);
    $anomalies = collect($report->anomalies ?? []);
    $aiSummary = $report->latestAiSummary;

    $typeClasses = [
        'daily' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
        'weekly' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300',
        'monthly' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
    ];

    $aiClass = match ($aiSummary?->status) {
        'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        'fallback' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        default => 'bg-gray-100 text-gray-500 dark:bg-white/[0.03] dark:text-gray-300',
    };

    $metricClasses = [
        'temperature' => 'from-sky-500/15 to-sky-500/5 ring-sky-500/20',
        'humidity' => 'from-violet-500/15 to-violet-500/5 ring-violet-500/20',
        'air_flow' => 'from-emerald-500/15 to-emerald-500/5 ring-emerald-500/20',
        'power_usage' => 'from-amber-500/15 to-amber-500/5 ring-amber-500/20',
    ];
@endphp

<x-app-layout>
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <a href="{{ route('reports.index') }}" class="app-link">&larr; Back to reports</a>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="app-pill {{ $typeClasses[$report->type] ?? 'bg-gray-100 text-gray-500 dark:bg-white/[0.03] dark:text-gray-300' }}">
                        {{ ucfirst($report->type) }}
                    </span>
                    <span class="app-pill {{ $aiClass }}">
                        {{ $aiSummary?->status === 'success' ? 'AI summary' : 'Fallback summary' }}
                    </span>
                </div>
                <h1 class="mt-4 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $report->title }}</h1>
                <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">
                    {{ $report->period_start->format('M d, Y H:i') }} to {{ $report->period_end->format('M d, Y H:i') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3" x-data="{ copied: false, copySummary() { navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($aiSummary?->summary_text ?? $report->summary ?? '') }}); this.copied = true; setTimeout(() => this.copied = false, 1800); } }">
                <button type="button" class="app-button-secondary" @click="copySummary()">
                    <svg class="mr-2 h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="6" y="6" width="10" height="10" rx="2"></rect>
                        <path d="M4 12V5a1 1 0 011-1h7" stroke-linecap="round"></path>
                    </svg>
                    <span x-text="copied ? 'Copied' : 'Copy summary'"></span>
                </button>

                <form method="POST" action="{{ route('reports.regenerate-summary', $report) }}">
                    @csrf
                    <button type="submit" class="app-button-primary">
                        <svg class="mr-2 h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M16 10a6 6 0 10-1.76 4.24L16 16v-4h-4" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        {{ $aiSummary?->status === 'success' ? 'Regenerate AI summary' : 'Retry AI summary' }}
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl">
        @if (session('status'))
            <div class="app-status-success">{{ session('status') }}</div>
        @endif

        @if (session('warning'))
            <div class="app-status-danger border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-500/10 dark:text-amber-300">
                {{ session('warning') }}
            </div>
        @endif
    </section>

    <section class="mx-auto max-w-7xl">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Sensors', 'value' => $overview['sensor_count'] ?? 0, 'note' => 'Included in the report'],
                ['label' => 'Readings', 'value' => $overview['reading_count'] ?? 0, 'note' => 'Historical points analyzed'],
                ['label' => 'Warnings', 'value' => $overview['warning_count'] ?? 0, 'note' => 'Threshold warning events'],
                ['label' => 'Critical', 'value' => $overview['critical_count'] ?? 0, 'note' => 'Critical breaches detected'],
            ] as $stat)
                <div class="app-card px-5 py-5 md:px-6">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                    <p class="mt-4 font-display text-[2.25rem] font-semibold tracking-tight text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $stat['note'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-7xl">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_360px]">
            <div class="space-y-6">
                <div class="app-card px-6 py-6 sm:px-7">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="app-section-title">AI insights</p>
                            <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">Executive Summary</h2>
                        </div>
                        <span class="app-pill {{ $aiClass }}">
                            {{ $aiSummary?->status === 'success' ? 'Groq' : 'Fallback' }}
                        </span>
                    </div>

                    <div class="mt-6 space-y-5">
                        <div class="app-surface-muted px-5 py-5">
                            <p class="report-prose">{{ $aiSummary?->summary_text ?? $report->summary }}</p>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="rounded-2xl border border-gray-100 px-5 py-5 dark:border-gray-800">
                                <p class="app-section-title">Observations</p>
                                <ul class="mt-4 space-y-3">
                                    @foreach ($aiSummary?->observations ?? [] as $observation)
                                        <li class="flex items-start gap-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                            <span class="mt-2 h-2.5 w-2.5 rounded-full bg-brand-500"></span>
                                            <span>{{ $observation }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="rounded-2xl border border-gray-100 px-5 py-5 dark:border-gray-800">
                                <p class="app-section-title">Recommendations</p>
                                <ul class="mt-4 space-y-3">
                                    @foreach ($aiSummary?->recommendations ?? [] as $recommendation)
                                        <li class="flex items-start gap-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                            <span class="mt-2 h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                            <span>{{ $recommendation }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>

                        @if ($aiSummary?->error_message)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-900/40 dark:bg-amber-500/10 dark:text-amber-300">
                                AI fallback was used because: {{ $aiSummary->error_message }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="app-card px-6 py-6 sm:px-7">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="app-section-title">Metrics</p>
                            <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">Computed Sensor Statistics</h2>
                        </div>
                        <span class="app-pill bg-gray-100 text-gray-500 dark:bg-white/[0.03] dark:text-gray-300">
                            {{ $metrics->count() }} sensors
                        </span>
                    </div>

                    <div class="mt-6 grid gap-4 lg:grid-cols-2">
                        @foreach ($metrics as $metric)
                            <article class="rounded-3xl border border-gray-100 bg-gradient-to-br {{ $metricClasses[$metric['key']] ?? 'from-brand-500/10 to-brand-500/5 ring-brand-500/20' }} p-5 ring-1 dark:border-gray-800">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="app-section-title">{{ $metric['location'] }}</p>
                                        <h3 class="mt-2 font-display text-xl font-semibold text-gray-900 dark:text-white">{{ $metric['name'] }}</h3>
                                    </div>
                                    <span class="app-pill {{ $typeClasses[$report->type] ?? 'bg-gray-100 text-gray-500 dark:bg-white/[0.03] dark:text-gray-300' }}">
                                        {{ $metric['latest_status'] }}
                                    </span>
                                </div>

                                <div class="mt-5 grid grid-cols-2 gap-3">
                                    <div class="app-surface-muted px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Average</p>
                                        <p class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">{{ $metric['average_value'] }}{{ $metric['unit'] }}</p>
                                    </div>
                                    <div class="app-surface-muted px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Latest</p>
                                        <p class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">{{ $metric['latest_value'] }}{{ $metric['unit'] }}</p>
                                    </div>
                                    <div class="app-surface-muted px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Min / Max</p>
                                        <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ $metric['min_value'] }} / {{ $metric['max_value'] }}{{ $metric['unit'] }}</p>
                                    </div>
                                    <div class="app-surface-muted px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Trend</p>
                                        <p class="mt-2 text-sm font-semibold capitalize text-gray-900 dark:text-white">{{ $metric['trend_direction'] }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span class="app-pill bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                        {{ $metric['warning_count'] }} warning
                                    </span>
                                    <span class="app-pill bg-pink-100 text-pink-700 dark:bg-pink-500/10 dark:text-pink-300">
                                        {{ $metric['critical_count'] }} critical
                                    </span>
                                    <span class="app-pill bg-gray-100 text-gray-500 dark:bg-white/[0.03] dark:text-gray-300">
                                        {{ $metric['anomaly_count'] }} anomalies
                                    </span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="app-card px-6 py-6 sm:px-7">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="app-section-title">Anomalies</p>
                            <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">Incidents</h2>
                        </div>
                        <span class="app-pill bg-gray-100 text-gray-500 dark:bg-white/[0.03] dark:text-gray-300">
                            {{ $anomalies->count() }} events
                        </span>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse ($anomalies as $anomaly)
                            <div class="rounded-2xl border border-gray-100 px-4 py-4 dark:border-gray-800">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-display text-lg font-semibold text-gray-900 dark:text-white">{{ $anomaly['sensor_name'] }}</p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $anomaly['reason'] }} at {{ \Carbon\Carbon::parse($anomaly['recorded_at'])->format('M d, H:i') }}</p>
                                    </div>
                                    <span class="app-pill {{ $anomaly['severity'] === 'Critical' ? 'bg-pink-100 text-pink-700 dark:bg-pink-500/10 dark:text-pink-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' }}">
                                        {{ $anomaly['severity'] }}
                                    </span>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="app-pill bg-gray-100 text-gray-500 dark:bg-white/[0.03] dark:text-gray-300">
                                        {{ $anomaly['value'] }}{{ $anomaly['unit'] }}
                                    </span>
                                    <span class="app-pill bg-gray-100 text-gray-500 dark:bg-white/[0.03] dark:text-gray-300">
                                        Delta {{ $anomaly['delta'] }}{{ $anomaly['unit'] }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-gray-200 px-4 py-10 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                No anomalies were recorded in this reporting period.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="app-card px-6 py-6 sm:px-7">
                    <p class="app-section-title">Versions</p>
                    <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">Summary History</h2>

                    <div class="mt-5 space-y-3">
                        @foreach ($report->aiSummaries as $summaryVersion)
                            <div class="rounded-2xl border border-gray-100 px-4 py-4 dark:border-gray-800">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ $summaryVersion->generated_at?->format('M d, Y H:i') }}
                                    </p>
                                    <span class="app-pill {{ $summaryVersion->status === 'success' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' }}">
                                        {{ $summaryVersion->status }}
                                    </span>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-gray-500 dark:text-gray-400">
                                    {{ \Illuminate\Support\Str::limit($summaryVersion->summary_text, 120) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
