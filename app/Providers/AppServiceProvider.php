<?php

namespace App\Providers;

use App\Contracts\Reports\SensorDataProvider;
use App\Models\MaintenanceTask;
use App\Models\User;
use App\Policies\MaintenanceTaskPolicy;
use App\Policies\UserPolicy;
use App\Services\Reports\FakeSensorDataProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SensorDataProvider::class, FakeSensorDataProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(MaintenanceTask::class, MaintenanceTaskPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
