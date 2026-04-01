<?php

namespace App\Services\Reports;

use App\Contracts\Reports\SensorDataProvider;
use App\Models\Report;
use App\Models\ReportAiSummary;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportGenerationService
{
    public function __construct(
        private readonly SensorDataProvider $sensorDataProvider,
        private readonly ReportMetricsCalculator $calculator,
        private readonly GroqReportSummaryService $aiSummaryService,
    ) {
    }

    public function generate(string $type, Carbon $referenceDate, ?User $user = null): Report
    {
        [$periodStart, $periodEnd] = $this->resolvePeriod($type, CarbonImmutable::instance($referenceDate));
        $dataset = $this->sensorDataProvider->forPeriod($type, $periodStart, $periodEnd);
        $snapshot = $this->calculator->calculate($type, $periodStart, $periodEnd, $dataset);

        return DB::transaction(function () use ($type, $periodStart, $periodEnd, $dataset, $snapshot, $user): Report {
            $report = Report::query()->create([
                'type' => $type,
                'source' => $dataset['source'],
                'title' => $this->titleFor($type, $periodStart, $periodEnd),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => 'generated',
                'generated_by' => $user?->id,
                'generated_at' => now(),
                'metrics_snapshot' => $snapshot,
                'anomalies' => $snapshot['anomalies'],
            ]);

            $summaryData = $this->aiSummaryService->generate($snapshot);
            $this->persistSummary($report, $summaryData);

            return $report->load(['latestAiSummary', 'generatedBy']);
        });
    }

    public function regenerateSummary(Report $report): ReportAiSummary
    {
        $summaryData = $this->aiSummaryService->generate($report->metrics_snapshot);

        return DB::transaction(function () use ($report, $summaryData): ReportAiSummary {
            $summary = $this->persistSummary($report, $summaryData);

            return $summary->refresh();
        });
    }

    private function persistSummary(Report $report, array $summaryData): ReportAiSummary
    {
        $summary = $report->aiSummaries()->create([
            'provider' => $summaryData['provider'],
            'model' => $summaryData['model'],
            'status' => $summaryData['status'],
            'summary_text' => $summaryData['summary_text'],
            'observations' => $summaryData['observations'],
            'recommendations' => $summaryData['recommendations'],
            'error_message' => $summaryData['error_message'],
            'generated_at' => now(),
        ]);

        $report->forceFill([
            'summary' => $summaryData['summary_text'],
        ])->save();

        return $summary;
    }

    private function resolvePeriod(string $type, CarbonImmutable $referenceDate): array
    {
        $anchor = $referenceDate->setTime(12, 0);

        return match ($type) {
            'daily' => [
                $anchor->startOfDay(),
                $anchor->endOfDay(),
            ],
            'weekly' => [
                $anchor->startOfWeek(CarbonImmutable::MONDAY),
                $anchor->endOfWeek(CarbonImmutable::SUNDAY),
            ],
            'monthly' => [
                $anchor->startOfMonth(),
                $anchor->endOfMonth(),
            ],
            default => [
                $anchor->startOfDay(),
                $anchor->endOfDay(),
            ],
        };
    }

    private function titleFor(string $type, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): string
    {
        return sprintf(
            '%s Sensor Report - %s to %s',
            Str::headline($type),
            $periodStart->format('M d, Y'),
            $periodEnd->format('M d, Y')
        );
    }
}
