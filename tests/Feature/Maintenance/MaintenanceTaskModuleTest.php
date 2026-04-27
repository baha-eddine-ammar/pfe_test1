<?php

namespace Tests\Feature\Maintenance;

use App\Events\MaintenanceTaskChanged;
use App\Models\MaintenanceTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MaintenanceTaskModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_head_can_create_task_and_assignment_notifications_are_sent(): void
    {
        config()->set('services.telegram.bot_token', 'telegram-test-token');
        Http::fake();

        $departmentHead = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
            'telegram_chat_id' => '999000111',
        ]);

        $response = $this->actingAs($departmentHead)->post(route('maintenance.store'), [
            'server_room' => 'Server Room Alpha',
            'maintenance_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'fix_description' => 'Replace cooling fan and verify rack airflow stability.',
            'priority' => MaintenanceTask::PRIORITY_URGENT,
            'assigned_to_user_id' => $staff->id,
        ]);

        $task = MaintenanceTask::query()->first();

        $response->assertRedirect(route('maintenance.index'));
        $this->assertNotNull($task);
        $this->assertSame(MaintenanceTask::STATUS_PENDING, $task->status);
        $this->assertDatabaseCount('maintenance_task_histories', 2);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $staff->id,
            'type' => 'maintenance.assigned',
            'title' => 'New maintenance task assigned',
        ]);

        $notification = $staff->userNotifications()->latest('id')->first();

        $this->assertSame($departmentHead->name, $notification->data['sender_name'] ?? null);
        $this->assertSame($task->id, $notification->data['task_id'] ?? null);

        Http::assertSent(function ($request) use ($staff) {
            return $request->url() === 'https://api.telegram.org/bottelegram-test-token/sendMessage'
                && $request['chat_id'] === $staff->telegram_chat_id
                && str_contains($request['text'], 'You have a new maintenance task:')
                && str_contains($request['text'], 'Server Room: Server Room Alpha');
        });
    }

    public function test_staff_only_sees_tasks_assigned_to_them(): void
    {
        $staffA = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $staffB = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $departmentHead = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        MaintenanceTask::query()->create([
            'server_room' => 'Room Visible',
            'maintenance_date' => now()->addDay(),
            'fix_description' => 'Visible to staff A',
            'priority' => MaintenanceTask::PRIORITY_HIGH,
            'status' => MaintenanceTask::STATUS_ASSIGNED,
            'assigned_to_user_id' => $staffA->id,
            'created_by_user_id' => $departmentHead->id,
        ]);

        MaintenanceTask::query()->create([
            'server_room' => 'Room Hidden',
            'maintenance_date' => now()->addDays(2),
            'fix_description' => 'Should only be visible to staff B',
            'priority' => MaintenanceTask::PRIORITY_LOW,
            'status' => MaintenanceTask::STATUS_PENDING,
            'assigned_to_user_id' => $staffB->id,
            'created_by_user_id' => $departmentHead->id,
        ]);

        $this->actingAs($staffA)
            ->get(route('maintenance.index'))
            ->assertOk()
            ->assertSeeText('Room Visible')
            ->assertDontSeeText('Room Hidden');
    }

    public function test_staff_can_update_their_task_status_with_a_note(): void
    {
        $departmentHead = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $task = MaintenanceTask::query()->create([
            'server_room' => 'Room Progress',
            'maintenance_date' => now()->addDay(),
            'fix_description' => 'Investigate UPS alert and update firmware.',
            'priority' => MaintenanceTask::PRIORITY_MEDIUM,
            'status' => MaintenanceTask::STATUS_ASSIGNED,
            'assigned_to_user_id' => $staff->id,
            'created_by_user_id' => $departmentHead->id,
        ]);

        $response = $this->actingAs($staff)->patch(route('maintenance.update-status', $task), [
            'status' => MaintenanceTask::STATUS_IN_PROGRESS,
            'note' => 'Started work and checking the power module now.',
        ]);

        $response->assertRedirect(route('maintenance.index'));
        $this->assertDatabaseHas('maintenance_tasks', [
            'id' => $task->id,
            'status' => MaintenanceTask::STATUS_IN_PROGRESS,
        ]);
        $this->assertDatabaseHas('maintenance_task_histories', [
            'maintenance_task_id' => $task->id,
            'action' => 'Status updated',
            'new_status' => MaintenanceTask::STATUS_IN_PROGRESS,
        ]);
    }

    public function test_status_updates_broadcast_maintenance_realtime_event(): void
    {
        Event::fake([MaintenanceTaskChanged::class]);

        $departmentHead = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $task = MaintenanceTask::query()->create([
            'server_room' => 'Room Realtime',
            'maintenance_date' => now()->addDay(),
            'fix_description' => 'Verify realtime task updates.',
            'priority' => MaintenanceTask::PRIORITY_MEDIUM,
            'status' => MaintenanceTask::STATUS_ASSIGNED,
            'assigned_to_user_id' => $staff->id,
            'created_by_user_id' => $departmentHead->id,
        ]);

        $this->actingAs($staff)->patch(route('maintenance.update-status', $task), [
            'status' => MaintenanceTask::STATUS_IN_PROGRESS,
        ]);

        Event::assertDispatched(MaintenanceTaskChanged::class, function (MaintenanceTaskChanged $event) use ($task, $staff): bool {
            return $event->action === 'status_updated'
                && $event->task['id'] === $task->id
                && in_array($staff->id, $event->userIds, true);
        });
    }

    public function test_department_head_can_delete_a_task(): void
    {
        $departmentHead = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $task = MaintenanceTask::query()->create([
            'server_room' => 'Room Delete',
            'maintenance_date' => now()->addDay(),
            'fix_description' => 'Remove failed drive and replace RAID member.',
            'priority' => MaintenanceTask::PRIORITY_HIGH,
            'status' => MaintenanceTask::STATUS_PENDING,
            'assigned_to_user_id' => $staff->id,
            'created_by_user_id' => $departmentHead->id,
        ]);

        $response = $this->actingAs($departmentHead)->delete(route('maintenance.destroy', $task));

        $response->assertRedirect(route('maintenance.index'));
        $this->assertDatabaseMissing('maintenance_tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_pending_or_non_staff_users_can_not_be_selected_as_assignees(): void
    {
        $departmentHead = User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
        ]);

        $pendingUser = User::factory()->create([
            'role' => 'staff',
            'status' => 'pending',
            'is_approved' => false,
        ]);

        $response = $this->actingAs($departmentHead)
            ->from(route('maintenance.index'))
            ->post(route('maintenance.store'), [
                'server_room' => 'Server Room Alpha',
                'maintenance_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'fix_description' => 'Replace cooling fan and verify rack airflow stability.',
                'priority' => MaintenanceTask::PRIORITY_URGENT,
                'assigned_to_user_id' => $pendingUser->id,
            ]);

        $response->assertRedirect(route('maintenance.index'));
        $response->assertSessionHasErrors('assigned_to_user_id');
        $this->assertDatabaseCount('maintenance_tasks', 0);
    }
}
