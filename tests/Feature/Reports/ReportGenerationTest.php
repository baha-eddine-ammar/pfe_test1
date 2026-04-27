<?php

namespace Tests\Feature\Reports;

use App\Models\Report;
use App\Models\Server;
use App\Models\User;
use App\Services\Reports\ReportGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_report_generation_still_succeeds_when_telegram_is_unreachable(): void
    {
        config()->set('services.telegram.bot_token', 'telegram-test-token');
        Http::fake(fn () => Http::failedConnection('Telegram API is unavailable.'));

        $user = User::factory()->create();

        User::factory()->create([
            'role' => 'department_head',
            'status' => 'approved',
            'is_approved' => true,
            'telegram_chat_id' => '123456789',
        ]);

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

    public function test_reports_include_real_server_metrics(): void
    {
        $user = User::factory()->create();
        $server = Server::query()->create([
            'name' => 'SRV-APP-01',
            'identifier' => 'srv-app-01',
            'api_token' => 'secret-token',
        ]);

        $server->metrics()->create([
            'cpu_percent' => 91.5,
            'ram_used_mb' => 7800,
            'ram_total_mb' => 8000,
            'disk_used_gb' => 450,
            'disk_total_gb' => 500,
            'net_rx_mbps' => 10,
            'net_tx_mbps' => 5,
            'temperature_c' => 72,
            'created_at' => '2026-03-12 10:15:00',
        ]);

        $report = app(ReportGenerationService::class)->generate('daily', now()->setDate(2026, 3, 12), $user);

        $this->assertSame('sensor_readings_and_server_metrics', $report->source);
        $this->assertContains(
            'server_cpu_percent',
            collect($report->metrics_snapshot['metrics'])->pluck('key')->all()
        );
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
