<x-app-layout>
    <section class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="app-section-title">Knowledge</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Problems
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Browse reported problems and open one to review details and solutions.
                </p>
            </div>

            <a href="{{ route('problems.create') }}" class="app-button-primary">
                Submit New Problem
            </a>
        </div>

        <div class="space-y-4">
            @forelse ($problems as $problem)
                <a href="{{ route('problems.show', $problem) }}" class="block">
                    <article class="app-card app-card-hover px-6 py-6 sm:px-7">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-3">
                                    <h2 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ $problem->title }}
                                    </h2>

                                    <span class="app-pill bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        {{ ucfirst($problem->status) }}
                                    </span>
                                </div>

                                <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">
                                    {{ \Illuminate\Support\Str::limit($problem->description, 180) }}
                                </p>

                                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-gray-400 dark:text-gray-500">
                                    <span>Submitted by {{ $problem->user->name }}</span>
                                    <span>{{ $problem->created_at->format('d M Y H:i') }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 sm:min-w-[220px]">
                                <div class="app-surface-muted px-4 py-3 text-center">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                        Files
                                    </p>
                                    <p class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ $problem->attachments_count }}
                                    </p>
                                </div>

                                <div class="app-surface-muted px-4 py-3 text-center">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                        Solutions
                                    </p>
                                    <p class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ $problem->solutions_count }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </article>
                </a>


                //If no problems exist → show message

                {{-- //If no problems exist → show message--}}
            @empty
                <div class="app-card px-6 py-10 text-center">
                    <h2 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">
                        No problems submitted yet
                    </h2>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        Start by creating the first problem report.
                    </p>

                    <div class="mt-6">
                        <a href="{{ route('problems.create') }}" class="app-button-primary">
                            Submit First Problem
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
