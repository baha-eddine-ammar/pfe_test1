<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->userNotifications()
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()
                ->userNotifications()
                ->whereNull('read_at')
                ->count(),
        ]);
    }

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

    protected function redirectToSafeTarget(Request $request): RedirectResponse
    {
        $target = $request->string('redirect_to')->trim()->toString();

        if ($target !== '' && str_starts_with($target, '/')) {
            return redirect($target);
        }

        return back();
    }
}
