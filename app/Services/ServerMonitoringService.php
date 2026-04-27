<?php

namespace App\Services;

use App\Models\Server;
use App\Models\ServerMetric;
use Carbon\CarbonInterface;

class ServerMonitoringService
{
    public function buildCards(iterable $servers): array
    {
        return collect($servers)
            ->map(fn (Server $server): array => $this->buildCard($server))
            ->all();
    }

    public function buildCard(Server $server): array
    {
        $latestMetric = $server->relationLoaded('latestMetric')
            ? $server->latestMetric
            : $server->latestMetric()->first();

        $lastSeenAt = $server->last_seen_at ?? $latestMetric?->created_at;

        if (! $latestMetric || ! $lastSeenAt) {
            return [
                'id' => $server->id,
                'name' => $server->name,
                'identifier' => $server->identifier,
                'status' => 'Offline',
                'statusColor' => 'slate',
                'lastSeenAt' => null,
                'lastSeenLabel' => 'Waiting for first sample',
                'narrative' => 'This server is registered and ready, but the monitoring agent has not posted its first telemetry sample yet. Start the agent on the target machine with this server identifier and API token to begin live updates.',
                'latestMetric' => null,
                'metrics' => $this->emptyMetrics(),
            ];
        }

        $cpuProgress = $this->clampProgress($latestMetric->cpu_percent);
        $ramProgress = $this->percentage($latestMetric->ram_used_mb, $latestMetric->ram_total_mb);
        $diskProgress = $this->percentage($latestMetric->disk_used_gb, $latestMetric->disk_total_gb);

        $storageTotalGb = $latestMetric->storage_total_gb ?: $latestMetric->disk_total_gb;
        $storageFreeGb = $latestMetric->storage_free_gb;

        if ($storageFreeGb === null) {
            $storageFreeGb = max(0, $storageTotalGb - $latestMetric->disk_used_gb);
        }

        $storageUsedGb = max(0, $storageTotalGb - $storageFreeGb);
        $storageProgress = $this->percentage($storageUsedGb, $storageTotalGb);
        $storageFreePercent = max(0, 100 - $storageProgress);
        $temperatureProgress = $latestMetric->temperature_c !== null
            ? $this->temperatureProgress((float) $latestMetric->temperature_c)
            : 0;

        [$status, $statusColor] = $this->resolveStatus(
            $lastSeenAt,
            $cpuProgress,
            $ramProgress,
            $diskProgress,
            $storageProgress,
            $latestMetric->temperature_c,
            $latestMetric->network_connected,
        );

        return [
            'id' => $server->id,
            'name' => $server->name,
            'identifier' => $server->identifier,
            'status' => $status,
            'statusColor' => $statusColor,
            'lastSeenAt' => $lastSeenAt->toDateTimeString(),
            'lastSeenLabel' => $this->lastSeenLabel($lastSeenAt),
            'narrative' => $this->narrative($server, $status, $latestMetric),
            'latestMetric' => [
                'cpu' => [
                    'value' => $this->formatPercent($latestMetric->cpu_percent),
                    'progress' => $cpuProgress,
                    'raw' => round((float) $latestMetric->cpu_percent, 1),
                ],
                'ram' => [
                    'value' => $this->formatMemory($latestMetric->ram_used_mb).'/'.$this->formatMemory($latestMetric->ram_total_mb),
                    'progress' => $ramProgress,
                    'usedMb' => (int) $latestMetric->ram_used_mb,
                    'totalMb' => (int) $latestMetric->ram_total_mb,
                ],
                'disk' => [
                    'value' => $this->formatStorage($latestMetric->disk_used_gb).'/'.$this->formatStorage($latestMetric->disk_total_gb),
                    'progress' => $diskProgress,
                    'usedGb' => round((float) $latestMetric->disk_used_gb, 1),
                    'totalGb' => round((float) $latestMetric->disk_total_gb, 1),
                ],
                'storage' => [
                    'value' => $this->storageState($storageProgress).' - '.$this->formatStorage($storageFreeGb).' free',
                    'progress' => $storageProgress,
                    'freeGb' => round((float) $storageFreeGb, 1),
                    'totalGb' => round((float) $storageTotalGb, 1),
                ],
                'temperature' => [
                    'value' => $latestMetric->temperature_c !== null
                        ? $this->formatNumber((float) $latestMetric->temperature_c).' C'
                        : 'Unavailable',
                    'progress' => $temperatureProgress,
                    'raw' => $latestMetric->temperature_c !== null ? round((float) $latestMetric->temperature_c, 1) : null,
                ],
                'network' => [
                    'value' => $this->networkValue($latestMetric),
                    'progress' => $this->networkProgress($latestMetric),
                    'connected' => $latestMetric->network_connected,
                    'name' => $latestMetric->network_name,
                    'ipv4' => $latestMetric->network_ipv4,
                    'rxMbps' => round((float) $latestMetric->net_rx_mbps, 1),
                    'txMbps' => round((float) $latestMetric->net_tx_mbps, 1),
                ],
                'lastSeen' => [
                    'at' => $lastSeenAt->toDateTimeString(),
                    'label' => $this->lastSeenLabel($lastSeenAt),
                ],
            ],
            'metrics' => [
                [
                    'key' => 'cpu',
                    'label' => 'CPU usage',
                    'value' => $this->formatPercent($latestMetric->cpu_percent),
                    'progress' => $cpuProgress,
                    'color' => 'cyan',
                    'footerLabel' => 'Utilization',
                    'progressLabel' => $cpuProgress.'%',
                ],
                [
                    'key' => 'ram',
                    'label' => 'RAM usage',
                    'value' => $this->formatMemory($latestMetric->ram_used_mb).'/'.$this->formatMemory($latestMetric->ram_total_mb),
                    'progress' => $ramProgress,
                    'color' => 'violet',
                    'footerLabel' => 'Utilization',
                    'progressLabel' => $ramProgress.'%',
                ],
                [
                    'key' => 'disk',
                    'label' => 'Disk usage',
                    'value' => $this->formatStorage($latestMetric->disk_used_gb).'/'.$this->formatStorage($latestMetric->disk_total_gb),
                    'progress' => $diskProgress,
                    'color' => 'pink',
                    'footerLabel' => 'Utilization',
                    'progressLabel' => $diskProgress.'%',
                ],
                [
                    'key' => 'storage',
                    'label' => 'Storage status',
                    'value' => $this->storageState($storageProgress).' - '.$this->formatStorage($storageFreeGb).' free',
                    'progress' => $storageProgress,
                    'color' => 'emerald',
                    'footerLabel' => 'Free space',
                    'progressLabel' => $storageFreePercent.'% free',
                ],
                [
                    'key' => 'temperature',
                    'label' => 'CPU temperature',
                    'value' => $latestMetric->temperature_c !== null
                        ? $this->formatNumber((float) $latestMetric->temperature_c).' C'
                        : 'Unavailable',
                    'progress' => $temperatureProgress,
                    'color' => 'amber',
                    'footerLabel' => 'Sensor',
                    'progressLabel' => $latestMetric->temperature_c !== null
                        ? $this->temperatureState((float) $latestMetric->temperature_c)
                        : 'Not exposed',
                ],
                [
                    'key' => 'network',
                    'label' => 'Network information',
                    'value' => $this->networkValue($latestMetric),
                    'progress' => $this->networkProgress($latestMetric),
                    'color' => 'emerald',
                    'footerLabel' => $latestMetric->network_name ?: 'Link',
                    'progressLabel' => $this->networkProgressLabel($latestMetric),
                ],
            ],
        ];
    }

    private function emptyMetrics(): array
    {
        return [
            [
                'key' => 'cpu',
                'label' => 'CPU usage',
                'value' => 'Waiting for first sample',
                'progress' => 0,
                'color' => 'cyan',
                'footerLabel' => 'Utilization',
                'progressLabel' => 'Pending',
            ],
            [
                'key' => 'ram',
                'label' => 'RAM usage',
                'value' => 'Waiting for first sample',
                'progress' => 0,
                'color' => 'violet',
                'footerLabel' => 'Utilization',
                'progressLabel' => 'Pending',
            ],
            [
                'key' => 'disk',
                'label' => 'Disk usage',
                'value' => 'Waiting for first sample',
                'progress' => 0,
                'color' => 'pink',
                'footerLabel' => 'Utilization',
                'progressLabel' => 'Pending',
            ],
            [
                'key' => 'storage',
                'label' => 'Storage status',
                'value' => 'Waiting for first sample',
                'progress' => 0,
                'color' => 'emerald',
                'footerLabel' => 'Free space',
                'progressLabel' => 'Pending',
            ],
            [
                'key' => 'temperature',
                'label' => 'CPU temperature',
                'value' => 'Waiting for first sample',
                'progress' => 0,
                'color' => 'amber',
                'footerLabel' => 'Sensor',
                'progressLabel' => 'Pending',
            ],
            [
                'key' => 'network',
                'label' => 'Network information',
                'value' => 'Waiting for first sample',
                'progress' => 0,
                'color' => 'emerald',
                'footerLabel' => 'Link',
                'progressLabel' => 'Pending',
            ],
        ];
    }

    private function lastSeenLabel(CarbonInterface $lastSeenAt): string
    {
        return $lastSeenAt->isToday()
            ? 'Updated '.$lastSeenAt->format('H:i:s')
            : $lastSeenAt->format('d M Y H:i');
    }

    private function narrative(Server $server, string $status, ServerMetric $latestMetric): string
    {
        $intro = match ($status) {
            'Offline' => 'This server is currently offline or its telemetry feed has gone stale beyond the accepted live monitoring window.',
            'Critical' => 'This server is reporting critical conditions or stale telemetry and needs immediate attention.',
            'Warning' => 'This server is reporting live telemetry, but one or more resources should be watched closely.',
            default => 'This server is reporting live telemetry and remains within expected operating ranges.',
        };

        $type = $server->server_type
            ? 'Type: '.$server->server_type.'.'
            : null;

        $uptime = $latestMetric->uptime_seconds !== null
            ? 'Uptime: '.$this->formatUptime((int) $latestMetric->uptime_seconds).'.'
            : null;

        $network = $latestMetric->network_connected === false
            ? 'The latest telemetry sample reported the network link as disconnected.'
            : 'Network link data is being received from the monitoring agent.';

        return collect([$intro, $type, $uptime, $network])
            ->filter()
            ->implode(' ');
    }

    private function resolveStatus(
        CarbonInterface $lastSeenAt,
        int $cpuProgress,
        int $ramProgress,
        int $diskProgress,
        int $storageProgress,
        ?float $temperatureC,
        ?bool $networkConnected,
    ): array {
        $secondsSinceLastSeen = $lastSeenAt->diffInSeconds(now());

        if ($secondsSinceLastSeen >= 300) {
            return ['Offline', 'slate'];
        }

        if (
            $secondsSinceLastSeen >= 120
            || $cpuProgress >= 90
            || $ramProgress >= 90
            || $diskProgress >= 90
            || $storageProgress >= 92
            || ($temperatureC !== null && $temperatureC >= 85)
        ) {
            return ['Critical', 'pink'];
        }

        if (
            $secondsSinceLastSeen >= 60
            || $cpuProgress >= 75
            || $ramProgress >= 75
            || $diskProgress >= 80
            || $storageProgress >= 85
            || $networkConnected === false
            || ($temperatureC !== null && $temperatureC >= 70)
        ) {
            return ['Warning', 'amber'];
        }

        return ['Live', 'emerald'];
    }

    private function storageState(int $storageProgress): string
    {
        if ($storageProgress >= 92) {
            return 'Low space';
        }

        if ($storageProgress >= 85) {
            return 'Watch';
        }

        return 'Healthy';
    }

    private function temperatureState(float $temperatureC): string
    {
        if ($temperatureC >= 85) {
            return 'Critical';
        }

        if ($temperatureC >= 70) {
            return 'Warm';
        }

        return 'Stable';
    }

    private function networkValue(ServerMetric $latestMetric): string
    {
        if ($latestMetric->network_connected === false) {
            return 'Disconnected';
        }

        if ($latestMetric->network_name && $latestMetric->network_speed_mbps) {
            return $latestMetric->network_name.' - '.$latestMetric->network_speed_mbps.' Mbps';
        }

        if (($latestMetric->net_rx_mbps > 0) || ($latestMetric->net_tx_mbps > 0)) {
            return 'Rx '.$this->formatNumber($latestMetric->net_rx_mbps).' Mbps / Tx '.$this->formatNumber($latestMetric->net_tx_mbps).' Mbps';
        }

        if ($latestMetric->network_name) {
            return $latestMetric->network_name;
        }

        return 'Connected';
    }

    private function networkProgressLabel(ServerMetric $latestMetric): string
    {
        if ($latestMetric->network_connected === false) {
            return 'Offline';
        }

        if ($latestMetric->network_ipv4) {
            return $latestMetric->network_ipv4;
        }

        if (($latestMetric->net_rx_mbps > 0) || ($latestMetric->net_tx_mbps > 0)) {
            return 'Traffic detected';
        }

        return 'Connected';
    }

    private function percentage(float|int $used, float|int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return $this->clampProgress(($used / $total) * 100);
    }

    private function clampProgress(float|int $value): int
    {
        return (int) round(max(0, min(100, $value)));
    }

    private function temperatureProgress(float $temperatureC): int
    {
        return $this->clampProgress((($temperatureC - 20) / 70) * 100);
    }

    private function networkProgress(ServerMetric $latestMetric): int
    {
        if ($latestMetric->network_connected === false) {
            return 0;
        }

        $transferMbps = (float) $latestMetric->net_rx_mbps + (float) $latestMetric->net_tx_mbps;

        if ($latestMetric->network_speed_mbps !== null && $latestMetric->network_speed_mbps > 0) {
            if ($transferMbps <= 0) {
                return 3;
            }

            return $this->clampProgress(($transferMbps / $latestMetric->network_speed_mbps) * 100);
        }

        return $transferMbps > 0 ? 10 : 3;
    }

    private function formatPercent(float $value): string
    {
        return $this->formatNumber($value).'%';
    }

    private function formatMemory(int $valueInMb): string
    {
        return $this->formatNumber($valueInMb / 1024).' GB';
    }

    private function formatStorage(float $valueInGb): string
    {
        return $this->formatNumber($valueInGb).' GB';
    }

    private function formatUptime(int $uptimeSeconds): string
    {
        $days = intdiv($uptimeSeconds, 86400);
        $hours = intdiv($uptimeSeconds % 86400, 3600);
        $minutes = intdiv($uptimeSeconds % 3600, 60);

        $parts = [];

        if ($days > 0) {
            $parts[] = $days.'d';
        }

        if ($hours > 0) {
            $parts[] = $hours.'h';
        }

        if ($minutes > 0 || $parts === []) {
            $parts[] = $minutes.'m';
        }

        return implode(' ', array_slice($parts, 0, 2));
    }

    private function formatNumber(float $value): string
    {
        $formatted = number_format($value, 1, '.', '');

        return str_ends_with($formatted, '.0')
            ? substr($formatted, 0, -2)
            : $formatted;
    }
}
