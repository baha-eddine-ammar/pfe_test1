<x-app-layout>
    <section class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="app-section-title">Knowledge</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Solutions
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Review all submitted solutions and jump back to the related problem when you need more context.
                </p>
            </div>

            <a href="{{ route('problems.index') }}" class="app-button-secondary">
                View Problems
            </a>
        </div>

        <div class="space-y-4">
            @forelse ($solutions as $solution)
                <article class="app-card app-card-hover px-6 py-6 sm:px-7">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ $solution->problem->title }}
                                </h2>

                                <span class="app-pill bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                    Solution
                                </span>
                            </div>

                            <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">
                                {{ \Illuminate\Support\Str::limit($solution->body, 220) }}
                            </p>

                            <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-gray-400 dark:text-gray-500">
                                <span>Submitted by {{ $solution->user->name }}</span>
                                <span>{{ $solution->created_at->format('d M Y H:i') }}</span>
                                <span>{{ $solution->attachments_count }} file(s)</span>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col gap-3 sm:min-w-[220px]">
                            <div class="app-surface-muted px-4 py-4 text-center">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                    Related Problem
                                </p>
                                <p class="mt-2 font-medium text-gray-900 dark:text-white">
                                    #{{ $solution->problem->id }}
                                </p>
                            </div>

                            <a href="{{ route('problems.show', $solution->problem) }}" class="app-button-primary text-center">
                                Open Problem
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="app-card px-6 py-10 text-center">
                    <h2 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">
                        No solutions submitted yet
                    </h2>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        Solutions will appear here after users open a problem and post their answer.
                    </p>

                    <div class="mt-6">
                        <a href="{{ route('problems.index') }}" class="app-button-primary">
                            Browse Problems
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
