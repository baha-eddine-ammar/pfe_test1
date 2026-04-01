<?php

namespace Tests\Feature\Reports;

use App\Models\Report;
use App\Models\User;
use App\Services\Reports\ReportGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_generate_a_report(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('reports.store'), [
                'type' => 'daily',
                'reference_date' => '2026-03-12',
            ]);

        $report = Report::query()->with('latestAiSummary')->first();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('reports.show', $report));

        $this->assertNotNull($report);
        $this->assertSame('daily', $report->type);
        $this->assertNotEmpty($report->summary);
        $this->assertNotNull($report->latestAiSummary);
    }

    public function test_report_detail_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $report = app(ReportGenerationService::class)->generate('weekly', now(), $user);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.show', $report));

        $response
            ->assertOk()
            ->assertSeeText($report->title)
            ->assertSeeText('Executive Summary')
            ->assertSeeText('Computed Sensor Statistics');
    }

    public function test_ai_summary_can_be_regenerated(): void
    {
        $user = User::factory()->create();
        $report = app(ReportGenerationService::class)->generate('monthly', now(), $user);
        $initialSummaryCount = $report->aiSummaries()->count();

        $response = $this
            ->actingAs($user)
            ->post(route('reports.regenerate-summary', $report));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('reports.show', $report));

        $this->assertSame($initialSummaryCount + 1, $report->fresh()->aiSummaries()->count());
    }
}
