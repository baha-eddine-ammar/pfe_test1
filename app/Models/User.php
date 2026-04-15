<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This is the main user model for authentication, roles, and relationships.
| It represents each person who can log into the system.
|
| Why this file exists:
| Almost every feature depends on the current user: login, authorization,
| maintenance assignment, chat, notifications, problems, and solutions.
|
| When this file is used:
| On login, registration, policy checks, relationship queries, and whenever the
| app needs user information.
|
| FILES TO READ (IN ORDER):
| 1. database/migrations/0001_01_01_000000_create_users_table.php
| 2. database/migrations/*users*.php
| 3. app/Models/User.php
| 4. app/Policies/*
| 5. app/Http/Controllers/Auth/*
| 6. features that reference User relationships
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. Read the migrations to learn the stored columns.
| 2. Read this model to understand helper methods and relationships.
| 3. Read policies/middleware to see how role checks are used.
| 4. Read feature controllers that depend on the authenticated user.
*/

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'department',
        'phone_number',
        'role',
        'status',
        'is_approved',
        'password',
        'telegram_chat_id',
        'telegram_link_token',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Carbon object used by Laravel auth/profile features.
            'email_verified_at' => 'datetime',
            'is_approved' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // Role helper used throughout policies, controllers, and views.
    public function isDepartmentHead(): bool
    {
        return $this->role === 'department_head';
    }

    // The project treats both "staff" and legacy "it_staff" as staff-level users.
    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'it_staff'], true);
    }

    // Approval can be stored either in the newer status column or the older
    // boolean flag, so this helper hides that schema history from the rest of the app.
    public function hasApprovedStatus(): bool
    {
        return $this->status === 'approved' || ($this->status === null && $this->is_approved);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where(function (Builder $approvalQuery): void {
            $approvalQuery
                ->where('status', 'approved')
                ->orWhere(function (Builder $legacyQuery): void {
                    $legacyQuery
                        ->whereNull('status')
                        ->where('is_approved', true);
                });
        });
    }

    public function scopeDepartmentHeads(Builder $query): Builder
    {
        return $query->where('role', 'department_head');
    }

    // Human-readable status for badges and labels.
    public function statusLabel(): string
    {
        return $this->status ?? ($this->is_approved ? 'approved' : 'pending');
    }

    // Human-readable role label for UI display.
    public function roleLabel(): string
    {
        return match (true) {
            $this->isDepartmentHead() => 'Department Head',
            $this->isStaff() => 'Staff',
            default => Str::headline((string) $this->role),
        };
    }

    // Converts a full name into 1-2 letters for avatar circles.
    public function initials(): string
    {
        $initials = collect(explode(' ', (string) $this->name))
            ->filter()
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        return $initials !== '' ? $initials : 'U';
    }

    // Creates a safe handle used by the chat mention system.
    // Example: john.doe@example.com -> john.doe
    public function chatHandle(): string
    {
        $localPart = Str::before((string) $this->email, '@');

        $normalized = Str::of($localPart)
            ->lower()
            ->replaceMatches('/[^a-z0-9._-]+/', '-')
            ->trim('-._')
            ->toString();

        if ($normalized !== '') {
            return $normalized;
        }

        $fallback = Str::slug((string) $this->name);

        return $fallback !== '' ? $fallback.'-'.$this->id : 'user-'.$this->id;
    }

    // Relationship:
    // one user can send many chat messages.
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // Relationship:
    // one user can report many problems.
    public function problems(): HasMany
    {
        return $this->hasMany(Problem::class);
    }

    // Relationship:
    // one user can submit many solutions.
    public function solutions(): HasMany
    {
        return $this->hasMany(Solution::class);
    }

    // Relationship:
    // one user (usually a department head) can create many maintenance tasks.
    public function maintenanceTasksCreated(): HasMany
    {
        return $this->hasMany(MaintenanceTask::class, 'created_by_user_id');
    }

    // Relationship:
    // one user can be assigned many maintenance tasks.
    public function maintenanceTasksAssigned(): HasMany
    {
        return $this->hasMany(MaintenanceTask::class, 'assigned_to_user_id');
    }

    // Relationship:
    // one user can appear in many maintenance task history rows as the actor.
    public function maintenanceTaskHistories(): HasMany
    {
        return $this->hasMany(MaintenanceTaskHistory::class, 'actor_id');
    }

    // Relationship:
    // one user can receive many in-app notifications.
    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }
}
