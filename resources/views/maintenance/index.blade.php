<x-app-layout>
    <section class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="app-section-title">Operations</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Maintenance Tasks
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    @if (auth()->user()->role === 'department_head')
                        Create, assign, and manage scheduled maintenance tasks for IT staff.
                    @else
                        Review the maintenance tasks assigned to you and keep their status updated.
                    @endif
                </p>
            </div>

            @can('create', \App\Models\MaintenanceTask::class)
                <a href="{{ route('maintenance.create') }}" class="app-button-primary">
                    Create Task
                </a>
            @endcan
        </div>

        @if (session('success'))
            <div class="app-status-success mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="app-card mb-6 px-6 py-6 sm:px-7">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="app-section-title">Filters</p>
                    <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">
                        Search Maintenance Tasks
                    </h2>
                </div>

                <a href="{{ route('maintenance.index') }}" class="app-button-secondary">
                    Clear Filters
                </a>
            </div>

            <form method="GET" action="{{ route('maintenance.index') }}" class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div class="xl:col-span-3">
                    <label for="search" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Search
                    </label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="app-input"
                        value="{{ $filters['search'] }}"
                        placeholder="Search by server room or description"
                    >
                </div>

                <div>
                    <label for="priority" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Priority
                    </label>
                    <select id="priority" name="priority" class="app-select">
                        <option value="">All priorities</option>
                        @foreach ($priorityOptions as $priority)
                            <option value="{{ $priority }}" @selected($filters['priority'] === $priority)>
                                {{ ucfirst($priority) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Status
                    </label>
                    <select id="status" name="status" class="app-select">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>
                                {{ str_replace('_', ' ', ucfirst($status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if (auth()->user()->role === 'department_head')
                    <div>
                        <label for="assigned_to_user_id" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Assigned IT staff
                        </label>
                        <select id="assigned_to_user_id" name="assigned_to_user_id" class="app-select">
                            <option value="">All IT staff</option>
                            @foreach ($itStaffUsers as $staffUser)
                                <option value="{{ $staffUser->id }}" @selected((string) $filters['assigned_to_user_id'] === (string) $staffUser->id)>
                                    {{ $staffUser->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label for="date_from" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Date from
                    </label>
                    <input
                        type="date"
                        id="date_from"
                        name="date_from"
                        class="app-input"
                        value="{{ $filters['date_from'] }}"
                    >
                </div>

                <div>
                    <label for="date_to" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Date to
                    </label>
                    <input
                        type="date"
                        id="date_to"
                        name="date_to"
                        class="app-input"
                        value="{{ $filters['date_to'] }}"
                    >
                </div>

                <div class="md:col-span-2 xl:col-span-3 flex justify-end">
                    <button type="submit" class="app-button-primary">
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Showing <span class="font-semibold text-gray-900 dark:text-white">{{ $tasks->count() }}</span> task(s)
            </p>
        </div>

        <div class="space-y-4">
            @forelse ($tasks as $task)
                <a href="{{ route('maintenance.show', $task) }}" class="block">
                    <article class="app-card app-card-hover px-6 py-6 sm:px-7">
                        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-3">
                                    <h2 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ $task->server_room }}
                                    </h2>

                                    <span class="app-pill
                                        @if ($task->priority === 'urgent') bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300
                                        @elseif ($task->priority === 'high') bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300
                                        @elseif ($task->priority === 'medium') bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300
                                        @else bg-gray-100 text-gray-700 dark:bg-white/[0.05] dark:text-gray-300
                                        @endif
                                    ">
                                        {{ strtoupper($task->priority) }}
                                    </span>

                                    <span class="app-pill
                                        @if ($task->status === 'completed') bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300
                                        @elseif ($task->status === 'cancelled') bg-gray-100 text-gray-700 dark:bg-white/[0.05] dark:text-gray-300
                                        @elseif ($task->status === 'in_progress') bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300
                                        @elseif ($task->status === 'assigned') bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300
                                        @else bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300
                                        @endif
                                    ">
                                        {{ str_replace('_', ' ', ucfirst($task->status)) }}
                                    </span>
                                </div>

                                <p class="mt-4 text-sm leading-7 text-gray-500 dark:text-gray-400">
                                    {{ \Illuminate\Support\Str::limit($task->fix_description, 180) }}
                                </p>

                                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-gray-400 dark:text-gray-500">
                                    <span>Assigned to {{ $task->assignedToUser->name }}</span>
                                    <span>Created by {{ $task->createdByUser->name }}</span>
                                    <span>{{ $task->maintenance_date->format('d M Y H:i') }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 xl:min-w-[240px]">
                                <div class="app-surface-muted px-4 py-3 text-center">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                        Date
                                    </p>
                                    <p class="mt-2 font-medium text-gray-900 dark:text-white">
                                        {{ $task->maintenance_date->format('d M Y') }}
                                    </p>
                                </div>

                                <div class="app-surface-muted px-4 py-3 text-center">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                        Time
                                    </p>
                                    <p class="mt-2 font-medium text-gray-900 dark:text-white">
                                        {{ $task->maintenance_date->format('H:i') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </article>
                </a>
            @empty
                <div class="app-card px-6 py-10 text-center">
                    <h2 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">
                        No maintenance tasks found
                    </h2>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        Try changing or clearing the filters.
                    </p>

                    <div class="mt-6">
                        <a href="{{ route('maintenance.index') }}" class="app-button-secondary">
                            Clear Filters
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
