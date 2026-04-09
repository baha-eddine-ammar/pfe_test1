<?php

use App\Http\Controllers\Api\ServerMetricsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Register API endpoints here. This file is loaded by bootstrap/app.php.
|
*/

Route::post('/server-metrics', [ServerMetricsController::class, 'store'])
    ->name('api.server-metrics.store');
