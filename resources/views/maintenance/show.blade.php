<x-app-layout>
    <section class="mx-auto max-w-5xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('maintenance.index') }}" class="app-link">
                    Back to Maintenance
                </a>

                <p class="app-section-title mt-4">Operations</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    {{ $maintenanceTask->server_room }}
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Review the maintenance task, assignment, schedule, and progress.
                </p>
            </div>

            @can('update', $maintenanceTask)
                <a href="{{ route('maintenance.edit', $maintenanceTask) }}" class="app-button-primary">
                    Edit Task
                </a>
            @endcan
        </div>

        @if (session('success'))
            <div class="app-status-success mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-6">
            <div class="app-card px-6 py-6 sm:px-7">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="app-surface-muted px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                            Assigned To
                        </p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">
                            {{ $maintenanceTask->assignedToUser->name }}
                        </p>
                    </div>

                    <div class="app-surface-muted px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                            Created By
                        </p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">
                            {{ $maintenanceTask->createdByUser->name }}
                        </p>
                    </div>

                    <div class="app-surface-muted px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                            Date
                        </p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">
                            {{ $maintenanceTask->maintenance_date->format('d M Y') }}
                        </p>
                    </div>

                    <div class="app-surface-muted px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                            Time
                        </p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">
                            {{ $maintenanceTask->maintenance_date->format('H:i') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
                <div class="space-y-6">
                    <div class="app-card px-6 py-6 sm:px-7">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="app-pill
                                @if ($maintenanceTask->priority === 'urgent') bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300
                                @elseif ($maintenanceTask->priority === 'high') bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300
                                @elseif ($maintenanceTask->priority === 'medium') bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300
                                @else bg-gray-100 text-gray-700 dark:bg-white/[0.05] dark:text-gray-300
                                @endif
                            ">
                                {{ strtoupper($maintenanceTask->priority) }}
                            </span>

                            <span class="app-pill
                                @if ($maintenanceTask->status === 'completed') bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300
                                @elseif ($maintenanceTask->status === 'cancelled') bg-gray-100 text-gray-700 dark:bg-white/[0.05] dark:text-gray-300
                                @elseif ($maintenanceTask->status === 'in_progress') bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300
                                @elseif ($maintenanceTask->status === 'assigned') bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300
                                @else bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300
                                @endif
                            ">
                                {{ str_replace('_', ' ', ucfirst($maintenanceTask->status)) }}
                            </span>
                        </div>

                        <h2 class="mt-5 font-display text-2xl font-semibold text-gray-900 dark:text-white">
                            Fix Description
                        </h2>

                        <div class="report-prose mt-4">
                            {!! nl2br(e($maintenanceTask->fix_description)) !!}
                        </div>
                    </div>

                    <div class="app-card px-6 py-6 sm:px-7">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="app-section-title">History</p>
                                <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">
                                    Activity Timeline
                                </h2>
                            </div>

                            <span class="app-pill bg-gray-100 text-gray-700 dark:bg-white/[0.05] dark:text-gray-300">
                                {{ $maintenanceTask->histories->count() }} event(s)
                            </span>
                        </div>

                        <div class="mt-5 space-y-4">
                            @forelse ($maintenanceTask->histories as $history)
                                <div class="app-surface-muted px-4 py-4">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ $history->action }}
                                            </p>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                {{ $history->description ?: 'No additional description provided.' }}
                                            </p>
                                        </div>

                                        <div class="text-sm text-gray-400 dark:text-gray-500">
                                            {{ $history->created_at?->format('d M Y H:i') }}
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-gray-400 dark:text-gray-500">
                                        <span>
                                            Actor:
                                            {{ $history->actor?->name ?? 'System' }}
                                        </span>

                                        @if ($history->old_status || $history->new_status)
                                            <span>
                                                {{ $history->old_status ?: 'none' }} → {{ $history->new_status ?: 'none' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="app-surface-muted px-4 py-4">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        No activity history yet.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="app-card px-6 py-6 sm:px-7">
                        <p class="app-section-title">Status</p>
                        <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">
                            Update Task Status
                        </h2>

                        @can('updateStatus', $maintenanceTask)
                            <form method="POST" action="{{ route('maintenance.update-status', $maintenanceTask) }}" class="mt-5 space-y-4">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">

                                <div>
                                    <label for="status" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Current status
                                    </label>
                                    <select id="status" name="status" class="app-select" required>
                                        @foreach ($maintenanceTask->allowedStatusTransitionsFor(auth()->user()) as $status)
                                            <option value="{{ $status }}" @selected(old('status', $maintenanceTask->status) === $status)>
                                                {{ str_replace('_', ' ', ucfirst($status)) }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('status')
                                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="note" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Optional note
                                    </label>
                                    <textarea
                                        id="note"
                                        name="note"
                                        rows="4"
                                        class="app-input min-h-[120px] resize-y"
                                        placeholder="Add a progress note, blocker, or completion comment."
                                    >{{ old('note') }}</textarea>

                                    @error('note')
                                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button type="submit" class="app-button-primary w-full">
                                    Update Status
                                </button>
                            </form>
                        @else
                            <div class="mt-4 app-surface-muted px-4 py-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    You do not have permission to update the status of this task.
                                </p>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
