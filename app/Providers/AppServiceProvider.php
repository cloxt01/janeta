<?php

namespace App\Providers;

use App\Services\Router\MikrotikConnectionManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            MikrotikConnectionManager::class,
            function () {
                return new MikrotikConnectionManager();
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
