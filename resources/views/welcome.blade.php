@php
    $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');
    $registerUrl = \Illuminate\Support\Facades\Route::has('register') ? route('register') : url('/register');
    $dashboardUrl = \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/dashboard');

    $featureCards = [
        [
            'title' => 'Environmental Monitoring',
            'description' => 'Track temperature, humidity, airflow, and room conditions from one operational view.',
            'icon' => 'environment',
        ],
        [
            'title' => 'Server Health Tracking',
            'description' => 'Monitor inventory, runtime metrics, and infrastructure status with clear operational context.',
            'icon' => 'server',
        ],
        [
            'title' => 'RFID Access Logs',
            'description' => 'Keep physical access records searchable, reviewable, and tied to accountability workflows.',
            'icon' => 'lock',
        ],
        [
            'title' => 'Alert Management',
            'description' => 'Escalate critical conditions quickly with role-aware notifications and response visibility.',
            'icon' => 'alert',
        ],
        [
            'title' => 'Maintenance Scheduling',
            'description' => 'Assign, track, and complete operational tasks inside a structured maintenance workflow.',
            'icon' => 'calendar',
        ],
        [
            'title' => 'Reports and Analytics',
            'description' => 'Review trends, stored reports, and historical decisions for planning and audit readiness.',
            'icon' => 'report',
        ],
    ];

    $compactStats = [
        ['label' => 'Visibility', 'value' => '24/7', 'note' => 'Continuous operational oversight'],
        ['label' => 'Control', 'value' => 'Role Based', 'note' => 'Access aligned to responsibility'],
        ['label' => 'Traceability', 'value' => 'Audit Ready', 'note' => 'Logs and reports remain searchable'],
        ['label' => 'Response', 'value' => 'Centralized', 'note' => 'Alerts and maintenance in one workflow'],
    ];

    $trustPoints = [
        ['title' => 'Real-time monitoring', 'text' => 'Environmental and operational signals stay visible while teams manage live infrastructure.'],
        ['title' => 'Secure role-based access', 'text' => 'Approval-driven permissions help maintain administrative control across the workspace.'],
        ['title' => 'Searchable logs and reports', 'text' => 'Historical visibility supports reviews, accountability, and informed decision-making.'],
        ['title' => 'Auditability and traceability', 'text' => 'Operational actions, maintenance records, and system events remain easy to follow.'],
    ];

    $renderIcon = static function (string $icon): string {
        return match ($icon) {
            'environment' => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 13.5V6.75A1.75 1.75 0 0 1 6.75 5h10.5A1.75 1.75 0 0 1 19 6.75v6.75"></path><path d="M4 18h16"></path><path d="M8 18v-2.5"></path><path d="M12 18v-4"></path><path d="M16 18v-6"></path></svg>',
            'server' => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="4.5" width="14" height="4.5" rx="1.25"></rect><rect x="5" y="10" width="14" height="4.5" rx="1.25"></rect><rect x="5" y="15.5" width="14" height="4.5" rx="1.25"></rect><path d="M8 7h.01M8 12.25h.01M8 17.75h.01"></path></svg>',
            'lock' => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 10V7.5A5 5 0 0 1 17 7.5V10"></path><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M12 14v2.5"></path></svg>',
            'alert' => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 4v8"></path><path d="M12 16h.01"></path><path d="M10.29 3.86L2.82 16.5A1.5 1.5 0 0 0 4.11 18.75h15.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.42 0Z"></path></svg>',
            'calendar' => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 4.75v3.5"></path><path d="M16 4.75v3.5"></path><rect x="4.5" y="6.75" width="15" height="12.75" rx="2"></rect><path d="M4.5 10.25h15"></path><path d="M9 14.25h2.5"></path><path d="M13.5 14.25H15"></path></svg>',
            default => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 18.5h14"></path><path d="M7.5 15V9.5"></path><path d="M12 15V5.5"></path><path d="M16.5 15v-3.5"></path></svg>',
        };
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Server Room Supervision') }}</title>

        <script>
            (function () {
                localStorage.removeItem('server-room-theme');
                localStorage.removeItem('theme');

                const savedTheme = localStorage.getItem('tailadmin-theme-v1');
                const theme = savedTheme || 'light';

                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                    document.documentElement.style.colorScheme = 'dark';
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.style.colorScheme = 'light';
                }
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-50 text-gray-800 antialiased dark:bg-gray-900 dark:text-gray-100">
        <div class="app-shell relative overflow-hidden">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-[560px] bg-[radial-gradient(circle_at_top_left,rgba(70,95,255,0.22),transparent_32%),radial-gradient(circle_at_85%_15%,rgba(14,165,233,0.16),transparent_24%)] dark:bg-[radial-gradient(circle_at_top_left,rgba(70,95,255,0.24),transparent_28%),radial-gradient(circle_at_85%_15%,rgba(14,165,233,0.12),transparent_22%)]"></div>
            <div class="pointer-events-none absolute inset-x-0 top-24 mx-auto hidden h-px max-w-7xl bg-gradient-to-r from-transparent via-brand-200/70 to-transparent dark:via-brand-500/20 lg:block"></div>

            <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/85 backdrop-blur-xl dark:border-gray-800 dark:bg-gray-900/85">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <a href="/" class="flex min-w-0 items-center gap-3">
                        <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-500/20">
                            <x-application-logo class="h-6 w-6 fill-current" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate font-display text-lg font-semibold tracking-tight text-gray-900 dark:text-white">Server Room Supervision</p>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="app-pill bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">Enterprise Ready</span>
                                <span class="hidden text-xs font-medium text-gray-400 dark:text-gray-500 sm:inline">IT Operations Platform</span>
                            </div>
                        </div>
                    </a>

                    <div class="flex items-center gap-3">
                        @auth
                            <a href="{{ $dashboardUrl }}" class="app-button-secondary px-4 py-2.5 sm:px-5">Dashboard</a>
                        @else
                            <a href="{{ $loginUrl }}" class="app-button-secondary px-4 py-2.5 sm:px-5">Login</a>
                            <a href="{{ $registerUrl }}" class="app-button-primary px-4 py-2.5 sm:px-5">Register</a>
                        @endauth
                    </div>
                </div>
            </header>

            <main>
                <section class="px-4 pb-14 pt-10 sm:px-6 lg:px-8 lg:pb-18 lg:pt-16">
                    <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[minmax(0,1.05fr)_460px] lg:items-center xl:gap-12">
                        <div class="max-w-2xl">
                            <span class="app-pill bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                                Critical Infrastructure Monitoring
                            </span>
                            <h1 class="mt-6 font-display text-4xl font-semibold tracking-tight text-gray-900 sm:text-5xl lg:text-[60px] lg:leading-[1.02] dark:text-white">
                                Enterprise-grade supervision for secure, reliable server room operations.
                            </h1>
                            <p class="mt-5 max-w-xl text-base leading-7 text-gray-500 dark:text-gray-400 sm:text-lg">
                                Monitor environmental conditions, server health, access activity, alerts, maintenance, and reporting from one operational workspace designed for uptime, traceability, and control.
                            </p>

                            <div class="mt-8 flex flex-wrap items-center gap-3">
                                @auth
                                    <a href="{{ $dashboardUrl }}" class="app-button-primary">Open Dashboard</a>
                                @else
                                    <a href="{{ $loginUrl }}" class="app-button-primary">Login</a>
                                    <a href="{{ $registerUrl }}" class="app-button-secondary">Register</a>
                                @endauth
                                <a href="#system-overview" class="app-link">View System Overview</a>
                            </div>

                            <div class="mt-10 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                @foreach ($compactStats as $stat)
                                    <div class="app-card px-5 py-5">
                                        <p class="app-section-title">{{ $stat['label'] }}</p>
                                        <p class="mt-3 font-display text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
                                        <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $stat['note'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="space-y-4">
                            <section class="app-card overflow-hidden rounded-[32px] p-6 sm:p-7">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="app-section-title">Operations Snapshot</p>
                                        <h2 class="mt-3 font-display text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Centralized monitoring for infrastructure teams</h2>
                                    </div>
                                    <span class="app-pill bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">Live</span>
                                </div>

                                <div class="mt-6 space-y-4">
                                    <div class="rounded-3xl border border-brand-100 bg-gradient-to-br from-brand-50 via-white to-sky-50 px-5 py-5 dark:border-brand-900/30 dark:from-brand-500/10 dark:via-gray-900 dark:to-sky-500/5">
                                        <div class="flex items-center justify-between gap-4">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-500 dark:text-brand-300">System Status</p>
                                                <p class="mt-2 font-display text-3xl font-semibold text-gray-900 dark:text-white">Stable</p>
                                            </div>
                                            <div class="rounded-2xl bg-white/80 px-4 py-3 shadow-theme-xs dark:bg-white/10">
                                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">Coverage</p>
                                                <p class="mt-2 text-right font-display text-xl font-semibold text-gray-900 dark:text-white">24/7</p>
                                            </div>
                                        </div>
                                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                            <div class="rounded-2xl bg-white/80 px-4 py-4 shadow-theme-xs dark:bg-white/5">
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Environment</p>
                                                <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Telemetry in view</p>
                                            </div>
                                            <div class="rounded-2xl bg-white/80 px-4 py-4 shadow-theme-xs dark:bg-white/5">
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Servers</p>
                                                <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Health tracking active</p>
                                            </div>
                                            <div class="rounded-2xl bg-white/80 px-4 py-4 shadow-theme-xs dark:bg-white/5">
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">Access</p>
                                                <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Logs retained</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="app-surface-muted rounded-3xl px-5 py-5">
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <p class="app-section-title">Current Priorities</p>
                                                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Alert review and maintenance coordination</p>
                                                </div>
                                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-500/20">
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                        <path d="M12 4v8"></path>
                                                        <path d="M12 16h.01"></path>
                                                        <path d="M10.29 3.86L2.82 16.5A1.5 1.5 0 0 0 4.11 18.75h15.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.42 0Z"></path>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="mt-4 space-y-3">
                                                <div class="flex items-start gap-3">
                                                    <div class="mt-1 h-2.5 w-2.5 rounded-full bg-brand-500"></div>
                                                    <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">Environmental telemetry remains visible in one workflow.</p>
                                                </div>
                                                <div class="flex items-start gap-3">
                                                    <div class="mt-1 h-2.5 w-2.5 rounded-full bg-emerald-500"></div>
                                                    <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">Teams can assign, track, and close maintenance work clearly.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="app-surface-muted rounded-3xl px-5 py-5">
                                            <p class="app-section-title">Operational Readiness</p>
                                            <div class="mt-4 space-y-4">
                                                <div>
                                                    <div class="flex items-center justify-between text-sm">
                                                        <span class="font-medium text-gray-700 dark:text-gray-300">Monitoring coverage</span>
                                                        <span class="text-gray-400 dark:text-gray-500">98%</span>
                                                    </div>
                                                    <div class="metric-progress-track mt-2">
                                                        <div class="h-full w-[98%] rounded-full bg-brand-500"></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="flex items-center justify-between text-sm">
                                                        <span class="font-medium text-gray-700 dark:text-gray-300">Response workflow</span>
                                                        <span class="text-gray-400 dark:text-gray-500">92%</span>
                                                    </div>
                                                    <div class="metric-progress-track mt-2">
                                                        <div class="h-full w-[92%] rounded-full bg-emerald-500"></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="flex items-center justify-between text-sm">
                                                        <span class="font-medium text-gray-700 dark:text-gray-300">Reporting continuity</span>
                                                        <span class="text-gray-400 dark:text-gray-500">95%</span>
                                                    </div>
                                                    <div class="metric-progress-track mt-2">
                                                        <div class="h-full w-[95%] rounded-full bg-sky-500"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </section>

                <section class="px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                    <div class="mx-auto max-w-7xl">
                        <div class="max-w-2xl">
                            <p class="app-section-title">Feature Highlights</p>
                            <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                                Monitoring, access, operations, and reporting in one system.
                            </h2>
                            <p class="mt-4 text-base leading-7 text-gray-500 dark:text-gray-400">
                                The platform is designed to support day-to-day operational discipline while keeping critical infrastructure status visible and actionable.
                            </p>
                        </div>

                        <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($featureCards as $feature)
                                <article class="app-card app-card-hover rounded-[28px] px-6 py-6">
                                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10 dark:text-brand-300">
                                        {!! $renderIcon($feature['icon']) !!}
                                    </div>
                                    <h3 class="mt-5 text-xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $feature['title'] }}</h3>
                                    <p class="mt-3 text-sm leading-7 text-gray-500 dark:text-gray-400">{{ $feature['description'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section id="system-overview" class="px-4 pb-14 sm:px-6 lg:px-8 lg:pb-18">
                    <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[minmax(0,0.95fr)_420px]">
                        <section class="app-card rounded-[30px] px-6 py-7 sm:px-8">
                            <p class="app-section-title">System Overview</p>
                            <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                                Built for operational clarity, control, and traceable infrastructure oversight.
                            </h2>
                            <p class="mt-4 max-w-2xl text-base leading-7 text-gray-500 dark:text-gray-400">
                                The platform supports day-to-day infrastructure supervision by combining telemetry, access visibility, maintenance coordination, and reporting in one dependable interface.
                            </p>

                            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                                @foreach ($trustPoints as $point)
                                    <div class="rounded-2xl border border-gray-200 bg-gray-50 px-5 py-5 dark:border-gray-800 dark:bg-white/[0.03]">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $point['title'] }}</p>
                                        <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $point['text'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <aside class="app-card rounded-[30px] px-6 py-7 sm:px-7">
                            <p class="app-section-title">Enterprise Readiness</p>
                            <div class="mt-5 space-y-4">
                                <div class="rounded-2xl border border-gray-200 bg-gray-50 px-5 py-5 dark:border-gray-800 dark:bg-white/[0.03]">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Centralized operations</p>
                                    <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">One workspace for monitoring, communication, task coordination, and operational follow-up.</p>
                                </div>
                                <div class="rounded-2xl border border-gray-200 bg-gray-50 px-5 py-5 dark:border-gray-800 dark:bg-white/[0.03]">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Reliable workflows</p>
                                    <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">Designed for teams managing sensitive environments where visibility and response discipline matter.</p>
                                </div>
                                <div class="rounded-2xl border border-gray-200 bg-gray-50 px-5 py-5 dark:border-gray-800 dark:bg-white/[0.03]">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Professional reporting</p>
                                    <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">Operational history remains accessible for review cycles, leadership updates, and infrastructure planning.</p>
                                </div>
                            </div>
                        </aside>
                    </div>
                </section>

                <section class="px-4 pb-16 sm:px-6 lg:px-8 lg:pb-20">
                    <div class="mx-auto max-w-7xl">
                        <div class="app-card rounded-[32px] px-6 py-8 sm:px-8 lg:flex lg:items-center lg:justify-between lg:gap-8">
                            <div class="max-w-2xl">
                                <p class="app-section-title">Get Started</p>
                                <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                                    A trustworthy workspace for monitoring, response, and infrastructure accountability.
                                </h2>
                                <p class="mt-4 text-base leading-7 text-gray-500 dark:text-gray-400">
                                    Sign in to continue operations or register a new account to request access to the platform.
                                </p>
                            </div>
                            <div class="mt-6 flex flex-wrap gap-3 lg:mt-0 lg:justify-end">
                                @auth
                                    <a href="{{ $dashboardUrl }}" class="app-button-primary">Open Dashboard</a>
                                @else
                                    <a href="{{ $loginUrl }}" class="app-button-primary">Login</a>
                                    <a href="{{ $registerUrl }}" class="app-button-secondary">Register</a>
                                @endauth
                            </div>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="border-t border-gray-200 bg-white/70 px-4 py-6 backdrop-blur-xl dark:border-gray-800 dark:bg-gray-900/70 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-7xl flex-col gap-3 text-sm text-gray-500 dark:text-gray-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>Server Room Supervision System</p>
                    <div class="flex items-center gap-5">
                        @auth
                            <a href="{{ $dashboardUrl }}" class="transition hover:text-gray-700 dark:hover:text-gray-200">Dashboard</a>
                        @else
                            <a href="{{ $loginUrl }}" class="transition hover:text-gray-700 dark:hover:text-gray-200">Login</a>
                            <a href="{{ $registerUrl }}" class="transition hover:text-gray-700 dark:hover:text-gray-200">Register</a>
                        @endauth
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
