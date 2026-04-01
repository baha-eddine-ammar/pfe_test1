<?php

namespace App\Services\Reports;

use Carbon\CarbonImmutable;

class ReportMetricsCalculator
{
    public function calculate(string $type, CarbonImmutable $periodStart, CarbonImmutable $periodEnd, array $dataset): array
    {
        $metrics = [];
        $anomalies = [];
        $totalReadings = 0;
        $warningCount = 0;
        $criticalCount = 0;

        foreach ($dataset['sensors'] as $sensor) {
            $values = array_map(fn (array $reading) => (float) $reading['value'], $sensor['readings']);
            $readingsCount = count($values);
            $totalReadings += $readingsCount;

            $sensorWarningCount = 0;
            $sensorCriticalCount = 0;
            $sensorAnomalies = [];
            $previousValue = null;

            foreach ($sensor['readings'] as $reading) {
                $value = (float) $reading['value'];
                $status = $this->classify($value, $sensor['thresholds']);

                if ($status === 'Warning') {
                    $sensorWarningCount++;
                    $warningCount++;
                }

                if ($status === 'Critical') {
                    $sensorCriticalCount++;
                    $criticalCount++;
                }

                if ($previousValue !== null) {
                    $delta = abs($value - $previousValue);
                    if ($delta >= ($sensor['thresholds']['spike_delta'] ?? INF)) {
                        $sensorAnomalies[] = [
                            'sensor_key' => $sensor['key'],
                            'sensor_name' => $sensor['name'],
                            'severity' => $status === 'Critical' ? 'Critical' : 'Warning',
                            'reason' => 'Spike detected',
                            'value' => round($value, 1),
                            'delta' => round($delta, 1),
                            'unit' => $sensor['unit'],
                            'recorded_at' => $reading['recorded_at'],
                            'location' => $sensor['location'],
                        ];
                    }
                }

                $previousValue = $value;
            }

            $latestValue = $values[$readingsCount - 1] ?? 0;
            $latestStatus = $this->classify($latestValue, $sensor['thresholds']);
            $metrics[] = [
                'key' => $sensor['key'],
                'name' => $sensor['name'],
                'unit' => $sensor['unit'],
                'location' => $sensor['location'],
                'thresholds' => $sensor['thresholds'],
                'reading_count' => $readingsCount,
                'average_value' => $readingsCount > 0 ? round(array_sum($values) / $readingsCount, 1) : 0,
                'min_value' => $readingsCount > 0 ? round(min($values), 1) : 0,
                'max_value' => $readingsCount > 0 ? round(max($values), 1) : 0,
                'latest_value' => round($latestValue, 1),
                'latest_status' => $latestStatus,
                'warning_count' => $sensorWarningCount,
                'critical_count' => $sensorCriticalCount,
                'anomaly_count' => count($sensorAnomalies),
                'trend_direction' => $this->trendDirection($values),
            ];

            $anomalies = array_merge($anomalies, $sensorAnomalies);
        }

        usort($anomalies, function (array $left, array $right): int {
            if ($left['severity'] !== $right['severity']) {
                return $left['severity'] === 'Critical' ? -1 : 1;
            }

            return strcmp($right['recorded_at'], $left['recorded_at']);
        });

        return [
            'type' => $type,
            'source' => $dataset['source'],
            'period_start' => $periodStart->toIso8601String(),
            'period_end' => $periodEnd->toIso8601String(),
            'overview' => [
                'sensor_count' => count($dataset['sensors']),
                'reading_count' => $totalReadings,
                'warning_count' => $warningCount,
                'critical_count' => $criticalCount,
                'anomaly_count' => count($anomalies),
                'latest_recorded_at' => collect($dataset['sensors'])
                    ->flatMap(fn (array $sensor) => $sensor['readings'])
                    ->max('recorded_at'),
            ],
            'metrics' => $metrics,
            'anomalies' => array_slice($anomalies, 0, 12),
        ];
    }

    private function classify(float $value, array $thresholds): string
    {
        if (($thresholds['critical_above'] ?? null) !== null && $value > $thresholds['critical_above']) {
            return 'Critical';
        }

        if (($thresholds['warning_above'] ?? null) !== null && $value > $thresholds['warning_above']) {
            return 'Warning';
        }

        if (($thresholds['critical_below'] ?? null) !== null && $value < $thresholds['critical_below']) {
            return 'Critical';
        }

        if (($thresholds['warning_below'] ?? null) !== null && $value < $thresholds['warning_below']) {
            return 'Warning';
        }

        return 'Stable';
    }

    private function trendDirection(array $values): string
    {
        if (count($values) < 4) {
            return 'stable';
        }

        $windowSize = max(1, intdiv(count($values), 4));
        $startWindow = array_slice($values, 0, $windowSize);
        $endWindow = array_slice($values, -$windowSize);
        $startAverage = array_sum($startWindow) / count($startWindow);
        $endAverage = array_sum($endWindow) / count($endWindow);

        if ($endAverage - $startAverage > 0.75) {
            return 'rising';
        }

        if ($startAverage - $endAverage > 0.75) {
            return 'falling';
        }

        return 'stable';
    }
}
