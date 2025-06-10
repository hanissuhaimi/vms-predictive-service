<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\VMSPredictionService;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(VMSPredictionService::class, function ($app) {
            return new VMSPredictionService();
        });
    }

    public function boot()
    {
        //
    }
}