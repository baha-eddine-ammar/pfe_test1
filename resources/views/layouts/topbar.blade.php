@php
    $user = auth()->user();
    $routeName = request()->route()?->getName() ?? 'dashboard';

    $pageMap = [
        'dashboard' => ['eyebrow' => 'Workspace', 'title' => 'Dashboard'],
        'reports.index' => ['eyebrow' => 'Intelligence', 'title' => 'Reports'],
        'reports.show' => ['eyebrow' => 'Intelligence', 'title' => 'Report Detail'],
        'chat.index' => ['eyebrow' => 'Communication', 'title' => 'Team Chat'],
        'problems.index' => ['eyebrow' => 'Knowledge', 'title' => 'Problems'],
        'problems.create' => ['eyebrow' => 'Knowledge', 'title' => 'Submit Problem'],
        'problems.show' => ['eyebrow' => 'Knowledge', 'title' => 'Problem Details'],
        'solutions.index' => ['eyebrow' => 'Knowledge', 'title' => 'Solutions'],
        'admin.index' => ['eyebrow' => 'Administration', 'title' => 'Admin'],
        'admin.users.index' => ['eyebrow' => 'Administration', 'title' => 'Users'],
        'profile.edit' => ['eyebrow' => 'Settings', 'title' => 'Profile'],
    ];

    $pageData = $pageMap[$routeName] ?? ['eyebrow' => 'Workspace', 'title' => 'Overview'];
    $roleLabel = $user->role === 'department_head' ? 'Department Head' : 'IT Staff';
    $initials = collect(explode(' ', $user->name))
        ->filter()
        ->map(fn (string $part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

<header class="sticky top-0 z-30 px-4 pt-4 sm:px-6 lg:px-8">
    <div class="app-topbar rounded-2xl px-4 py-3 sm:px-5 lg:px-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center">
            <div class="flex items-center gap-3 xl:min-w-[14rem]">
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

            <div class="flex-1">
                <label class="relative block">
                    <span class="sr-only">Search</span>
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M17.5 17.5l-3.5-3.5" stroke-linecap="round"></path>
                        <circle cx="8.8" cy="8.8" r="5.3"></circle>
                    </svg>
                    <input type="search" class="app-search pr-20" placeholder="Search or type command...">
                    <span class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-semibold tracking-[0.2em] text-gray-400 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-500 sm:inline-flex">
                        Ctrl K
                    </span>
                </label>
            </div>

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

                <button type="button" class="app-icon-button relative" aria-label="Notifications">
                    <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-red-400"></span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M5.5 8a4.5 4.5 0 119 0v2.5l1.5 2.5h-12l1.5-2.5V8z" stroke-linejoin="round"></path>
                        <path d="M8.5 15.5a1.5 1.5 0 003 0" stroke-linecap="round"></path>
                    </svg>
                </button>

                <div class="relative" @click.outside="profileOpen = false">
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
                                {{ $user->role === 'department_head' && ! $user->is_approved ? 'Pending department approval' : 'Verified workspace access' }}
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
