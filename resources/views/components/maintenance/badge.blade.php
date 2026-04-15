@props([
    'type' => 'status',
    'value',
])

@php
    $normalized = (string) $value;

    $classes = match ($type) {
        'priority' => match ($normalized) {
            'urgent' => 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20',
            'high' => 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
            'medium' => 'bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20',
            default => 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200 dark:bg-white/[0.06] dark:text-slate-300 dark:ring-white/10',
        },
        'overdue' => 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20',
        default => match ($normalized) {
            'pending' => 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200 dark:bg-white/[0.06] dark:text-slate-300 dark:ring-white/10',
            'assigned' => 'bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100 dark:bg-brand-500/10 dark:text-brand-300 dark:ring-brand-500/20',
            'in_progress' => 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
            'completed' => 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
            'cancelled' => 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20',
            default => 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200 dark:bg-white/[0.06] dark:text-slate-300 dark:ring-white/10',
        },
    };

    $label = $type === 'overdue'
        ? 'Overdue'
        : str($normalized)->replace('_', ' ')->title()->toString();
@endphp

<span {{ $attributes->class("inline-flex items-center rounded-full px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] {$classes}") }}>
    {{ $label }}
</span>
