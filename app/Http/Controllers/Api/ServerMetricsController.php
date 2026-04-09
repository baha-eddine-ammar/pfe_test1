<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ServerMetricsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'api_token' => ['nullable', 'string'],
            'cpu_percent' => ['required', 'numeric', 'between:0,100'],
            'ram_used_mb' => ['required', 'integer', 'min:0'],
            'ram_total_mb' => ['required', 'integer', 'min:1', 'gte:ram_used_mb'],
            'disk_used_gb' => ['required', 'numeric', 'min:0'],
            'disk_total_gb' => ['required', 'numeric', 'gt:0', 'gte:disk_used_gb'],
            'net_rx_mbps' => ['required', 'numeric', 'min:0'],
            'net_tx_mbps' => ['required', 'numeric', 'min:0'],
        ]);

        $server = Server::query()
            ->where('identifier', $validated['identifier'])
            ->first();

        $providedToken = (string) ($request->header('X-Server-Token') ?: ($validated['api_token'] ?? ''));

        if (! $server || $providedToken === '' || ! hash_equals($server->api_token, $providedToken)) {
            return response()->json([
                'message' => 'Invalid server credentials.',
            ], 401);
        }

        $metric = $server->metrics()->create(Arr::only($validated, [
            'cpu_percent',
            'ram_used_mb',
            'ram_total_mb',
            'disk_used_gb',
            'disk_total_gb',
            'net_rx_mbps',
            'net_tx_mbps',
        ]));

        $server->forceFill([
            'last_seen_at' => now(),
        ])->save();

        return response()->json([
            'message' => 'Metrics stored successfully.',
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'identifier' => $server->identifier,
            ],
            'metric_id' => $metric->id,
        ], 201);
    }
}
