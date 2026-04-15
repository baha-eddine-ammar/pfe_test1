<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProblemController extends Controller
{


// index show when we open problem page
    public function index(): View
    {
        // $problems  list of problem = 12 problem
        //Get problems from database and the user info / Show list of problems (page /problems)
        $problems = Problem::with('user')
            ->withCount(['attachments', 'solutions'])
            ->latest()
            //Show only 12 problems per page.
            ->paginate(12)
            ->withQueryString();

        return view('problems.index', [
            'problems' => $problems,
        ]);
    }



    //Open the form page to create a problem in Load Blade file:/problems/create.blade.php
    //User clicks "Submit Problem"
    //Route calls create()
    //This function returns the form page
    public function create(): View
    {
        return view('problems.create');
    }



    public function store(Request $request, NotificationService $notificationService): RedirectResponse
    {
        $request->merge([
            //Get title from form and Remove spaces at beginning and end
            'title' => trim((string) $request->input('title', '')),
            'description' => trim((string) $request->input('description', '')),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,doc,docx,txt,png,jpg,jpeg,zip', 'max:10240'],
        ]);

        $problem = DB::transaction(function () use ($request, $validated) {
            //Insert new problem into database
            $problem = Problem::create([
                'user_id' => $request->user()->id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'status' => 'open',
            ]);


                //CHECK If user have a file and Save file info in database
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $storedPath = $file->store('problem-attachments', 'local');

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


        // notification to the deparment head
        $notificationService->notifyApprovedDepartmentHeads(
            'problem.created',
            'New problem submitted',
            $validated['title'],

            //Generate URL of the problem page
            //Admin clicks notification → goes to problem page
            route('problems.show', $problem, false),
            ['problem_id' => $problem->id]
        );


        //Send the user to the problem page
        return redirect()
            ->route('problems.show', $problem)
            ->with('success', 'Problem submitted successfully.');
    }


    //Show ONE problem + its solutions (page /problems/{id})
    //$problem = ONE row from database (Problem model)
    public function show(Problem $problem): View
    {
        $problem->load([
            'user',
            'attachments',
        ])->loadCount('solutions');

      //Get solutions of this problem
        $solutions = $problem->solutions()

        //load who create it and his files with Newest solutions first
            ->with(['user', 'attachments'])
            ->latest()
            //Only 8 solutions per page
            ->paginate(8)
            ->withQueryString();

        return view('problems.show', [
            'problem' => $problem,
            'solutions' => $solutions,
        ]);
    }
}
