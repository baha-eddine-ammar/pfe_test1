@php
    $typeLabels = [
        'all' => 'All Reports',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
    ];

    $typeClasses = [
        'daily' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
        'weekly' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300',
        'monthly' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
    ];

    $aiClasses = [
        'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        'fallback' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        'failed' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
    ];
@endphp

<x-app-layout>
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="app-section-title">Operational intelligence</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">AI Reports</h1>
                <p class="mt-3 max-w-3xl text-sm leading-7 text-gray-500 dark:text-gray-400">
                    Generate concise operational reports from demo sensor history now, then swap to real ESP32 input later without changing the reporting flow.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="app-pill bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                    {{ $typeLabels[$typeFilter] }}
                </span>
                @if ($latestReport)
                    <span class="app-pill bg-gray-100 text-gray-500 dark:bg-white/[0.03] dark:text-gray-300">
                        Last generated {{ $latestReport->generated_at?->diffForHumans() }}
                    </span>
                @endif
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

        @if ($errors->any())
            <div class="app-status-danger">
                {{ $errors->first() }}
            </div>
        @endif
    </section>

    <section class="mx-auto max-w-7xl">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'All reports', 'value' => $reportCounts['all'], 'note' => 'Stored report documents', 'class' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300'],
                ['label' => 'Daily', 'value' => $reportCounts['daily'], 'note' => 'Short operational snapshots', 'class' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300'],
                ['label' => 'Weekly', 'value' => $reportCounts['weekly'], 'note' => 'Trend summaries across the week', 'class' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300'],
                ['label' => 'Monthly', 'value' => $reportCounts['monthly'], 'note' => 'Longer pattern analysis', 'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'],
            ] as $stat)
                <div class="app-card px-5 py-5 md:px-6">
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <span class="app-pill {{ $stat['class'] }}">{{ $stat['label'] }}</span>
                    </div>
                    <p class="mt-4 font-display text-[2.25rem] font-semibold tracking-tight text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $stat['note'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-7xl">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_380px]">
            <div class="space-y-6">
                <div class="app-card px-6 py-6 sm:px-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="app-section-title">History</p>
                            <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">Saved Reports</h2>
                        </div>

                        <div class="inline-flex items-center gap-1 rounded-xl bg-gray-100 p-1 dark:bg-white/[0.03]">
                            @foreach ($typeLabels as $value => $label)
                                <a
                                    href="{{ $value === 'all' ? route('reports.index') : route('reports.index', ['type' => $value]) }}"
                                    class="rounded-lg px-4 py-2 text-sm font-medium transition {{ $typeFilter === $value ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}"
                                >
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6">
                        @if ($reports->isEmpty())
                            @include('reports.partials.empty-state')
                        @else
                            <div class="grid gap-4 lg:grid-cols-2">
                                @foreach ($reports as $report)
                                    @php
                                        $overview = $report->metrics_snapshot['overview'] ?? [];
                                        $aiSummary = $report->latestAiSummary;
                                        $aiClass = $aiClasses[$aiSummary?->status ?? 'fallback'] ?? $aiClasses['fallback'];
                                    @endphp

                                    <article class="app-card app-card-hover px-5 py-5">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="app-pill {{ $typeClasses[$report->type] ?? 'bg-gray-100 text-gray-500 dark:bg-white/[0.03] dark:text-gray-300' }}">
                                                        {{ ucfirst($report->type) }}
                                                    </span>
                                                    <span class="app-pill {{ $aiClass }}">
                                                        {{ $aiSummary?->status === 'success' ? 'AI summary' : 'Fallback summary' }}
                                                    </span>
                                                </div>
                                                <h3 class="mt-4 font-display text-xl font-semibold text-gray-900 dark:text-white">{{ $report->title }}</h3>
                                            </div>

                                            <a href="{{ route('reports.show', $report) }}" class="app-button-secondary px-4 py-2">
                                                View
                                            </a>
                                        </div>

                                        <div class="mt-4 grid grid-cols-2 gap-3">
                                            <div class="app-surface-muted px-4 py-3">
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Period</p>
                                                <p class="mt-2 text-sm font-medium text-gray-800 dark:text-gray-200">
                                                    {{ $report->period_start->format('M d') }} - {{ $report->period_end->format('M d, Y') }}
                                                </p>
                                            </div>
                                            <div class="app-surface-muted px-4 py-3">
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Generated</p>
                                                <p class="mt-2 text-sm font-medium text-gray-800 dark:text-gray-200">
                                                    {{ $report->generated_at?->format('M d, Y H:i') }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-4 grid grid-cols-3 gap-3">
                                            <div class="rounded-2xl border border-gray-100 px-4 py-3 dark:border-gray-800">
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Warnings</p>
                                                <p class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">{{ $overview['warning_count'] ?? 0 }}</p>
                                            </div>
                                            <div class="rounded-2xl border border-gray-100 px-4 py-3 dark:border-gray-800">
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Critical</p>
                                                <p class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">{{ $overview['critical_count'] ?? 0 }}</p>
                                            </div>
                                            <div class="rounded-2xl border border-gray-100 px-4 py-3 dark:border-gray-800">
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">Anomalies</p>
                                                <p class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">{{ $overview['anomaly_count'] ?? 0 }}</p>
                                            </div>
                                        </div>

                                        <p class="mt-4 text-sm leading-7 text-gray-500 dark:text-gray-400">
                                            {{ \Illuminate\Support\Str::limit($report->summary ?? 'Report summary unavailable.', 180) }}
                                        </p>
                                    </article>
                                @endforeach
                            </div>

                            <div class="mt-6">
                                {{ $reports->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <aside class="space-y-6">
                <div class="app-card px-6 py-6 sm:px-7" x-data="{ generating: false }">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="app-section-title">Generate</p>
                            <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">New Report</h2>
                        </div>
                        <span class="app-pill bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                            AI ready
                        </span>
                    </div>

                    <form method="POST" action="{{ route('reports.store') }}" class="mt-6 space-y-5" @submit="generating = true">
                        @csrf

                        <div>
                            <label for="type" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Report type</label>
                            <select id="type" name="type" class="app-select">
                                <option value="daily">Daily report</option>
                                <option value="weekly">Weekly report</option>
                                <option value="monthly">Monthly report</option>
                            </select>
                        </div>

                        <div>
                            <label for="reference_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Reference date</label>
                            <input id="reference_date" type="date" name="reference_date" value="{{ $defaultReferenceDate }}" class="app-input">
                        </div>

                        <button type="submit" class="app-button-primary w-full">
                            <svg class="mr-2 h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                                <path d="M10 3v14M3 10h14" stroke-linecap="round"></path>
                            </svg>
                            Generate Report
                        </button>

                        <div x-cloak x-show="generating" class="space-y-3" style="display: none;">
                            <div class="app-skeleton h-4 w-32"></div>
                            <div class="app-skeleton h-20 w-full"></div>
                            <div class="app-skeleton h-4 w-48"></div>
                        </div>
                    </form>
                </div>

                <div class="app-card px-6 py-6 sm:px-7">
                    <p class="app-section-title">AI flow</p>
                    <div class="mt-4 space-y-3">
                        @foreach ([
                            'Fake sensor history is generated from a dedicated provider.',
                            'Statistics are computed before any AI call is made.',
                            'Only calculated metrics are sent to Groq, not the full raw dataset.',
                            'If AI is unavailable, a deterministic fallback summary is still saved.',
                        ] as $item)
                            <div class="app-surface-muted flex items-start gap-3 px-4 py-3">
                                <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full bg-brand-500"></span>
                                <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $item }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </section>
</x-app-layout>
