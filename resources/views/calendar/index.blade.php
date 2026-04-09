@php
    $weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
@endphp

<x-app-layout>
    <section class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="app-section-title">Planning</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">Calendar</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-gray-500 dark:text-gray-400">
                    A single month view for maintenance schedules and generated reports.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('calendar.index', ['month' => $previousMonth]) }}" class="app-icon-button" aria-label="Previous month">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M12.5 4.5L7 10l5.5 5.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </a>
                <a href="{{ route('calendar.index') }}" class="app-button-secondary px-4 py-3">
                    Today
                </a>
                <a href="{{ route('calendar.index', ['month' => $nextMonth]) }}" class="app-icon-button" aria-label="Next month">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M7.5 4.5L13 10l-5.5 5.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl">
        <div class="app-card overflow-hidden px-0 py-0">
            <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 sm:px-7">
                <div>
                    <p class="app-section-title">Month view</p>
                    <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">{{ $month->format('F Y') }}</h2>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <span class="app-pill bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">Maintenance</span>
                    <span class="app-pill bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300">Reports</span>
                </div>
            </div>

            <div class="grid grid-cols-7 border-b border-gray-100 dark:border-gray-800">
                @foreach ($weekdayLabels as $label)
                    <div class="px-4 py-4 text-center text-xs font-semibold uppercase tracking-[0.24em] text-gray-400 dark:text-gray-500">
                        {{ $label }}
                    </div>
                @endforeach
            </div>

            @foreach ($weeks as $week)
                <div class="grid grid-cols-7">
                    @foreach ($week as $day)
                        <div class="min-h-[165px] border-b border-r border-gray-100 px-3 py-3 align-top last:border-r-0 dark:border-gray-800 {{ $day['isCurrentMonth'] ? 'bg-white dark:bg-transparent' : 'bg-gray-50/70 dark:bg-white/[0.02]' }}">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full text-sm font-semibold {{ $day['isToday'] ? 'bg-brand-500 text-white' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $day['date']->day }}
                                </span>

                                @if ($day['events']->isNotEmpty())
                                    <span class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                        {{ $day['events']->count() }} event{{ $day['events']->count() > 1 ? 's' : '' }}
                                    </span>
                                @endif
                            </div>

                            <div class="mt-3 space-y-2">
                                @forelse ($day['events']->take(3) as $event)
                                    @php
                                        $eventClasses = match ($event['tone']) {
                                            'critical' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/30 dark:bg-rose-500/10 dark:text-rose-300',
                                            'warning' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/30 dark:bg-amber-500/10 dark:text-amber-300',
                                            'success' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/30 dark:bg-sky-500/10 dark:text-sky-300',
                                            default => 'border-brand-100 bg-brand-50 text-brand-700 dark:border-brand-900/30 dark:bg-brand-500/10 dark:text-brand-300',
                                        };
                                    @endphp
                                    <a href="{{ $event['url'] }}" class="block rounded-2xl border px-3 py-2 transition hover:-translate-y-0.5 hover:shadow-sm {{ $eventClasses }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="truncate text-sm font-semibold">{{ $event['title'] }}</p>
                                            <span class="text-[11px] font-semibold uppercase tracking-[0.18em]">{{ $event['time'] }}</span>
                                        </div>
                                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.18em] opacity-80">{{ $event['subtitle'] }}</p>
                                    </a>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-gray-200 px-3 py-4 text-center text-xs font-medium text-gray-400 dark:border-gray-800 dark:text-gray-500">
                                        No events
                                    </div>
                                @endforelse

                                @if ($day['events']->count() > 3)
                                    <div class="rounded-2xl bg-gray-50 px-3 py-2 text-center text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                                        +{{ $day['events']->count() - 3 }} more
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>
</x-app-layout>
