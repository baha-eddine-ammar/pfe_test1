{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This is the main maintenance workspace page.
| It changes based on the authenticated user's role:
| - department_head: create + filter + manage all tasks
| - staff: view only assigned tasks and update progress
|
| Why this file exists:
| It is the visual entry point for the full maintenance module.
|
| When this file is used:
| After MaintenanceTaskController@index prepares the task list, stats, filters,
| and assignee directory.
|
| FILES TO READ (IN ORDER):
| 1. routes/web.php
| 2. app/Policies/MaintenanceTaskPolicy.php
| 3. app/Http/Controllers/MaintenanceTaskController.php
| 4. app/Services/MaintenanceTaskWorkflowService.php
| 5. app/Models/MaintenanceTask.php
| 6. resources/views/maintenance/index.blade.php
| 7. resources/views/components/maintenance/*
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. The controller sends $tasks, $stats, $filters, and role information here.
| 2. This view decides which layout to show based on $isDepartmentHead.
| 3. Forms submit back to maintenance routes.
| 4. Components render reusable badges/cards for task data.
--}}
@php
    // $user is the currently authenticated user.
    $user = auth()->user();
    // $isDepartmentHead controls which version of the workspace is rendered.
    $isDepartmentHead = $user->isDepartmentHead();
    // $currentUri is reused so forms can redirect back safely after actions.
    $currentUri = request()->getRequestUri();
@endphp

<x-app-layout>
    {{--
        This wrapper starts an Alpine component used only for lightweight UI behavior:
        - searchable assignee picker
        - optional auto-refresh of visible task areas
        Data comes from MaintenanceTaskController@index.
    --}}
    <div
        x-data="maintenanceWorkspace({
            assignees: @js($assigneeDirectory),
            selectedAssigneeId: @js((string) old('assigned_to_user_id', '')),
        })"
        x-init="init()"
        class="maintenance-shell relative isolate mx-auto max-w-[1600px] space-y-6 pb-10"
    >
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[32rem] bg-[radial-gradient(circle_at_top_left,_rgba(70,95,255,0.18),_transparent_30%),radial-gradient(circle_at_top_right,_rgba(34,197,94,0.1),_transparent_24%),linear-gradient(180deg,_rgba(255,255,255,0.96),_rgba(248,250,252,0))] blur-2xl dark:bg-[radial-gradient(circle_at_top_left,_rgba(70,95,255,0.2),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(16,185,129,0.08),_transparent_20%),linear-gradient(180deg,_rgba(15,23,42,0.66),_rgba(2,6,23,0))]"></div>

        {{--
            Page header:
            Shows the current workspace mode and high-level explanation.
            Text changes depending on whether the user is a department head or staff.
        --}}
        <section class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="app-section-title">Operations</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white sm:text-4xl">
                    {{ $isDepartmentHead ? 'Maintenance management workspace' : 'My maintenance tasks' }}
                </h1>
                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500 dark:text-slate-400">
                    @if ($isDepartmentHead)
                        Create, assign, and supervise maintenance work from one secure workspace with live operational visibility and automatic notifications.
                    @else
                        Track the tasks assigned to you, update progress quickly, and leave notes that stay attached to the activity timeline.
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="maintenance-soft-tile rounded-full px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Workspace mode</p>
                    <p class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">
                        {{ $isDepartmentHead ? 'Department Head' : 'Staff' }}
                    </p>
                </div>
                <div class="maintenance-soft-tile rounded-full px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Last sync</p>
                    <p class="mt-1 text-sm font-semibold text-slate-950 dark:text-white" x-text="lastRefreshLabel"></p>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="app-status-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="app-status-danger">
                <p class="font-semibold">Please review the highlighted maintenance form fields.</p>
                <ul class="mt-3 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{--
            Summary stats:
            Data comes from MaintenanceTaskController::buildStats().
            Each card is a quick snapshot of the visible task set for this user.
        --}}
        <section data-maintenance-stats class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <article class="maintenance-metric-card">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-3 font-display text-4xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white">{{ $stat['value'] }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $stat['caption'] }}</p>
                </article>
            @endforeach
        </section>

        @if ($isDepartmentHead)
            {{--
                Department-head layout:
                Left = task creation form
                Right = filters + global task registry
            --}}
            <section class="grid gap-6 xl:grid-cols-[420px_minmax(0,1fr)]">
                <aside class="space-y-6">
                    {{--
                        Task creation form:
                        Submits to maintenance.store.
                        Validation happens in StoreMaintenanceTaskRequest.
                        Actual database/history/notification work happens in MaintenanceTaskWorkflowService.
                    --}}
                    <article id="task-creator" class="maintenance-panel px-6 py-6 sm:px-7 xl:sticky xl:top-24">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="app-section-title">Task creator</p>
                                <h2 class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">Create and assign work</h2>
                                <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">
                                    New tasks are created as pending, logged in history, and instantly delivered through platform notifications plus Telegram.
                                </p>
                            </div>
                            <span class="dashboard-live-badge">
                                <span class="dashboard-live-dot"></span>
                                Live dispatch
                            </span>
                        </div>

                        <form method="POST" action="{{ route('maintenance.store') }}" class="mt-6 space-y-5">
                            @csrf

                            <div>
                                <label for="server_room" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Server room
                                </label>
                                <input
                                    type="text"
                                    id="server_room"
                                    name="server_room"
                                    class="app-input"
                                    value="{{ old('server_room') }}"
                                    placeholder="Example: Server Room A"
                                    required
                                >
                                @error('server_room')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="maintenance_date" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Maintenance date
                                </label>
                                <input
                                    type="datetime-local"
                                    id="maintenance_date"
                                    name="maintenance_date"
                                    class="app-input"
                                    value="{{ old('maintenance_date') }}"
                                    required
                                >
                                @error('maintenance_date')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="priority" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Priority
                                </label>
                                <select id="priority" name="priority" class="app-select" required>
                                    <option value="">Select priority</option>
                                    @foreach ($priorityOptions as $priority)
                                        <option value="{{ $priority }}" @selected(old('priority') === $priority)>{{ str($priority)->title() }}</option>
                                    @endforeach
                                </select>
                                @error('priority')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="relative" @click.outside="assigneeMenuOpen = false">
                                <label for="assignee-search" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Assign user
                                </label>
                                <input type="hidden" name="assigned_to_user_id" :value="selectedAssigneeId">
                                <input
                                    id="assignee-search"
                                    type="text"
                                    class="app-input"
                                    placeholder="Search approved staff by name or email"
                                    x-model="assigneeQuery"
                                    @focus="assigneeMenuOpen = true"
                                    @input="assigneeMenuOpen = true"
                                    autocomplete="off"
                                    required
                                >

                                <div
                                    x-cloak
                                    x-show="assigneeMenuOpen"
                                    x-transition.origin.top.left
                                    class="maintenance-panel absolute z-20 mt-3 max-h-72 w-full overflow-y-auto px-3 py-3"
                                    style="display: none;"
                                >
                                    <template x-if="filteredAssignees().length === 0">
                                        <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
                                            No user matched your search.
                                        </div>
                                    </template>

                                    <div class="space-y-2">
                                        <template x-for="assignee in filteredAssignees()" :key="assignee.id">
                                            <button
                                                type="button"
                                                class="maintenance-soft-tile flex w-full items-center justify-between rounded-[20px] px-4 py-3 text-left transition hover:-translate-y-0.5"
                                                @click="chooseAssignee(assignee)"
                                            >
                                                <div>
                                                    <p class="font-medium text-slate-900 dark:text-white" x-text="assignee.name"></p>
                                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" x-text="assignee.email"></p>
                                                </div>
                                                <span class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500" x-text="assignee.department || 'Staff'"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                @error('assigned_to_user_id')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="fix_description" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    Fix description
                                </label>
                                <textarea
                                    id="fix_description"
                                    name="fix_description"
                                    rows="6"
                                    class="app-input min-h-[180px] resize-y"
                                    placeholder="Describe the issue, expected work, and any operational constraints."
                                    required
                                >{{ old('fix_description') }}</textarea>
                                @error('fix_description')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="app-button-primary w-full">
                                Create Maintenance Task
                            </button>
                        </form>
                    </article>

                </aside>

                <div data-maintenance-board class="space-y-6">
                    {{--
                        Task registry:
                        Department heads see all visible tasks in a table.
                        Badge components render priority and status consistently.
                    --}}
                    <article class="maintenance-panel px-6 py-6 sm:px-7">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="app-section-title">Task registry</p>
                                <h2 class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">Assigned maintenance tasks</h2>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Showing <span class="font-semibold text-slate-900 dark:text-white">{{ $tasks->total() }}</span> task(s)
                            </p>
                        </div>

                        <div class="mt-6">
                            @if ($tasks->isEmpty())
                                <div class="rounded-[28px] border border-dashed border-slate-200 px-6 py-14 text-center dark:border-white/10">
                                    <p class="font-display text-2xl font-semibold text-slate-950 dark:text-white">No maintenance tasks matched these filters</p>
                                    <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">Adjust the filters or create a new task from the workspace on the left.</p>
                                </div>
                            @else
                                <div class="maintenance-table-wrap">
                                    <div class="overflow-x-auto">
                                        <table class="maintenance-table">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Server Room</th>
                                                    <th>Assigned User</th>
                                                    <th>Priority</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($tasks as $task)
                                                    <tr>
                                                        <td>
                                                            <div class="font-semibold text-slate-950 dark:text-white">#{{ $task->id }}</div>
                                                            <div class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">{{ $task->updated_at->diffForHumans() }}</div>
                                                        </td>
                                                        <td>
                                                            <p class="font-semibold text-slate-950 dark:text-white">{{ $task->server_room }}</p>
                                                            <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Str::limit($task->fix_description, 96) }}</p>
                                                        </td>
                                                        <td>
                                                            <p class="font-medium text-slate-900 dark:text-white">{{ $task->assignedToUser->name }}</p>
                                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $task->assignedToUser->email }}</p>
                                                        </td>
                                                        <td>
                                                            <x-maintenance.badge type="priority" :value="$task->priority" />
                                                        </td>
                                                        <td class="space-y-2">
                                                            <x-maintenance.badge :value="$task->status" />
                                                            @if ($task->is_overdue)
                                                                <div>
                                                                    <x-maintenance.badge type="overdue" value="overdue" />
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <p class="font-medium text-slate-900 dark:text-white">{{ $task->maintenance_date->format('d M Y') }}</p>
                                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $task->maintenance_date->format('H:i') }}</p>
                                                        </td>
                                                        <td>
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <a href="{{ route('maintenance.show', $task) }}" class="app-button-secondary px-3 py-2">
                                                                    View
                                                                </a>
                                                                <a href="{{ route('maintenance.edit', $task) }}" class="app-button-secondary px-3 py-2">
                                                                    Edit
                                                                </a>
                                                                <form method="POST" action="{{ route('maintenance.destroy', $task) }}" onsubmit="return confirm('Delete this maintenance task?');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <input type="hidden" name="redirect_to" value="{{ $currentUri }}">
                                                                    <button type="submit" class="app-button-danger px-3 py-2">
                                                                        Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    {{ $tasks->links() }}
                                </div>
                            @endif
                        </div>
                    </article>
                </div>
            </section>
        @else
            {{--
                Staff layout:
                Staff users do not see the creation form.
                They only see filters plus cards for tasks assigned to them.
            --}}
            <section data-maintenance-board class="space-y-6">
                @if ($tasks->isEmpty())
                    <article class="maintenance-panel px-6 py-14 text-center sm:px-7">
                        <p class="font-display text-2xl font-semibold text-slate-950 dark:text-white">No assigned maintenance tasks right now</p>
                        <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">Once new work is assigned to you, it will appear here automatically.</p>
                    </article>
                @else
                    {{--
                        Reusable task cards for staff:
                        Each card is rendered by <x-maintenance.task-card>.
                    --}}
                    <div class="grid gap-5 xl:grid-cols-2">
                        @foreach ($tasks as $task)
                            <x-maintenance.task-card
                                :task="$task"
                                :status-options="$statusOptions"
                                :redirect-to="$currentUri"
                            />
                        @endforeach
                    </div>

                    <div>
                        {{ $tasks->links() }}
                    </div>
                @endif
            </section>
        @endif
    </div>

    <script>
        /*
         * This small page-local Alpine helper handles only presentation behavior.
         * Business logic still lives in the controller/service/model layer.
         *
         * What it does:
         * - searchable assignee dropdown for the creator form
         * - WebSocket-triggered refresh of the maintenance stats/board sections
         * - last refresh timestamp display
         */
        window.maintenanceWorkspace = window.maintenanceWorkspace || function (config) {
            return {
                assignees: Array.isArray(config.assignees) ? config.assignees : [],
                selectedAssigneeId: config.selectedAssigneeId || '',
                assigneeQuery: '',
                assigneeMenuOpen: false,
                lastRefreshLabel: new Date().toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                }),
                // Initializes the assignee field and realtime refresh listener.
                init() {
                    if (this.selectedAssigneeId) {
                        const selected = this.assignees.find((assignee) => String(assignee.id) === String(this.selectedAssigneeId));

                        if (selected) {
                            this.assigneeQuery = selected.name;
                        }
                    }

                    window.addEventListener('maintenance-task-changed', () => {
                        this.refreshWorkspace();
                    });
                },
                // Filters the assignee directory by name, email, or department.
                filteredAssignees() {
                    const query = this.assigneeQuery.trim().toLowerCase();

                    if (query === '') {
                        return this.assignees.slice(0, 8);
                    }

                    return this.assignees
                        .filter((assignee) => {
                            return [assignee.name, assignee.email, assignee.department]
                                .filter(Boolean)
                                .some((value) => value.toLowerCase().includes(query));
                        })
                        .slice(0, 8);
                },
                // Stores the chosen user ID in the hidden input and shows the name in the search box.
                chooseAssignee(assignee) {
                    this.selectedAssigneeId = String(assignee.id);
                    this.assigneeQuery = assignee.name;
                    this.assigneeMenuOpen = false;
                },
                // Re-fetches the current page HTML and swaps only the task/stats sections.
                async refreshWorkspace() {
                    if (document.visibilityState === 'hidden') {
                        return;
                    }

                    try {
                        const response = await fetch(window.location.href, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            },
                        });

                        if (! response.ok) {
                            return;
                        }

                        const html = await response.text();
                        const parser = new DOMParser();
                        const nextDocument = parser.parseFromString(html, 'text/html');

                        ['[data-maintenance-stats]', '[data-maintenance-board]'].forEach((selector) => {
                            const currentNode = document.querySelector(selector);
                            const nextNode = nextDocument.querySelector(selector);

                            if (! currentNode || ! nextNode) {
                                return;
                            }

                            currentNode.innerHTML = nextNode.innerHTML;

                            if (window.Alpine) {
                                window.Alpine.initTree(currentNode);
                            }
                        });

                        this.lastRefreshLabel = new Date().toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                        });
                    } catch (error) {
                        console.error('Failed to refresh maintenance workspace', error);
                    }
                },
            };
        };
    </script>
</x-app-layout>
