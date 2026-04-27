{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| Shared sidebar navigation for authenticated pages.
|
| Why this file exists:
| It centralizes the navigation menu so all pages use the same route links,
| icons, and role-based visibility.
|
| When this file is used:
| Inside resources/views/layouts/app.blade.php on every authenticated page.
|--------------------------------------------------------------------------
--}}
@php
    // Current user decides whether the administration group should appear.
    $user = auth()->user();
    $isApprovedDepartmentHead = $user?->isDepartmentHead() && $user?->hasApprovedStatus();

    // Menu definition used to render grouped navigation links.
    $menuGroups = [
        [
            'title' => 'Menu',
            'items' => [
                ['label' => 'Dashboard', 'route' => route('dashboard'), 'pattern' => 'dashboard', 'icon' => 'dashboard', 'soon' => false],
                ['label' => 'Server Section', 'route' => route('servers.index'), 'pattern' => 'servers.*', 'icon' => 'server', 'soon' => false],
                ['label' => 'Maintenance', 'route' => route('maintenance.index'), 'pattern' => 'maintenance.*', 'icon' => 'tool', 'soon' => false],
                ['label' => 'Reports', 'route' => route('reports.index'), 'pattern' => 'reports.*', 'icon' => 'chart', 'soon' => false],
                ['label' => 'Calendar', 'route' => route('calendar.index'), 'pattern' => 'calendar.*', 'icon' => 'calendar', 'soon' => false],
                ['label' => 'Chat', 'route' => route('chat.index'), 'pattern' => 'chat.*', 'icon' => 'chat', 'soon' => false],
                ['label' => 'AI Chat', 'route' => route('ai-chat.index'), 'pattern' => 'ai-chat.*', 'icon' => 'spark', 'soon' => false],
            ],
        ],
        [
            'title' => 'Knowledge',
            'items' => [
                ['label' => 'Problems', 'route' => route('problems.index'), 'pattern' => 'problems.*', 'icon' => 'circle-alert', 'soon' => false],
                ['label' => 'Solutions', 'route' => route('solutions.index'), 'pattern' => 'solutions.*', 'icon' => 'check', 'soon' => false],
            ],
        ],
    ];

    if ($isApprovedDepartmentHead) {
        $menuGroups[] = [
            'title' => 'Administration',
            'items' => [
                ['label' => 'Admin', 'route' => route('admin.index'), 'pattern' => 'admin.index', 'icon' => 'cog', 'soon' => false],
                ['label' => 'Users', 'route' => route('admin.users.index'), 'pattern' => 'admin.users.*', 'icon' => 'users', 'soon' => false],
            ],
        ];
    }

    // SVG icon library used by the menu items below.
    $iconMap = [
        'dashboard' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <rect x="3" y="3" width="6" height="6" rx="1.8"></rect>
    <rect x="11" y="3" width="6" height="10" rx="1.8"></rect>
    <rect x="3" y="11" width="6" height="6" rx="1.8"></rect>
    <rect x="11" y="15" width="6" height="2" rx="1"></rect>
</svg>
SVG,
        'pulse' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <path d="M2.5 10h3l1.5-4 3 8 2-4h5.5" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
SVG,
        'server' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <rect x="3" y="4" width="14" height="5" rx="1.8"></rect>
    <rect x="3" y="11" width="14" height="5" rx="1.8"></rect>
    <path d="M6 6.5h.01M6 13.5h.01"></path>
</svg>
SVG,
        'bell' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <path d="M5.5 8a4.5 4.5 0 119 0v2.5l1.5 2.5h-12l1.5-2.5V8z" stroke-linejoin="round"></path>
    <path d="M8.5 15.5a1.5 1.5 0 003 0" stroke-linecap="round"></path>
</svg>
SVG,
        'shield' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <path d="M10 2.5l5.5 3v4c0 3.5-2.3 5.9-5.5 7-3.2-1.1-5.5-3.5-5.5-7v-4l5.5-3z" stroke-linejoin="round"></path>
</svg>
SVG,
        'tool' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <path d="M12.8 4.2a2.6 2.6 0 003.6 3.6l-7.8 7.8-3.2.7.7-3.2 7.8-7.8z" stroke-linejoin="round"></path>
</svg>
SVG,
        'chart' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <path d="M4 16V9M10 16V5M16 16v-7" stroke-linecap="round"></path>
</svg>
SVG,
        'calendar' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <rect x="3" y="4.5" width="14" height="12" rx="2"></rect>
    <path d="M6.5 2.8v3.4M13.5 2.8v3.4M3 8h14" stroke-linecap="round"></path>
</svg>
SVG,
        'chat' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <path d="M5.5 13.5L3 16v-2.8A5.5 5.5 0 018.5 7.7h3A5.5 5.5 0 0117 13.2v.3" stroke-linejoin="round"></path>
</svg>
SVG,
        'spark' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <path d="M10 2.8l1.3 3.4 3.4 1.3-3.4 1.3L10 12.2 8.7 8.8 5.3 7.5l3.4-1.3L10 2.8z" stroke-linejoin="round"></path>
    <path d="M15.2 12.8l.8 2 .8.8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2z" stroke-linejoin="round"></path>
</svg>
SVG,
        'circle-alert' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <circle cx="10" cy="10" r="6.5"></circle>
    <path d="M10 6.8v3.8" stroke-linecap="round"></path>
    <path d="M10 13h.01"></path>
</svg>
SVG,
        'check' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <circle cx="10" cy="10" r="6.5"></circle>
    <path d="M7.2 10.1l2 2 3.8-4.2" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
SVG,
        'cog' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <circle cx="10" cy="10" r="2.4"></circle>
    <path d="M10 2.8v1.6M10 15.6v1.6M15.6 10h1.6M2.8 10h1.6M14.6 5.4l1.1-1.1M4.3 15.7l1.1-1.1M14.6 14.6l1.1 1.1M4.3 4.3l1.1 1.1" stroke-linecap="round"></path>
</svg>
SVG,
        'users' => <<<'SVG'
<svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
    <circle cx="7" cy="7" r="2.3"></circle>
    <circle cx="13.5" cy="8.2" r="1.8"></circle>
    <path d="M3.8 15a3.5 3.5 0 016.4 0M11.8 15a2.9 2.9 0 015.2 0" stroke-linecap="round"></path>
</svg>
SVG,
    ];
@endphp

<div
    x-cloak
    x-show="!$store.sidebar.isDesktop() && $store.sidebar.open"
    class="fixed inset-0 z-40 bg-gray-900/40 backdrop-blur-sm lg:hidden"
    @click="$store.sidebar.toggle()"
    style="display: none;"
></div>

<aside
    class="app-sidebar custom-scrollbar fixed inset-y-0 left-0 z-50 flex w-[290px] -translate-x-full flex-col overflow-y-auto px-5 py-6 transition-transform duration-300 ease-in-out"
    :class="$store.sidebar.open ? 'translate-x-0' : '-translate-x-full'"
>
    {{--
        Brand header:
        project name + mobile close button
    --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-500 text-white shadow-lg shadow-brand-500/20">
                <x-application-logo class="h-6 w-6 fill-current" />
            </div>
            <div>
                <p class="font-display text-lg font-semibold text-gray-900 dark:text-white">Server Room</p>
                <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-gray-400 dark:text-gray-500">Supervision</p>
            </div>
        </a>

        <button type="button" class="app-icon-button lg:hidden" @click="$store.sidebar.toggle()" aria-label="Close sidebar">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M5 5l10 10M15 5L5 15" stroke-linecap="round"></path>
            </svg>
        </button>
    </div>

    {{--
        Navigation groups:
        routeIs() is used to highlight the current page.
    --}}
    <nav class="mt-10 space-y-8">
        @foreach ($menuGroups as $group)
            <section>
                <p class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-[0.28em] text-gray-400 dark:text-gray-500">
                    {{ $group['title'] }}
                </p>
                <div class="space-y-1.5">
                    @foreach ($group['items'] as $item)
                        @php
                            $active = $item['pattern'] ? request()->routeIs($item['pattern']) : false;
                        @endphp
                        @if ($item['soon'])
                            <div class="menu-item menu-item-inactive justify-between opacity-70">
                                <span class="flex items-center gap-3">
                                    <span class="text-current">{!! $iconMap[$item['icon']] !!}</span>
                                    <span>{{ $item['label'] }}</span>
                                </span>
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.24em] text-gray-400 dark:bg-white/[0.04] dark:text-gray-500">
                                    Soon
                                </span>
                            </div>
                        @else
                            <a href="{{ $item['route'] }}" class="menu-item {{ $active ? 'menu-item-active' : 'menu-item-inactive' }}" @click="$store.sidebar.closeOnMobile()">
                                <span class="text-current">{!! $iconMap[$item['icon']] !!}</span>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    </nav>

    {{--
        Access summary:
        shows the logged-in user, approval state, and department.
    --}}
    <div class="app-card mt-auto px-4 py-5">
        <p class="app-section-title">Current access</p>
        <div class="mt-4 flex items-center justify-between gap-4">
            <div>
                <p class="font-display text-lg font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $user->isDepartmentHead() ? 'Department Head' : 'Staff' }}
                </p>
            </div>

            <span class="app-pill {{ $user->hasApprovedStatus() ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' }}">
                {{ ucfirst($user->statusLabel()) }}
            </span>
        </div>

        <div class="mt-5 rounded-2xl bg-gray-50 px-4 py-4 dark:bg-white/[0.03]">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Department</p>
            <p class="mt-1 font-display text-lg font-semibold text-gray-900 dark:text-white">{{ $user->department }}</p>
        </div>
    </div>
</aside>
