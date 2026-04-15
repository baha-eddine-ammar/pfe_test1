{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| Shared top navigation bar for authenticated pages.
|
| What it shows:
| - current page title
| - theme toggle
| - notification dropdown
| - user/profile dropdown
|
| Data source:
| Most values come from the authenticated user plus route name and
| userNotifications relationship queries inside this file.
|--------------------------------------------------------------------------
--}}
@php
    // Current user drives role labels, initials, and notification queries.
    $user = auth()->user();
    $routeName = request()->route()?->getName() ?? 'dashboard';

    // Route name -> human-friendly page title map.
    $pageMap = [
        'dashboard' => ['eyebrow' => 'Workspace', 'title' => 'Dashboard'],
        'reports.index' => ['eyebrow' => 'Intelligence', 'title' => 'Reports'],
        'reports.show' => ['eyebrow' => 'Intelligence', 'title' => 'Report Detail'],
        'calendar.index' => ['eyebrow' => 'Planning', 'title' => 'Calendar'],
        'chat.index' => ['eyebrow' => 'Communication', 'title' => 'Team Chat'],
        'ai-chat.index' => ['eyebrow' => 'Communication', 'title' => 'AI Chat'],
        'servers.index' => ['eyebrow' => 'Infrastructure', 'title' => 'Servers'],
        'servers.create' => ['eyebrow' => 'Infrastructure', 'title' => 'Add Server'],
        'servers.show' => ['eyebrow' => 'Infrastructure', 'title' => 'Server Detail'],
        'problems.index' => ['eyebrow' => 'Knowledge', 'title' => 'Problems'],
        'problems.create' => ['eyebrow' => 'Knowledge', 'title' => 'Submit Problem'],
        'problems.show' => ['eyebrow' => 'Knowledge', 'title' => 'Problem Details'],
        'solutions.index' => ['eyebrow' => 'Knowledge', 'title' => 'Solutions'],
        'maintenance.index' => ['eyebrow' => 'Operations', 'title' => 'Maintenance'],
        'maintenance.create' => ['eyebrow' => 'Operations', 'title' => 'Create Maintenance Task'],
        'maintenance.show' => ['eyebrow' => 'Operations', 'title' => 'Maintenance Task'],
        'maintenance.edit' => ['eyebrow' => 'Operations', 'title' => 'Edit Maintenance Task'],
        'admin.index' => ['eyebrow' => 'Administration', 'title' => 'Admin'],
        'admin.users.index' => ['eyebrow' => 'Administration', 'title' => 'Users'],
        'notifications.index' => ['eyebrow' => 'Workspace', 'title' => 'Notifications'],
        'profile.edit' => ['eyebrow' => 'Settings', 'title' => 'Profile'],
    ];

    $pageData = $pageMap[$routeName] ?? ['eyebrow' => 'Workspace', 'title' => 'Overview'];
    $roleLabel = $user->isDepartmentHead() ? 'Department Head' : 'Staff';
    // Latest notification list used by the dropdown panel.
    $latestNotifications = $user->userNotifications()->latest('created_at')->limit(6)->get();
    $unreadNotificationsCount = $user->userNotifications()->whereNull('read_at')->count();
    // Initials are shown inside the profile avatar circle.
    $initials = collect(explode(' ', $user->name))
        ->filter()
        ->map(fn (string $part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<header class="sticky top-0 z-30 px-4 pt-4 sm:px-6 lg:px-8">
    <div class="app-topbar rounded-2xl px-4 py-3 sm:px-5 lg:px-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            {{--
                Left side:
                sidebar toggle buttons + dynamic page title
            --}}
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" class="app-icon-button lg:hidden" @click="$store.sidebar.toggle()" aria-label="Open sidebar">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M3.5 5.5h13M3.5 10h13M3.5 14.5h13" stroke-linecap="round"></path>
                    </svg>
                </button>

                <button type="button" class="app-icon-button hidden lg:inline-flex" @click="$store.sidebar.toggle()" aria-label="Toggle sidebar">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M3.5 5.5h13M3.5 10h10M3.5 14.5h13" stroke-linecap="round"></path>
                    </svg>
                </button>

                <div>
                    <p class="app-section-title">{{ $pageData['eyebrow'] }}</p>
                    <h1 class="mt-1 font-display text-2xl font-semibold text-gray-900 dark:text-white">{{ $pageData['title'] }}</h1>
                </div>
            </div>

            {{--
                Right side:
                theme toggle, notifications dropdown, and profile menu
            --}}
            <div class="ml-auto flex items-center gap-2 sm:gap-3">
                <button type="button" class="app-icon-button" @click="$store.theme.toggle()" aria-label="Toggle theme">
                    <svg x-cloak x-show="$store.theme.theme !== 'dark'" class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M17.2 10.8A7.2 7.2 0 119.2 2.8a5.7 5.7 0 008 8z"></path>
                    </svg>
                    <svg x-cloak x-show="$store.theme.theme === 'dark'" class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                        <circle cx="10" cy="10" r="3"></circle>
                        <path d="M10 2v2M10 16v2M18 10h-2M4 10H2M15.6 4.4l-1.4 1.4M5.8 14.2l-1.4 1.4M15.6 15.6l-1.4-1.4M5.8 5.8L4.4 4.4" stroke-linecap="round"></path>
                    </svg>
                </button>

                <div class="relative" @click.outside="notificationOpen = false">
                    {{--
                        Notification dropdown:
                        Reads recent rows from user_notifications and links the
                        user to the correct destination when clicked.
                    --}}
                    <button type="button" class="app-icon-button relative" aria-label="Notifications" @click="notificationOpen = !notificationOpen; profileOpen = false">
                        @if ($unreadNotificationsCount > 0)
                            <span class="absolute right-2 top-2 inline-flex min-w-[1.15rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                                {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
                            </span>
                        @endif
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M5.5 8a4.5 4.5 0 119 0v2.5l1.5 2.5h-12l1.5-2.5V8z" stroke-linejoin="round"></path>
                            <path d="M8.5 15.5a1.5 1.5 0 003 0" stroke-linecap="round"></path>
                        </svg>
                    </button>

                    <div
                        x-cloak
                        x-show="notificationOpen"
                        x-transition.origin.top.right
                        class="app-card absolute right-0 mt-3 w-[22rem] px-4 py-4"
                        style="display: none;"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-display text-lg font-semibold text-gray-900 dark:text-white">Notifications</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $unreadNotificationsCount }} unread</p>
                            </div>

                            @if ($unreadNotificationsCount > 0)
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="redirect_to" value="{{ request()->getPathInfo() }}">
                                    <button type="submit" class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-500 transition hover:text-brand-600">
                                        Mark all
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse ($latestNotifications as $notification)
                                @php
                                    $toneClass = match ($notification->type) {
                                        'maintenance.assigned' => 'bg-amber-400',
                                        'chat.mentioned' => 'bg-violet-500',
                                        'report.generated' => 'bg-sky-400',
                                        'alert.critical' => 'bg-rose-500',
                                        'user.approved' => 'bg-emerald-400',
                                        'user.rejected' => 'bg-rose-400',
                                        default => 'bg-brand-500',
                                    };
                                @endphp

                                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="redirect_to" value="{{ $notification->url ?? request()->getPathInfo() }}">
                                    <button type="submit" class="w-full rounded-2xl border px-4 py-3 text-left transition hover:-translate-y-0.5 hover:shadow-sm {{ $notification->read_at ? 'border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]' : 'border-brand-100 bg-brand-50/70 dark:border-brand-900/30 dark:bg-brand-500/10' }}">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-1 inline-flex h-2.5 w-2.5 rounded-full {{ $toneClass }}"></span>
                                            <div class="min-w-0">
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="truncate font-medium text-gray-900 dark:text-white">{{ $notification->title }}</p>
                                                    <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">
                                                        {{ $notification->created_at->diffForHumans() }}
                                                    </span>
                                                </div>
                                                @if ($notification->body)
                                                    <p class="mt-1 line-clamp-2 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $notification->body }}</p>
                                                @endif
                                                @if ($notification->type === 'maintenance.assigned' && is_array($notification->data))
                                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">
                                                        @if (! empty($notification->data['task_id']))
                                                            <span>Task #{{ $notification->data['task_id'] }}</span>
                                                        @endif
                                                        @if (! empty($notification->data['sender_name']))
                                                            <span>By {{ $notification->data['sender_name'] }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                                @if ($notification->type === 'chat.mentioned' && is_array($notification->data))
                                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">
                                                        @if (! empty($notification->data['sender_name']))
                                                            <span>By {{ $notification->data['sender_name'] }}</span>
                                                        @endif
                                                        @if (! empty($notification->data['sender_handle']))
                                                            <span>{{ '@'.$notification->data['sender_handle'] }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </button>
                                </form>
                            @empty
                                <div class="rounded-2xl border border-dashed border-gray-200 px-4 py-8 text-center dark:border-gray-800">
                                    <p class="font-medium text-gray-900 dark:text-white">No notifications yet</p>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">System activity will appear here.</p>
                                </div>
                            @endforelse
                        </div>

                        <a href="{{ route('notifications.index') }}" class="mt-4 inline-flex text-sm font-medium text-brand-500 transition hover:text-brand-600">
                            View all notifications
                        </a>
                    </div>
                </div>

                <div class="relative" @click.outside="profileOpen = false">
                    {{--
                        Profile dropdown:
                        Shows user identity, access state, profile link, and logout.
                    --}}
                    <button
                        type="button"
                        class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white px-2 py-2 pl-3 text-left transition hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-white/[0.03]"
                        @click="profileOpen = !profileOpen"
                        aria-label="Open user menu"
                    >
                        <div class="hidden sm:block">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-gray-400 dark:text-gray-500">{{ $roleLabel }}</p>
                            <p class="font-display text-sm font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-sky-400 text-sm font-semibold text-white">
                            {{ $initials }}
                        </div>
                    </button>

                    <div
                        x-cloak
                        x-show="profileOpen"
                        x-transition.origin.top.right
                        class="app-card absolute right-0 mt-3 w-64 px-4 py-4"
                        style="display: none;"
                    >
                        <p class="font-display text-lg font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>

                        <div class="mt-4 rounded-2xl bg-gray-50 px-4 py-3 dark:bg-white/[0.03]">
                            <p class="app-section-title">Access</p>
                            <p class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $user->hasApprovedStatus() ? 'Verified workspace access' : 'Your account is pending approval.' }}
                            </p>
                        </div>

                        <div class="mt-4 space-y-2">
                            <a href="{{ route('profile.edit') }}" class="menu-item menu-item-inactive border border-gray-200 dark:border-gray-800">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <path d="M10 2.8l5.5 3v4c0 3.5-2.3 5.9-5.5 7-3.2-1.1-5.5-3.5-5.5-7v-4l5.5-3z" stroke-linejoin="round"></path>
                                </svg>
                                <span>Profile settings</span>
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="menu-item menu-item-inactive w-full border border-gray-200 dark:border-gray-800">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                                        <path d="M7 5.5H4.5A1.5 1.5 0 003 7v6a1.5 1.5 0 001.5 1.5H7" stroke-linecap="round"></path>
                                        <path d="M11 6.5l3.5 3.5L11 13.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <path d="M14.5 10H7" stroke-linecap="round"></path>
                                    </svg>
                                    <span>Log out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
