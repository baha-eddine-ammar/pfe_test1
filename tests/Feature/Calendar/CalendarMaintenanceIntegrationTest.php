<?php

namespace Tests\Feature\Calendar;

use App\Models\MaintenanceTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarMaintenanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_head_sees_all_maintenance_tasks_in_calendar(): void
    {
        $departmentHead = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $staffA = User::factory()->create();
        $staffB = User::factory()->create();

        MaintenanceTask::query()->create([
            'server_room' => 'SRV Alpha',
            'maintenance_date' => now()->startOfMonth()->addDays(3)->setTime(9, 30),
            'fix_description' => 'Replace airflow sensor bank A.',
            'priority' => MaintenanceTask::PRIORITY_HIGH,
            'status' => MaintenanceTask::STATUS_ASSIGNED,
            'assigned_to_user_id' => $staffA->id,
            'created_by_user_id' => $departmentHead->id,
        ]);

        MaintenanceTask::query()->create([
            'server_room' => 'SRV Beta',
            'maintenance_date' => now()->startOfMonth()->addDays(8)->setTime(14, 0),
            'fix_description' => 'Inspect rack cooling loop.',
            'priority' => MaintenanceTask::PRIORITY_MEDIUM,
            'status' => MaintenanceTask::STATUS_PENDING,
            'assigned_to_user_id' => $staffB->id,
            'created_by_user_id' => $departmentHead->id,
        ]);

        $this->actingAs($departmentHead)
            ->get(route('calendar.index', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSeeText('Team maintenance calendar')
            ->assertSeeText('SRV Alpha')
            ->assertSeeText('SRV Beta');
    }

    public function test_staff_calendar_only_shows_assigned_tasks(): void
    {
        $departmentHead = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $staffA = User::factory()->create();
        $staffB = User::factory()->create();

        MaintenanceTask::query()->create([
            'server_room' => 'Visible Room',
            'maintenance_date' => now()->startOfMonth()->addDays(4)->setTime(11, 0),
            'fix_description' => 'Visible to staff A on the calendar.',
            'priority' => MaintenanceTask::PRIORITY_URGENT,
            'status' => MaintenanceTask::STATUS_ASSIGNED,
            'assigned_to_user_id' => $staffA->id,
            'created_by_user_id' => $departmentHead->id,
        ]);

        MaintenanceTask::query()->create([
            'server_room' => 'Hidden Room',
            'maintenance_date' => now()->startOfMonth()->addDays(5)->setTime(13, 0),
            'fix_description' => 'Should not appear in staff A schedule.',
            'priority' => MaintenanceTask::PRIORITY_LOW,
            'status' => MaintenanceTask::STATUS_PENDING,
            'assigned_to_user_id' => $staffB->id,
            'created_by_user_id' => $departmentHead->id,
        ]);

        $this->actingAs($staffA)
            ->get(route('calendar.index', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSeeText('Personal maintenance schedule')
            ->assertSeeText('Visible Room')
            ->assertDontSeeText('Hidden Room');
    }

    public function test_calendar_filters_priority_and_status(): void
    {
        $departmentHead = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $staff = User::factory()->create();

        MaintenanceTask::query()->create([
            'server_room' => 'Urgent Match',
            'maintenance_date' => now()->startOfMonth()->addDays(7)->setTime(10, 0),
            'fix_description' => 'Urgent and in progress.',
            'priority' => MaintenanceTask::PRIORITY_URGENT,
            'status' => MaintenanceTask::STATUS_IN_PROGRESS,
            'assigned_to_user_id' => $staff->id,
            'created_by_user_id' => $departmentHead->id,
        ]);

        MaintenanceTask::query()->create([
            'server_room' => 'Filtered Out',
            'maintenance_date' => now()->startOfMonth()->addDays(7)->setTime(15, 0),
            'fix_description' => 'Different priority and status.',
            'priority' => MaintenanceTask::PRIORITY_LOW,
            'status' => MaintenanceTask::STATUS_PENDING,
            'assigned_to_user_id' => $staff->id,
            'created_by_user_id' => $departmentHead->id,
        ]);

        $this->actingAs($departmentHead)
            ->get(route('calendar.index', [
                'month' => now()->format('Y-m'),
                'priority' => MaintenanceTask::PRIORITY_URGENT,
                'status' => MaintenanceTask::STATUS_IN_PROGRESS,
            ]))
            ->assertOk()
            ->assertSeeText('Urgent Match')
            ->assertDontSeeText('Filtered Out');
    }
}
