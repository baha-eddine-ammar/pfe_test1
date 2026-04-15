{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This is the month calendar view for maintenance tasks.
|
| Why this file exists:
| It gives users a schedule-first way to understand maintenance work:
| department heads see the team calendar, staff see only their assigned tasks.
|
| When this file is used:
| After CalendarController@index maps MaintenanceTask models into calendar events.
|
| FILES TO READ (IN ORDER):
| 1. routes/web.php
| 2. app/Policies/MaintenanceTaskPolicy.php
| 3. app/Http/Controllers/CalendarController.php
| 4. app/Models/MaintenanceTask.php
| 5. resources/views/calendar/index.blade.php
| 6. resources/views/components/calendar-event.blade.php
| 7. resources/js/calendar-workspace.js
--}}
@php
    // Labels used to render the 7-column weekday header.
    $weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    // Current URI is passed into the frontend helper for modal/action context.
    $currentUri = request()->getRequestUri();
    // Base filters are reused when moving to previous/next month links.
    $baseFilters = array_filter([
        'priority' => $filters['priority'],
        'status' => $filters['status'],
    ], fn ($value) => filled($value));
    // Used to decide whether to show the empty-state helper text.
    $hasEvents = $days->contains(fn ($day) => count($day['events']) > 0);
@endphp

<x-app-layout>
    {{--
        Root Alpine calendar workspace.
        It receives the full day/event payload built by CalendarController.
    --}}
    <div
        x-data="calendarWorkspace({
            role: @js($isDepartmentHead ? 'department_head' : 'staff'),
            currentUri: @js($currentUri),
            days: @js($daysPayload),
        })"
        x-init="init()"
        x-on:keydown.escape.window="eventModalOpen ? closeEventModal() : (dayModalOpen ? closeDayModal() : null)"
        data-calendar-workspace
        class="calendar-shell relative isolate mx-auto max-w-[1600px] space-y-6 pb-10"
    >
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[34rem] bg-[radial-gradient(circle_at_top_left,_rgba(70,95,255,0.18),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(249,115,22,0.12),_transparent_22%),linear-gradient(180deg,_rgba(255,255,255,0.96),_rgba(248,250,252,0))] blur-2xl dark:bg-[radial-gradient(circle_at_top_left,_rgba(70,95,255,0.22),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(249,115,22,0.08),_transparent_20%),linear-gradient(180deg,_rgba(15,23,42,0.72),_rgba(2,6,23,0))]"></div>

        {{--
            Header area:
            Left card explains the role-specific calendar purpose.
            Right card controls month navigation context.
        --}}
        <section
            class="grid gap-6 2xl:grid-cols-[minmax(0,1.2fr)_400px]"
            x-bind:class="ready ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
            style="transition: opacity 320ms ease, transform 320ms ease;"
        >
            <article class="calendar-panel px-6 py-6 sm:px-7">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="max-w-3xl">
                        <p class="app-section-title">{{ $isDepartmentHead ? 'Maintenance calendar' : 'My schedule' }}</p>
                        <h1 class="mt-2 font-display text-3xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white sm:text-4xl">
                            {{ $isDepartmentHead ? 'Team maintenance calendar' : 'Personal maintenance schedule' }}
                        </h1>
                        <p class="mt-3 text-sm leading-7 text-slate-500 dark:text-slate-400">
                            {{ $isDepartmentHead
                                ? 'See every maintenance task in one premium month planner, grouped by day with drill-down details for each assignment.'
                                : 'Your assigned maintenance tasks flow straight into this calendar, so your daily schedule stays visible without opening the task registry.' }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:w-[320px]">
                        <div class="calendar-mini-card">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Mode</p>
                            <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">
                                {{ $isDepartmentHead ? 'Global operations view' : 'Assigned tasks only' }}
                            </p>
                        </div>
                        <div class="calendar-mini-card">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Loaded</p>
                            <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white" x-text="lastLoadedAt"></p>
                        </div>
                    </div>
                </div>
            </article>

            <article class="calendar-panel px-6 py-6 sm:px-7">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="app-section-title">Month</p>
                        <h2 class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">{{ $month->format('F Y') }}</h2>
                    </div>

                    <span class="dashboard-live-badge">
                        <span class="dashboard-live-dot"></span>
                        {{ $isDepartmentHead ? 'All tasks live' : 'My tasks live' }}
                    </span>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <a href="{{ route('calendar.index', array_merge($baseFilters, ['month' => $previousMonth])) }}" class="app-icon-button" aria-label="Previous month">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M12.5 4.5L7 10l5.5 5.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </a>
                    <a href="{{ route('calendar.index', $baseFilters) }}" class="app-button-secondary px-4 py-3">
                        Today
                    </a>
                    <a href="{{ route('calendar.index', array_merge($baseFilters, ['month' => $nextMonth])) }}" class="app-icon-button" aria-label="Next month">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M7.5 4.5L13 10l-5.5 5.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </a>
                </div>
            </article>
        </section>

        {{--
            Summary statistics for the selected month.
            Data comes from CalendarController::buildStats().
        --}}
        <section data-calendar-summary class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <article class="calendar-stat-card">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-3 font-display text-4xl font-semibold tracking-[-0.04em] text-slate-950 dark:text-white">{{ $stat['value'] }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $stat['caption'] }}</p>
                </article>
            @endforeach
        </section>

        {{--
            Month grid:
            Each day renders up to 3 visible events, then a "+N more" button.
            Each event uses the reusable <x-calendar-event> component.
        --}}
        <section data-calendar-grid class="calendar-panel overflow-hidden">
            <div class="border-b border-slate-200/70 px-6 py-6 dark:border-white/10 sm:px-7">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <p class="app-section-title">Month grid</p>
                        <h2 class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white">
                            {{ $isDepartmentHead ? 'Team workload by day' : 'My workload by day' }}
                        </h2>
                        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500 dark:text-slate-400">
                            Priority color drives the event surface, status adds a live state overlay, and every card opens full task detail without leaving the calendar.
                        </p>
                    </div>

                    <form method="GET" action="{{ route('calendar.index') }}" class="grid gap-3 sm:grid-cols-3 xl:w-[560px]">
                        <input type="hidden" name="month" value="{{ $filters['month'] }}">

                        <div>
                            <label for="calendar_priority" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Priority
                            </label>
                            <select id="calendar_priority" name="priority" class="app-select">
                                <option value="">All priorities</option>
                                @foreach ($priorityOptions as $priority)
                                    <option value="{{ $priority }}" @selected($filters['priority'] === $priority)>{{ str($priority)->title() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="calendar_status" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Status
                            </label>
                            <select id="calendar_status" name="status" class="app-select">
                                <option value="">All statuses</option>
                                @foreach ($statusOptions as $status)
                                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-end gap-3">
                            <button type="submit" class="app-button-primary flex-1">
                                Apply
                            </button>
                            <a href="{{ route('calendar.index', ['month' => $filters['month']]) }}" class="app-button-secondary px-4 py-3">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>

                @if (! $hasEvents)
                    <div class="mt-5 rounded-[24px] border border-dashed border-slate-200/80 px-5 py-5 text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
                        No maintenance tasks match the current month and filters. Try switching status or priority to widen the schedule.
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <div class="min-w-[980px]">
                    <div class="grid grid-cols-7 border-b border-slate-200/70 dark:border-white/10">
                        @foreach ($weekdayLabels as $label)
                            <div class="px-4 py-4 text-center text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">
                                {{ $label }}
                            </div>
                        @endforeach
                    </div>

                    @foreach ($weeks as $week)
                        <div class="grid grid-cols-7 border-b border-slate-200/70 last:border-b-0 dark:border-white/10">
                            @foreach ($week as $day)
                                <article class="calendar-day {{ $day['is_current_month'] ? 'calendar-day--current' : 'calendar-day--outside' }} {{ $day['is_today'] ? 'calendar-day--today' : '' }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <span class="calendar-day-number {{ $day['is_today'] ? 'calendar-day-number--today' : '' }}">
                                                {{ $day['date']->day }}
                                            </span>
                                            <p class="mt-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                                                {{ $day['date']->format('M') }}
                                            </p>
                                        </div>

                                        @if (count($day['events']) > 0)
                                            <button
                                                type="button"
                                                class="calendar-day-count"
                                                @click="openDay('{{ $day['date_key'] }}')"
                                            >
                                                {{ count($day['events']) }} {{ count($day['events']) === 1 ? 'task' : 'tasks' }}
                                            </button>
                                        @endif
                                    </div>

                                    <div class="mt-4 space-y-3">
                                        @forelse ($day['visible_events'] as $event)
                                            <x-calendar-event :event="$event" />
                                        @empty
                                            <div class="calendar-empty-slot">
                                                {{ $day['is_current_month'] ? 'No scheduled tasks' : 'Outside current month' }}
                                            </div>
                                        @endforelse

                                        @if ($day['hidden_count'] > 0)
                                            <button
                                                type="button"
                                                class="calendar-more-button"
                                                @click="openDay('{{ $day['date_key'] }}')"
                                            >
                                                +{{ $day['hidden_count'] }} more
                                            </button>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{--
            Day modal:
            Opens when the user clicks a day count or overflow button.
            It shows all events scheduled for one date.
        --}}
        <div
            x-cloak
            x-show="dayModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
            style="display: none;"
        >
            <div class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm" @click="closeDayModal()"></div>

            <div
                class="calendar-modal-panel relative z-10 flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden"
                x-transition:enter="ease-out duration-250"
                x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="ease-in duration-180"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-3 scale-[0.98]"
            >
                <div class="flex items-start justify-between gap-4 border-b border-slate-200/70 px-6 py-5 dark:border-white/10 sm:px-7">
                    <div>
                        <p class="app-section-title">Day queue</p>
                        <h3 class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white" x-text="activeDayLabel()"></h3>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            <span x-text="activeDayCount()"></span> scheduled maintenance task(s)
                        </p>
                    </div>

                    <button type="button" class="app-icon-button" @click="closeDayModal()" aria-label="Close day view">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M6 6l8 8M14 6l-8 8" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </button>
                </div>

                <div class="max-h-[70vh] overflow-y-auto px-6 py-6 sm:px-7">
                    @foreach ($days as $day)
                        <div
                            x-cloak
                            x-show="selectedDayKey === '{{ $day['date_key'] }}'"
                            class="space-y-3"
                            style="display: none;"
                        >
                            @forelse ($day['events'] as $event)
                                <x-calendar-event :event="$event" variant="list" />
                            @empty
                                <div class="calendar-empty-slot min-h-[140px] rounded-[24px]">
                                    No scheduled tasks for this day.
                                </div>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div
            x-cloak
            x-show="eventModalOpen && activeEvent"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6"
            style="display: none;"
        >
            <div class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm" @click="closeEventModal()"></div>

            <div
                class="calendar-modal-panel relative z-10 flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden"
                x-transition:enter="ease-out duration-250"
                x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="ease-in duration-180"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-3 scale-[0.98]"
            >
                <div class="border-b border-slate-200/70 px-6 py-5 dark:border-white/10 sm:px-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="app-section-title">{{ $isDepartmentHead ? 'Task details' : 'My assignment' }}</p>
                            <h3 class="mt-2 font-display text-2xl font-semibold text-slate-950 dark:text-white" x-text="currentModalTitle()"></h3>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                Scheduled for <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="activeEvent ? `${activeEvent.date_label} at ${activeEvent.time_label}` : ''"></span>
                            </p>
                        </div>

                        <button type="button" class="app-icon-button" @click="closeEventModal()" aria-label="Close event view">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                                <path d="M6 6l8 8M14 6l-8 8" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] ring-1 ring-inset"
                            :class="badgeClass('priority', activeEvent ? activeEvent.priority : '')"
                            x-text="activeEvent ? activeEvent.priority_label : ''"
                        ></span>
                        <span
                            class="inline-flex items-center rounded-full px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] ring-1 ring-inset"
                            :class="badgeClass('status', activeEvent ? activeEvent.status : '')"
                            x-text="activeEvent ? activeEvent.status_label : ''"
                        ></span>
                        <span
                            x-show="activeEvent && activeEvent.is_overdue"
                            class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20"
                        >
                            Overdue
                        </span>
                    </div>
                </div>

                <div class="max-h-[72vh] space-y-6 overflow-y-auto px-6 py-6 sm:px-7">
                    <section class="grid gap-4 sm:grid-cols-2">
                        <div class="calendar-detail-tile">
                            <p class="app-section-title">Assigned user</p>
                            <p class="mt-2 text-base font-semibold text-slate-950 dark:text-white" x-text="activeEvent ? activeEvent.assigned_user_name : ''"></p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" x-text="activeEvent ? (activeEvent.assigned_user_email || 'No email available') : ''"></p>
                        </div>

                        <div class="calendar-detail-tile">
                            <p class="app-section-title">Created by</p>
                            <p class="mt-2 text-base font-semibold text-slate-950 dark:text-white" x-text="activeEvent ? activeEvent.created_by_name : ''"></p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Maintenance task owner
                            </p>
                        </div>
                    </section>

                    <section class="calendar-detail-tile">
                        <p class="app-section-title">Description</p>
                        <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600 dark:text-slate-300" x-text="activeEvent ? activeEvent.description : ''"></p>
                    </section>

                    <section
                        x-show="activeEvent && activeEvent.can_update_status"
                        class="calendar-detail-tile"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="app-section-title">Quick actions</p>
                                <h4 class="mt-2 font-display text-xl font-semibold text-slate-950 dark:text-white">Update from the calendar</h4>
                            </div>

                            <a
                                class="app-button-secondary px-4 py-3"
                                :href="activeEvent ? activeEvent.detail_url : '#'"
                            >
                                Open full task
                            </a>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <form
                                x-show="activeEvent && activeEvent.can_quick_start"
                                method="POST"
                                :action="activeEvent ? activeEvent.status_update_url : ''"
                            >
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ \App\Models\MaintenanceTask::STATUS_IN_PROGRESS }}">
                                <input type="hidden" name="redirect_to" value="{{ $currentUri }}">

                                <button type="submit" class="app-button-primary w-full">
                                    Start task
                                </button>
                            </form>

                            <form
                                x-show="activeEvent && activeEvent.can_quick_complete"
                                method="POST"
                                :action="activeEvent ? activeEvent.status_update_url : ''"
                            >
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ \App\Models\MaintenanceTask::STATUS_COMPLETED }}">
                                <input type="hidden" name="redirect_to" value="{{ $currentUri }}">

                                <button type="submit" class="app-button-primary w-full bg-emerald-500 hover:bg-emerald-600 focus:ring-emerald-500/20">
                                    Mark completed
                                </button>
                            </form>
                        </div>
                    </section>

                    <section
                        x-show="activeEvent && ! activeEvent.can_update_status"
                        class="calendar-detail-tile"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="app-section-title">Task access</p>
                                <p class="mt-2 text-sm leading-7 text-slate-500 dark:text-slate-400">
                                    Open the full maintenance record to review its history, assignment details, and detailed status flow.
                                </p>
                            </div>

                            <a
                                class="app-button-secondary px-4 py-3"
                                :href="activeEvent ? activeEvent.detail_url : '#'"
                            >
                                Open task
                            </a>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
