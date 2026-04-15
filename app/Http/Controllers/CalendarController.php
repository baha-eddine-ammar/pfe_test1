<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This controller transforms maintenance tasks into a month-based calendar view.
| It acts as the bridge between the maintenance module and the calendar UI.
|
| Why this file exists:
| The calendar should not store duplicated "event" rows. Instead, it reads the
| existing maintenance tasks and maps them into calendar-friendly event data.
|
| When this file is used:
| Whenever a user opens /calendar or changes month/filters in the calendar.
|
| FILES TO READ (IN ORDER):
| 1. routes/web.php
| 2. app/Policies/MaintenanceTaskPolicy.php
| 3. app/Http/Controllers/CalendarController.php
| 4. app/Models/MaintenanceTask.php
| 5. resources/views/calendar/index.blade.php
| 6. resources/views/components/calendar-event.blade.php
| 7. resources/js/calendar-workspace.js
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. A user opens the calendar route.
| 2. This controller checks role-based visibility.
| 3. It loads maintenance tasks for the selected calendar range.
| 4. It maps those tasks into day/event payloads.
| 5. The Blade/JS layer renders the month grid and modals.
*/

namespace App\Http\Controllers;

use App\Models\MaintenanceTask;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CalendarController extends Controller
{
    /*
    |----------------------------------------------------------------------
    | Calendar page
    |----------------------------------------------------------------------
    | Flow:
    | Request -> visible maintenance query -> mapped events -> calendar view
    |
    | Important variables:
    | - $viewer: current logged-in user; controls role-based visibility.
    | - $month: the selected month being displayed.
    | - $calendarStart / $calendarEnd: extra days added so the calendar grid
    |   always begins on Sunday and ends on Saturday.
    | - $visibleTasksQuery: base role-aware query.
    | - $tasks: maintenance tasks that fall inside the visible grid range.
    */
    public function index(Request $request): View
    {
        $viewer = $request->user();

        abort_unless($viewer instanceof User, 403);
        $this->authorize('viewAny', MaintenanceTask::class);

        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'priority' => ['nullable', Rule::in(MaintenanceTask::priorityOptions())],
            'status' => ['nullable', Rule::in(MaintenanceTask::statusOptions())],
        ]);

        $month = $this->resolveMonth($filters['month'] ?? null);
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $calendarStart = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $monthEnd->copy()->endOfWeek(Carbon::SATURDAY);

        $visibleTasksQuery = MaintenanceTask::query()
            ->visibleTo($viewer)
            ->applyFilters($filters);

        // Data flow:
        // Database -> MaintenanceTask model -> event mapping -> calendar view
        $tasks = (clone $visibleTasksQuery)
            ->withListingRelations()
            ->whereBetween('maintenance_date', [
                $calendarStart->copy()->startOfDay(),
                $calendarEnd->copy()->endOfDay(),
            ])
            ->orderBy('maintenance_date')
            ->get();

        $eventsByDate = $tasks
            ->map(fn (MaintenanceTask $task) => $this->mapTaskToEvent($task, $viewer))
            ->groupBy('date_key');

        $weeks = $this->buildWeeks($month, $calendarStart, $calendarEnd, $eventsByDate);
        $days = collect($weeks)->flatten(1)->values();

        return view('calendar.index', [
            'month' => $month,
            'weeks' => $weeks,
            'days' => $days,
            'stats' => $this->buildStats($viewer, $visibleTasksQuery, $monthStart, $monthEnd),
            'filters' => [
                'month' => $month->format('Y-m'),
                'priority' => $filters['priority'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'previousMonth' => $month->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonthNoOverflow()->format('Y-m'),
            'daysPayload' => $days
                ->mapWithKeys(fn (array $day) => [
                    $day['date_key'] => [
                        'date_key' => $day['date_key'],
                        'date_label' => $day['date_label'],
                        'is_today' => $day['is_today'],
                        'events' => $day['events'],
                    ],
                ])
                ->all(),
            'priorityOptions' => MaintenanceTask::priorityOptions(),
            'statusOptions' => MaintenanceTask::statusOptions(),
            'isDepartmentHead' => $viewer->isDepartmentHead(),
        ]);
    }

    // Converts the optional ?month=YYYY-MM query string into a Carbon month.
    protected function resolveMonth(?string $month): Carbon
    {
        if ($month !== null && $month !== '') {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        return now()->startOfMonth();
    }

    // Builds the 7-column calendar grid.
    // Each day contains both the full event list and a trimmed visible list
    // used for "+N more" overflow handling in the UI.
    protected function buildWeeks(
        Carbon $month,
        Carbon $calendarStart,
        Carbon $calendarEnd,
        Collection $eventsByDate
    ): array {
        return collect(CarbonPeriod::create($calendarStart, $calendarEnd))
            ->map(function (Carbon $date) use ($month, $eventsByDate): array {
                $dateKey = $date->toDateString();
                $events = collect($eventsByDate->get($dateKey, []))
                    ->values()
                    ->all();

                return [
                    'date' => $date->copy(),
                    'date_key' => $dateKey,
                    'date_label' => $date->format('D, d M Y'),
                    'is_current_month' => $date->isSameMonth($month),
                    'is_today' => $date->isToday(),
                    'events' => $events,
                    'visible_events' => array_slice($events, 0, 3),
                    'hidden_count' => max(0, count($events) - 3),
                ];
            })
            ->chunk(7)
            ->map(fn (Collection $week) => $week->values()->all())
            ->values()
            ->all();
    }

    // Summary metrics shown above the calendar.
    // They are computed from the same visible task query so admin/staff each
    // see stats only for the tasks they are allowed to view.
    protected function buildStats(
        User $viewer,
        Builder $visibleTasksQuery,
        Carbon $monthStart,
        Carbon $monthEnd
    ): array {
        $scheduledThisMonth = (clone $visibleTasksQuery)
            ->whereBetween('maintenance_date', [
                $monthStart->copy()->startOfDay(),
                $monthEnd->copy()->endOfDay(),
            ])
            ->count();

        $dueToday = (clone $visibleTasksQuery)
            ->whereDate('maintenance_date', now()->toDateString())
            ->count();

        $inProgress = (clone $visibleTasksQuery)
            ->where('status', MaintenanceTask::STATUS_IN_PROGRESS)
            ->count();

        $overdue = (clone $visibleTasksQuery)
            ->where('maintenance_date', '<', now())
            ->whereNotIn('status', [MaintenanceTask::STATUS_COMPLETED, MaintenanceTask::STATUS_CANCELLED])
            ->count();

        $completedThisMonth = (clone $visibleTasksQuery)
            ->where('status', MaintenanceTask::STATUS_COMPLETED)
            ->whereBetween('maintenance_date', [
                $monthStart->copy()->startOfDay(),
                $monthEnd->copy()->endOfDay(),
            ])
            ->count();

        if ($viewer->isDepartmentHead()) {
            return [
                [
                    'label' => 'Scheduled this month',
                    'value' => $scheduledThisMonth,
                    'caption' => 'All maintenance work across the full team calendar.',
                ],
                [
                    'label' => 'Due today',
                    'value' => $dueToday,
                    'caption' => 'Tasks landing in the operational window today.',
                ],
                [
                    'label' => 'In progress',
                    'value' => $inProgress,
                    'caption' => 'Active maintenance currently being executed.',
                ],
                [
                    'label' => 'Overdue',
                    'value' => $overdue,
                    'caption' => 'Scheduled work that has drifted past its target time.',
                ],
            ];
        }

        return [
            [
                'label' => 'My schedule',
                'value' => $scheduledThisMonth,
                'caption' => 'Tasks assigned to you in the selected month.',
            ],
                [
                    'label' => 'Due today',
                    'value' => $dueToday,
                    'caption' => 'Items you should address during today\'s shift.',
                ],
            [
                'label' => 'In progress',
                'value' => $inProgress,
                'caption' => 'Assignments already underway in your queue.',
            ],
            [
                'label' => 'Completed',
                'value' => $completedThisMonth,
                'caption' => 'Tasks you have already closed in this month view.',
            ],
        ];
    }

    // Converts one MaintenanceTask model into the richer event shape used by
    // the calendar page and event modal.
    protected function mapTaskToEvent(MaintenanceTask $task, User $viewer): array
    {
        return [
            'id' => $task->id,
            'title' => $task->server_room,
            'preview' => Str::limit($task->fix_description, 110),
            'description' => $task->fix_description,
            'priority' => $task->priority,
            'priority_label' => $task->priorityLabel(),
            'status' => $task->status,
            'status_label' => $task->statusLabel(),
            'scheduled_at' => $task->maintenance_date?->toIso8601String(),
            'date_key' => $task->maintenance_date?->toDateString(),
            'date_label' => $task->maintenance_date?->format('D, d M Y'),
            'time_label' => $task->maintenance_date?->format('H:i'),
            'assigned_user_name' => $task->assignedToUser?->name ?? 'Unassigned',
            'assigned_user_email' => $task->assignedToUser?->email,
            'created_by_name' => $task->createdByUser?->name ?? 'System',
            'detail_url' => route('maintenance.show', $task),
            'status_update_url' => route('maintenance.update-status', $task),
            'can_update_status' => $viewer->isStaff() && $task->assigned_to_user_id === $viewer->id,
            'can_quick_start' => $viewer->isStaff() && $task->assigned_to_user_id === $viewer->id && $task->canQuickStart(),
            'can_quick_complete' => $viewer->isStaff() && $task->assigned_to_user_id === $viewer->id && $task->canQuickComplete(),
            'is_overdue' => $task->is_overdue,
        ];
    }
}
