<?php

namespace App\Providers;

use App\Models\Training;
use App\Observers\TrainingObserver;
use App\Services\PermissionMatrix;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PermissionMatrix::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Training::observe(TrainingObserver::class);
    }
}
