{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| Reusable chart container for dashboard analytics blocks.
|
| Why this component exists:
| Many dashboard sections share the same outer structure:
| eyebrow + title + optional description + optional action area + inner chart slot.
--}}
@props([
    'eyebrow' => 'Analytics',
    'title',
    'description' => null,
    'height' => 'h-[320px]',
])

<article {{ $attributes->class(['dashboard-panel overflow-hidden px-6 py-6 sm:px-7']) }}>
    {{--
        Header area for the chart/card.
        The actual chart or custom content is passed through $slot below.
    --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="max-w-2xl">
            <p class="app-section-title">{{ $eyebrow }}</p>
            <h2 class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">{{ $title }}</h2>
            @if ($description)
                <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">{{ $description }}</p>
            @endif
        </div>

        @isset($action)
            <div class="shrink-0">
                {{ $action }}
            </div>
        @endisset
    </div>

    <div class="mt-6 {{ $height }}">
        {{ $slot }}
    </div>
</article>
