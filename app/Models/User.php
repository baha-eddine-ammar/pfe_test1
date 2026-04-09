<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
            'email_verified_at' => 'datetime',
            'is_approved' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isDepartmentHead(): bool
    {
        return $this->role === 'department_head';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'it_staff'], true);
    }

    public function hasApprovedStatus(): bool
    {
        return $this->status === 'approved' || ($this->status === null && $this->is_approved);
    }

    public function statusLabel(): string
    {
        return $this->status ?? ($this->is_approved ? 'approved' : 'pending');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function problems(): HasMany
    {
        return $this->hasMany(Problem::class);
    }

    public function solutions(): HasMany
    {
        return $this->hasMany(Solution::class);
    }

    public function maintenanceTasksCreated(): HasMany
    {
        return $this->hasMany(MaintenanceTask::class, 'created_by_user_id');
    }

    public function maintenanceTasksAssigned(): HasMany
    {
        return $this->hasMany(MaintenanceTask::class, 'assigned_to_user_id');
    }

    public function maintenanceTaskHistories(): HasMany
    {
        return $this->hasMany(MaintenanceTaskHistory::class, 'actor_id');
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }
}
