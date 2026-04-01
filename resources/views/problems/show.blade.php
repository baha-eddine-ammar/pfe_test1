<x-app-layout>
    <section class="mx-auto max-w-5xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('problems.index') }}" class="app-link">
                    Back to Problems
                </a>

                <p class="app-section-title mt-4">Problem Details</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    {{ $problem->title }}
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Review the reported problem, its attachments, and the submitted solutions below.
                </p>
            </div>

            <span class="app-pill bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ ucfirst($problem->status) }}
            </span>
        </div>

        @if (session('success'))
            <div class="app-status-success mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-6">
            <div class="app-card px-6 py-6 sm:px-7">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="app-surface-muted px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                            Submitted By
                        </p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">
                            {{ $problem->user->name }}
                        </p>
                    </div>

                    <div class="app-surface-muted px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                            Created
                        </p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">
                            {{ $problem->created_at->format('d M Y H:i') }}
                        </p>
                    </div>

                    <div class="app-surface-muted px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                            Files
                        </p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">
                            {{ $problem->attachments->count() }}
                        </p>
                    </div>

                    <div class="app-surface-muted px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                            Solutions
                        </p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">
                            {{ $problem->solutions_count }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="app-card px-6 py-6 sm:px-7">
                <h2 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">
                    Description
                </h2>

                <div class="report-prose mt-4">
                    {!! nl2br(e($problem->description)) !!}
                </div>
            </div>

            <div class="app-card px-6 py-6 sm:px-7">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="app-section-title">Attachments</p>
                        <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">
                            Problem Files
                        </h2>
                    </div>

                    <span class="app-pill bg-gray-100 text-gray-600 dark:bg-white/[0.05] dark:text-gray-300">
                        {{ $problem->attachments->count() }} file(s)
                    </span>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($problem->attachments as $attachment)
                        <div class="app-surface-muted flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $attachment->original_name }}
                                </p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $attachment->mime_type ?? 'Unknown file type' }}
                                    @if ($attachment->file_size)
                                        • {{ number_format($attachment->file_size / 1024, 1) }} KB
                                    @endif
                                </p>
                            </div>

                            <a
                                href="{{ asset('storage/' . $attachment->file_path) }}"
                                target="_blank"
                                class="app-button-secondary"
                            >
                                Open File
                            </a>
                        </div>
                    @empty
                        <div class="app-surface-muted px-4 py-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                No files were attached to this problem.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="app-card px-6 py-6 sm:px-7">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="app-section-title">Solutions</p>
                        <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">
                            Submitted Solutions
                        </h2>
                    </div>

                    <span class="app-pill bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                        {{ $problem->solutions_count }} total
                    </span>
                </div>

                <div class="mt-5 space-y-4">
                    @forelse ($problem->solutions as $solution)
                        <article class="app-surface-muted px-5 py-5">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-display text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $solution->user->name }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $solution->created_at->format('d M Y H:i') }}
                                    </p>
                                </div>

                                <span class="app-pill bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                    Solution
                                </span>
                            </div>

                            <div class="report-prose mt-4">
                                {!! nl2br(e($solution->body)) !!}
                            </div>

                            @if ($solution->attachments->isNotEmpty())
                                <div class="mt-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                        Solution Attachments
                                    </p>

                                    <div class="mt-3 space-y-3">
                                        @foreach ($solution->attachments as $attachment)
                                            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-4 dark:border-gray-800 dark:bg-gray-900">
                                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <div>
                                                        <p class="font-medium text-gray-900 dark:text-white">
                                                            {{ $attachment->original_name }}
                                                        </p>
                                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                            {{ $attachment->mime_type ?? 'Unknown file type' }}
                                                            @if ($attachment->file_size)
                                                                • {{ number_format($attachment->file_size / 1024, 1) }} KB
                                                            @endif
                                                        </p>
                                                    </div>

                                                    <a
                                                        href="{{ asset('storage/' . $attachment->file_path) }}"
                                                        target="_blank"
                                                        class="app-button-secondary"
                                                    >
                                                        Open File
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="app-surface-muted px-4 py-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                No solutions have been submitted for this problem yet.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="app-card px-6 py-6 sm:px-7">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="app-section-title">Add Solution</p>
                        <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">
                            Submit a Solution
                        </h2>
                    </div>

                    <span class="app-pill bg-gray-100 text-gray-600 dark:bg-white/[0.05] dark:text-gray-300">
                        Text + files
                    </span>
                </div>

                <form
                    method="POST"
                    action="{{ route('problems.solutions.store', $problem) }}"
                    enctype="multipart/form-data"
                    class="mt-5 space-y-5"
                >
                    @csrf

                    <div>
                        <label for="body" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Solution text
                        </label>
                        <textarea
                            id="body"
                            name="body"
                            rows="8"
                            class="app-input min-h-[180px] resize-y"
                            placeholder="Explain the solution clearly so other users can understand and use it."
                            required
                        >{{ old('body') }}</textarea>

                        @error('body')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="attachments" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Attach files (optional)
                        </label>
                        <input
                            type="file"
                            id="attachments"
                            name="attachments[]"
                            class="app-input py-3"
                            multiple
                            accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg,.zip"
                        >

                        @error('attachments')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror

                        @error('attachments.*')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror

                        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                            Allowed file types: PDF, DOC, DOCX, TXT, PNG, JPG, JPEG, ZIP. Maximum file size: 10 MB each.
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            These files will be attached only to this solution, not to the main problem.
                        </p>

                        <button type="submit" class="app-button-primary">
                            Submit Solution
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-app-layout>
