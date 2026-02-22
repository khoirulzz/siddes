<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email', '')));
            $identity = $email !== '' ? $email : 'guest';

            return Limit::perMinute(6)->by($identity . '|' . (string) $request->ip());
        });

        RateLimiter::for('service-lookup', function (Request $request) {
            return Limit::perMinute(80)->by((string) $request->ip());
        });

        RateLimiter::for('service-submit', function (Request $request) {
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(20)->by($ip),
                Limit::perHour(120)->by($ip),
            ];
        });
    }
}
