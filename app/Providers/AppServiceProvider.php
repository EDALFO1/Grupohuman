<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Schema::defaultStringLength(191);
        // Para Bootstrap 5:
    if (method_exists(Paginator::class, 'useBootstrapFive')) {
        Paginator::useBootstrapFive();
    } else {
        // Compatibilidad con proyectos más antiguos:
        Paginator::useBootstrap();
    }
    }
}
