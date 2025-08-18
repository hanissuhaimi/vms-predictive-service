<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ErrorHandlingService;

class MaintenanceServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->app->singleton(ErrorHandlingService::class);
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Register custom error handlers if needed
    }
}