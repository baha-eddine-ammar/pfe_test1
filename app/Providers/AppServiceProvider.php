<?php

namespace App\Providers;

use App\Contracts\Reports\SensorDataProvider;
use App\Services\Reports\FakeSensorDataProvider;
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
        //
    }
}
