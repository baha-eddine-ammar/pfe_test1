<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceTaskRequest;
use App\Http\Requests\UpdateMaintenanceTaskRequest;
use App\Http\Requests\UpdateMaintenanceTaskStatusRequest;
use App\Models\MaintenanceTask;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaintenanceTaskController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MaintenanceTask::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(MaintenanceTask::priorityOptions())],
            'status' => ['nullable', Rule::in(MaintenanceTask::statusOptions())],
            'assigned_to_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['staff', 'it_staff'])
                        ->where('status', 'approved');
                }),
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $tasksQuery = MaintenanceTask::query()
            ->with(['createdByUser', 'assignedToUser'])
            ->latest('maintenance_date');

        if ($request->user()->isStaff()) {
            $tasksQuery->where('assigned_to_user_id', $request->user()->id);
        } elseif (! empty($validated['assigned_to_user_id'])) {
            $tasksQuery->where('assigned_to_user_id', $validated['assigned_to_user_id']);
        }

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);

            $tasksQuery->where(function ($query) use ($search) {
                $query->where('server_room', 'like', '%' . $search . '%')
                    ->orWhere('fix_description', 'like', '%' . $search . '%');
            });
        }

        if (! empty($validated['priority'])) {
            $tasksQuery->where('priority', $validated['priority']);
        }

        if (! empty($validated['status'])) {
            $tasksQuery->where('status', $validated['status']);
        }

        if (! empty($validated['date_from'])) {
            $tasksQuery->whereDate('maintenance_date', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $tasksQuery->whereDate('maintenance_date', '<=', $validated['date_to']);
        }

        $tasks = $tasksQuery->get();

        return view('maintenance.index', [
            'tasks' => $tasks,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'priority' => $validated['priority'] ?? '',
                'status' => $validated['status'] ?? '',
                'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? '',
                'date_from' => $validated['date_from'] ?? '',
                'date_to' => $validated['date_to'] ?? '',
            ],
            'priorityOptions' => MaintenanceTask::priorityOptions(),
            'statusOptions' => MaintenanceTask::statusOptions(),
            'itStaffUsers' => $request->user()->isDepartmentHead()
                ? $this->approvedItStaffUsers()
                : collect(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', MaintenanceTask::class);

        return view('maintenance.create', [
            'itStaffUsers' => $this->approvedItStaffUsers(),
            'priorityOptions' => MaintenanceTask::priorityOptions(),
            'statusOptions' => MaintenanceTask::statusOptions(),
        ]);
    }

    public function store(StoreMaintenanceTaskRequest $request, NotificationService $notificationService): RedirectResponse
    {
        $validated = $request->validated();

        $maintenanceTask = DB::transaction(function () use ($request, $validated) {
            $maintenanceTask = MaintenanceTask::create([
                'server_room' => trim($validated['server_room']),
                'maintenance_date' => $validated['maintenance_date'],
                'fix_description' => trim($validated['fix_description']),
                'priority' => $validated['priority'],
                'status' => $validated['status'],
                'assigned_to_user_id' => $validated['assigned_to_user_id'],
                'created_by_user_id' => $request->user()->id,
            ]);

            $maintenanceTask->load('assignedToUser');

            $this->logHistory(
                $maintenanceTask,
                $request->user()->id,
                'Task created',
                'Maintenance task created and assigned to ' . $maintenanceTask->assignedToUser->name . '.',
                null,
                $maintenanceTask->status
            );

            return $maintenanceTask;
        });

        if ($maintenanceTask->assignedToUser) {
            $notificationService->notifyUser(
                $maintenanceTask->assignedToUser,
                'maintenance.assigned',
                'New maintenance task assigned',
                $maintenanceTask->server_room . ' on ' . $maintenanceTask->maintenance_date->format('d M Y H:i'),
                route('maintenance.show', $maintenanceTask, false),
                ['maintenance_task_id' => $maintenanceTask->id]
            );
        }

        return redirect()
            ->route('maintenance.show', $maintenanceTask)
            ->with('success', 'Maintenance task created successfully.');
    }

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

    public function edit(MaintenanceTask $maintenanceTask): View
    {
        $this->authorize('update', $maintenanceTask);

        return view('maintenance.edit', [
            'maintenanceTask' => $maintenanceTask,
            'itStaffUsers' => $this->approvedItStaffUsers(),
            'priorityOptions' => MaintenanceTask::priorityOptions(),
            'statusOptions' => MaintenanceTask::statusOptions(),
        ]);
    }

    public function update(UpdateMaintenanceTaskRequest $request, MaintenanceTask $maintenanceTask): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $maintenanceTask) {
            $oldStatus = $maintenanceTask->status;
            $oldAssignedToUserId = $maintenanceTask->assigned_to_user_id;
            $oldMaintenanceDate = $maintenanceTask->maintenance_date;
            $oldPriority = $maintenanceTask->priority;

            $maintenanceTask->update([
                'server_room' => trim($validated['server_room']),
                'maintenance_date' => $validated['maintenance_date'],
                'fix_description' => trim($validated['fix_description']),
                'priority' => $validated['priority'],
                'status' => $validated['status'],
                'assigned_to_user_id' => $validated['assigned_to_user_id'],
            ]);

            $maintenanceTask->load('assignedToUser');

            $this->logHistory(
                $maintenanceTask,
                $request->user()->id,
                'Task updated',
                'Maintenance task details were updated.',
                $oldStatus,
                $maintenanceTask->status
            );

            if ((int) $oldAssignedToUserId !== (int) $maintenanceTask->assigned_to_user_id) {
                $this->logHistory(
                    $maintenanceTask,
                    $request->user()->id,
                    'Assignment updated',
                    'Task was reassigned to ' . $maintenanceTask->assignedToUser->name . '.',
                    null,
                    null
                );
            }

            if ((string) $oldMaintenanceDate !== (string) $maintenanceTask->maintenance_date) {
                $this->logHistory(
                    $maintenanceTask,
                    $request->user()->id,
                    'Schedule updated',
                    'Maintenance date changed to ' . $maintenanceTask->maintenance_date->format('d M Y H:i') . '.',
                    null,
                    null
                );
            }

            if ($oldPriority !== $maintenanceTask->priority) {
                $this->logHistory(
                    $maintenanceTask,
                    $request->user()->id,
                    'Priority updated',
                    'Priority changed from ' . $oldPriority . ' to ' . $maintenanceTask->priority . '.',
                    null,
                    null
                );
            }

            if ($oldStatus !== $maintenanceTask->status) {
                $this->logHistory(
                    $maintenanceTask,
                    $request->user()->id,
                    'Status updated',
                    'Status changed from ' . $oldStatus . ' to ' . $maintenanceTask->status . '.',
                    $oldStatus,
                    $maintenanceTask->status
                );
            }
        });

        return redirect()
            ->route('maintenance.show', $maintenanceTask)
            ->with('success', 'Maintenance task updated successfully.');
    }

    public function updateStatus(UpdateMaintenanceTaskStatusRequest $request, MaintenanceTask $maintenanceTask): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $maintenanceTask) {
            $oldStatus = $maintenanceTask->status;

            $maintenanceTask->update([
                'status' => $validated['status'],
            ]);

            $this->logHistory(
                $maintenanceTask,
                $request->user()->id,
                'Status updated',
                'Status changed from ' . $oldStatus . ' to ' . $maintenanceTask->status . '.',
                $oldStatus,
                $maintenanceTask->status
            );
        });

        return redirect()
            ->route('maintenance.show', $maintenanceTask)
            ->with('success', 'Maintenance task status updated successfully.');
    }

    protected function approvedItStaffUsers()
    {
        return User::query()
            ->whereIn('role', ['staff', 'it_staff'])
            ->where('status', 'approved')
            ->orderBy('name')
            ->get();
    }

    protected function logHistory(
        MaintenanceTask $maintenanceTask,
        ?int $actorId,
        string $action,
        ?string $description = null,
        ?string $oldStatus = null,
        ?string $newStatus = null
    ): void {
        $maintenanceTask->histories()->create([
            'actor_id' => $actorId,
            'action' => $action,
            'description' => $description,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'created_at' => now(),
        ]);
    }
}
