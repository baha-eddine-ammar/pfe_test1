<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use App\Services\NotificationService;
use App\Services\Reports\ReportGenerationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportGenerationService $reportGenerationService,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function index(Request $request): View
    {
        $typeFilter = $request->string('type')->toString();
        if (! in_array($typeFilter, ['daily', 'weekly', 'monthly'], true)) {
            $typeFilter = 'all';
        }

        $baseQuery = Report::query()->with(['latestAiSummary', 'generatedBy'])->latest('generated_at');
        $filteredQuery = $typeFilter === 'all'
            ? clone $baseQuery
            : (clone $baseQuery)->where('type', $typeFilter);

        $reports = $filteredQuery->paginate(9)->withQueryString();

        return view('reports.index', [
            'reports' => $reports,
            'typeFilter' => $typeFilter,
            'reportCounts' => [
                'all' => Report::query()->count(),
                'daily' => Report::query()->where('type', 'daily')->count(),
                'weekly' => Report::query()->where('type', 'weekly')->count(),
                'monthly' => Report::query()->where('type', 'monthly')->count(),
            ],
            'latestReport' => Report::query()->with('latestAiSummary')->latest('generated_at')->first(),
            'defaultReferenceDate' => now()->toDateString(),
        ]);
    }

    public function store(StoreReportRequest $request): RedirectResponse
    {
        $report = $this->reportGenerationService->generate(
            $request->string('type')->toString(),
            Carbon::parse($request->string('reference_date')->toString()),
            $request->user(),
        );

        $this->notificationService->notifyApprovedDepartmentHeads(
            'report.generated',
            ucfirst($report->type) . ' report generated',
            $report->title,
            route('reports.show', $report, false),
            ['report_id' => $report->id]
        );

        $criticalCount = (int) data_get($report->metrics_snapshot, 'overview.critical_count', 0);

        if ($criticalCount > 0) {
            $this->notificationService->notifyApprovedDepartmentHeads(
                'alert.critical',
                'Critical conditions detected',
                $criticalCount . ' critical condition(s) were found in the latest report.',
                route('reports.show', $report, false),
                ['report_id' => $report->id, 'critical_count' => $criticalCount]
            );
        }

        $message = optional($report->latestAiSummary)->status === 'success'
            ? 'Report generated successfully.'
            : 'Report generated with fallback AI summary because the AI provider was unavailable.';

        return redirect()
            ->route('reports.show', $report)
            ->with('status', $message);
    }

    public function show(Report $report): View
    {
        $report->load(['generatedBy', 'latestAiSummary', 'aiSummaries' => fn ($query) => $query->latest('generated_at')]);

        return view('reports.show', [
            'report' => $report,
        ]);
    }

    public function regenerateSummary(Report $report): RedirectResponse
    {
        $summary = $this->reportGenerationService->regenerateSummary($report);

        $message = $summary->status === 'success'
            ? 'AI summary regenerated successfully.'
            : 'AI regeneration fell back to the deterministic summary.';

        return redirect()
            ->route('reports.show', $report)
            ->with($summary->status === 'success' ? 'status' : 'warning', $message);
    }
}
