<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Display the admin area.
     */
    public function __invoke(): View
    {
        return view('admin.index');
    }
}
