<?php

namespace App\Services;

use App\Models\User;

class NotificationService
{
    public function notifyUser(
        User $user,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        array $data = []
    ): void {
        $user->userNotifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'data' => $data,
        ]);
    }

    public function notifyUsers(
        iterable $users,
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        array $data = []
    ): void {
        $seen = [];

        foreach ($users as $user) {
            if (! $user instanceof User || isset($seen[$user->id])) {
                continue;
            }

            $seen[$user->id] = true;

            $this->notifyUser($user, $type, $title, $body, $url, $data);
        }
    }

    public function notifyApprovedDepartmentHeads(
        string $type,
        string $title,
        ?string $body = null,
        ?string $url = null,
        array $data = []
    ): void {
        $users = User::query()
            ->where('role', 'department_head')
            ->where('status', 'approved')
            ->orderBy('id')
            ->get();

        $this->notifyUsers($users, $type, $title, $body, $url, $data);
    }
}
