<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceTask;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $month = $this->resolveMonth($request->query('month'));
        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $maintenanceTasks = MaintenanceTask::query()
            ->with(['assignedToUser', 'createdByUser'])
            ->when($request->user()->isStaff(), function ($query) use ($request) {
                $query->where('assigned_to_user_id', $request->user()->id);
            })
            ->whereBetween('maintenance_date', [$gridStart->copy()->startOfDay(), $gridEnd->copy()->endOfDay()])
            ->orderBy('maintenance_date')
            ->get();

        $reports = Report::query()
            ->with('generatedBy')
            ->whereBetween('generated_at', [$gridStart->copy()->startOfDay(), $gridEnd->copy()->endOfDay()])
            ->orderBy('generated_at')
            ->get();

        $eventsByDate = collect();

        foreach ($maintenanceTasks as $task) {
            $dateKey = $task->maintenance_date->toDateString();
            $eventsByDate[$dateKey] = collect($eventsByDate[$dateKey] ?? [])->push([
                'title' => $task->server_room,
                'subtitle' => 'Maintenance',
                'time' => $task->maintenance_date->format('H:i'),
                'url' => route('maintenance.show', $task),
                'tone' => match ($task->priority) {
                    MaintenanceTask::PRIORITY_URGENT => 'critical',
                    MaintenanceTask::PRIORITY_HIGH => 'warning',
                    default => 'info',
                },
            ])->all();
        }

        foreach ($reports as $report) {
            $dateKey = $report->generated_at?->toDateString();

            if (! $dateKey) {
                continue;
            }

            $eventsByDate[$dateKey] = collect($eventsByDate[$dateKey] ?? [])->push([
                'title' => ucfirst($report->type).' report',
                'subtitle' => 'Report',
                'time' => $report->generated_at->format('H:i'),
                'url' => route('reports.show', $report),
                'tone' => 'success',
            ])->all();
        }

        return view('calendar.index', [
            'month' => $month,
            'weeks' => $this->buildWeeks($gridStart, $gridEnd, $month, $eventsByDate),
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }

    protected function resolveMonth(?string $input): Carbon
    {
        if (filled($input)) {
            try {
                return Carbon::createFromFormat('Y-m', $input)->startOfMonth();
            } catch (\Throwable) {
                // Fall through to current month.
            }
        }

        return now()->startOfMonth();
    }

    protected function buildWeeks(Carbon $gridStart, Carbon $gridEnd, Carbon $month, Collection $eventsByDate): array
    {
        $cursor = $gridStart->copy();
        $weeks = [];

        while ($cursor->lte($gridEnd)) {
            $week = [];

            for ($day = 0; $day < 7; $day++) {
                $date = $cursor->copy();
                $week[] = [
                    'date' => $date,
                    'isCurrentMonth' => $date->month === $month->month,
                    'isToday' => $date->isToday(),
                    'events' => collect($eventsByDate[$date->toDateString()] ?? []),
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }
}
