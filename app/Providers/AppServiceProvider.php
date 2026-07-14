<?php

namespace App\Providers;

use App\Contracts\SmsGateway;
use App\Services\Sms\MessagingServiceSmsGateway;
use App\Services\Sms\NextSmsGateway;
use App\Services\Sms\LogSmsGateway;
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
        $this->app->bind(SmsGateway::class, function ($app) {
            $driver = (string) config('prms.sms.driver', 'log');

            if ($driver !== 'http') {
                return $app->make(LogSmsGateway::class);
            }

            $provider = (string) config('prms.sms.provider', 'nextsms');

            return match ($provider) {
                'messaging_service' => $app->make(MessagingServiceSmsGateway::class),
                default => $app->make(NextSmsGateway::class),
            };
        });
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
