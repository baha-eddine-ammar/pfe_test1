<?php

namespace App\Services\Reports;

use App\Contracts\Reports\SensorDataProvider;
use Carbon\CarbonImmutable;

class FakeSensorDataProvider implements SensorDataProvider
{
    private const SENSOR_DEFINITIONS = [
        [
            'key' => 'temperature',
            'name' => 'Temperature',
            'unit' => '°C',
            'location' => 'Rack Corridor',
            'base' => 22.8,
            'amplitude' => 1.8,
            'noise' => 1.0,
            'wave' => 4.0,
            'lower_bound' => 18.0,
            'upper_bound' => 33.5,
            'warning_above' => 25.0,
            'critical_above' => 30.0,
            'spike_delta' => 2.5,
            'spike_chance' => 7,
            'spike_magnitude' => 5.6,
            'spike_direction' => 'up',
        ],
        [
            'key' => 'humidity',
            'name' => 'Humidity',
            'unit' => '%',
            'location' => 'Cooling Intake',
            'base' => 48.0,
            'amplitude' => 5.4,
            'noise' => 2.4,
            'wave' => 5.0,
            'lower_bound' => 35.0,
            'upper_bound' => 76.0,
            'warning_above' => 60.0,
            'critical_above' => 70.0,
            'spike_delta' => 6.5,
            'spike_chance' => 8,
            'spike_magnitude' => 11.0,
            'spike_direction' => 'up',
        ],
        [
            'key' => 'air_flow',
            'name' => 'Air Flow',
            'unit' => 'm/s',
            'location' => 'Ventilation Duct',
            'base' => 5.8,
            'amplitude' => 0.55,
            'noise' => 0.4,
            'wave' => 6.0,
            'lower_bound' => 2.2,
            'upper_bound' => 8.0,
            'warning_below' => 4.0,
            'critical_below' => 3.0,
            'spike_delta' => 1.3,
            'spike_chance' => 6,
            'spike_magnitude' => 2.1,
            'spike_direction' => 'down',
        ],
        [
            'key' => 'power_usage',
            'name' => 'Power Usage',
            'unit' => '%',
            'location' => 'UPS Zone',
            'base' => 62.0,
            'amplitude' => 7.0,
            'noise' => 3.2,
            'wave' => 5.5,
            'lower_bound' => 30.0,
            'upper_bound' => 94.0,
            'warning_above' => 70.0,
            'critical_above' => 85.0,
            'spike_delta' => 8.0,
            'spike_chance' => 8,
            'spike_magnitude' => 14.0,
            'spike_direction' => 'up',
        ],
    ];

    public function forPeriod(string $type, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        return [
            'source' => 'demo',
            'period_start' => $periodStart->toIso8601String(),
            'period_end' => $periodEnd->toIso8601String(),
            'sensors' => array_map(
                fn (array $sensor) => [
                    'key' => $sensor['key'],
                    'name' => $sensor['name'],
                    'unit' => $sensor['unit'],
                    'location' => $sensor['location'],
                    'thresholds' => [
                        'warning_above' => $sensor['warning_above'] ?? null,
                        'critical_above' => $sensor['critical_above'] ?? null,
                        'warning_below' => $sensor['warning_below'] ?? null,
                        'critical_below' => $sensor['critical_below'] ?? null,
                        'target_min' => $sensor['lower_bound'],
                        'target_max' => $sensor['upper_bound'],
                        'spike_delta' => $sensor['spike_delta'],
                    ],
                    'readings' => $this->generateReadings($sensor, $type, $periodStart, $periodEnd),
                ],
                self::SENSOR_DEFINITIONS
            ),
        ];
    }

    private function generateReadings(array $sensor, string $type, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array
    {
        $interval = match ($type) {
            'daily' => 15,
            'weekly' => 120,
            'monthly' => 360,
            default => 60,
        };

        $readings = [];
        $cursor = $periodStart;
        $index = 0;
        $reportSeed = crc32($sensor['key'].'-'.$type.'-'.$periodStart->format('Ymd'));
        $phase = (($reportSeed % 100) / 100) * 3.14159;

        while ($cursor->lte($periodEnd)) {
            $value = $sensor['base']
                + sin(($index / max(2, $sensor['wave'])) + $phase) * $sensor['amplitude']
                + cos(($index / max(2, $sensor['wave'] / 2)) + ($phase / 2)) * ($sensor['amplitude'] * 0.34)
                + $this->deterministicNoise($sensor['key'], $type, $index, $sensor['noise']);

            if ($this->shouldSpike($sensor['key'], $type, $index, $sensor['spike_chance'])) {
                $value += $sensor['spike_direction'] === 'down'
                    ? -$sensor['spike_magnitude']
                    : $sensor['spike_magnitude'];
            }

            $value = max($sensor['lower_bound'], min($sensor['upper_bound'], $value));

            $readings[] = [
                'recorded_at' => $cursor->toIso8601String(),
                'value' => round($value, 1),
            ];

            $cursor = $cursor->addMinutes($interval);
            $index++;
        }

        return $readings;
    }

    private function deterministicNoise(string $sensorKey, string $type, int $index, float $noise): float
    {
        $hash = crc32($sensorKey.'-'.$type.'-'.$index);
        $scale = (($hash % 1000) / 1000) - 0.5;

        return $scale * $noise;
    }

    private function shouldSpike(string $sensorKey, string $type, int $index, int $chance): bool
    {
        $hash = crc32('spike-'.$sensorKey.'-'.$type.'-'.$index);

        return ($hash % 100) < $chance;
    }
}
