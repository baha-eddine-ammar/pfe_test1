<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\ServerMetricsIngestionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServerMetricsController extends Controller
{
    public function __construct(
        private readonly ServerMetricsIngestionService $ingestionService,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'identifier' => Str::lower(trim((string) $request->input('identifier', ''))),
        ]);

        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'cpu_percent' => ['required', 'numeric', 'between:0,100'],
            'ram_used_mb' => ['required', 'integer', 'min:0'],
            'ram_total_mb' => ['required', 'integer', 'min:1', 'gte:ram_used_mb'],
            'disk_used_gb' => ['required', 'numeric', 'min:0'],
            'disk_total_gb' => ['required', 'numeric', 'gt:0', 'gte:disk_used_gb'],
            'storage_free_gb' => ['nullable', 'numeric', 'min:0'],
            'storage_total_gb' => ['nullable', 'numeric', 'gt:0'],
            'temperature_c' => ['nullable', 'numeric', 'between:-30,150'],
            'net_rx_mbps' => ['nullable', 'numeric', 'min:0'],
            'net_tx_mbps' => ['nullable', 'numeric', 'min:0'],
            'network_connected' => ['nullable', 'boolean'],
            'network_name' => ['nullable', 'string', 'max:255'],
            'network_speed_mbps' => ['nullable', 'integer', 'min:0'],
            'network_ipv4' => ['nullable', 'ip'],
            'uptime_seconds' => ['nullable', 'integer', 'min:0'],
            'sampled_at' => ['nullable', 'date'],
        ]);

        $server = Server::query()
            ->where('identifier', $validated['identifier'])
            ->first();

        $providedToken = (string) $request->header('X-Server-Token', '');

        if (! $server || $providedToken === '' || ! hash_equals($server->api_token, $providedToken)) {
            return response()->json([
                'message' => 'Invalid server credentials.',
            ], 401);
        }

        $capturedAt = isset($validated['sampled_at'])
            ? Carbon::parse($validated['sampled_at'])
            : now();

        $result = $this->ingestionService->ingest($server, $validated, $capturedAt);
        $metric = $result['metric'];
        $freshServer = $result['server'];

        return response()->json([
            'message' => 'Metrics stored successfully.',
            'server' => [
                'id' => $freshServer->id,
                'name' => $freshServer->name,
                'identifier' => $freshServer->identifier,
                'last_seen_at' => optional($freshServer->last_seen_at)->toIso8601String(),
            ],
            'metric_id' => $metric->id,
        ], 201);
    }
}
