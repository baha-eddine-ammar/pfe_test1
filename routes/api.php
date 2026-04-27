<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| API route definitions for non-browser integrations.
|
| Why this file exists:
| The project receives server telemetry through an API endpoint rather than
| only through web forms/pages.
|
| When this file is used:
| When an external system posts data to the application's API.
|
| FILES TO READ (IN ORDER):
| 1. routes/api.php
| 2. app/Http/Controllers/Api/ServerMetricsController.php
| 3. app/Models/Server.php and app/Models/ServerMetric.php
*/
use App\Http\Controllers\Api\SensorReadingController;
use App\Http\Controllers\Api\ServerMetricsController;
use Illuminate\Support\Facades\Route;

// Server monitoring ingestion endpoint.
Route::post('/server-metrics', [ServerMetricsController::class, 'store'])
    ->middleware('throttle:server-metrics')
    ->name('api.server-metrics.store');

Route::post('/sensor-readings', [SensorReadingController::class, 'store'])
    ->middleware('throttle:sensor-readings')
    ->name('api.sensor-readings.store');
