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
        // Throttle credential endpoints against brute-force attempts:
        // 5 requests per minute per IP+email for login, per IP otherwise.
        RateLimiter::for('auth', function (Request $request) {
            $key = $request->input('email', $request->ip());

            return Limit::perMinute(5)->by($key);
        });
    }
}
