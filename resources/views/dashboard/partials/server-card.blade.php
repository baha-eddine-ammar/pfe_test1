@php
    $statusClasses = [
        'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        'pink' => 'bg-pink-100 text-pink-700 dark:bg-pink-500/10 dark:text-pink-300',
    ];

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

    $serverStatusClass = $statusClasses[$server['statusColor']] ?? 'bg-gray-100 text-gray-500 dark:bg-white/[0.03] dark:text-gray-300';
@endphp

<article class="app-card app-card-hover px-6 py-6 sm:px-7">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="app-section-title">Server</p>
            <h3 class="mt-2 font-display text-xl font-semibold tracking-tight text-gray-900 dark:text-white">
                {{ $server['name'] }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $server['metrics'][0]['value'] }} CPU load
            </p>
        </div>

        <span class="app-pill {{ $serverStatusClass }}">
            {{ $server['status'] }}
        </span>
    </div>

    <div class="mt-6 space-y-4">
        @foreach ($server['metrics'] as $metric)
            @php
                $metricStyle = $metricStyles[$metric['color']] ?? $metricStyles['cyan'];
            @endphp

            <div class="rounded-2xl bg-gray-50 px-4 py-3 dark:bg-white/[0.03]">
                <div class="mb-2 flex items-center justify-between gap-4">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ $metric['label'] }}
                    </span>
                    <span class="text-sm font-semibold {{ $metricStyle['text'] }}">
                        {{ $metric['value'] }}
                    </span>
                </div>

                <div class="metric-progress-track">
                    <div
                        class="h-full rounded-full bg-gradient-to-r {{ $metricStyle['bar'] }}"
                        style="width: {{ $metric['progress'] }}%;"
                    ></div>
                </div>
            </div>
        @endforeach
    </div>
</article>
