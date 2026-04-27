<x-app-layout>
    @php
        $chartSamples = $recentMetrics
            ->reverse()
            ->values()
            ->map(function ($metric) {
                $ramProgress = $metric->ram_total_mb > 0
                    ? round(($metric->ram_used_mb / $metric->ram_total_mb) * 100, 1)
                    : 0;

                $diskProgress = $metric->disk_total_gb > 0
                    ? round(($metric->disk_used_gb / $metric->disk_total_gb) * 100, 1)
                    : 0;

                return [
                    'label' => $metric->created_at?->timezone(config('app.timezone'))->format('H:i:s') ?? 'Sample',
                    'cpu' => round((float) $metric->cpu_percent, 1),
                    'ram' => max(0, min(100, $ramProgress)),
                    'disk' => max(0, min(100, $diskProgress)),
                    'network' => round((float) $metric->net_rx_mbps + (float) $metric->net_tx_mbps, 1),
                ];
            })
            ->all();

        $serverChartProps = [
            'serverId' => $server->id,
            'recentMetrics' => $chartSamples,
        ];
    @endphp

    <section class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('servers.index') }}" class="app-link">
                    Back to Servers
                </a>

                <p class="app-section-title mt-4">Infrastructure</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
                    {{ $server->name }}
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Review the current server health snapshot, connection details, and recent telemetry history.
                </p>
            </div>

            @if (auth()->user()->isDepartmentHead())
                <a href="{{ route('servers.create') }}" class="app-button-secondary">
                    Add Another Server
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="app-status-success mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
            <div class="space-y-6">
                @include('dashboard.partials.server-card', [
                    'server' => $serverCard,
                    'feedUrl' => $feedUrl,
                ])

                <div class="app-card px-6 py-6 sm:px-7">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="app-section-title">Telemetry</p>
                            <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">
                                Live Server Trend
                            </h2>
                        </div>

                        <span class="app-pill bg-gray-100 text-gray-700 dark:bg-white/[0.05] dark:text-gray-300">
                            {{ $recentMetrics->count() }} sample(s)
                        </span>
                    </div>

                    <div class="mt-5">
                        <div
                            data-react-server-telemetry-chart
                            data-props='{{ json_encode($serverChartProps, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT) }}'
                        ></div>
                    </div>
                </div>

                <div class="app-card px-6 py-6 sm:px-7">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="app-section-title">History</p>
                            <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">
                                Recent Metrics
                            </h2>
                        </div>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                    <th class="pb-3 font-medium">Captured</th>
                                    <th class="pb-3 font-medium">CPU</th>
                                    <th class="pb-3 font-medium">RAM</th>
                                    <th class="pb-3 font-medium">Disk</th>
                                    <th class="pb-3 font-medium">Temperature</th>
                                    <th class="pb-3 font-medium">Network</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($recentMetrics as $metric)
                                    <tr>
                                        <td class="py-3 text-gray-500 dark:text-gray-400">
                                            {{ $metric->created_at?->timezone(config('app.timezone'))->format('d M Y H:i:s') }}
                                        </td>
                                        <td class="py-3 text-gray-900 dark:text-white">
                                            {{ number_format($metric->cpu_percent, 1) }}%
                                        </td>
                                        <td class="py-3 text-gray-900 dark:text-white">
                                            {{ number_format($metric->ram_used_mb / 1024, 1) }}/{{ number_format($metric->ram_total_mb / 1024, 1) }} GB
                                        </td>
                                        <td class="py-3 text-gray-900 dark:text-white">
                                            {{ number_format($metric->disk_used_gb, 1) }}/{{ number_format($metric->disk_total_gb, 1) }} GB
                                        </td>
                                        <td class="py-3 text-gray-900 dark:text-white">
                                            {{ $metric->temperature_c !== null ? number_format($metric->temperature_c, 1).' C' : 'Unavailable' }}
                                        </td>
                                        <td class="py-3 text-gray-900 dark:text-white">
                                            {{ $metric->network_name ?: 'Network' }} -
                                            ↓ {{ number_format($metric->net_rx_mbps, 1) }} /
                                            ↑ {{ number_format($metric->net_tx_mbps, 1) }} Mbps
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-center text-gray-500 dark:text-gray-400">
                                            No telemetry received yet. Send the first metric payload from your monitoring agent.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="app-card px-6 py-6 sm:px-7">
                    <p class="app-section-title">Connection</p>
                    <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">
                        Server Details
                    </h2>

                    <div class="mt-5 space-y-4">
                        <div class="app-surface-muted px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                Identifier
                            </p>
                            <p class="mt-2 font-mono text-sm text-gray-900 dark:text-white">
                                {{ $server->identifier }}
                            </p>
                        </div>

                        <div class="app-surface-muted px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                Last Seen
                            </p>
                            <p class="mt-2 font-medium text-gray-900 dark:text-white">
                                {{ $serverCard['lastSeenLabel'] }}
                            </p>
                        </div>

                        <div class="app-surface-muted px-4 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                Registered
                            </p>
                            <p class="mt-2 font-medium text-gray-900 dark:text-white">
                                {{ $server->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}
                            </p>
                        </div>
                    </div>
                </div>

                @if (auth()->user()->isDepartmentHead())
                    <div class="app-card px-6 py-6 sm:px-7">
                        <p class="app-section-title">Agent Setup</p>
                        <h2 class="mt-2 font-display text-2xl font-semibold text-gray-900 dark:text-white">
                            API Credentials
                        </h2>

                        <div class="mt-5 space-y-4">
                            <div class="app-surface-muted px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                    API Token
                                </p>
                                <p class="mt-2 break-all font-mono text-sm text-gray-900 dark:text-white">
                                    {{ $server->api_token }}
                                </p>
                            </div>

                            <div class="app-surface-muted px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                    POST Endpoint
                                </p>
                                <p class="mt-2 break-all font-mono text-sm text-gray-900 dark:text-white">
                                    {{ route('api.server-metrics.store') }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-2xl bg-gray-50 px-4 py-4 dark:bg-white/[0.03]">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Example payload</p>
                            <pre class="mt-3 overflow-x-auto text-xs leading-6 text-gray-700 dark:text-gray-300"><code>{
  "identifier": "{{ $server->identifier }}",
  "cpu_percent": 37.5,
  "ram_used_mb": 8192,
  "ram_total_mb": 16384,
  "disk_used_gb": 320.4,
  "disk_total_gb": 1000,
  "net_rx_mbps": 12.8,
  "net_tx_mbps": 4.2,
  "temperature_c": 42.5
}</code></pre>
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                Send the token in the <span class="font-mono">X-Server-Token</span> header to authenticate the server agent.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-app-layout>
