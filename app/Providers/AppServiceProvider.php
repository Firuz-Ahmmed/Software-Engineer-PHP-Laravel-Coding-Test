<?php

namespace App\Providers;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
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
        ValidateCsrfToken::except([
            "http://127.0.0.1:8000/users",
            "http://127.0.0.1:8000/login",
            "http://127.0.0.1:8000/deposit"
        ]);
    }
}
