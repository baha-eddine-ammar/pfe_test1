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
            $problemAttachment->mime_type
        );
    }

    public function solution(SolutionAttachment $solutionAttachment): StreamedResponse
    {
        return $this->download(
            $solutionAttachment->file_path,
            $solutionAttachment->original_name,
            $solutionAttachment->mime_type
        );
    }

    protected function download(string $path, string $originalName, ?string $mimeType = null): StreamedResponse
    {
        foreach (['local', 'public'] as $disk) {
            if (! Storage::disk($disk)->exists($path)) {
                continue;
            }

            $headers = $mimeType ? ['Content-Type' => $mimeType] : [];

            return Storage::disk($disk)->download($path, $originalName, $headers);
        }

        abort(Response::HTTP_NOT_FOUND);
    }
}
