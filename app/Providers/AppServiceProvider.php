<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

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
        Paginator::useBootstrap();

        if (env('FORCE_HTTPS')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('iclock', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip()); // 60 requests per minute per IP
        });
    }
}
