<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This service converts raw server + latest metric data into dashboard cards.
|
| Why this file exists:
| The dashboard should not know how to interpret CPU/RAM/disk/network values.
| This service prepares a UI-ready array from Server and ServerMetric models.
|
| When this file is used:
| When DashboardController needs to build the server fleet cards.
|
| FILES TO READ (IN ORDER):
| 1. app/Models/Server.php
| 2. app/Models/ServerMetric.php
| 3. app/Services/ServerMonitoringService.php
| 4. app/Http/Controllers/DashboardController.php
| 5. resources/views/dashboard/partials/server-card.blade.php
*/

namespace App\Services;

use App\Models\Server;
use App\Models\ServerMetric;
use Carbon\CarbonInterface;

class ServerMonitoringService
{
    // Converts a collection/list of Server models into dashboard card arrays.
    public function buildCards(iterable $servers): array
    {
        return collect($servers)
            ->map(fn (Server $server) => $this->buildCard($server))
            ->all();
    }

    /*
    |----------------------------------------------------------------------
    | Build one server card
    |----------------------------------------------------------------------
    | Flow:
    | Server model -> latestMetric relation -> formatted values -> Blade card
    */
    public function buildCard(Server $server): array
    {
        // latestMetric may already be eager loaded by the controller.
        $latestMetric = $server->relationLoaded('latestMetric')
            ? $server->latestMetric
            : $server->latestMetric()->first();

        // last_seen_at can come from the server row itself or fall back to the
        // timestamp of the latest metric row.
        $lastSeenAt = $server->last_seen_at ?? $latestMetric?->created_at;
        [$status, $statusColor] = $this->resolveStatus($lastSeenAt, $latestMetric);

        return [
            'id' => $server->id,
            'name' => $server->name,
            'identifier' => $server->identifier,
            'status' => $status,
            'statusColor' => $statusColor,
            'lastSeenAt' => $lastSeenAt?->toDateTimeString(),
            'lastSeenLabel' => $lastSeenAt ? $lastSeenAt->diffForHumans() : 'No data yet',
            'metrics' => [
                [
                    'label' => 'CPU',
                    'value' => $latestMetric ? $this->formatPercent($latestMetric->cpu_percent) : 'No data',
                    'progress' => $latestMetric ? $this->clampProgress($latestMetric->cpu_percent) : 0,
                    'color' => 'cyan',
                ],
                [
                    'label' => 'RAM',
                    'value' => $latestMetric
                        ? $this->formatMemory($latestMetric->ram_used_mb).'/'.$this->formatMemory($latestMetric->ram_total_mb)
                        : 'No data',
                    'progress' => $latestMetric ? $this->percentage($latestMetric->ram_used_mb, $latestMetric->ram_total_mb) : 0,
                    'color' => 'violet',
                ],
                [
                    'label' => 'Disk',
                    'value' => $latestMetric
                        ? $this->formatStorage($latestMetric->disk_used_gb).'/'.$this->formatStorage($latestMetric->disk_total_gb)
                        : 'No data',
                    'progress' => $latestMetric ? $this->percentage($latestMetric->disk_used_gb, $latestMetric->disk_total_gb) : 0,
                    'color' => 'pink',
                ],
                [
                    'label' => 'Network',
                    'value' => $latestMetric
                        ? '↓ '.$this->formatNumber($latestMetric->net_rx_mbps).' Mbps ↑ '.$this->formatNumber($latestMetric->net_tx_mbps).' Mbps'
                        : 'No data',
                    'progress' => $latestMetric ? $this->networkProgress($latestMetric) : 0,
                    'color' => 'emerald',
                ],
            ],
        ];
    }

    // Determines the overall server health status from telemetry freshness and
    // resource pressure thresholds.
    private function resolveStatus(?CarbonInterface $lastSeenAt, ?ServerMetric $latestMetric): array
    {
        if (! $lastSeenAt || ! $latestMetric) {
            return ['Critical', 'pink'];
        }

        $minutesSinceLastSeen = $lastSeenAt->diffInMinutes(now());
        $ramPercent = $this->percentage($latestMetric->ram_used_mb, $latestMetric->ram_total_mb);
        $diskPercent = $this->percentage($latestMetric->disk_used_gb, $latestMetric->disk_total_gb);

        if (
            $minutesSinceLastSeen >= 10
            || $latestMetric->cpu_percent >= 90
            || $ramPercent >= 90
            || $diskPercent >= 90
        ) {
            return ['Critical', 'pink'];
        }

        if (
            $minutesSinceLastSeen >= 3
            || $latestMetric->cpu_percent >= 75
            || $ramPercent >= 75
            || $diskPercent >= 80
        ) {
            return ['Warning', 'amber'];
        }

        return ['Online', 'emerald'];
    }

    // Percentage helper reused by RAM and disk calculations.
    private function percentage(float|int $used, float|int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return $this->clampProgress(($used / $total) * 100);
    }

    // Keeps progress values inside the 0-100 range required by the UI bars.
    private function clampProgress(float|int $value): int
    {
        return (int) round(max(0, min(100, $value)));
    }

    // Network visualization uses average inbound/outbound throughput as a simple score.
    private function networkProgress(ServerMetric $latestMetric): int
    {
        return $this->clampProgress(($latestMetric->net_rx_mbps + $latestMetric->net_tx_mbps) / 2);
    }

    // Formatting helpers used to keep numeric display consistent across cards.
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

    private function formatNumber(float $value): string
    {
        $formatted = number_format($value, 1, '.', '');

        return str_ends_with($formatted, '.0')
            ? substr($formatted, 0, -2)
            : $formatted;
    }
}
