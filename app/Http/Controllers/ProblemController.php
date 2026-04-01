<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProblemController extends Controller
{
    public function index(): View
    {
        $problems = Problem::with('user')
            ->withCount(['attachments', 'solutions'])
            ->latest()
            ->get();

        return view('problems.index', [
            'problems' => $problems,
        ]);
    }

    public function create(): View
    {
        return view('problems.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,txt,png,jpg,jpeg,zip', 'max:10240'],
        ]);

        $problem = DB::transaction(function () use ($request, $validated) {
            $problem = Problem::create([
                'user_id' => $request->user()->id,
                'title' => trim($validated['title']),
                'description' => trim($validated['description']),
                'status' => 'open',
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $storedPath = $file->store('problem-attachments', 'public');

                    $problem->attachments()->create([
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $storedPath,
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            return $problem;
        });

        return redirect()
            ->route('problems.show', $problem)
            ->with('success', 'Problem submitted successfully.');
    }

    public function show(Problem $problem): View
    {
        $problem->load([
            'user',
            'attachments',
            'solutions' => function ($query) {
                $query->with(['user', 'attachments'])->latest();
            },
        ])->loadCount('solutions');

        return view('problems.show', [
            'problem' => $problem,
        ]);
    }
}
