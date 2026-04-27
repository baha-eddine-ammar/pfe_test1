<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This controller manages the maintenance module UI and request flow.
| It does not contain all business logic itself; instead, it validates and
| authorizes requests, then delegates task workflow operations to a service.
|
| Why this file exists:
| The maintenance feature needs one controller to serve the list page, detail
| page, create/edit flows, and status updates.
|
| When this file is used:
| - When users open /maintenance
| - When department heads create/edit/delete tasks
| - When staff update the status of their assigned tasks
|
| FILES TO READ (IN ORDER):
| 1. routes/web.php
| 2. app/Policies/MaintenanceTaskPolicy.php
| 3. app/Http/Controllers/MaintenanceTaskController.php
| 4. app/Http/Requests/StoreMaintenanceTaskRequest.php
| 5. app/Http/Requests/UpdateMaintenanceTaskRequest.php
| 6. app/Http/Requests/UpdateMaintenanceTaskStatusRequest.php
| 7. app/Services/MaintenanceTaskWorkflowService.php
| 8. app/Models/MaintenanceTask.php
| 9. resources/views/maintenance/*
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. The route points to this controller.
| 2. The policy decides whether the current user is allowed to act.
| 3. The request class validates incoming data.
| 4. This controller prepares filters/data for the UI.
| 5. The workflow service performs database writes and notifications.
| 6. The Blade views display the tasks and forms.
*/

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceTaskRequest;
use App\Http\Requests\UpdateMaintenanceTaskRequest;
use App\Http\Requests\UpdateMaintenanceTaskStatusRequest;
use App\Models\MaintenanceTask;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\MaintenanceTaskWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaintenanceTaskController extends Controller
{
    // The workflow service contains the write-side business logic:
    // task creation, assignment, status changes, history, and notifications.
    public function __construct(
        protected MaintenanceTaskWorkflowService $workflowService
    ) {
    }

    /*
    |----------------------------------------------------------------------
    | Maintenance workspace page
    |----------------------------------------------------------------------
    | Flow:
    | Request filters -> authorized query -> stats + task list -> view
    |
    | Important variables:
    | - $filters: validated query-string filters coming from the page form.
    | - $viewer: the currently authenticated user.
    | - $staffUsers: used to build the assign/filter UI for department heads.
    | - $visibleTasksQuery: base query representing only the tasks this user may see.
    | - $tasks: paginated task list shown in the table/cards.
    */



    //index() = show the main list page
    public function index(Request $request): View
    {

        // Laravel authorization system => It checks a Policy : Can this user perform this action? ( inside MaintenanceTaskPolicy.php)
        $this->authorize('viewAny', MaintenanceTask::class);

        // we remove the filter section for now
        // begin
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(MaintenanceTask::priorityOptions())],
            'status' => ['nullable', Rule::in(MaintenanceTask::statusOptions())],
            'assigned_to_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'overdue' => ['nullable', 'in:0,1'],
        ]);
        //end


        // $viewer = get Get the currently logged-in user ( DH / stuff )

        $viewer = $request->user();

        // if the user is DH load  assignable staff users ? else  give empty list
        $staffUsers = $viewer->isDepartmentHead()
            ? $this->assignableUsers()
            : collect();




        // only show task this user is  allowed to see , we save it in $visibleTasksQuery to calculate later Completed tasks ..

        $visibleTasksQuery = MaintenanceTask::query()->visibleTo($viewer);

        // Data flow:
        // Database -> MaintenanceTask scopes -> paginated list -> Blade page

        //Load task + related users together
        $tasks = MaintenanceTask::query()
            ->withListingRelations()
            ->visibleTo($viewer)
            ->applyFilters($filters)
            ->latest('maintenance_date')
            ->paginate($viewer->isDepartmentHead() ? 12 : 9)
            ->withQueryString();

        return view('maintenance.index', [
            'tasks' => $tasks,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'priority' => $filters['priority'] ?? '',
                'status' => $filters['status'] ?? '',
                'assigned_to_user_id' => (string) ($filters['assigned_to_user_id'] ?? ''),
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
                'overdue' => (string) ($filters['overdue'] ?? ''),
            ],
            'priorityOptions' => MaintenanceTask::priorityOptions(),
            'statusOptions' => MaintenanceTask::statusOptions(),
            'itStaffUsers' => $staffUsers,
            'assigneeDirectory' => $staffUsers
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department' => $user->department,
                ])
                ->values(),
            'stats' => $this->buildStats($visibleTasksQuery, $viewer),
        ]);
    }

    // The create page was replaced by an inline creator on the index page.
    // This redirect keeps the old route working without breaking navigation.
    public function create(): RedirectResponse
    {
        $this->authorize('create', MaintenanceTask::class);

        return redirect()
            ->route('maintenance.index')
            ->with('success', 'Use the task creator on the maintenance page to add a new maintenance task.');
    }

    // Validated form data is passed to the workflow service.
    // That service creates the task, records history, and sends notifications.
    public function store(StoreMaintenanceTaskRequest $request): RedirectResponse
    {
        $maintenanceTask = $this->workflowService->createTask(
            $request->validated(),
            $request->user()
        );

        app(AuditLogService::class)->record('maintenance.created', $maintenanceTask, [
            'priority' => $maintenanceTask->priority,
            'status' => $maintenanceTask->status,
            'assigned_to_user_id' => $maintenanceTask->assigned_to_user_id,
        ], $request->user());

        return redirect()
            ->route('maintenance.index')
            ->with('success', "Maintenance task #{$maintenanceTask->id} was created and the assignee has been notified.");
    }

    // Detail page for a single maintenance task.
    // Extra relationships are eager loaded so the view can show creator,
    // assignee, and status history without N+1 queries.
    public function show(MaintenanceTask $maintenanceTask): View
    {
        $this->authorize('view', $maintenanceTask);

        $maintenanceTask->load([
            'createdByUser',
            'assignedToUser',
            'histories.actor',
        ]);

        return view('maintenance.show', [
            'maintenanceTask' => $maintenanceTask,
            'statusOptions' => MaintenanceTask::statusOptions(),
        ]);
    }

    // Department heads use this page to edit an existing task.
    public function edit(MaintenanceTask $maintenanceTask): View
    {
        $this->authorize('update', $maintenanceTask);

        return view('maintenance.edit', [
            'maintenanceTask' => $maintenanceTask,
            'itStaffUsers' => $this->assignableUsers(),
            'priorityOptions' => MaintenanceTask::priorityOptions(),
            'statusOptions' => MaintenanceTask::statusOptions(),
        ]);
    }

    // Updates the main task record.
    // The service decides whether assignment/status history should also be recorded.
    public function update(UpdateMaintenanceTaskRequest $request, MaintenanceTask $maintenanceTask): RedirectResponse
    {
        $this->workflowService->updateTask(
            $maintenanceTask,
            $request->validated(),
            $request->user()
        );

        app(AuditLogService::class)->record('maintenance.updated', $maintenanceTask->fresh(), [
            'priority' => $maintenanceTask->priority,
            'status' => $maintenanceTask->status,
            'assigned_to_user_id' => $maintenanceTask->assigned_to_user_id,
        ], $request->user());

        return $this->redirectToSafeTarget(
            $request,
            route('maintenance.index')
        )->with('success', "Maintenance task #{$maintenanceTask->id} was updated successfully.");
    }

    // Deletes a task after authorization.
    public function destroy(Request $request, MaintenanceTask $maintenanceTask): RedirectResponse
    {
        $this->authorize('delete', $maintenanceTask);

        $taskId = $maintenanceTask->id;
        app(AuditLogService::class)->record('maintenance.deleted', $maintenanceTask, [
            'priority' => $maintenanceTask->priority,
            'status' => $maintenanceTask->status,
        ], $request->user());
        $this->workflowService->deleteTask($maintenanceTask, $request->user());

        return $this->redirectToSafeTarget(
            $request,
            route('maintenance.index')
        )->with('success', "Maintenance task #{$taskId} was deleted.");
    }

    // Staff and department heads use this endpoint to move a task between
    // statuses such as pending, in_progress, or completed.
    public function updateStatus(UpdateMaintenanceTaskStatusRequest $request, MaintenanceTask $maintenanceTask): RedirectResponse
    {
        $validated = $request->validated();

        $this->workflowService->updateStatus(
            $maintenanceTask,
            $validated['status'],
            $request->user(),
            $validated['note'] ?? null
        );

        app(AuditLogService::class)->record('maintenance.status.updated', $maintenanceTask->fresh(), [
            'status' => $validated['status'],
            'note' => $validated['note'] ?? null,
        ], $request->user());

        return $this->redirectToSafeTarget(
            $request,
            route('maintenance.index')
        )->with('success', "Maintenance task #{$maintenanceTask->id} status was updated.");
    }

    // This list populates the assignment and filtering UI.
    // It currently includes every user in the database, ordered alphabetically.
    protected function assignableUsers()
    {
        return User::query()
            ->approved()
            ->staffMembers()
            ->select(['id', 'name', 'email', 'department'])
            ->orderBy('name')
            ->get();
    }

    // Summary cards shown at the top of the maintenance workspace.
    // The same base visible-tasks query is cloned so each metric stays limited
    // to the current user's allowed task set.
    protected function buildStats($visibleTasksQuery, User $viewer): array
    {
        $total = (clone $visibleTasksQuery)->count();
        $overdue = (clone $visibleTasksQuery)
            ->where('maintenance_date', '<', now())
            ->whereNotIn('status', [MaintenanceTask::STATUS_COMPLETED, MaintenanceTask::STATUS_CANCELLED])
            ->count();
        $urgent = (clone $visibleTasksQuery)
            ->where('priority', MaintenanceTask::PRIORITY_URGENT)
            ->count();
        $inProgress = (clone $visibleTasksQuery)
            ->where('status', MaintenanceTask::STATUS_IN_PROGRESS)
            ->count();
        $completedThisWeek = (clone $visibleTasksQuery)
            ->where('status', MaintenanceTask::STATUS_COMPLETED)
            ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        $dueToday = (clone $visibleTasksQuery)
            ->whereDate('maintenance_date', now()->toDateString())
            ->count();

        if ($viewer->isDepartmentHead()) {
            return [
                [
                    'label' => 'All tasks',
                    'value' => $total,
                    'caption' => 'Across your maintenance workspace',
                ],
                [
                    'label' => 'Overdue',
                    'value' => $overdue,
                    'caption' => 'Past due and still unresolved',
                ],
                [
                    'label' => 'Urgent',
                    'value' => $urgent,
                    'caption' => 'Priority queue needing fast response',
                ],
                [
                    'label' => 'Completed this week',
                    'value' => $completedThisWeek,
                    'caption' => 'Tasks closed by the team this week',
                ],
            ];
        }

        return [
            [
                'label' => 'My tasks',
                'value' => $total,
                'caption' => 'Tasks assigned directly to you',
            ],
            [
                'label' => 'Due today',
                'value' => $dueToday,
                'caption' => 'Scheduled for today',
            ],
            [
                'label' => 'In progress',
                'value' => $inProgress,
                'caption' => 'Work currently underway',
            ],
            [
                'label' => 'Overdue',
                'value' => $overdue,
                'caption' => 'Items that need immediate follow-up',
            ],
        ];
    }

    // Allows the UI to send users back to the correct page after form actions.
    // Only relative application paths are accepted to avoid unsafe redirects.
    protected function redirectToSafeTarget(Request $request, string $fallback): RedirectResponse
    {
        $target = $request->string('redirect_to')->trim()->toString();

        if ($target !== '' && str_starts_with($target, '/') && ! str_starts_with($target, '//')) {
            return redirect($target);
        }

        return redirect($fallback);
    }
}
