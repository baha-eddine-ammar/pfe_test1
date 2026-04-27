<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This is the main web entry point for the application.
| It defines the browser-accessible routes used by the project and connects
| URLs to controllers, middleware, and named routes.
|
| Why this file exists:
| Laravel needs one place to describe which page or action should run when a
| user opens a URL in the browser.
|
| When this file is used:
| It is read on every web request so Laravel can match the incoming URL to
| the correct controller method or closure.
|
| FILES TO READ (IN ORDER):
| 1. routes/web.php
| 2. app/Http/Controllers/*Controller.php
| 3. app/Http/Requests/*Request.php
| 4. app/Services/*
| 5. app/Models/*
| 6. resources/views/*
|
| HOW TO UNDERSTAND THIS FEATURE:
| 1. Start here and find the route name/path.
| 2. Open the controller or method used by that route.
| 3. Check whether a FormRequest validates the input.
| 4. Check whether a service class contains business logic.
| 5. Check the model relationships used to read/write data.
| 6. Open the Blade view returned by the controller.
*/

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AIChatController;
use App\Http\Controllers\AttachmentDownloadController;
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


// Landing pages and public endpoints that do not require authentication.

Route::get('/', function () {
    return view('welcome');
});

// for now  we dont use it yet
Route::get('/launch', [LaunchController::class, 'index'])->name('launch');

Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])->name('telegram.webhook');

// Dashboard routes:
// These routes power the main monitoring page plus the JSON feeds used by the
// dashboard charts/cards for live updates.
Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/temperature-feed', [DashboardController::class, 'temperatureFeed'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.temperature');

Route::get('/dashboard/humidity-feed', [DashboardController::class, 'humidityFeed'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.humidity');

Route::get('/dashboard/trend-feed', [DashboardController::class, 'trendFeed'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.trend');

// Department head administration:
// Only approved department heads can access these routes because they manage
// users and higher-level administration.
Route::middleware(['auth', 'verified', 'department.head'])->group(function () {
    Route::get('/admin', AdminController::class)->name('admin.index');
    Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::patch('/admin/users/{user}/approve', [UserManagementController::class, 'approve'])->name('admin.users.approve');
    Route::patch('/admin/users/{user}/reject', [UserManagementController::class, 'reject'])->name('admin.users.reject');
    Route::patch('/admin/users/{user}/promote', [UserManagementController::class, 'promote'])->name('admin.users.promote');
    Route::patch('/admin/users/{user}/demote', [UserManagementController::class, 'demote'])->name('admin.users.demote');

});

// Authenticated user account tools:
// Users must be logged in, but email verification is not required for these
// profile and notification management actions.
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/telegram/connect', [TelegramController::class, 'connect'])->name('profile.telegram.connect');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('/notifications/{userNotification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

});

// Main authenticated application features:
// Reports, chat, AI chat, calendar, knowledge base, servers, and maintenance.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:reports-generate')->name('reports.store');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::post('/reports/{report}/regenerate-summary', [ReportController::class, 'regenerateSummary'])->middleware('throttle:reports-generate')->name('reports.regenerate-summary');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'store'])->middleware('throttle:chat-messages')->name('chat.store');
    Route::get('/chat/messages', [ChatController::class, 'messages'])->middleware('throttle:chat-sync')->name('chat.messages');

    // AI chat routes
    Route::get('/ai-chat', [AIChatController::class, 'index'])->name('ai-chat.index');
    Route::post('/ai-chat/send', [AIChatController::class, 'send'])->middleware('throttle:ai-chat')->name('ai-chat.send');


    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    Route::get('/problems', [ProblemController::class, 'index'])->name('problems.index');
    Route::get('/problems/create', [ProblemController::class, 'create'])->name('problems.create');
    Route::post('/problems', [ProblemController::class, 'store'])->name('problems.store');
    Route::get('/problems/{problem}', [ProblemController::class, 'show'])->name('problems.show');
    Route::get('/problem-attachments/{problemAttachment}', [AttachmentDownloadController::class, 'problem'])->name('problems.attachments.download');
    Route::get('/solution-attachments/{solutionAttachment}', [AttachmentDownloadController::class, 'solution'])->name('solutions.attachments.download');

    Route::get('/solutions', [SolutionController::class, 'index'])->name('solutions.index');
    Route::post('/problems/{problem}/solutions', [SolutionController::class, 'store'])->name('problems.solutions.store');

    Route::get('/servers', [ServerController::class, 'index'])->name('servers.index');
    Route::get('/servers/{server}/feed', [ServerController::class, 'feed'])->whereNumber('server')->name('servers.feed');
    Route::get('/servers/{server}', [ServerController::class, 'show'])->whereNumber('server')->name('servers.show');

    Route::get('/maintenance', [MaintenanceTaskController::class, 'index'])->name('maintenance.index');
    Route::get('/maintenance/{maintenanceTask}', [MaintenanceTaskController::class, 'show'])->name('maintenance.show');
    Route::patch('/maintenance/{maintenanceTask}/status', [MaintenanceTaskController::class, 'updateStatus'])->name('maintenance.update-status');
});

// Department-head-only creation/management actions:
// These routes change shared system data, so they are restricted to the admin
// role used in this project.
//middleware is : a security/filter layer that runs before the controller
//['auth', 'verified', 'department.head'] : This is an array of 3 middlewares.
Route::middleware(['auth', 'verified', 'department.head'])->group(function () {
    Route::get('/servers/create', [ServerController::class, 'create'])->name('servers.create');
    Route::post('/servers', [ServerController::class, 'store'])->name('servers.store');

    Route::get('/maintenance/create', [MaintenanceTaskController::class, 'create'])->name('maintenance.create');
    Route::post('/maintenance', [MaintenanceTaskController::class, 'store'])->name('maintenance.store');
    Route::get('/maintenance/{maintenanceTask}/edit', [MaintenanceTaskController::class, 'edit'])->name('maintenance.edit');
    Route::put('/maintenance/{maintenanceTask}', [MaintenanceTaskController::class, 'update'])->name('maintenance.update');
    Route::delete('/maintenance/{maintenanceTask}', [MaintenanceTaskController::class, 'destroy'])->name('maintenance.destroy');
});

require __DIR__.'/auth.php';
