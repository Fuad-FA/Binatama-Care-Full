<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

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
        // Pagination memakai gaya Bootstrap 5 (bawaan Laravel memakai Tailwind sehingga ikon menjadi sangat besar)
        Paginator::useBootstrapFive();
    }
}
