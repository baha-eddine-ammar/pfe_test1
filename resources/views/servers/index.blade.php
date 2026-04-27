<x-app-layout>
    <section class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="app-section-title">Server section</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Monitoring servers
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Register monitoring nodes manually, then watch their live telemetry update here as agents report in.
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

        @if ($servers->isNotEmpty())
            <div class="mb-6 grid gap-4 sm:grid-cols-3">
                <div class="app-card px-5 py-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Registered Servers</p>
                    <p class="mt-4 font-display text-4xl font-semibold text-gray-900 dark:text-white">{{ $servers->count() }}</p>
                </div>

                <div class="app-card px-5 py-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Live</p>
                    <p class="mt-4 font-display text-4xl font-semibold text-gray-900 dark:text-white">
                        {{ collect($serverCards)->where('status', 'Live')->count() }}
                    </p>
                </div>

                <div class="app-card px-5 py-5">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Need Attention</p>
                    <p class="mt-4 font-display text-4xl font-semibold text-gray-900 dark:text-white">
                        {{ collect($serverCards)->whereIn('status', ['Warning', 'Critical', 'Offline'])->count() }}
                    </p>
                </div>
            </div>

            <div class="mb-5">
                <p class="app-section-title">Registered nodes</p>
                <h2 class="mt-2 font-display text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    Monitoring servers
                </h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Each card below reflects a registered server and refreshes automatically as new telemetry arrives.
                </p>
            </div>

            <div class="grid gap-5 xl:grid-cols-2">
                @foreach ($serverCards as $server)
                    <a href="{{ route('servers.show', $server['id']) }}" class="block">
                        @include('dashboard.partials.server-card', [
                            'server' => $server,
                            'feedUrl' => route('servers.feed', $server['id']),
                        ])
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</x-app-layout>
