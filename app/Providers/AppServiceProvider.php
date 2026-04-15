<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| Application-wide service/provider registration.
|
| Why this file exists:
| Laravel service providers are where shared bindings, policies, and rate
| limiters are registered during application boot.
|
| When this file is used:
| During framework startup on every request.
|
| FILES TO READ (IN ORDER):
| 1. app/Providers/AppServiceProvider.php
| 2. app/Policies/*
| 3. routes/web.php
| 4. app/Services/Reports/*
*/

namespace App\Providers;

use App\Contracts\Reports\SensorDataProvider;
use App\Models\MaintenanceTask;
use App\Models\Message;
use App\Models\User;
use App\Policies\MaintenanceTaskPolicy;
use App\Policies\MessagePolicy;
use App\Policies\UserPolicy;
use App\Services\Reports\FakeSensorDataProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    // Service container bindings are registered here.
    // This tells Laravel to use FakeSensorDataProvider when code asks for
    // the SensorDataProvider contract.
    public function register(): void
    {
        $this->app->bind(SensorDataProvider::class, FakeSensorDataProvider::class);
    }

    // Bootstraps policies and named rate limiters used elsewhere in the app.
    public function boot(): void
    {
        // Policy registration connects models to their authorization classes.
        Gate::policy(MaintenanceTask::class, MaintenanceTaskPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Prevents chat message spam.
        RateLimiter::for('chat-messages', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // Allows frequent but still controlled polling for live chat updates.
        RateLimiter::for('chat-sync', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('ai-chat', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('reports-generate', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('server-metrics', function (Request $request) {
            $identifier = trim((string) $request->input('identifier', ''));

            return Limit::perMinute(240)->by($identifier !== '' ? $identifier.'|'.$request->ip() : $request->ip());
        });
    }
}
