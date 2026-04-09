<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LaunchController extends Controller
{
    public function index(): View
    {
        return view('launch');
    }
}
