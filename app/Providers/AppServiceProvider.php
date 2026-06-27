<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
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
        Paginator::defaultView('vendor.pagination.prms-bootstrap-5');

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(8)->by($request->ip().'|'.$request->input('login_id', ''));
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });

        RateLimiter::for('public-search', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
