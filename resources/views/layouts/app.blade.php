{{--
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This is the main authenticated application layout.
|
| Why this file exists:
| Most pages in the app share the same shell:
| - sidebar
| - topbar
| - themed content area
|
| When this file is used:
| Any page rendered inside <x-app-layout>.
|
| FILES TO READ (IN ORDER):
| 1. resources/views/layouts/app.blade.php
| 2. resources/views/layouts/sidebar.blade.php
| 3. resources/views/layouts/topbar.blade.php
| 4. resources/js/app.js
| 5. the feature Blade file using <x-app-layout>
|--------------------------------------------------------------------------
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Server Room Supervision') }}</title>

        <script>
            /*
             * Applies the saved theme as early as possible to avoid a flash
             * between light and dark mode before Alpine fully boots.
             */
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
    <body
        class="app-shell"
        x-data="{ profileOpen: false, notificationOpen: false }"
        x-init="$store.theme.init(); $store.sidebar.sync(); window.addEventListener('resize', () => $store.sidebar.sync())"
    >
        {{--
            Shared authenticated shell:
            sidebar on the left, topbar at the top, current page content in <main>.
        --}}
        <div class="min-h-screen lg:flex">
            @include('layouts.sidebar')

            <div class="flex min-h-screen flex-1 flex-col transition-[padding] duration-300 ease-in-out" :class="$store.sidebar.open ? 'lg:pl-[290px]' : 'lg:pl-0'">
                @include('layouts.topbar')

                <main class="flex-1 px-4 pb-8 pt-4 sm:px-6 lg:px-8">
                    @isset($header)
                        <section class="mb-6">
                            {{ $header }}
                        </section>
                    @endisset

                    <div class="space-y-6">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
