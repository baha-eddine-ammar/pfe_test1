<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This middleware blocks routes so only approved department heads can access them.
|
| Why this file exists:
| Some parts of the system are administrative and should not be available to
| staff users or unapproved accounts.
|
| When this file is used:
| On routes that include the "department.head" middleware alias.
|
| FILES TO READ (IN ORDER):
| 1. bootstrap/app.php
| 2. app/Http/Middleware/EnsureDepartmentHead.php
| 3. routes/web.php
*/

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDepartmentHead
{
    // Stops the request early unless the current user is both:
    // 1. a department head
    // 2. approved
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isDepartmentHead() || ! $user->hasApprovedStatus()) {
            abort(403);
        }
        // continue , go to the controller
        return $next($request);
    }
}
