{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| Full notification inbox page.
|
| Data source:
| Notifications are loaded by NotificationController@index from the current
| user's userNotifications relationship.
|--------------------------------------------------------------------------
--}}
<x-app-layout>
    {{--
        Inbox header:
        explains what kinds of system activity can appear here.
    --}}
    <section class="mx-auto max-w-6xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="app-section-title">Inbox</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">Notifications</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-gray-500 dark:text-gray-400">
                    Real activity updates from reports, maintenance, approvals, and problem submissions.
                </p>
            </div>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="redirect_to" value="{{ route('notifications.index', [], false) }}">
                    <button type="submit" class="app-button-secondary px-5 py-3">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>
    </section>

    {{--
        Notification list:
        each row shows title, body, metadata badges, and actions.
    --}}
    <section class="mx-auto max-w-6xl">
        <div class="app-card px-6 py-6 sm:px-7">
            @if ($notifications->isEmpty())
                <div class="rounded-3xl border border-dashed border-gray-200 px-6 py-12 text-center dark:border-gray-800">
                    <p class="font-display text-2xl font-semibold text-gray-900 dark:text-white">No notifications yet</p>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Once the system records new activity, it will appear here.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($notifications as $notification)
                        @php
                            // Notification type determines the colored dot shown on the left.
                            $toneClasses = match ($notification->type) {
                                'maintenance.assigned' => 'bg-amber-400',
                                'chat.mentioned' => 'bg-violet-500',
                                'report.generated' => 'bg-sky-400',
                                'alert.critical' => 'bg-rose-500',
                                'user.approved' => 'bg-emerald-400',
                                'user.rejected' => 'bg-rose-400',
                                default => 'bg-brand-500',
                            };
                        @endphp

                        <div class="rounded-3xl border border-gray-100 px-5 py-5 transition dark:border-gray-800 {{ $notification->read_at ? 'bg-white dark:bg-gray-950/40' : 'bg-brand-50/60 dark:bg-brand-500/5' }}">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex items-start gap-4">
                                    <span class="mt-1 inline-flex h-3 w-3 rounded-full {{ $toneClasses }}"></span>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <p class="font-display text-xl font-semibold text-gray-900 dark:text-white">{{ $notification->title }}</p>
                                            @if ($notification->read_at === null)
                                                <span class="app-pill bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">Unread</span>
                                            @endif
                                        </div>
                                        @if ($notification->body)
                                            <p class="mt-2 text-sm leading-7 text-gray-500 dark:text-gray-400">{{ $notification->body }}</p>
                                        @endif
                                        @if ($notification->type === 'maintenance.assigned' && is_array($notification->data))
                                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                                @if (! empty($notification->data['task_id']))
                                                    <span class="app-pill bg-slate-100 text-slate-700 dark:bg-white/[0.06] dark:text-slate-300">
                                                        Task #{{ $notification->data['task_id'] }}
                                                    </span>
                                                @endif
                                                @if (! empty($notification->data['sender_name']))
                                                    <span class="app-pill bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                                                        {{ $notification->data['sender_name'] }}
                                                    </span>
                                                @endif
                                                @if (! empty($notification->data['priority']))
                                                    <span class="app-pill bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                                        {{ str($notification->data['priority'])->title() }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                        @if ($notification->type === 'chat.mentioned' && is_array($notification->data))
                                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                                @if (! empty($notification->data['sender_name']))
                                                    <span class="app-pill bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300">
                                                        {{ $notification->data['sender_name'] }}
                                                    </span>
                                                @endif
                                                @if (! empty($notification->data['sender_handle']))
                                                    <span class="app-pill bg-slate-100 text-slate-700 dark:bg-white/[0.06] dark:text-slate-300">
                                                        {{ '@'.$notification->data['sender_handle'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    {{--
                                        "Open" uses the URL stored with the notification.
                                        "Mark read" updates only the current user's notification row.
                                    --}}
                                    @if ($notification->url)
                                        <a href="{{ $notification->url }}" class="app-button-secondary px-4 py-2">
                                            Open
                                        </a>
                                    @endif

                                    @if ($notification->read_at === null)
                                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="redirect_to" value="{{ route('notifications.index', [], false) }}">
                                            <button type="submit" class="app-button-secondary px-4 py-2">
                                                Mark read
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
