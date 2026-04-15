<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use App\Models\Solution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $storedPath,
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('problems.show', $problem)
            ->with('success', 'Solution submitted successfully.');
    }
}
