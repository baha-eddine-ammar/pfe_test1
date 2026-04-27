<?php

namespace App\Services\Reports;

use App\Events\ReportGenerated;
use App\Contracts\Reports\SensorDataProvider;
use App\Models\Report;
use App\Models\ReportAiSummary;
use App\Services\PredictiveMaintenanceService;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\TelegramService;
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
        private readonly TelegramService $telegramService,
        private readonly AuditLogService $auditLogService,
        private readonly PredictiveMaintenanceService $predictiveMaintenanceService,
    ) {
    }

    public function generate(string $type, Carbon $referenceDate, ?User $user = null): Report
    {
        [$periodStart, $periodEnd] = $this->resolvePeriod($type, CarbonImmutable::instance($referenceDate));
        $dataset = $this->sensorDataProvider->forPeriod($type, $periodStart, $periodEnd);
        $snapshot = $this->calculator->calculate($type, $periodStart, $periodEnd, $dataset);
        $snapshot['predictive_insights'] = $this->predictiveMaintenanceService->insightsForSnapshot($snapshot);

        $report = DB::transaction(function () use ($type, $periodStart, $periodEnd, $dataset, $snapshot, $user): Report {
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

        $criticalCount = (int) ($snapshot['overview']['critical_count'] ?? 0);

        if ($criticalCount > 0) {
            $message = "Critical alert\n"
                ."Report: {$report->title}\n"
                ."Critical count: {$criticalCount}\n"
                ."Generated at: {$report->generated_at?->format('Y-m-d H:i:s')}";

            User::query()
                ->departmentHeads()
                ->approved()
                ->whereNotNull('telegram_chat_id')
                ->get()
                ->each(function (User $departmentHead) use ($message): void {
                    $this->telegramService->sendMessage($departmentHead->telegram_chat_id, $message);
                });
        }

        $this->auditLogService->record('report.generated', $report, [
            'type' => $report->type,
            'source' => $report->source,
                'critical_count' => $criticalCount,
                'warning_count' => (int) ($snapshot['overview']['warning_count'] ?? 0),
                'reading_count' => (int) ($snapshot['overview']['reading_count'] ?? 0),
                'predictive_insights' => count($snapshot['predictive_insights'] ?? []),
            ], $user);

        ReportGenerated::dispatch($report);

        return $report;
    }

    public function regenerateSummary(Report $report): ReportAiSummary
    {
        $summaryData = $this->aiSummaryService->generate($report->metrics_snapshot);

        return DB::transaction(function () use ($report, $summaryData): ReportAiSummary {
            $summary = $this->persistSummary($report, $summaryData);

            $this->auditLogService->record('report.summary.regenerated', $report, [
                'summary_id' => $summary->id,
                'provider' => $summary->provider,
                'model' => $summary->model,
                'status' => $summary->status,
            ]);

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
