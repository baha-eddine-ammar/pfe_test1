<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Server Room Supervision') }}</title>

        @include('layouts.partials.brand-preloader-head')

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
    <body class="auth-shell" x-data x-init="$store.theme.init()">
        @include('layouts.partials.brand-preloader')

        <div class="mx-auto flex min-h-screen max-w-7xl items-center px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid w-full gap-8 lg:grid-cols-[1.1fr_minmax(0,0.9fr)]">
                <section class="auth-card auth-showcase hidden overflow-hidden px-8 py-10 lg:flex lg:flex-col lg:justify-between">
                    <div>
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-500/20">
                            <x-application-logo class="h-7 w-7 fill-current" />
                        </div>
                        <p class="mt-6 text-sm font-semibold uppercase tracking-[0.35em] text-brand-500 dark:text-brand-300">Tailadmin Inspired</p>
                        <h1 class="mt-4 font-display text-4xl font-semibold tracking-tight text-gray-900 dark:text-white">
                            Server Room Supervision
                        </h1>
                        <p class="mt-4 max-w-md text-sm leading-7 text-gray-500 dark:text-gray-400">
                            A modern monitoring workspace for infrastructure health, environment metrics, access control, and operational workflows.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="app-card bg-white/80 px-5 py-5 dark:bg-gray-900/80">
                            <p class="app-section-title">Environment</p>
                            <p class="mt-3 font-display text-2xl font-semibold text-gray-900 dark:text-white">22.4 deg C</p>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Temperature and humidity trends remain visible at all times.</p>
                        </div>
                        <div class="app-card bg-white/80 px-5 py-5 dark:bg-gray-900/80">
                            <p class="app-section-title">Security</p>
                            <p class="mt-3 font-display text-2xl font-semibold text-gray-900 dark:text-white">RFID Live</p>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Account approval, audit trails, and real-time access monitoring.</p>
                        </div>
                    </div>
                </section>

                <section class="auth-card px-6 py-8 sm:px-8 sm:py-10">
                    <div class="mb-8 flex items-center justify-between">
                        <a href="/" class="flex items-center gap-3">
                            <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-500/20">
                                <x-application-logo class="h-6 w-6 fill-current" />
                            </div>
                            <div>
                                <p class="font-display text-xl font-semibold text-gray-900 dark:text-white">Server Room</p>
                                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gray-400 dark:text-gray-500">Control Center</p>
                            </div>
                        </a>

                        <button
                            type="button"
                            x-data
                            @click="$store.theme.toggle()"
                            class="app-icon-button"
                            aria-label="Toggle theme"
                        >
                            <svg x-cloak x-show="$store.theme.theme !== 'dark'" class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                                <path d="M17.5 10.8A7.5 7.5 0 119.2 2.5a6 6 0 008.3 8.3z"></path>
                            </svg>
                            <svg x-cloak x-show="$store.theme.theme === 'dark'" class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7">
                                <circle cx="10" cy="10" r="3"></circle>
                                <path d="M10 1.5v2.2M10 16.3v2.2M18.5 10h-2.2M3.7 10H1.5M15.8 4.2l-1.6 1.6M5.8 14.2l-1.6 1.6M15.8 15.8l-1.6-1.6M5.8 5.8L4.2 4.2" stroke-linecap="round"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="max-w-md">
                        {{ $slot }}
                    </div>
                </section>
            </div>
        </div>
    </body>
</html>
