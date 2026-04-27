<?php

namespace App\Http\Controllers;

use App\Models\ProblemAttachment;
use App\Models\SolutionAttachment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentDownloadController extends Controller
{
    public function problem(ProblemAttachment $problemAttachment): StreamedResponse
    {
        return $this->download(
            $problemAttachment->file_path,
            $problemAttachment->original_name,
            $problemAttachment->mime_type,
            'problem-attachments/'
        );
    }

    public function solution(SolutionAttachment $solutionAttachment): StreamedResponse
    {
        return $this->download(
            $solutionAttachment->file_path,
            $solutionAttachment->original_name,
            $solutionAttachment->mime_type,
            'solution-attachments/'
        );
    }

    protected function download(string $path, string $originalName, ?string $mimeType = null, string $expectedPrefix = ''): StreamedResponse
    {
        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');
        $downloadName = trim(str_replace(["\r", "\n"], ' ', basename($originalName)));

        if ($normalizedPath === '' || ($expectedPrefix !== '' && ! str_starts_with($normalizedPath, $expectedPrefix))) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if (! Storage::disk('local')->exists($normalizedPath)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $resolvedMimeType = $mimeType ?: Storage::disk('local')->mimeType($normalizedPath);
        $headers = $resolvedMimeType ? ['Content-Type' => $resolvedMimeType] : [];

        return Storage::disk('local')->download(
            $normalizedPath,
            $downloadName !== '' ? $downloadName : 'attachment',
            $headers
        );
    }
}
