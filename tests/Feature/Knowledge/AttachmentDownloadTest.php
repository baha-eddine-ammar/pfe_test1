<?php

namespace Tests\Feature\Knowledge;

use App\Models\Problem;
use App\Models\ProblemAttachment;
use App\Models\Solution;
use App\Models\SolutionAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_download_problem_attachments_through_the_protected_route(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $problem = Problem::query()->create([
            'user_id' => $user->id,
            'title' => 'Network issue',
            'description' => 'A switch is unreachable.',
            'status' => 'open',
        ]);

        $path = UploadedFile::fake()->create('network-report.txt', 12, 'text/plain')
            ->store('problem-attachments', 'local');

        $attachment = ProblemAttachment::query()->create([
            'problem_id' => $problem->id,
            'original_name' => 'network-report.txt',
            'file_path' => $path,
            'mime_type' => 'text/plain',
            'file_size' => Storage::disk('local')->size($path),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('problems.attachments.download', $attachment));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=network-report.txt');
    }

    public function test_authenticated_users_can_download_solution_attachments_through_the_protected_route(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $problem = Problem::query()->create([
            'user_id' => $user->id,
            'title' => 'Cooling issue',
            'description' => 'Airflow seems low.',
            'status' => 'open',
        ]);

        $solution = Solution::query()->create([
            'problem_id' => $problem->id,
            'user_id' => $user->id,
            'body' => 'Inspect and clean the intake vents.',
        ]);

        $path = UploadedFile::fake()->create('repair-note.txt', 8, 'text/plain')
            ->store('solution-attachments', 'local');

        $attachment = SolutionAttachment::query()->create([
            'solution_id' => $solution->id,
            'original_name' => 'repair-note.txt',
            'file_path' => $path,
            'mime_type' => 'text/plain',
            'file_size' => Storage::disk('local')->size($path),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('solutions.attachments.download', $attachment));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=repair-note.txt');
    }

    public function test_guests_cannot_access_attachment_download_routes(): void
    {
        $user = User::factory()->create();
        $problem = Problem::query()->create([
            'user_id' => $user->id,
            'title' => 'UPS issue',
            'description' => 'Load is fluctuating.',
            'status' => 'open',
        ]);

        $attachment = ProblemAttachment::query()->create([
            'problem_id' => $problem->id,
            'original_name' => 'secret.txt',
            'file_path' => 'problem-attachments/secret.txt',
        ]);

        $response = $this->get(route('problems.attachments.download', $attachment));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_problem_attachment_download_rejects_files_outside_local_attachment_storage(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $problem = Problem::query()->create([
            'user_id' => $user->id,
            'title' => 'Network issue',
            'description' => 'A switch is unreachable.',
            'status' => 'open',
        ]);

        Storage::disk('public')->put('problem-attachments/public-only.txt', 'public content');

        $attachment = ProblemAttachment::query()->create([
            'problem_id' => $problem->id,
            'original_name' => 'public-only.txt',
            'file_path' => 'problem-attachments/public-only.txt',
            'mime_type' => 'text/plain',
            'file_size' => 14,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('problems.attachments.download', $attachment));

        $response->assertNotFound();
    }
}
