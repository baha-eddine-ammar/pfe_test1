<?php

namespace App\Services;

use App\Events\ServerMetricStored;
use App\Models\Server;
use App\Models\ServerMetric;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ServerMetricsIngestionService
{
    public function __construct(
        private readonly ServerMonitoringService $monitoringService,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function ingest(Server $server, array $validated, CarbonInterface $capturedAt): array
    {
        $metric = DB::transaction(function () use ($server, $validated, $capturedAt): ServerMetric {
            $storageTotalGb = (float) ($validated['storage_total_gb'] ?? $validated['disk_total_gb']);
            $storageFreeGb = array_key_exists('storage_free_gb', $validated) && $validated['storage_free_gb'] !== null
                ? (float) $validated['storage_free_gb']
                : max(0, $storageTotalGb - (float) $validated['disk_used_gb']);

            $metric = $server->metrics()->create([
                'cpu_percent' => (float) $validated['cpu_percent'],
                'ram_used_mb' => (int) $validated['ram_used_mb'],
                'ram_total_mb' => (int) $validated['ram_total_mb'],
                'disk_used_gb' => (float) $validated['disk_used_gb'],
                'disk_total_gb' => (float) $validated['disk_total_gb'],
                'storage_free_gb' => $storageFreeGb,
                'storage_total_gb' => $storageTotalGb,
                'temperature_c' => $validated['temperature_c'] ?? null,
                'net_rx_mbps' => (float) ($validated['net_rx_mbps'] ?? 0),
                'net_tx_mbps' => (float) ($validated['net_tx_mbps'] ?? 0),
                'network_connected' => array_key_exists('network_connected', $validated)
                    ? (bool) $validated['network_connected']
                    : true,
                'network_name' => $validated['network_name'] ?? null,
                'network_speed_mbps' => $validated['network_speed_mbps'] ?? null,
                'network_ipv4' => $validated['network_ipv4'] ?? null,
                'uptime_seconds' => $validated['uptime_seconds'] ?? null,
                'created_at' => $capturedAt,
            ]);

            if ($server->last_seen_at === null || $capturedAt->greaterThan($server->last_seen_at)) {
                $server->forceFill([
                    'last_seen_at' => $capturedAt,
                ])->save();
            }

            return $metric;
        });

        $freshServer = $server->fresh(['latestMetric']);
        $serverCard = $this->monitoringService->buildCard($freshServer);

        $this->auditLogService->record('server.metrics.ingested', $freshServer, [
            'metric_id' => $metric->id,
            'cpu_percent' => (float) $metric->cpu_percent,
            'ram_used_mb' => (int) $metric->ram_used_mb,
            'disk_used_gb' => (float) $metric->disk_used_gb,
            'temperature_c' => $metric->temperature_c,
            'sampled_at' => $capturedAt->toIso8601String(),
        ]);

        ServerMetricStored::dispatch($freshServer->id, $metric->id, $serverCard);

        return [
            'metric' => $metric,
            'server' => $freshServer,
            'serverCard' => $serverCard,
        ];
    }
}
