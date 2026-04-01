<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SolutionController;
use Illuminate\Support\Facades\Route;

// sail docker/*

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/temperature-feed', [DashboardController::class, 'temperatureFeed'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.temperature');

Route::get('/dashboard/humidity-feed', [DashboardController::class, 'humidityFeed'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.humidity');

Route::get('/dashboard/airflow-feed', [DashboardController::class, 'airFlowFeed'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.airflow');

Route::get('/dashboard/power-usage-feed', [DashboardController::class, 'powerUsageFeed'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.power');

Route::get('/dashboard/trend-feed', [DashboardController::class, 'trendFeed'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.trend');

Route::middleware(['auth', 'verified', 'department.head'])->group(function () {
    Route::get('/admin', AdminController::class)->name('admin.index');
    Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::patch('/admin/users/{user}/approve', [UserManagementController::class, 'approve'])->name('admin.users.approve');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// prefix
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::post('/reports/{report}/regenerate-summary', [ReportController::class, 'regenerateSummary'])->name('reports.regenerate-summary');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/messages', [ChatController::class, 'messages'])->name('chat.messages');

    Route::get('/problems', [ProblemController::class, 'index'])->name('problems.index');
    Route::get('/problems/create', [ProblemController::class, 'create'])->name('problems.create');
    Route::post('/problems', [ProblemController::class, 'store'])->name('problems.store');
    Route::get('/problems/{problem}', [ProblemController::class, 'show'])->name('problems.show');

    Route::get('/solutions', [SolutionController::class, 'index'])->name('solutions.index');
    Route::post('/problems/{problem}/solutions', [SolutionController::class, 'store'])->name('problems.solutions.store');
});

require __DIR__.'/auth.php';
