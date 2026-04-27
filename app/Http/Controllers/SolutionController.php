<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use App\Models\Solution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SolutionController extends Controller
{
    public function index(): View
    {
        $solutions = Solution::with(['user', 'problem'])
            ->withCount('attachments')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('solutions.index', [
            'solutions' => $solutions,
        ]);
    }

    public function store(Request $request, Problem $problem): RedirectResponse
    {
        $request->merge([
            'body' => trim((string) $request->input('body', '')),
        ]);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,txt,png,jpg,jpeg,zip', 'max:10240'],
        ]);

        DB::transaction(function () use ($request, $problem, $validated) {
            $solution = $problem->solutions()->create([
                'user_id' => $request->user()->id,
                'body' => $validated['body'],
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $storedPath = $file->store('solution-attachments', 'local');

                    $solution->attachments()->create([
                        'original_name' => $this->safeAttachmentName($file),
                        'file_path' => $storedPath,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('problems.show', $problem)
            ->with('success', 'Solution submitted successfully.');
    }

    protected function safeAttachmentName(UploadedFile $file): string
    {
        $name = trim(str_replace(["\r", "\n"], ' ', basename((string) $file->getClientOriginalName())));
        $name = preg_replace('/[^\w.\- ]+/u', '_', $name) ?: 'attachment';
        $extension = trim((string) $file->getClientOriginalExtension());

        if ($extension !== '' && ! str_contains($name, '.')) {
            $name .= '.'.$extension;
        }

        return Str::limit($name, 180, '');
    }
}
