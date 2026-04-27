<?php

namespace App\Services\Reports;

use App\Contracts\Reports\SensorDataProvider;
use App\Models\SensorReading;
use App\Models\ServerMetric;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DatabaseSensorDataProvider implements SensorDataProvider
{
    private const SENSOR_DEFINITIONS = [
        'temperature' => [
            'name' => 'Temperature',
            'unit' => '°C',
            'location' => 'ESP32 DHT22',
            'warning_above' => 25.0,
            'critical_above' => 30.0,
            'target_min' => 18.0,
            'target_max' => 25.0,
            'spike_delta' => 2.0,
        ],
        'humidity' => [
            'name' => 'Humidity',
            'unit' => '%',
            'location' => 'ESP32 DHT22',
            'warning_above' => 60.0,
            'critical_above' => 70.0,
            'warning_below' => 35.0,
            'critical_below' => 30.0,
            'target_min' => 35.0,
            'target_max' => 60.0,
            'spike_delta' => 6.0,
        ],
    ];

    private const SERVER_METRIC_DEFINITIONS = [
        'server_cpu_percent' => [
            'name' => 'Server CPU',
            'unit' => '%',
            'location' => 'Registered servers',
            'warning_above' => 80.0,
            'critical_above' => 90.0,
            'target_min' => 0.0,
            'target_max' => 70.0,
            'spike_delta' => 20.0,
        ],
        'server_ram_percent' => [
            'name' => 'Server RAM',
            'unit' => '%',
            'location' => 'Registered servers',
            'warning_above' => 80.0,
            'critical_above' => 90.0,
            'target_min' => 0.0,
            'target_max' => 70.0,
            'spike_delta' => 15.0,
        ],
        'server_disk_percent' => [
            'name' => 'Server Disk',
            'unit' => '%',
            'location' => 'Registered servers',
            'warning_above' => 80.0,
            'critical_above' => 90.0,
            'target_min' => 0.0,
            'target_max' => 75.0,
            'spike_delta' => 10.0,
        ],
        'server_temperature_c' => [
            'name' => 'Server Temperature',
            'unit' => 'deg C',
            'location' => 'Registered servers',
            'warning_above' => 70.0,
            'critical_above' => 85.0,
            'target_min' => 0.0,
            'target_max' => 65.0,
            'spike_delta' => 8.0,
        ],
        'server_network_mbps' => [
            'name' => 'Server Network Throughput',
            'unit' => 'Mbps',
            'location' => 'Registered servers',
            'target_min' => 0.0,
            'target_max' => 1000.0,
            'spike_delta' => 200.0,
        ],
    ];

    public function forPeriod(string $type, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $readings = SensorReading::query()
            ->whereBetween('recorded_at', [$periodStart, $periodEnd])
            ->orderBy('recorded_at')
            ->get();

        $serverMetrics = ServerMetric::query()
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->orderBy('created_at')
            ->get();

        return [
            'source' => 'sensor_readings_and_server_metrics',
            'period_start' => $periodStart->toIso8601String(),
            'period_end' => $periodEnd->toIso8601String(),
            'sensors' => array_merge([
                $this->buildSensorPayload('temperature', $readings),
                $this->buildSensorPayload('humidity', $readings),
            ], $this->buildServerMetricPayloads($serverMetrics)),
        ];
    }

    private function buildSensorPayload(string $key, Collection $readings): array
    {
        $definition = self::SENSOR_DEFINITIONS[$key];

        return [
            'key' => $key,
            'name' => $definition['name'],
            'unit' => $definition['unit'],
            'location' => $definition['location'],
            'thresholds' => [
                'warning_above' => $definition['warning_above'] ?? null,
                'critical_above' => $definition['critical_above'] ?? null,
                'warning_below' => $definition['warning_below'] ?? null,
                'critical_below' => $definition['critical_below'] ?? null,
                'target_min' => $definition['target_min'],
                'target_max' => $definition['target_max'],
                'spike_delta' => $definition['spike_delta'],
            ],
            'readings' => $readings
                ->filter(fn (SensorReading $reading) => $reading->{$key} !== null)
                ->map(fn (SensorReading $reading) => [
                    'recorded_at' => $reading->recorded_at->toIso8601String(),
                    'value' => round((float) $reading->{$key}, 1),
                ])
                ->values()
                ->all(),
        ];
    }

    private function buildServerMetricPayloads(Collection $serverMetrics): array
    {
        return [
            $this->buildServerMetricPayload('server_cpu_percent', $serverMetrics, fn (ServerMetric $metric): ?float => $metric->cpu_percent),
            $this->buildServerMetricPayload('server_ram_percent', $serverMetrics, function (ServerMetric $metric): ?float {
                if ((int) $metric->ram_total_mb <= 0) {
                    return null;
                }

                return ((float) $metric->ram_used_mb / (float) $metric->ram_total_mb) * 100;
            }),
            $this->buildServerMetricPayload('server_disk_percent', $serverMetrics, function (ServerMetric $metric): ?float {
                if ((float) $metric->disk_total_gb <= 0) {
                    return null;
                }

                return ((float) $metric->disk_used_gb / (float) $metric->disk_total_gb) * 100;
            }),
            $this->buildServerMetricPayload('server_temperature_c', $serverMetrics, fn (ServerMetric $metric): ?float => $metric->temperature_c),
            $this->buildServerMetricPayload('server_network_mbps', $serverMetrics, fn (ServerMetric $metric): ?float => (float) $metric->net_rx_mbps + (float) $metric->net_tx_mbps),
        ];
    }

    private function buildServerMetricPayload(string $key, Collection $serverMetrics, callable $valueResolver): array
    {
        $definition = self::SERVER_METRIC_DEFINITIONS[$key];

        return [
            'key' => $key,
            'name' => $definition['name'],
            'unit' => $definition['unit'],
            'location' => $definition['location'],
            'thresholds' => [
                'warning_above' => $definition['warning_above'] ?? null,
                'critical_above' => $definition['critical_above'] ?? null,
                'warning_below' => $definition['warning_below'] ?? null,
                'critical_below' => $definition['critical_below'] ?? null,
                'target_min' => $definition['target_min'],
                'target_max' => $definition['target_max'],
                'spike_delta' => $definition['spike_delta'],
            ],
            'readings' => $serverMetrics
                ->map(function (ServerMetric $metric) use ($valueResolver): ?array {
                    $value = $valueResolver($metric);

                    if ($value === null) {
                        return null;
                    }

                    return [
                        'recorded_at' => $metric->created_at->toIso8601String(),
                        'value' => round((float) $value, 1),
                    ];
                })
                ->filter()
                ->values()
                ->all(),
        ];
    }
}
