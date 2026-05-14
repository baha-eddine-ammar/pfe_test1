<?php

namespace App\Services;

use App\Models\PowerReading;
use App\Models\SensorReading;

class EnvironmentalTelemetryService
{
    private const DISPLAY_TIMEZONE = 'Africa/Tunis';

    public function latestTemperature(float $fallback = 22.4): float
    {
        $reading = SensorReading::query()
            ->latest('recorded_at')
            ->first();

        return round((float) ($reading?->temperature ?? $fallback), 1);
    }

    public function latestHumidity(float $fallback = 45.0): float
    {
        $reading = SensorReading::query()
            ->whereNotNull('humidity')
            ->latest('recorded_at')
            ->first();

        return round((float) ($reading?->humidity ?? $fallback), 1);
    }

    public function temperatureTrend(int $limit = 7): array
    {
        return SensorReading::query()
            ->latest('recorded_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (SensorReading $reading) => [
                'label' => $reading->recorded_at->copy()->timezone(self::DISPLAY_TIMEZONE)->format('H:i:s'),
                'value' => round($reading->temperature, 1),
            ])
            ->all();
    }

    public function dashboardTrend(int $limit = 60): array
    {
        $readings = SensorReading::query()
            ->latest('recorded_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $powerReadings = PowerReading::query()
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $latestReading = $readings->last();
        $latestRecordedAt = $latestReading?->recorded_at?->copy()->timezone(self::DISPLAY_TIMEZONE);
        $latestPowerReading = $powerReadings->last();

        return [
            'timezone' => self::DISPLAY_TIMEZONE,
            'hasData' => $readings->isNotEmpty(),
            'labels' => $readings
                ->map(fn (SensorReading $reading) => $reading->recorded_at->copy()->timezone(self::DISPLAY_TIMEZONE)->format('H:i:s'))
                ->all(),
            'timestamps' => $readings
                ->map(fn (SensorReading $reading) => $reading->recorded_at->copy()->timezone(self::DISPLAY_TIMEZONE)->toIso8601String())
                ->all(),
            'tooltipLabels' => $readings
                ->map(fn (SensorReading $reading) => $reading->recorded_at->copy()->timezone(self::DISPLAY_TIMEZONE)->format('d M Y, H:i:s'))
                ->all(),
            'temperature' => $readings
                ->map(fn (SensorReading $reading) => round($reading->temperature, 1))
                ->all(),
            'humidity' => $readings
                ->map(fn (SensorReading $reading) => $reading->humidity === null ? null : round((float) $reading->humidity, 1))
                ->all(),
            'power' => $powerReadings
                ->map(fn (PowerReading $reading) => round((float) $reading->power_w, 2))
                ->all(),
            'powerLabels' => $powerReadings
                ->map(fn (PowerReading $reading) => $reading->created_at->copy()->timezone(self::DISPLAY_TIMEZONE)->format('H:i:s'))
                ->all(),
            'powerTimestamps' => $powerReadings
                ->map(fn (PowerReading $reading) => $reading->created_at->copy()->timezone(self::DISPLAY_TIMEZONE)->toIso8601String())
                ->all(),
            'latest' => [
                'temperature' => $latestReading ? round($latestReading->temperature, 1) : null,
                'humidity' => $latestReading && $latestReading->humidity !== null ? round((float) $latestReading->humidity, 1) : null,
                'power' => $latestPowerReading ? round((float) $latestPowerReading->power_w, 2) : null,
                'voltage' => $latestPowerReading ? round((float) $latestPowerReading->voltage_v, 2) : null,
                'current' => $latestPowerReading ? round((float) $latestPowerReading->current_a, 3) : null,
                'power_reading' => $latestPowerReading ? [
                    'device_id' => $latestPowerReading->device_id,
                    'voltage_v' => round((float) $latestPowerReading->voltage_v, 2),
                    'current_a' => round((float) $latestPowerReading->current_a, 3),
                    'power_w' => round((float) $latestPowerReading->power_w, 2),
                    'energy_kwh' => $latestPowerReading->energy_kwh === null ? null : round((float) $latestPowerReading->energy_kwh, 4),
                    'frequency_hz' => $latestPowerReading->frequency_hz === null ? null : round((float) $latestPowerReading->frequency_hz, 2),
                    'power_factor' => $latestPowerReading->power_factor === null ? null : round((float) $latestPowerReading->power_factor, 3),
                    'created_at' => $latestPowerReading->created_at?->copy()->timezone(self::DISPLAY_TIMEZONE)->toIso8601String(),
                ] : null,
                'recordedAt' => $latestRecordedAt?->toIso8601String(),
                'label' => $latestRecordedAt?->format('H:i:s'),
                'fullLabel' => $latestRecordedAt?->format('d M Y, H:i:s'),
            ],
            'lastUpdatedLabel' => $latestRecordedAt
                ? 'Last sensor update '.$latestRecordedAt->format('H:i:s').' Tunisia time'
                : 'Waiting for ESP32 readings',
        ];
    }
}
