<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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
        RateLimiter::for('mayar-webhook', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(300)->by($request->ip()),
            Limit::perMinute(5)->by($request->input('email').'|'.$request->ip()),
        ]);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
