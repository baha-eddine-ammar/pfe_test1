<?php

namespace App\Services;

class PredictiveMaintenanceService
{
    public function insightsForSnapshot(array $snapshot): array
    {
        $metrics = collect($snapshot['metrics'] ?? []);
        $insights = [];

        foreach ($metrics as $metric) {
            $key = $metric['key'] ?? null;
            $trend = $metric['trend_direction'] ?? 'stable';
            $latestStatus = $metric['latest_status'] ?? 'Stable';
            $averageValue = (float) ($metric['average_value'] ?? 0);
            $latestValue = (float) ($metric['latest_value'] ?? 0);

            if ($key === 'temperature' && in_array($trend, ['rising'], true)) {
                $insights[] = [
                    'severity' => $latestStatus === 'Critical' ? 'Critical' : 'Warning',
                    'title' => 'Cooling efficiency decreasing',
                    'message' => 'Temperature trend is rising across the reporting window. Inspect airflow paths, rack ventilation, and cooling redundancy within 7 days.',
                    'metric' => 'Temperature',
                    'recommended_action' => 'Schedule preventive cooling inspection',
                ];
            }

            if ($key === 'humidity' && ($latestStatus !== 'Stable' || $trend !== 'stable')) {
                $insights[] = [
                    'severity' => $latestStatus === 'Critical' ? 'Critical' : 'Warning',
                    'title' => 'Humidity stability drifting',
                    'message' => 'Humidity is moving away from the target band. Validate room sealing, humidification controls, and condensation risk around the monitored zone.',
                    'metric' => 'Humidity',
                    'recommended_action' => 'Check environmental controls and sensor placement',
                ];
            }

            if ($latestStatus === 'Critical' && $averageValue > 0) {
                $insights[] = [
                    'severity' => 'Critical',
                    'title' => 'Immediate preventive intervention required',
                    'message' => sprintf(
                        '%s reached a critical operating state with a latest reading of %.1f. Escalate this item and create a maintenance task immediately.',
                        $metric['name'] ?? 'Sensor',
                        $latestValue
                    ),
                    'metric' => $metric['name'] ?? 'Sensor',
                    'recommended_action' => 'Escalate to urgent maintenance',
                ];
            }
        }

        return collect($insights)
            ->unique(fn (array $insight) => $insight['title'].'|'.$insight['metric'])
            ->values()
            ->all();
    }
}
