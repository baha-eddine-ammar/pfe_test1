<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| Application bootstrap file for Laravel 12.
|
| Why this file exists:
| It tells Laravel how to boot the app: which route files to load, which
| middleware aliases to register, and how to configure exceptions.
|
| When this file is used:
| On every request while the framework is starting up.
|
| FILES TO READ (IN ORDER):
| 1. bootstrap/app.php
| 2. routes/web.php and routes/api.php
| 3. app/Http/Middleware/*
*/

use App\Http\Middleware\EnsureDepartmentHead;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // These files are the entry points for different request types.
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Custom middleware alias used in routes/web.php to protect
        // department-head-only sections.
        $middleware->alias([
            'department.head' => EnsureDepartmentHead::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
