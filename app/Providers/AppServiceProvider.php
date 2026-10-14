<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;

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
        Schema::defaultStringLength(191);

        // Single password policy shared by registration and password reset.
        Password::defaults(fn() => Password::min(8)->letters()->numbers());

        // Dev only: throw on lazy loading so N+1 queries show up immediately.
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
