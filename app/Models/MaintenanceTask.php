<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This model represents one maintenance task stored in the database.
| It also defines task statuses, priorities, query scopes, helper labels,
| and relationships to users/history rows.
|
| Why this file exists:
| Laravel models are the main bridge between PHP code and database tables.
| This one encapsulates the maintenance domain rules that are reused across
| controllers, services, policies, and views.
|
| When this file is used:
| Whenever the app creates, reads, filters, updates, or displays maintenance tasks.
|
| FILES TO READ (IN ORDER):
| 1. database/migrations/*maintenance*.php
| 2. app/Models/MaintenanceTask.php
| 3. app/Policies/MaintenanceTaskPolicy.php
| 4. app/Services/MaintenanceTaskWorkflowService.php
| 5. app/Http/Controllers/MaintenanceTaskController.php
| 6. resources/views/maintenance/*
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. Read the migration to see the columns.
| 2. Read this model to see scopes, labels, and relationships.
| 3. Read the policy to see who can access what.
| 4. Read the service/controller to see how tasks move through the app.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MaintenanceTask extends Model
{
    // Priority values are reused across validation, filtering, and badges.
    public const PRIORITY_URGENT = 'urgent';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_LOW = 'low';

    // Status values describe the lifecycle of a maintenance task.
    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'server_room',
        'maintenance_date',
        'fix_description',
        'priority',
        'status',
        'assigned_to_user_id',
        'created_by_user_id',
    ];

    protected $appends = [
        'is_overdue',
    ];

    protected function casts(): array
    {
        return [
            // This makes maintenance_date a Carbon object automatically.
            'maintenance_date' => 'datetime',
        ];
    }

    // Used by validation rules and form dropdowns.
    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_URGENT,
            self::PRIORITY_HIGH,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_LOW,
        ];
    }

    // Used by validation rules and status selectors.
    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ASSIGNED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    // Eager loads the two user relationships most listing pages need.
    public function scopeWithListingRelations(Builder $query): Builder
    {
        return $query->with(['createdByUser', 'assignedToUser']);
    }

    // Role-based visibility:
    // department heads can see all tasks, staff see only their own assignments.
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isDepartmentHead()) {
            return $query;
        }

        return $query->where('assigned_to_user_id', $user->id);
    }

    // Applies search/filter form values from the maintenance workspace UI.
    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        if (($search = trim((string) ($filters['search'] ?? ''))) !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('server_room', 'like', '%'.$search.'%')
                    ->orWhere('fix_description', 'like', '%'.$search.'%');
            });
        }

        if (($priority = $filters['priority'] ?? null) !== null && $priority !== '') {
            $query->where('priority', $priority);
        }

        if (($status = $filters['status'] ?? null) !== null && $status !== '') {
            $query->where('status', $status);
        }

        if (($assignedTo = $filters['assigned_to_user_id'] ?? null) !== null && $assignedTo !== '') {
            $query->where('assigned_to_user_id', $assignedTo);
        }

        if (($dateFrom = $filters['date_from'] ?? null) !== null && $dateFrom !== '') {
            $query->whereDate('maintenance_date', '>=', $dateFrom);
        }

        if (($dateTo = $filters['date_to'] ?? null) !== null && $dateTo !== '') {
            $query->whereDate('maintenance_date', '<=', $dateTo);
        }

        if (($overdue = $filters['overdue'] ?? null) === '1') {
            $query
                ->where('maintenance_date', '<', now())
                ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
        }

        return $query;
    }

    // Computed property appended to JSON/array output.
    // It tells the UI whether the task has passed its planned date without
    // reaching a terminal status.
    protected function isOverdue(): Attribute
    {
        return Attribute::get(fn (): bool => $this->maintenance_date !== null
            && $this->maintenance_date->isPast()
            && ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true));
    }

    // Human-friendly label for badge text.
    public function priorityLabel(): string
    {
        return Str::headline($this->priority);
    }

    // Human-friendly label for badge text.
    public function statusLabel(): string
    {
        return Str::of($this->status)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    // Staff users are intentionally limited so they can only move tasks through
    // sensible next states. Department heads can choose any status.
    public function allowedStatusTransitionsFor(User $user): array
    {
        if ($user->isDepartmentHead()) {
            return self::statusOptions();
        }

        return match ($this->status) {
            self::STATUS_PENDING => [self::STATUS_PENDING, self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_ASSIGNED => [self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS => [self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED => [self::STATUS_COMPLETED],
            self::STATUS_CANCELLED => [self::STATUS_CANCELLED],
            default => self::statusOptions(),
        };
    }

    // Used by quick-action buttons in the UI.
    public function canQuickStart(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_ASSIGNED], true);
    }

    // Used by quick-action buttons in the UI.
    public function canQuickComplete(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    // Relationship:
    // many maintenance tasks can be created by one user.
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Relationship:
    // many maintenance tasks can be assigned to one user.
    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    // Relationship:
    // one maintenance task has many history records.
    public function histories(): HasMany
    {
        return $this->hasMany(MaintenanceTaskHistory::class)->latest('created_at');
    }
}
