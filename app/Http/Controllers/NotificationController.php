<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This controller displays and updates the in-app notification center.
|
| Why this file exists:
| Notifications are stored in the database, and users need a way to list them
| and mark them as read individually or in bulk.
|
| When this file is used:
| - GET /notifications
| - PATCH /notifications/{userNotification}/read
| - PATCH /notifications/read-all
|
| FILES TO READ (IN ORDER):
| 1. app/Services/NotificationService.php
| 2. app/Models/UserNotification.php
| 3. app/Http/Controllers/NotificationController.php
| 4. resources/views/layouts/topbar.blade.php
| 5. resources/views/notifications/index.blade.php
*/

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    // Loads the current user's notifications page.
    // Data flow:
    // Database -> userNotifications relationship -> Blade view
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->userNotifications()
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            // unreadCount powers the unread summary shown in the page header.
            'unreadCount' => $request->user()
                ->userNotifications()
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    // Marks one notification as read, but only if it belongs to the current user.
    public function markAsRead(Request $request, UserNotification $userNotification): RedirectResponse
    {
        abort_unless($userNotification->user_id === $request->user()->id, 404);

        if ($userNotification->read_at === null) {
            $userNotification->forceFill([
                'read_at' => now(),
            ])->save();
        }

        return $this->redirectToSafeTarget($request);
    }

    // Marks every unread notification belonging to the current user as read.
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->userNotifications()
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return $this->redirectToSafeTarget($request);
    }

    // Prevents unsafe redirects by accepting only internal relative paths.
    protected function redirectToSafeTarget(Request $request): RedirectResponse
    {
        $target = $request->string('redirect_to')->trim()->toString();

        if ($target !== '' && str_starts_with($target, '/') && ! str_starts_with($target, '//')) {
            return redirect($target);
        }

        return back();
    }
}
