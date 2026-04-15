{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| Reusable maintenance task card used in the staff "My Tasks" view.
|
| Data source:
| Each $task model comes from MaintenanceTaskController@index.
| Helper methods on the MaintenanceTask model control available actions.
|--------------------------------------------------------------------------
--}}
@props([
    'task',
    'statusOptions' => [],
    'redirectTo' => '/maintenance',
])

@php
    // The model decides which next statuses the current user may choose.
    $availableStatuses = $task->allowedStatusTransitionsFor(auth()->user());
    $displayStatusOptions = array_values(array_intersect($statusOptions, $availableStatuses));
    // Human-friendly date label used in the schedule box.
    $dueLabel = $task->maintenance_date->format('d M Y \\a\\t H:i');
    // Used so the user returns to the correct listing page after an update.
    $redirectTarget = str_starts_with($redirectTo, '/') ? $redirectTo : route('maintenance.index', [], false);
@endphp

<article
    x-data="{ expanded: false }"
    class="maintenance-panel maintenance-panel-hover group overflow-hidden px-6 py-6 sm:px-7"
>
    <div class="flex flex-col gap-5">
        {{--
            Top section:
            room name, badges, description, and scheduling summary.
        --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <p class="font-display text-2xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white">
                        {{ $task->server_room }}
                    </p>

                    <x-maintenance.badge type="priority" :value="$task->priority" />
                    <x-maintenance.badge :value="$task->status" />

                    @if ($task->is_overdue)
                        <x-maintenance.badge type="overdue" value="overdue" />
                    @endif
                </div>

                <p class="mt-4 text-sm leading-7 text-slate-500 dark:text-slate-400">
                    {{ $task->fix_description }}
                </p>
            </div>

            <div class="maintenance-soft-tile min-w-[220px] rounded-[24px] px-4 py-4 lg:text-right">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Scheduled</p>
                <p class="mt-2 font-display text-xl font-semibold text-slate-950 dark:text-white">{{ $dueLabel }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                    Assigned by {{ $task->createdByUser->name }}
                </p>
            </div>
        </div>

        {{--
            Small metadata tiles:
            task ID, assignee, and last update time.
        --}}
        <div class="grid gap-3 md:grid-cols-3">
            <div class="maintenance-soft-tile rounded-[22px] px-4 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Task ID</p>
                <p class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">#{{ $task->id }}</p>
            </div>

            <div class="maintenance-soft-tile rounded-[22px] px-4 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Assigned to</p>
                <p class="mt-2 font-medium text-slate-900 dark:text-white">{{ $task->assignedToUser->name }}</p>
            </div>

            <div class="maintenance-soft-tile rounded-[22px] px-4 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Last update</p>
                <p class="mt-2 font-medium text-slate-900 dark:text-white">{{ $task->updated_at->diffForHumans() }}</p>
            </div>
        </div>

        {{--
            Quick actions:
            start, complete, open details, or reveal the manual update form.
        --}}
        <div class="flex flex-wrap items-center gap-3">
            @if ($task->canQuickStart())
                <form method="POST" action="{{ route('maintenance.update-status', $task) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ \App\Models\MaintenanceTask::STATUS_IN_PROGRESS }}">
                    <input type="hidden" name="redirect_to" value="{{ $redirectTarget }}">
                    <button type="submit" class="app-button-primary px-4 py-2.5">
                        Start Task
                    </button>
                </form>
            @endif

            @if ($task->canQuickComplete())
                <form method="POST" action="{{ route('maintenance.update-status', $task) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ \App\Models\MaintenanceTask::STATUS_COMPLETED }}">
                    <input type="hidden" name="redirect_to" value="{{ $redirectTarget }}">
                    <button type="submit" class="app-button-secondary px-4 py-2.5">
                        Complete Task
                    </button>
                </form>
            @endif

            <button
                type="button"
                class="app-button-secondary px-4 py-2.5"
                @click="expanded = !expanded"
            >
                <span x-text="expanded ? 'Hide Update Form' : 'Update Status'"></span>
            </button>

            <a href="{{ route('maintenance.show', $task) }}" class="app-link">
                Open Details
            </a>
        </div>

        {{--
            Expandable manual update form:
            lets the user choose an allowed status and optionally add a note.
        --}}
        <div
            x-cloak
            x-show="expanded"
            x-transition.opacity.duration.200ms
            class="maintenance-soft-tile rounded-[26px] px-5 py-5"
        >
            <form method="POST" action="{{ route('maintenance.update-status', $task) }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="redirect_to" value="{{ $redirectTarget }}">

                <div class="grid gap-4 md:grid-cols-[minmax(0,220px)_minmax(0,1fr)]">
                    <div>
                        <label for="task-status-{{ $task->id }}" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Status
                        </label>
                        <select id="task-status-{{ $task->id }}" name="status" class="app-select" required>
                            @foreach ($displayStatusOptions as $status)
                                <option value="{{ $status }}" @selected(old('status', $task->status) === $status)>
                                    {{ str($status)->replace('_', ' ')->title() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="task-note-{{ $task->id }}" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Optional note
                        </label>
                        <textarea
                            id="task-note-{{ $task->id }}"
                            name="note"
                            rows="3"
                            class="app-input min-h-[110px] resize-y"
                            placeholder="Add a progress note, blocker, or handoff comment."
                        >{{ old('note') }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="app-button-primary">
                        Save Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</article>
