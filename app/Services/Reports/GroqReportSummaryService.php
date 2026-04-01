<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GroqReportSummaryService
{
    public function generate(array $snapshot): array
    {
        $provider = 'groq';
        $baseUrl = config('services.groq.base_url');
        $model = config('services.groq.model');
        $apiKey = config('services.groq.api_key');

        if (! $baseUrl || ! $model || ! $apiKey) {
            return $this->fallback($snapshot, 'Missing Groq configuration.');
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->withToken($apiKey)
                ->acceptJson()
                ->timeout(20)
                ->post('/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.2,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You generate concise operational reports. Use only the numbers you are given. Return valid JSON with keys: summary, observations, recommendations. Observations and recommendations must each be arrays of short strings.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Create a concise operational sensor report from this structured metrics payload. Do not invent numbers.\n\n".json_encode($this->promptPayload($snapshot), JSON_PRETTY_PRINT),
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                return $this->fallback($snapshot, 'Groq request failed with status '.$response->status().'.');
            }

            $content = data_get($response->json(), 'choices.0.message.content');
            $parsed = $this->decodeSummary($content);

            if (! $parsed) {
                return $this->fallback($snapshot, 'Groq response could not be parsed.');
            }

            return [
                'provider' => $provider,
                'model' => $model,
                'status' => 'success',
                'summary_text' => trim((string) ($parsed['summary'] ?? '')),
                'observations' => array_values(array_filter($parsed['observations'] ?? [])),
                'recommendations' => array_values(array_filter($parsed['recommendations'] ?? [])),
                'error_message' => null,
            ];
        } catch (Throwable $exception) {
            return $this->fallback($snapshot, $exception->getMessage());
        }
    }

    private function promptPayload(array $snapshot): array
    {
        return [
            'type' => $snapshot['type'],
            'period_start' => $snapshot['period_start'],
            'period_end' => $snapshot['period_end'],
            'overview' => $snapshot['overview'],
            'metrics' => array_map(function (array $metric): array {
                return [
                    'name' => $metric['name'],
                    'unit' => $metric['unit'],
                    'average_value' => $metric['average_value'],
                    'min_value' => $metric['min_value'],
                    'max_value' => $metric['max_value'],
                    'latest_value' => $metric['latest_value'],
                    'latest_status' => $metric['latest_status'],
                    'warning_count' => $metric['warning_count'],
                    'critical_count' => $metric['critical_count'],
                    'anomaly_count' => $metric['anomaly_count'],
                    'trend_direction' => $metric['trend_direction'],
                ];
            }, $snapshot['metrics']),
            'anomalies' => array_slice($snapshot['anomalies'], 0, 5),
        ];
    }

    private function decodeSummary(?string $content): ?array
    {
        if (! $content) {
            return null;
        }

        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $content, $matches) !== 1) {
            return null;
        }

        $decoded = json_decode($matches[0], true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            ? $decoded
            : null;
    }

    private function fallback(array $snapshot, string $reason): array
    {
        $metrics = collect($snapshot['metrics']);
        $topRiskMetrics = $metrics
            ->sortByDesc(fn (array $metric) => ($metric['critical_count'] * 2) + $metric['warning_count'] + $metric['anomaly_count'])
            ->take(2)
            ->values();

        $observations = $metrics->map(function (array $metric): string {
            return sprintf(
                '%s averaged %s%s with %d warning readings and %d critical readings.',
                $metric['name'],
                $metric['average_value'],
                $metric['unit'],
                $metric['warning_count'],
                $metric['critical_count']
            );
        })->take(4)->all();

        $recommendations = $topRiskMetrics->map(function (array $metric): string {
            $action = $metric['critical_count'] > 0
                ? 'Schedule immediate investigation and verify threshold breaches.'
                : 'Review warning behaviour and monitor for persistent drift.';

            return sprintf('%s is the priority metric. %s', $metric['name'], $action);
        })->all();

        if ($recommendations === []) {
            $recommendations[] = 'No significant risk surfaced in the selected period. Keep the monitoring cadence unchanged.';
        }

        $summary = sprintf(
            '%s report for %s to %s. %d sensors were analyzed across %d readings. %d warning readings, %d critical readings, and %d anomalies were detected. %s',
            Str::headline($snapshot['type']),
            \Carbon\Carbon::parse($snapshot['period_start'])->format('M d, Y H:i'),
            \Carbon\Carbon::parse($snapshot['period_end'])->format('M d, Y H:i'),
            $snapshot['overview']['sensor_count'],
            $snapshot['overview']['reading_count'],
            $snapshot['overview']['warning_count'],
            $snapshot['overview']['critical_count'],
            $snapshot['overview']['anomaly_count'],
            $topRiskMetrics->isNotEmpty()
                ? $topRiskMetrics->map(fn (array $metric) => $metric['name'].' requires the closest attention.')->implode(' ')
                : 'All tracked metrics remained stable.'
        );

        return [
            'provider' => 'fallback',
            'model' => 'deterministic',
            'status' => 'fallback',
            'summary_text' => $summary,
            'observations' => $observations,
            'recommendations' => $recommendations,
            'error_message' => $reason,
        ];
    }
}
