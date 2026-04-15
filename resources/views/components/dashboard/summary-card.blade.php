@props([
    'label',
    'value',
    'note',
    'pill',
    'pillClass' => '',
    'series' => [],
    'trend' => null,
    'icon' => 'pulse',
    'accent' => 'blue',
])

@php
    $series = collect($series)->map(fn ($value) => (float) $value)->values()->all();

    if (count($series) < 2) {
        $series = array_pad($series, 2, $series[0] ?? 0);
    }

    $min = min($series);
    $max = max($series);
    $range = max($max - $min, 1);

    $points = collect($series)
        ->values()
        ->map(function (float $point, int $index) use ($series, $min, $range) {
            $x = count($series) > 1 ? ($index / (count($series) - 1)) * 100 : 50;
            $y = 26 - ((($point - $min) / $range) * 20);

            return number_format($x, 2, '.', '').','.number_format($y, 2, '.', '');
        })
        ->implode(' ');

    $gradientId = 'summary-spark-'.md5($label.$value.$accent);
    $accentMap = [
        'blue' => ['from' => '#38BDF8', 'to' => '#465FFF', 'orb' => 'bg-sky-500/15 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300'],
        'emerald' => ['from' => '#34D399', 'to' => '#10B981', 'orb' => 'bg-emerald-500/15 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300'],
        'amber' => ['from' => '#FBBF24', 'to' => '#F59E0B', 'orb' => 'bg-amber-500/15 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300'],
        'violet' => ['from' => '#A78BFA', 'to' => '#8B5CF6', 'orb' => 'bg-violet-500/15 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300'],
    ];
    $accentStyles = $accentMap[$accent] ?? $accentMap['blue'];
@endphp

<article class="dashboard-panel dashboard-panel-hover group overflow-hidden px-5 py-5 sm:px-6">
    <div class="flex items-start justify-between gap-4">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $accentStyles['orb'] }}">
            @if ($icon === 'servers')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <rect x="4" y="4" width="16" height="6" rx="2"></rect>
                    <rect x="4" y="14" width="16" height="6" rx="2"></rect>
                    <path d="M8 7h.01M8 17h.01"></path>
                </svg>
            @elseif ($icon === 'alert')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path d="M12 4l8 14H4L12 4z" stroke-linejoin="round"></path>
                    <path d="M12 9v4" stroke-linecap="round"></path>
                    <path d="M12 16h.01"></path>
                </svg>
            @elseif ($icon === 'target')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <circle cx="12" cy="12" r="7"></circle>
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 2v3M22 12h-3M12 22v-3M2 12h3" stroke-linecap="round"></path>
                </svg>
            @else
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path d="M4 12h4l2-5 4 10 2-5h4" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            @endif
        </div>

        <span class="app-pill {{ $pillClass }}">{{ $pill }}</span>
    </div>

    <div class="mt-6">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $label }}</p>
        <div class="mt-3 flex items-end justify-between gap-3">
            <div>
                <p class="font-display text-4xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white">{{ $value }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $note }}</p>
            </div>

            @if ($trend)
                <span class="rounded-full border border-white/60 bg-white/80 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 shadow-sm shadow-slate-200/50 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:shadow-none">
                    {{ $trend }}
                </span>
            @endif
        </div>
    </div>

    <div class="dashboard-surface-glass mt-5 rounded-[24px] p-3">
        <svg viewBox="0 0 100 32" class="h-14 w-full" preserveAspectRatio="none" aria-hidden="true">
            <defs>
                <linearGradient id="{{ $gradientId }}" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="{{ $accentStyles['from'] }}"></stop>
                    <stop offset="100%" stop-color="{{ $accentStyles['to'] }}"></stop>
                </linearGradient>
            </defs>
            <polyline
                fill="none"
                stroke="url(#{{ $gradientId }})"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
                points="{{ $points }}"
            ></polyline>
        </svg>
    </div>
</article>
