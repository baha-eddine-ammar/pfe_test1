<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AIChatController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaunchController;
use App\Http\Controllers\MaintenanceTaskController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProblemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\SolutionController;
use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;


// sail docker/*

Route::get('/', function () {
    return view('welcome');
});

Route::get('/launch', [LaunchController::class, 'index'])->name('launch');

Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])->name('telegram.webhook');


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
    Route::patch('/admin/users/{user}/reject', [UserManagementController::class, 'reject'])->name('admin.users.reject');
    Route::patch('/admin/users/{user}/promote', [UserManagementController::class, 'promote'])->name('admin.users.promote');
    Route::patch('/admin/users/{user}/demote', [UserManagementController::class, 'demote'])->name('admin.users.demote');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/telegram/connect', [TelegramController::class, 'connect'])->name('profile.telegram.connect');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('/notifications/{userNotification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::post('/reports/{report}/regenerate-summary', [ReportController::class, 'regenerateSummary'])->name('reports.regenerate-summary');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::get('/ai-chat', [AIChatController::class, 'index'])->name('ai-chat.index');
    Route::post('/ai-chat/send', [AIChatController::class, 'send'])->name('ai-chat.send');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    Route::get('/problems', [ProblemController::class, 'index'])->name('problems.index');
    Route::get('/problems/create', [ProblemController::class, 'create'])->name('problems.create');
    Route::post('/problems', [ProblemController::class, 'store'])->name('problems.store');
    Route::get('/problems/{problem}', [ProblemController::class, 'show'])->name('problems.show');

    Route::get('/solutions', [SolutionController::class, 'index'])->name('solutions.index');
    Route::post('/problems/{problem}/solutions', [SolutionController::class, 'store'])->name('problems.solutions.store');

    Route::get('/servers', [ServerController::class, 'index'])->name('servers.index');
    Route::get('/servers/{server}', [ServerController::class, 'show'])->whereNumber('server')->name('servers.show');

    Route::get('/maintenance', [MaintenanceTaskController::class, 'index'])->name('maintenance.index');
    Route::get('/maintenance/{maintenanceTask}', [MaintenanceTaskController::class, 'show'])->name('maintenance.show');
    Route::patch('/maintenance/{maintenanceTask}/status', [MaintenanceTaskController::class, 'updateStatus'])->name('maintenance.update-status');
});

Route::middleware(['auth', 'verified', 'department.head'])->group(function () {
    Route::get('/servers/create', [ServerController::class, 'create'])->name('servers.create');
    Route::post('/servers', [ServerController::class, 'store'])->name('servers.store');

    Route::get('/maintenance/create', [MaintenanceTaskController::class, 'create'])->name('maintenance.create');
    Route::post('/maintenance', [MaintenanceTaskController::class, 'store'])->name('maintenance.store');
    Route::get('/maintenance/{maintenanceTask}/edit', [MaintenanceTaskController::class, 'edit'])->name('maintenance.edit');
    Route::put('/maintenance/{maintenanceTask}', [MaintenanceTaskController::class, 'update'])->name('maintenance.update');
});

require __DIR__.'/auth.php';
