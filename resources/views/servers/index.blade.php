<x-app-layout>
    <section class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="app-section-title">Infrastructure</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Servers
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Track registered monitoring nodes, current health, and the latest telemetry snapshot.
                </p>
            </div>

            @if ($user->isDepartmentHead())
                <a href="{{ route('servers.create') }}" class="app-button-primary">
                    Add Server
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="app-status-success mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="app-card px-5 py-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Registered Servers</p>
                <p class="mt-4 font-display text-4xl font-semibold text-gray-900 dark:text-white">{{ $servers->count() }}</p>
            </div>

            <div class="app-card px-5 py-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Online</p>
                <p class="mt-4 font-display text-4xl font-semibold text-gray-900 dark:text-white">
                    {{ collect($serverCards)->where('status', 'Online')->count() }}
                </p>
            </div>

            <div class="app-card px-5 py-5">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Need Attention</p>
                <p class="mt-4 font-display text-4xl font-semibold text-gray-900 dark:text-white">
                    {{ collect($serverCards)->whereIn('status', ['Warning', 'Critical'])->count() }}
                </p>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            @forelse ($serverCards as $server)
                <a href="{{ route('servers.show', $server['id']) }}" class="block">
                    @include('dashboard.partials.server-card', ['server' => $server])
                </a>
            @empty
                <div class="app-card px-6 py-10 text-center xl:col-span-2">
                    <h2 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">
                        No servers registered yet
                    </h2>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        Add your first monitoring node to start collecting real CPU, RAM, disk, and network metrics.
                    </p>

                    @if ($user->isDepartmentHead())
                        <div class="mt-6">
                            <a href="{{ route('servers.create') }}" class="app-button-primary">
                                Add First Server
                            </a>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
