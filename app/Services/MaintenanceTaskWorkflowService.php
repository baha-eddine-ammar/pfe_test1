<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This service contains the write-side business logic for maintenance tasks.
| It is responsible for creating, updating, deleting, tracking history, and
| triggering notifications after important maintenance actions.
|
| Why this file exists:
| Controllers should stay focused on HTTP concerns. This service keeps the
| domain workflow in one reusable place.
|
| When this file is used:
| - When a maintenance task is created
| - When task details are updated
| - When status changes
| - When assignment notifications must be sent
|
| FILES TO READ (IN ORDER):
| 1. app/Http/Controllers/MaintenanceTaskController.php
| 2. app/Services/MaintenanceTaskWorkflowService.php
| 3. app/Services/NotificationService.php
| 4. app/Services/TelegramService.php
| 5. app/Models/MaintenanceTask.php
| 6. app/Models/MaintenanceTaskHistory.php
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. The controller validates and authorizes the action.
| 2. This service performs the database transaction.
| 3. History rows are created to explain what changed.
| 4. Notifications are sent after assignment.
*/

namespace App\Services;

use App\Events\MaintenanceTaskChanged;
use App\Models\MaintenanceTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MaintenanceTaskWorkflowService
{
    // Two services are injected because task assignment communicates through
    // both the in-app notification system and Telegram when available.
    public function __construct(
        protected NotificationService $notificationService,
        protected TelegramService $telegramService
    ) {
    }

    /*
    |----------------------------------------------------------------------
    | Create a new maintenance task
    |----------------------------------------------------------------------
    | Flow:
    | validated form data -> DB transaction -> task row -> history rows
    | -> notifications
    */
    public function createTask(array $attributes, User $actor): MaintenanceTask
    {
        $maintenanceTask = DB::transaction(function () use ($attributes, $actor): MaintenanceTask {
            // $attributes comes from StoreMaintenanceTaskRequest.
            // $actor is the authenticated department head creating the task.
            $maintenanceTask = MaintenanceTask::create([
                'server_room' => trim($attributes['server_room']),
                'maintenance_date' => $attributes['maintenance_date'],
                'fix_description' => trim($attributes['fix_description']),
                'priority' => $attributes['priority'],
                'status' => MaintenanceTask::STATUS_PENDING,
                'assigned_to_user_id' => $attributes['assigned_to_user_id'],
                'created_by_user_id' => $actor->id,
            ]);

            $maintenanceTask->loadMissing(['createdByUser', 'assignedToUser']);

            $this->recordHistory(
                $maintenanceTask,
                $actor,
                'Task created',
                sprintf(
                    'Maintenance task #%d was created for %s.',
                    $maintenanceTask->id,
                    $maintenanceTask->server_room
                )
            );

            // Separate history rows make the timeline easier to understand later:
            // one for creation and one for assignment.
            $this->recordHistory(
                $maintenanceTask,
                $actor,
                'Task assigned',
                sprintf(
                    'Task assigned to %s for %s.',
                    $maintenanceTask->assignedToUser->name,
                    $maintenanceTask->maintenance_date->format('d M Y H:i')
                )
            );

            return $maintenanceTask;
        });

        $this->dispatchAssignmentNotifications($maintenanceTask, $actor);
        $this->broadcastChange('created', $maintenanceTask, [
            $maintenanceTask->assigned_to_user_id,
        ]);

        return $maintenanceTask;
    }

    // Updates the task itself, then records history for reassignment and/or
    // status changes when those fields actually changed.
    public function updateTask(MaintenanceTask $maintenanceTask, array $attributes, User $actor): MaintenanceTask
    {
        $previousAssignedUserId = $maintenanceTask->assigned_to_user_id;
        $previousStatus = $maintenanceTask->status;

        $updatedTask = DB::transaction(function () use ($maintenanceTask, $attributes, $actor, $previousAssignedUserId, $previousStatus): MaintenanceTask {
            $maintenanceTask->update([
                'server_room' => trim($attributes['server_room']),
                'maintenance_date' => $attributes['maintenance_date'],
                'fix_description' => trim($attributes['fix_description']),
                'priority' => $attributes['priority'],
                'status' => $attributes['status'],
                'assigned_to_user_id' => $attributes['assigned_to_user_id'],
            ]);

            $maintenanceTask->loadMissing(['createdByUser', 'assignedToUser']);

            $this->recordHistory(
                $maintenanceTask,
                $actor,
                'Task updated',
                'Maintenance task details were updated.'
            );

            if ((int) $previousAssignedUserId !== (int) $maintenanceTask->assigned_to_user_id) {
                $this->recordHistory(
                    $maintenanceTask,
                    $actor,
                    'Assignment updated',
                    sprintf('Task reassigned to %s.', $maintenanceTask->assignedToUser->name)
                );
            }

            if ($previousStatus !== $maintenanceTask->status) {
                $this->recordHistory(
                    $maintenanceTask,
                    $actor,
                    'Status updated',
                    sprintf(
                        'Status changed from %s to %s.',
                        $this->humanizeStatus($previousStatus),
                        $this->humanizeStatus($maintenanceTask->status)
                    ),
                    $previousStatus,
                    $maintenanceTask->status
                );
            }

            return $maintenanceTask->fresh(['createdByUser', 'assignedToUser']);
        });

        if ((int) $previousAssignedUserId !== (int) $updatedTask->assigned_to_user_id) {
            $this->dispatchAssignmentNotifications($updatedTask, $actor);
        }

        $this->broadcastChange('updated', $updatedTask, [
            $previousAssignedUserId,
            $updatedTask->assigned_to_user_id,
        ]);

        return $updatedTask;
    }

    // Changes only the task status and optionally records a note from the actor.
    public function updateStatus(MaintenanceTask $maintenanceTask, string $status, User $actor, ?string $note = null): MaintenanceTask
    {
        $trimmedNote = $note !== null ? trim($note) : null;
        $previousStatus = $maintenanceTask->status;

        $updatedTask = DB::transaction(function () use ($maintenanceTask, $status, $actor, $trimmedNote, $previousStatus): MaintenanceTask {
            $maintenanceTask->update([
                'status' => $status,
            ]);

            if ($previousStatus !== $status) {
                $description = sprintf(
                    'Status changed from %s to %s.',
                    $this->humanizeStatus($previousStatus),
                    $this->humanizeStatus($status)
                );

                if ($trimmedNote !== null && $trimmedNote !== '') {
                    $description .= ' Note: '.$trimmedNote;
                }

                $this->recordHistory(
                    $maintenanceTask,
                    $actor,
                    'Status updated',
                    $description,
                    $previousStatus,
                    $status
                );
            } elseif ($trimmedNote !== null && $trimmedNote !== '') {
                $this->recordHistory(
                    $maintenanceTask,
                    $actor,
                    'Task note added',
                    $trimmedNote,
                    $status,
                    $status
                );
            }

            return $maintenanceTask->fresh(['createdByUser', 'assignedToUser']);
        });

        $this->broadcastChange('status_updated', $updatedTask, [
            $updatedTask->assigned_to_user_id,
        ]);

        return $updatedTask;
    }

    // Deleting currently has no extra side effects besides removing the row.
    // A transaction is still used so future workflow steps can be added safely.
    public function deleteTask(MaintenanceTask $maintenanceTask, ?User $actor = null): void
    {
        $maintenanceTask->loadMissing(['createdByUser', 'assignedToUser']);
        $payload = $this->taskPayload($maintenanceTask);
        $userIds = [
            $maintenanceTask->assigned_to_user_id,
            $actor?->id,
        ];

        DB::transaction(function () use ($maintenanceTask): void {
            $maintenanceTask->delete();
        });

        MaintenanceTaskChanged::dispatch('deleted', $payload, $userIds);
    }

    // Sends notifications only when the task has a real assignee.
    protected function dispatchAssignmentNotifications(MaintenanceTask $maintenanceTask, User $actor): void
    {
        $maintenanceTask->loadMissing(['createdByUser', 'assignedToUser']);

        if (! $maintenanceTask->assignedToUser) {
            return;
        }

        $this->notificationService->notifyMaintenanceTaskAssigned(
            $maintenanceTask->assignedToUser,
            $maintenanceTask,
            $actor
        );

        $this->telegramService->sendMaintenanceTaskAssigned(
            $maintenanceTask->assignedToUser,
            $maintenanceTask,
            $actor
        );
    }

    // History rows create a readable audit trail for the maintenance detail page.
    protected function recordHistory(
        MaintenanceTask $maintenanceTask,
        User $actor,
        string $action,
        ?string $description = null,
        ?string $oldStatus = null,
        ?string $newStatus = null
    ): void {
        $maintenanceTask->histories()->create([
            'actor_id' => $actor->id,
            'action' => $action,
            'description' => $description,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'created_at' => now(),
        ]);
    }

    // Turns stored values like "in_progress" into beginner-friendly text.
    protected function humanizeStatus(?string $status): string
    {
        if ($status === null || $status === '') {
            return 'Unknown';
        }

        return str($status)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    protected function broadcastChange(string $action, MaintenanceTask $maintenanceTask, array $userIds = []): void
    {
        $maintenanceTask->loadMissing(['createdByUser', 'assignedToUser']);

        MaintenanceTaskChanged::dispatch(
            $action,
            $this->taskPayload($maintenanceTask),
            $userIds
        );
    }

    protected function taskPayload(MaintenanceTask $maintenanceTask): array
    {
        return [
            'id' => $maintenanceTask->id,
            'server_room' => $maintenanceTask->server_room,
            'priority' => $maintenanceTask->priority,
            'status' => $maintenanceTask->status,
            'assigned_to_user_id' => $maintenanceTask->assigned_to_user_id,
            'created_by_user_id' => $maintenanceTask->created_by_user_id,
            'maintenance_date' => $maintenanceTask->maintenance_date?->toIso8601String(),
            'updated_at' => $maintenanceTask->updated_at?->toIso8601String(),
        ];
    }
}
