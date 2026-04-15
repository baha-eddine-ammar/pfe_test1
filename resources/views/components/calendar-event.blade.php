{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| Reusable calendar event card for maintenance tasks.
|
| Data source:
| The $event array is created by CalendarController::mapTaskToEvent().
| It already contains UI-ready fields like labels, status, priority, and URLs.
|--------------------------------------------------------------------------
--}}
@props([
    'event',
    'variant' => 'grid',
])

@php
    // Priority controls the surface color family of the event.
    $priorityClasses = match ($event['priority']) {
        'urgent' => 'border-rose-200/80 bg-rose-50/90 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200',
        'high' => 'border-orange-200/80 bg-orange-50/90 text-orange-700 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-200',
        'medium' => 'border-sky-200/80 bg-sky-50/90 text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-200',
        default => 'border-slate-200/80 bg-slate-50/90 text-slate-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200',
    };

    // Status controls the smaller badge tone.
    $statusBadgeClasses = match ($event['status']) {
        'assigned' => 'bg-brand-50 text-brand-600 ring-brand-100 dark:bg-brand-500/10 dark:text-brand-300 dark:ring-brand-500/20',
        'in_progress' => 'bg-amber-100 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
        'completed' => 'bg-emerald-100 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
        'cancelled' => 'bg-rose-100 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20',
        default => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-white/[0.06] dark:text-slate-300 dark:ring-white/10',
    };

    // Surface classes are combined with status-specific modifiers below.
    $surfaceClasses = match ($event['priority']) {
        'urgent' => 'calendar-event-card--urgent',
        'high' => 'calendar-event-card--high',
        'medium' => 'calendar-event-card--medium',
        default => 'calendar-event-card--low',
    };

    if ($event['status'] === 'completed') {
        $surfaceClasses .= ' calendar-event-card--completed';
    }

    if ($event['is_overdue']) {
        $surfaceClasses .= ' calendar-event-card--overdue';
    }

    $sizeClasses = $variant === 'list'
        ? 'min-h-[120px] px-4 py-4'
        : 'min-h-[108px] px-3.5 py-3.5';

    $preview = $event['preview'] ?: 'No preview available.';
@endphp

<button
    {{--
        Interaction behavior:
        - hover/focus shows the quick tooltip
        - click opens the full event modal through calendar-workspace.js
    --}}
    type="button"
    x-data="{ previewOpen: false }"
    x-on:mouseenter="previewOpen = true"
    x-on:mouseleave="previewOpen = false"
    x-on:focus="previewOpen = true"
    x-on:blur="previewOpen = false"
    x-on:click="openEvent(@js($event))"
    class="calendar-event-card {{ $surfaceClasses }} {{ $sizeClasses }} group relative w-full overflow-hidden rounded-[22px] border text-left transition duration-300 ease-out"
>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                {{ $event['time_label'] }}
            </p>
            <p class="mt-2 truncate text-sm font-semibold text-slate-950 dark:text-white {{ $variant === 'list' ? 'sm:text-base' : '' }}">
                {{ $event['title'] }}
            </p>
        </div>

        @if ($event['status'] === 'in_progress')
            <span class="calendar-pulse-dot mt-1 shrink-0 bg-amber-400"></span>
        @elseif ($event['status'] === 'completed')
            <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-400 shadow-[0_0_18px_rgba(52,211,153,0.55)]"></span>
        @endif
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] {{ $priorityClasses }}">
            {{ $event['priority_label'] }}
        </span>
        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] ring-1 ring-inset {{ $statusBadgeClasses }}">
            {{ $event['status_label'] }}
        </span>
    </div>

    <p class="mt-3 line-clamp-2 text-xs leading-5 text-slate-500 dark:text-slate-400 {{ $variant === 'list' ? 'sm:text-sm' : '' }}">
        {{ $preview }}
    </p>

    <div
        x-cloak
        x-show="previewOpen"
        x-transition.opacity.duration.150ms
        class="calendar-event-tooltip pointer-events-none absolute left-4 right-4 top-4 z-20 hidden rounded-2xl border border-slate-200/90 bg-white/95 px-4 py-3 text-left shadow-2xl shadow-slate-950/10 backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/92 dark:shadow-black/40 sm:block"
    >
        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
            {{ $event['time_label'] }} - {{ $event['priority_label'] }}
        </p>
        <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ $event['title'] }}</p>
        <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $preview }}</p>
    </div>
</button>
